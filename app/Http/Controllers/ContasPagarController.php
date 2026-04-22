<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaPagar;
use App\Models\CategoriaConta;
use App\Models\Fornecedor;
use App\Models\ConfigNota;
use App\Models\ContaEmpresa;
use App\Models\ItemContaEmpresa;
use Dompdf\Dompdf;
use App\Utils\ContaEmpresaUtil;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ContasPagarExport;
use Illuminate\Support\Facades\Schema;
use App\Models\Veiculo;


class ContasPagarController extends Controller
{
    protected $empresa_id = null;
    protected $util;

    public function __construct(ContaEmpresaUtil $util){
        $this->util = $util;

        $this->middleware(function ($request, $next) {
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            // Pega o ID da empresa que está na sessão do usuário
            $this->empresa_id = $value['empresa'];
            return $next($request);
        });
    }

    /**
     * Função auxiliar para calcular o valor líquido deduzindo todas as retenções.
     */
    private function calculaValorLiquido($valor, $inss = 0, $iss = 0, $pis = 0, $cofins = 0, $ir = 0, $outras = 0)
    {
        return (float)$valor
            - (float)($inss ?: 0)
            - (float)($iss ?: 0)
            - (float)($pis ?: 0)
            - (float)($cofins ?: 0)
            - (float)($ir ?: 0)
            - (float)($outras ?: 0);
    }

    private function comRetencoes(){
        return ContaPagar::where('empresa_id', $this->empresa_id)
            ->where('valor_inss', '>', 0)
            ->orWhere('valor_iss', '>', 0)
            ->orWhere('valor_pis', '>', 0)
            ->orWhere('valor_cofins', '>', 0)
            ->orWhere('valor_ir', '>', 0)
            ->orWhere('outras_retencoes', '>', 0)
            ->first();
    }

    public function index(Request $request)
    {
        // 1. MEMÓRIA DE FILTRO: Se o usuário entra no "index" (pelo menu),
        // mas já tinha filtrado algo antes, nós mandamos ele de volta para o filtro dele.
        if (!$request->has('fornecedorId') && session()->has('filtros_contas_pagar')) {
            return $this->filtro(new Request(session('filtros_contas_pagar')));
        }

        // 2. LOGICA ORIGINAL: Rastreio de redirecionamento
        __saveRedirect($this->empresa_id, '', 'contas_pagar');

        $comRetencoes = $this->comRetencoes();
        $permissaoAcesso = __getLocaisUsarioLogado();
        $local_padrao = __get_local_padrao();

        if($local_padrao == -1){
            $local_padrao = null;
        }

        // 3. BUSCA PADRÃO (Próximos 30 dias)
        $contas = ContaPagar::with(['usuario', 'usuarioEdicao', 'usuarioBaixa', 'veiculo', 'categoria'])
            ->whereBetween('data_vencimento', [
                date("Y-m-d"),
                date('Y-m-d', strtotime('+1 month'))
            ])
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('data_vencimento', 'asc')
            ->get();

        // 4. CARREGAMENTO DE DADOS PARA A TELA
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $somaContas = $this->somaCategoriaDeContas($contas);
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->get();
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->get();
        $tiposPagamento = \App\Models\ContaPagar::tiposPagamento(); // Puxando direto do seu Model!

        $dataInicial = date('d/m/Y');
        $dataFinal = date('d/m/Y', strtotime('+1 month'));

        return view('contaPagar/list')
            ->with('contas', $contas)
            ->with('comRetencoes', $comRetencoes)
            ->with('categorias', $categorias)
            ->with('fornecedores', $fornecedores)
            ->with('veiculos', $veiculos)
            ->with('graficoJs', true)
            ->with('somaContas', $somaContas)
            ->with('infoDados', "Dos próximos 30 dias")
            ->with('dataInicial', $dataInicial)
            ->with('dataFinal', $dataFinal)
            ->with('contasEmpresa', $contasEmpresa)
            ->with('tiposPagamento', $tiposPagamento)
            ->with('title', 'Contas a Pagar');
    }

    private function somaCategoriaDeContas($contas){
        $arrayCategorias = $this->criaArrayDecategoriaDeContas();
        $temp = [];
        foreach($contas as $c){
            foreach($arrayCategorias as $a){
                if($c->categoria->nome == $a){
                    if(isset($temp[$a])){
                        $temp[$a] = $temp[$a] + $c->valor_integral;
                    }else{
                        $temp[$a] = $c->valor_integral;
                    }
                }
            }
        }

        return $temp;
    }

    private function criaArrayDecategoriaDeContas(){
        $categorias = CategoriaConta::
        where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->get();

        $temp = [];
        foreach($categorias as $c){
            array_push($temp, $c->nome);
        }

        return $temp;
    }

    //Agnaldo em 09032026
    public function filtro(Request $request){

        // 1. SALVA OS FILTROS NA SESSÃO DO USUÁRIO LOGADO
        session(['filtros_contas_pagar' => $request->all()]);

        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $fornecedorId = $request->fornecedorId;
        $status = $request->status;
        $filial_id = $request->filial_id;
        $tipoFiltro = $request->tipo_filtro;
        $tipo_filtro_data = $request->tipo_filtro_data;
        $veiculo_id_filtro = $request->veiculo_id_filtro;

        // NOVOS FILTROS
        $conta_id = $request->conta_id;
        $compra_id_filtro = $request->compra_id_filtro;

        $contas = [];
        $comRetencoes = $this->comRetencoes();

        $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        __saveRedirect($this->empresa_id, $url, 'contas_pagar');

        $permissaoAcesso = __getLocaisUsarioLogado();

        // --- PRIMEIRA CONSULTA (Contas Pagar Diretas) ---
        $c = ContaPagar::with(['usuario', 'usuarioEdicao', 'usuarioBaixa', 'veiculo', 'categoria'])
            ->select('conta_pagars.*')
            // Join para filtrar por conta bancária de saída
            ->leftJoin('item_conta_empresas as ice', 'conta_pagars.id', '=', 'ice.conta_pagar_id')
            ->where(function($query) use ($permissaoAcesso){
                if($permissaoAcesso != null){
                    foreach ($permissaoAcesso as $value) {
                        if($value == -1) $value = null;
                        $query->orWhere('conta_pagars.filial_id', $value);
                    }
                }
            })
            ->when($filial_id, function ($query) use ($filial_id) {
                $filial_id = $filial_id == -1 ? null : $filial_id;
                return $query->where('conta_pagars.filial_id', $filial_id);
            });

        // Filtro por Conta Empresa
        if($conta_id && $conta_id != 'todos'){
            $c->where('ice.conta_id', $conta_id);
        }

        // Filtro por ID da Compra
        if($compra_id_filtro){
            $c->where('conta_pagars.compra_id', $compra_id_filtro);
        }

        if($veiculo_id_filtro && $veiculo_id_filtro != 'todos'){
            $c->where('conta_pagars.veiculo_id', $veiculo_id_filtro);
        }

        if($fornecedorId != "null"){
            $c->where('fornecedor_id', $fornecedorId);
        }

        if($dataInicial && $dataFinal){
            if($request->tipo_filtro_data == 1){
                $c->whereBetween('conta_pagars.data_vencimento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal)]);
            }elseif($request->tipo_filtro_data == 2){
                $c->whereBetween('conta_pagars.created_at', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            }elseif($request->tipo_filtro_data == 4){
                $c->whereBetween('conta_pagars.data_emissao', [$this->parseDate($dataInicial), $this->parseDate($dataFinal)]);
            }else{
                $c->whereBetween('conta_pagars.data_pagamento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            }
        }

        if($status != 'todos'){
            if($status == 'pago') $c->where('status', true);
            else if($status == 'pendente') $c->where('status', false);
            else if($status == 'vencido') $c->where('status', false)->whereDate('data_vencimento', '<=', date('Y-m-d'));
        }

        if($request->categoria != 'todos') $c->where('categoria_id', $request->categoria);
        if($request->tipo_pagamento) $c->where('tipo_pagamento', $request->tipo_pagamento);
        if($request->numero_nota_fiscal) $c->where('conta_pagars.numero_nota_fiscal', $request->numero_nota_fiscal);

        $c->where('conta_pagars.empresa_id', $this->empresa_id);
        if($request->tipo_filtro_data == 1) $c->orderBy('conta_pagars.data_vencimento', 'asc');

        $temp = $c->get();
        foreach($temp as $t) array_push($contas, $t);

        // --- SEGUNDA CONSULTA (Contas Pagar vindas de Compras) ---
        $c = ContaPagar::with(['usuario', 'usuarioEdicao', 'usuarioBaixa', 'veiculo', 'categoria'])
            ->select('conta_pagars.*')
            // Join para filtrar por conta empresa
            ->leftJoin('item_conta_empresas as ice', 'conta_pagars.id', '=', 'ice.conta_pagar_id')
            ->where(function($query) use ($permissaoAcesso){
                if($permissaoAcesso != null){
                    foreach ($permissaoAcesso as $value) {
                        if($value == -1) $value = null;
                        $query->orWhere('conta_pagars.filial_id', $value);
                    }
                }
            })
            ->when($filial_id, function ($query) use ($filial_id) {
                $filial_id = $filial_id == -1 ? null : $filial_id;
                return $query->where('conta_pagars.filial_id', $filial_id);
            });

        // Filtro por Conta Empresa
        if($conta_id && $conta_id != 'todos'){
            $c->where('ice.conta_id', $conta_id);
        }

        // Filtro por ID da Compra
        if($compra_id_filtro){
            $c->where('conta_pagars.compra_id', $compra_id_filtro);
        }

        if($veiculo_id_filtro && $veiculo_id_filtro != 'todos'){
            $c->where('conta_pagars.veiculo_id', $veiculo_id_filtro);
        }

        if($fornecedorId != "null"){
            $c->join('compras', 'compras.id' , '=', 'conta_pagars.compra_id')
                ->where('compras.fornecedor_id', $fornecedorId);
        }

        if($dataInicial && $dataFinal){
            if($request->tipo_filtro_data == 1) $c->whereBetween('conta_pagars.data_vencimento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal)]);
            elseif($request->tipo_filtro_data == 2) $c->whereBetween('conta_pagars.created_at', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            elseif($request->tipo_filtro_data == 4) $c->whereBetween('conta_pagars.data_emissao', [$this->parseDate($dataInicial), $this->parseDate($dataFinal)]);
            else $c->whereBetween('conta_pagars.data_pagamento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
        }

        if($status != 'todos'){
            if($status == 'pago') $c->where('status', true);
            else if($status == 'pendente') $c->where('status', false);
            else if($status == 'vencido') $c->where('status', false)->whereDate('data_vencimento', '<=', date('Y-m-d'));
        }

        if($request->categoria != 'todos') $c->where('categoria_id', $request->categoria);
        if($request->tipo_pagamento) $c->where('tipo_pagamento', $request->tipo_pagamento);
        if($request->numero_nota_fiscal) $c->where('conta_pagars.numero_nota_fiscal', $request->numero_nota_fiscal);

        $c->where('conta_pagars.empresa_id', $this->empresa_id);
        $temp = $c->get();
        foreach($temp as $t){
            if(!$this->validaInArray($t, $contas)) array_push($contas, $t);
        }

        // --- FINALIZAÇÃO ---
        $somaContas = $this->somaCategoriaDeContas($contas);
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->get();
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();

        // Busca contas empresa para o select
        $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->get();

        return view('contaPagar/list')
            ->with('contas', $contas)
            ->with('comRetencoes', $comRetencoes)
            ->with('fornecedorId', $fornecedorId)
            ->with('fornecedores', $fornecedores)
            ->with('veiculos', $veiculos)
            ->with('veiculo_id_filtro', $veiculo_id_filtro)
            ->with('contasEmpresa', $contasEmpresa) // Envia para a view
            ->with('conta_id', $conta_id)           // Mantém selecionado
            ->with('compra_id_filtro', $compra_id_filtro) // Mantém preenchido
            ->with('filial_id', $filial_id)
            ->with('categorias', $categorias)
            ->with('dataInicial', $dataInicial)
            ->with('dataFinal', $dataFinal)
            ->with('status', $status)
            ->with('url', $url)
            ->with('tipo_filtro_data', $request->tipo_filtro_data)
            ->with('somaContas', $somaContas)
            ->with('graficoJs', true)
            ->with('paraImprimir', true)
            ->with('categoria', $request->categoria)
            ->with('tipo_pagamento', $request->tipo_pagamento)
            ->with('numero_nota_fiscal', $request->numero_nota_fiscal)
            ->with('infoDados', "Contas filtradas")
            ->with('title', 'Filtro Contas a Pagar');
    }

    private function validaInArray($ct, $contas){
        foreach($contas as $c){
            if($c->id == $ct->id) return true;
        }
        return false;
    }

    public function salvarParcela(Request $request)
    {
        $parcela = $request->parcela;

        // Trata o valor da parcela
        $valorParcela = str_replace(",", ".", $parcela['valor_parcela']);
        $valorParcela = str_replace(" ", "", $valorParcela);

        // Obtém a categoria – se houver categoria selecionada, usa-a; senão, pega a primeira categoria do tipo "pagar"
        $categoria = CategoriaConta::where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')->first();
        if (isset($parcela['categoria_conta_id']) && $parcela['categoria_conta_id']) {
            $categoria = CategoriaConta::findOrFail($parcela['categoria_conta_id']);
        }

        // Se a parcela for proveniente de uma compra e o número da nota não estiver informado,
        // busca o número da nota a partir do registro da compra.
        $numeroNota = isset($parcela['numero_nota_fiscal']) ? $parcela['numero_nota_fiscal'] : null;
        if (empty($numeroNota) && isset($parcela['compra_id']) && !empty($parcela['compra_id'])) {
            $compra = \App\Models\Compra::find($parcela['compra_id']);
            if ($compra) {
                $numeroNota = $compra->nf; // supondo que o campo "nf" na compra contenha o número da nota fiscal
            }
        }
        if (empty($numeroNota)) {
            $numeroNota = 0; // ou deixe null, conforme sua necessidade
        }

        // Cria a conta a pagar
        $result = ContaPagar::create([
            'compra_id'          => $parcela['compra_id'],
            'veiculo_id'         => $parcela['veiculo_id'] ?? null,
            'data_vencimento'    => $this->parseDate($parcela['vencimento']),
            'data_emissao'       => $parcela['data_emissao'] ?? date('Y-m-d'),
            'data_pagamento'     => $this->parseDate($parcela['vencimento']),
            'valor_integral'     => $valorParcela,
            'valor_pago'         => 0,
            'status'             => false,
            'referencia'         => $parcela['referencia'],
            'categoria_id'       => $categoria->id,
            'empresa_id'         => $this->empresa_id,
            'usuario_id'         => session('user_logged')['id'], // ADICIONE ESTA LINHA
            'filial_id'          => $parcela['filial_id'] != -1 ? $parcela['filial_id'] : null,
            'fornecedor_id'      => $parcela['fornecedor_id'],
            'numero_nota_fiscal' => $numeroNota
        ]);
        echo json_encode($parcela);
    }

    //Agnaldo em 09032026

    public function save(Request $request)
    {
        // 1. Validação
        $this->_validate($request);

        // 2. Coleta de dados básicos
        $data = $request->all();
        $data['data_vencimento'] = $this->parseDate($request->vencimento);
        $data['data_emissao'] = $request->data_emissao ? $this->parseDate($request->data_emissao) : date('Y-m-d');
        $data['valor_integral'] = $request->valor_final ? __replace($request->valor_final) : __replace($request->valor);
        $data['usuario_id'] = session('user_logged')['id'];
        $data['empresa_id'] = $this->empresa_id;
        $data['filial_id'] = $request->filial_id == -1 ? null : $request->filial_id;
        $data['numero_nota_fiscal'] = $request->numero_nota_fiscal ?? 0;
        $data['tipo_pagamento'] = $request->tipo_pagamento ?? 'PIX';

        // 3. Busca número da nota (se for de compra)
        $numeroNota = $request->numero_nota_fiscal;
        if (empty($numeroNota) && isset($request->compra_id) && $request->compra_id != "") {
            $compra = \App\Models\Compra::find($request->compra_id);
            if ($compra) $numeroNota = $compra->nf;
        }
        $data['numero_nota_fiscal'] = $numeroNota ?? 0;

        // 4. Lógica de Recorrência
        if (isset($request->recorrencia) && strlen($request->recorrencia) == 5) {
            if (!$this->validaRecorrencia($request->recorrencia)) {
                session()->flash('mensagem_erro', 'Valor recorrente inválido!');
                return redirect('/contasPagar/new');
            }
        }

        // 5. Lógica de Pagamento (Checkbox "Conta Paga")
        if ($request->has('status') || $request->status == 'on') {
            $data['status'] = 1;
            $data['valor_pago'] = $data['valor_integral'];
            $data['data_pagamento'] = $request->data_pagamento ? $this->parseDate($request->data_pagamento) : date('Y-m-d H:i:s');
            $data['usuario_baixa_id'] = $data['usuario_id'];
        } else {
            $data['status'] = 0;
            $data['valor_pago'] = 0;
            $data['data_pagamento'] = null;
        }

        // 6. Campos de retenção
        $camposRetencao = ['valor_inss', 'valor_iss', 'valor_pis', 'valor_cofins', 'valor_ir', 'outras_retencoes'];
        foreach ($camposRetencao as $f) {
            $data[$f] = __replace($request->input($f, 0));
        }

        // 7. Processamento de Parcelas
        $parcelas = json_decode($request->parcelas ?? '[]');
        if (count($parcelas) > 0) {
            $data['referencia'] = $request->referencia . " - parcela 1/" . (count($parcelas) + 1);
        }

        // SALVA CONTA PRINCIPAL
        $conta = ContaPagar::create($data);

        // 8. Salva Parcelas Adicionais
        if (count($parcelas) > 0) {
            foreach ($parcelas as $key => $p) {
                $dp = $data;
                $dp['data_vencimento'] = $this->parseDate($p->vencimento);
                $dp['valor_integral'] = str_replace(",", ".", $p->valor);
                $dp['referencia'] = $request->referencia . " - parcela " . ($key + 2) . "/" . (count($parcelas) + 1);
                $dp['status'] = 0;
                $dp['valor_pago'] = 0;
                $dp['data_pagamento'] = null;
                ContaPagar::create($dp);
            }
        }

        // 9. Lançamento no Extrato Bancário
        if ($conta->status == 1 && $request->conta_id) {
            $forn = Fornecedor::find($conta->fornecedor_id);
            $item = ItemContaEmpresa::create([
                'conta_id'       => $request->conta_id,
                'descricao'      => "Pgto " . ($forn->razao_social ?? 'Fornecedor') . " | Ref: " . $conta->referencia,
                'tipo_pagamento' => $conta->tipo_pagamento ?? 'Dinheiro',
                'valor'          => $conta->valor_integral,
                'tipo'           => 'saida',
                'data_pagamento' => $conta->data_pagamento,
                'categoria_id'   => $conta->categoria_id,
                'usuario_id'     => $conta->usuario_id,
                'origem'         => 'ContaPagar',
                'conta_pagar_id' => $conta->id,
                'user_id'        => session('user_logged')['id'],

            ]);

            if (isset($this->util)) {
                $this->util->atualizaSaldo($item);
            }
        }

        return redirect('/contasPagar')->with('mensagem_sucesso', 'Registro inserido com sucesso!');
    } // Aqui termina o save. A próxima linha já deve ser o public function update...

    //Agnaldo em 09032026
    public function update(Request $request)
    {
        // Validação dos dados
        $this->_validate($request);

        // Busca a conta a pagar para a empresa atual
        $conta = ContaPagar::where('id', $request->id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        // Ajusta o campo filial (se -1, considera como null)
        $request->merge([
            'filial_id' => $request->filial_id == -1 ? null : $request->filial_id
        ]);

        // Atualiza os campos básicos
        $conta->data_vencimento = $this->parseDate($request->vencimento);
        $conta->data_emissao    = $request->data_emissao ? $this->parseDate($request->data_emissao) : null;
        $conta->referencia       = $request->referencia;
        $conta->observacao       = $request->observacao ?? "";
        $conta->valor_integral   = str_replace(",", ".", $request->valor);
        $conta->categoria_id     = $request->categoria_id;
        $conta->tipo_pagamento   = $request->tipo_pagamento ?? '';
        $conta->veiculo_id = $request->veiculo_id == 'todos' ? null : $request->veiculo_id;
        $conta->usuario_edicao_id = session('user_logged')['id'];
        $conta->save();

        /*
         * Correção para o número da nota fiscal:
         * Se o usuário deixou o campo de nota fiscal em branco (ou "zerado")
         * e a conta tem um compra_id vinculado, tenta-se recuperar o número
         * da nota fiscal a partir da compra (campo "nf").
         * Caso contrário, utiliza o valor enviado pelo request.
         */
        if (empty($request->numero_nota_fiscal) && $conta->compra_id) {
            $compra = \App\Models\Compra::find($conta->compra_id);
            if ($compra) {
                $conta->numero_nota_fiscal = $compra->nf;
            } else {
                $conta->numero_nota_fiscal = 0;
            }
        } else {
            $conta->numero_nota_fiscal = $request->numero_nota_fiscal;
        }

        // Atualiza os campos de retenções (se houver)
        $conta->valor_inss       = $request->valor_inss ? __replace($request->valor_inss) : 0;
        $conta->valor_iss        = $request->valor_iss ? __replace($request->valor_iss) : 0;
        $conta->valor_pis        = $request->valor_pis ? __replace($request->valor_pis) : 0;
        $conta->valor_cofins     = $request->valor_cofins ? __replace($request->valor_cofins) : 0;
        $conta->valor_ir         = $request->valor_ir ? __replace($request->valor_ir) : 0;
        $conta->outras_retencoes = $request->outras_retencoes ? __replace($request->outras_retencoes) : 0;

        // Atualiza a filial
        $conta->filial_id = $request->filial_id;

        // Salva as alterações
        $result = $conta->save();

        if ($result) {
            session()->flash('mensagem_sucesso', 'Registro editado!');
        } else {
            session()->flash('mensagem_erro', 'Ocorreu um erro!');
        }

        $rota = __getRedirect($this->empresa_id, 'contas_pagar');
        if ($rota != "") {
            return redirect($rota);
        }
        return redirect('/contasPagar');
    }

    private function calculaRecorrencia($recorrencia){
        if(strlen($recorrencia) == 5){
            $dataAtual = date("Y-m");
            $dif = strtotime($this->parseRecorrencia($recorrencia)) - strtotime($dataAtual);

            $meses = floor($dif / (60 * 60 * 24 * 30));

            return $meses;
        }
        return 0;
    }

    public function validaRecorrencia($rec){
        $mesAutal = date('m');
        $anoAtual = date('y');
        $temp = explode("/", $rec);
        if($anoAtual > $temp[1]) return false;
        if((int)$temp[0] <= $mesAutal && $anoAtual == $temp[1]) return false;

        return true;
    }

    private function _validate(Request $request){
        $rules = [
            'fornecedor_id' => $request->id == 0 ? 'required' : '',
            'referencia' => 'required',
            'valor' => 'required',
            //'observacao' => 'max:100',
            'observacao' => 'nullable|string',
            'vencimento' => 'required',
        ];

        $messages = [
            'referencia.required' => 'O campo referencia é obrigatório.',
            'observacao.max' => 'Máximo de 100 caracteres.',
            'fornecedor_id.required' => 'O campo fornecedor é obrigatório.',
            'valor.required' => 'O campo valor é obrigatório.',
            'vencimento.required' => 'O campo vencimento é obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function new(){
        $veiculos   = \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->get();
        $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->get();
        $categorias = CategoriaConta::
        where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->orderBy('nome')
            ->get();

        if(sizeof($categorias) == 0){
            session()->flash('mensagem_alerta', 'Cadastre uma categoria com o tipo pagar!');
            return redirect('/categoriasConta');
        }

        $fornecedores = Fornecedor::
        where('empresa_id', $this->empresa_id)
            ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
            ->first();

        if($config == null){
            session()->flash('mensagem_alerta', 'Informe a configuração do emitente!');
            return redirect('/configNF');
        }

        return view('contaPagar/register')
            ->with('categorias', $categorias)
            ->with('fornecedores', $fornecedores)
            ->with('config', $config)
            ->with('title', 'Cadastrar Contas a Pagar')
            ->with('veiculos', $veiculos)
            ->with('contasEmpresa', $contasEmpresa);
    }

    public function edit($id){

        $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->get();

        $veiculos   = \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->get();

        $categorias = CategoriaConta::
        where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->orderBy('nome')
            ->get();

        $conta = ContaPagar::
        where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        $fornecedores = Fornecedor::
        where('empresa_id', $this->empresa_id)
            ->get();

        if($conta->fornecedor_id == null){
            if($conta->compra->fornecedor){
                $conta->fornecedor_id = $conta->compra->fornecedor_id;
                $conta->save();
            }
        }

        if(valida_objeto($conta)){

            return view('contaPagar/register')
                ->with('conta', $conta)
                ->with('fornecedores', $fornecedores)
                ->with('categorias', $categorias)
                ->with('title', 'Editar Contas a Pagar')
                ->with('veiculos', $veiculos)
                ->with('contasEmpresa', $contasEmpresa);
        }else{
            return redirect('/403');
        }
    }

    public function pagar($id){
        $categorias = CategoriaConta::
        where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->get();

        $conta = ContaPagar::findOrFail($id);

        if(valida_objeto($conta)){

            $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
                ->where('status', 1)->get();
            return view('contaPagar/pagar')
                ->with('conta', $conta)
                ->with('contasEmpresa', $contasEmpresa)
                ->with('categorias', $categorias)
                ->with('title', 'Pagar Conta');
        }else{
            return redirect('/403');
        }
    }

    //Agnaldo em 09032026
    public function estorno(Request $request)
    {
        // 1. Verifica se a senha está chegando e se está certa
        $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();

        if (md5($request->senha) != $config->senha_remover) {
            session()->flash('mensagem_erro', 'Senha de autorização incorreta!');
            return redirect()->back();
        }

        try {
            $conta = ContaPagar::findOrFail($request->id);

            // 2. BUSCA BRUTA (RAW SQL): Ignora travas
            $itensBancarios = \Illuminate\Support\Facades\DB::select(
                "SELECT * FROM item_conta_empresas WHERE conta_pagar_id = ?",
                [$conta->id]
            );

            // SE ELE NÃO ACHAR NADA, VAI TRAVAR A TELA AQUI PARA VOCÊ VER!
            if(count($itensBancarios) == 0) {
                dd("O SISTEMA NÃO ACHOU NENHUM EXTRATO COM O conta_pagar_id = " . $conta->id . ". Vá no banco de dados e confirme se o id da conta a pagar realmente está na coluna conta_pagar_id deste lançamento.");
            }

            $estornouBanco = false;

            // 3. Força a devolução e a exclusão
            foreach($itensBancarios as $item) {

                if ($item->conta_id) {
                    \Illuminate\Support\Facades\DB::update(
                        "UPDATE conta_empresas SET saldo = saldo + ? WHERE id = ?",
                        [$item->valor, $item->conta_id]
                    );
                }

                \Illuminate\Support\Facades\DB::delete(
                    "DELETE FROM item_conta_empresas WHERE id = ?",
                    [$item->id]
                );

                $estornouBanco = true;
            }

            // 4. Reverte o status da conta a pagar
            $conta->status = false;
            $conta->valor_pago = 0;
            $conta->data_pagamento = null;
            $conta->usuario_baixa_id = null;
            $conta->save();

            // SE CHEGAR AQUI, VAI TRAVAR A TELA AVISANDO QUE DEU TUDO CERTO
            //dd("SUCESSO! O SQL DE EXCLUSÃO RODOU E O SALDO FOI DEVOLVIDO. Pode remover os 'dd()' do código agora.");

        } catch (\Exception $e) {
            // SE DER ERRO NO BANCO, ELE TRAVA AQUI
            //dd("DEU ERRO NO CÓDIGO: " . $e->getMessage());
        }

        return redirect()->back();
    }

    public function estornoConta(Request $request)
    {
        // 1. Validação da Senha
        $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();

        if (md5($request->senha) != $config->senha_remover) {
            session()->flash('mensagem_erro', 'Senha de autorização incorreta!');
            return redirect()->back();
        }

        try {
            $conta = ContaPagar::findOrFail($request->id);

            // 2. BUSCA BRUTA (RAW SQL): Acha no banco de dados sem depender do Laravel
            $itensBancarios = \Illuminate\Support\Facades\DB::select(
                "SELECT * FROM item_conta_empresas WHERE conta_pagar_id = ?",
                [$conta->id]
            );

            $estornouBanco = false;

            // 3. Força a devolução e a exclusão
            if (count($itensBancarios) > 0) {
                foreach($itensBancarios as $item) {

                    if ($item->conta_id) {
                        // Devolve o dinheiro pro saldo
                        \Illuminate\Support\Facades\DB::update(
                            "UPDATE conta_empresas SET saldo = saldo + ? WHERE id = ?",
                            [$item->valor, $item->conta_id]
                        );
                    }

                    // Exclui a linha do extrato bancário
                    \Illuminate\Support\Facades\DB::delete(
                        "DELETE FROM item_conta_empresas WHERE id = ?",
                        [$item->id]
                    );

                    $estornouBanco = true;
                }
            }

            // 4. Reverte o status da conta
            $conta->status = false;
            $conta->valor_pago = 0;
            $conta->data_pagamento = null;
            $conta->usuario_baixa_id = null;
            $conta->estorno = true;
            $conta->motivo_estorno = "Estorno autorizado";
            $conta->save();

            // 5. Feedback
            if ($estornouBanco) {
                session()->flash('mensagem_sucesso', 'Estorno realizado! Extrato apagado e saldo devolvido.');
            } else {
                session()->flash('mensagem_alerta', 'Conta estornada, MAS não havia registro no extrato bancário.');
            }

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }

        $rota = __getRedirect($this->empresa_id, 'contas_pagar');
        if($rota != ""){
            return redirect($rota);
        }
        return redirect('/contasPagar');
    }

    public function pagarConta(Request $request)
    {
        // Busca a conta a pagar pelo ID
        $conta = ContaPagar::where('id', $request->id)->first();

        // --- INÍCIO DA ALTERAÇÃO PARA JUROS E MULTA ---

        // Trata o valor Principal
        $valor = str_replace(".", "", $request->valor);
        $valor = str_replace(",", ".", $valor);

        // Trata o valor de Juros (se não preenchido, assume 0)
        $juros = str_replace(".", "", $request->juros ?? '0,00');
        $juros = str_replace(",", ".", $juros);

        // Trata o valor de Multa (se não preenchido, assume 0)
        $multa = str_replace(".", "", $request->multa ?? '0,00');
        $multa = str_replace(",", ".", $multa);

        $conta->status = true;
        $conta->tipo_pagamento = $request->tipo_pagamento;
        $conta->valor_pago = $valor;

        // Salva os novos campos no banco
        $conta->juros = $juros;
        $conta->multa = $multa;
        $conta->usuario_baixa_id = session('user_logged')['id']; // Ajuste conforme a chave que você usa na sessão

        // --- FIM DA ALTERAÇÃO ---

        // Processa a data de pagamento (Mantendo a lógica original do seu sistema)
        if (strlen($request->data_pagamento) == 10) {
            $dtPag = \Carbon\Carbon::createFromFormat('d/m/Y', $request->data_pagamento)
                    ->format('Y-m-d') . " " . date("H:i:s");
        } else {
            $dtPag = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $request->data_pagamento)
                ->format('Y-m-d H:i:s');
        }
        $conta->data_pagamento = $dtPag;

        // Salva a conta paga
        $result = $conta->save();

        // 1. Busca o nome do fornecedor (Garante que a variável exista)
        $nomeFornecedor = "N/A";
        if ($conta->compra_id) {
            $compraDoc = \App\Models\Compra::find($conta->compra_id);
            if ($compraDoc && $compraDoc->fornecedor) {
                $nomeFornecedor = $compraDoc->fornecedor->razao_social;
            }
        } elseif ($conta->fornecedor_id) {
            $forn = \App\Models\Fornecedor::find($conta->fornecedor_id);
            if($forn) $nomeFornecedor = $forn->razao_social;
        }

        // Registro na conta empresa (se houver)
        if (isset($request->conta_id)) {
            $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);

            $data = [
                'conta_id'       => $request->conta_id,
                'descricao'      => "Pgto " . $nomeFornecedor . " | Ref: " . $conta->referencia,
                'tipo_pagamento' => $tipoPagamento,
                'valor'          => $valor,
                'tipo'           => 'saida',
                // Gravando a data no campo novo
                'data_pagamento' => \Carbon\Carbon::parse($dtPag)->format('Y-m-d'),
                'categoria_id'   => $conta->categoria_id,
                'usuario_id'     => session('user_logged')['id'],
                'user_id'        => session('user_logged')['id'],
                'origem'         => 'ContaPagar',
                'conta_pagar_id' => $conta->id,
                'created_at'     => $dtPag,
                'updated_at'     => $dtPag
            ];
            $itemContaEmpresa = \App\Models\ItemContaEmpresa::create($data);
            $this->util->atualizaSaldo($itemContaEmpresa);
        }

        if ($result) {
            session()->flash('mensagem_sucesso', 'Conta paga!');
            if (isset($itemContaEmpresa)) {
                session()->flash('print_recibo_id', $itemContaEmpresa->id);

            } else {
                session()->flash('mensagem_erro', 'Erro!');
            }

            $rota = __getRedirect($this->empresa_id, 'contas_pagar');
            if ($rota != "") {
                return redirect($rota);
            }
            return redirect('/contasPagar');
        }

    }

    public function delete($id){
        $conta = ContaPagar
            ::where('id', $id)
            ->first();
        if(valida_objeto($conta)){
            if($conta->delete()){

                session()->flash('mensagem_sucesso', 'Registro removido!');
            }else{

                session()->flash('mensagem_erro', 'Erro!');
            }
            return redirect()->back();
        }else{
            return redirect('/403');
        }

    }

    private function parseDate($date, $plusDay = false)
    {
        if (empty($date)) return null; // Retorna null se a data estiver vazia

        // Substitui barras por traços
        $date = str_replace("/", "-", $date);

        $timestamp = strtotime($date);

        if ($plusDay) {
            $timestamp = strtotime("+1 day", $timestamp);
        }

        return date('Y-m-d', $timestamp);
    }

    private function parseRecorrencia($rec){
        $temp = explode("/", $rec);
        $rec = "01/".$temp[0]."/20".$temp[1];
        //echo $rec;
        return date('Y-m', strtotime(str_replace("/", "-", $rec)));
    }

    public function relatorio(Request $request){
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $fornecedorId = $request->fornecedorId;
        $status = $request->status;
        $filial_id = $request->filial_id;

        $contas = [];
        $permissaoAcesso = __getLocaisUsarioLogado();

        $c = ContaPagar::
        select('conta_pagars.*')
            ->where(function($query) use ($permissaoAcesso){
                if($permissaoAcesso != null){
                    foreach ($permissaoAcesso as $value) {
                        if($value == -1){
                            $value = null;
                        }
                        $query->orWhere('conta_pagars.filial_id', $value);
                    }
                }
            })
            ->when($filial_id, function ($query) use ($filial_id) {
                $filial_id = $filial_id == -1 ? null : $filial_id;
                return $query->where('conta_pagars.filial_id', $filial_id);
            });
        if($fornecedorId != "null"){

            // $c->join('fornecedors', 'fornecedors.id' , '=', 'conta_pagars.fornecedor_id')
            // ->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");

            $c->where('fornecedor_id', $fornecedorId);
        }
        if($dataInicial && $dataFinal){
            if($request->tipo_filtro_data == 1){
                $c->whereBetween('conta_pagars.data_vencimento',
                    [
                        $this->parseDate($dataInicial),
                        $this->parseDate($dataFinal, 1)
                    ]
                );
            }else if($request->tipo_filtro_data == 2){
                $c->whereBetween('conta_pagars.created_at',
                    [
                        $this->parseDate($dataInicial),
                        $this->parseDate($dataFinal, true)
                    ]
                );
            }else{
                $c->whereBetween('conta_pagars.data_pagamento',
                    [
                        $this->parseDate($dataInicial),
                        $this->parseDate($dataFinal, true)
                    ]
                );
            }
        }
        if($status != 'todos'){
            if($status == 'pago'){
                $c->where('status', true);
            } else if($status == 'pendente'){
                $c->where('status', false);
            }else if($status == 'vencido'){
                $c->where('status', false)
                    ->whereDate('data_vencimento', '<=', date('Y-m-d'));
            }
        }
        if($request->tipo_filtro_data == 3){
            $c->where('status', true);
        }
        if($request->categoria != 'todos'){
            $c->where('categoria_id', $request->categoria);
        }
        if($request->tipo_pagamento){
            $c->where('tipo_pagamento', $request->tipo_pagamento);
        }
        $c->where('conta_pagars.empresa_id', $this->empresa_id);

        if($request->tipo_filtro_data == 1){
            $c->orderBy('conta_pagars.data_vencimento', 'asc');
        }

        if($request->numero_nota_fiscal){
            $c->where('conta_pagars.numero_nota_fiscal', $request->numero_nota_fiscal);
        }
        $temp = $c->get();

        foreach($temp as $t){
            array_push($contas, $t);
        }

        $c = ContaPagar::
        select('conta_pagars.*')
            ->where(function($query) use ($permissaoAcesso){
                if($permissaoAcesso != null){
                    foreach ($permissaoAcesso as $value) {
                        if($value == -1){
                            $value = null;
                        }
                        $query->orWhere('conta_pagars.filial_id', $value);
                    }
                }
            })
            ->when($filial_id, function ($query) use ($filial_id) {
                $filial_id = $filial_id == -1 ? null : $filial_id;
                return $query->where('conta_pagars.filial_id', $filial_id);
            });

        if($fornecedorId != "null"){
            // $c->join('compras', 'compras.id' , '=', 'conta_pagars.compra_id')
            // ->join('fornecedors', 'fornecedors.id' , '=', 'compras.fornecedor_id')
            // ->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");

            $c->join('compras', 'compras.id' , '=', 'conta_pagars.compra_id')
                ->where('compras.fornecedor_id', $fornecedorId);
        }
        if($dataInicial && $dataFinal){
            if($request->tipo_filtro_data == 1){
                $c->whereBetween('conta_pagars.data_vencimento',
                    [
                        $this->parseDate($dataInicial),
                        $this->parseDate($dataFinal)
                    ]
                );
            }elseif($request->tipo_filtro_data == 2){
                $c->whereBetween('conta_pagars.created_at',
                    [
                        $this->parseDate($dataInicial),
                        $this->parseDate($dataFinal, true)
                    ]
                );
            }else{
                $c->whereBetween('conta_pagars.data_pagamento',
                    [
                        $this->parseDate($dataInicial),
                        $this->parseDate($dataFinal, true)
                    ]
                );
            }
        }
        if($status != 'todos'){
            if($status == 'pago'){
                $c->where('status', true);
            } else if($status == 'pendente'){
                $c->where('status', false);
            }else if($status == 'vencido'){
                $c->where('status', false)
                    ->whereDate('data_vencimento', '<=', date('Y-m-d'));
            }
        }
        if($request->tipo_filtro_data == 3){
            $c->where('status', true);
        }
        if($request->categoria != 'todos'){
            $c->where('categoria_id', $request->categoria);
        }
        if($request->tipo_pagamento){
            $c->where('tipo_pagamento', $request->tipo_pagamento);
        }
        if($request->numero_nota_fiscal){
            $c->where('conta_pagars.numero_nota_fiscal', $request->numero_nota_fiscal);
        }
        $c->where('conta_pagars.empresa_id', $this->empresa_id);
        $temp = $c->get();
        foreach($temp as $t){
            if(!$this->validaInArray($t, $contas)){
                array_push($contas, $t);
            }
        }

        $p = view('relatorios/relatorio_contas_pagar')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('contas', $contas);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório Contas a Pagar.pdf", array("Attachment" => false));

    }

    public function pagarMultiplos($ids){
        $ids = explode(",", $ids);
        $somaTotal = 0;
        $contas = [];

        foreach($ids as $id){
            $conta = ContaPagar::find($id);
            // if($conta){
            // $conta->status = true;
            // $conta->valor_pago = $conta->valor_integral;
            // $conta->data_pagamento = date('Y-m-d H:i:s');
            // $conta->save();
            // }
            if($conta->empresa_id != $this->empresa_id){
                session()->flash('mensagem_erro', "Erro inesperado!");
                return redirect()->back();
            }
            $somaTotal += $conta->valor_integral;

            array_push($contas, $conta);
        }

        $title = 'Pagar contas';

        $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
            ->where('status', 1)->get();

        return view('contaPagar/pagar_multi',
            compact('somaTotal', 'title', 'contas', 'ids', 'contasEmpresa')
        );
    }

    public function pagarMultiploStore(Request $request)
    {
        $valorRecebido  = __replace($request->valor);
        $somaTotal      = $request->somaTotal;
        $tipo_pagamento = $request->tipo_pagamento;

        try {
            // Se o request contiver a data de pagamento, processa-a;
            // caso contrário, usa a data/hora atual.
            if ($request->has('data_pagamento') && !empty($request->data_pagamento)) {
                // Se o campo tiver 10 caracteres (ex.: "01/01/2023"), assume que é só a data
                if (strlen($request->data_pagamento) == 10) {
                    $dtPag = \Carbon\Carbon::createFromFormat('d/m/Y', $request->data_pagamento)
                            ->format('Y-m-d') . " " . date("H:i:s");
                } else {
                    // Se contiver data e hora (ex.: "01/01/2023 14:30:00")
                    $dtPag = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $request->data_pagamento)
                        ->format('Y-m-d H:i:s');
                }
            } else {
                $dtPag = date('Y-m-d H:i:s');
            }

            // Itera sobre os IDs das contas a pagar
            for ($i = 0; $i < sizeof($request->conta_pagar_id); $i++) {
                $conta = ContaPagar::findOrFail($request->conta_pagar_id[$i]);

                if ($conta->empresa_id == $this->empresa_id) {
                    $conta->status         = true;
                    $conta->valor_pago     = $conta->valor_integral;
                    $conta->tipo_pagamento = $request->tipo_pagamento;
                    // Utiliza a data de pagamento processada
                    $conta->data_pagamento = $dtPag;
                    $conta->save();

                    if (isset($request->conta_id)) {
                        $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);

                        $data = [
                            'conta_id'       => $request->conta_id,
                            'descricao'      => "Pagamento da conta " . $conta->referencia,
                            'tipo_pagamento' => $tipoPagamento,
                            'valor'          => $conta->valor_integral,
                            'origem'         => 'Conta Pagar', // <--- Ajuste aqui de 'manual' para 'Conta Pagar'
                            'tipo'           => 'saida',
                            'data_pagamento' => \Carbon\Carbon::parse($dtPag)->format('Y-m-d'), // ADICIONADO
                            'categoria_id'   => $conta->categoria_id,                           // ADICIONADO
                            'user_id'        => session('user_logged')['id'],                  // CORRIGIDO/ADICIONADO
                            'origem'         => 'Conta Pagar',                                  // CORRIGIDO
                            'conta_pagar_id' => $conta->id,
                        ];
                        $itemContaEmpresa = \App\Models\ItemContaEmpresa::create($data);
                        $this->util->atualizaSaldo($itemContaEmpresa);
                    }
                }
            }

            session()->flash('mensagem_sucesso', 'Contas pagas!');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }

        return redirect('/contasPagar');
    }

    public function exportExcel_17122025_bkp(Request $request)
    {
        // Recebe os parâmetros de filtro via query string
        $dataInicial     = $request->input('data_inicial');
        $dataFinal       = $request->input('data_final');
        $fornecedorId    = $request->input('fornecedorId');
        $status          = $request->input('status', 'todos');
        $categoria       = $request->input('categoria', 'todos');
        $tipo_pagamento  = $request->input('tipo_pagamento');
        $numero_nota_fiscal = $request->input('numero_nota_fiscal');
        $tipo_filtro_data = $request->input('tipo_filtro_data', 1);
        $filial_id       = $request->input('filial_id');

        // Garante que a empresa_id do usuário logado está setada
        if (!$this->empresa_id) {
            return redirect()->back()->with('mensagem_erro', 'Empresa não identificada!');
        }

        // Converte as datas para o formato correto
        $dataInicialFormatada = $dataInicial ? $this->parseDate($dataInicial) : date("Y-m-d");
        $dataFinalFormatada   = $dataFinal ? $this->parseDate($dataFinal, true) : date('Y-m-d', strtotime('+1 month'));

        // Filtra somente os dados da empresa do usuário logado e aplica o filtro da filial selecionada
        $query = \DB::table('conta_pagars as cp')
            ->join('categoria_contas as cc', 'cp.categoria_id', '=', 'cc.id')
            ->leftJoin('compras as comp', 'cp.compra_id', '=', 'comp.id')
            ->leftJoin('fornecedors as f', 'cp.fornecedor_id', '=', 'f.id')
            ->leftJoin('filials as fil', 'cp.filial_id', '=', 'fil.id') // Join para buscar o nome da filial
            ->select(
                'cp.id',
                'cp.referencia',
                'cp.valor_integral',
                'cp.valor_pago',
                'cp.data_vencimento',
                'cp.data_pagamento',
                'cp.status',
                'cp.tipo_pagamento',
                'cp.numero_nota_fiscal',
                'cc.nome as categoria_nome',
                'f.razao_social as fornecedor_razao',
                'f.cpf_cnpj as fornecedor_cpf_cnpj',
                'fil.descricao as filial_nome' // Adiciona a filial no relatório
            )
            ->where('cp.empresa_id', $this->empresa_id);

        // Aplicação dos filtros adicionais
        if ($dataInicial && $dataFinal) {
            if ($tipo_filtro_data == 1) {
                $query->whereBetween('cp.data_vencimento', [$dataInicialFormatada, $dataFinalFormatada]);
            } elseif ($tipo_filtro_data == 2) {
                $query->whereBetween('cp.created_at', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            } else {
                $query->whereBetween('cp.data_pagamento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            }
        }

        // Outros filtros
        if ($fornecedorId && $fornecedorId != 'null') {
            $query->where('cp.fornecedor_id', $fornecedorId);
        }
        if ($status && $status != 'todos') {
            if ($status == 'pago') {
                $query->where('cp.status', true);
            } elseif ($status == 'pendente') {
                $query->where('cp.status', false);
            } elseif ($status == 'vencido') {
                $query->where('cp.status', false)
                    ->whereDate('cp.data_vencimento', '<=', date('Y-m-d'));
            }
        }
        if ($categoria && $categoria != 'todos') {
            $query->where('cp.categoria_id', $categoria);
        }
        if ($tipo_pagamento) {
            $query->where('cp.tipo_pagamento', $tipo_pagamento);
        }
        if ($numero_nota_fiscal) {
            $query->where('cp.numero_nota_fiscal', $numero_nota_fiscal);
        }
        if ($filial_id && $filial_id != -1) {
            $query->where('cp.filial_id', $filial_id);
        }

        $contas = $query->orderBy('cp.data_vencimento', 'asc')->get();

        // Retorna o download do Excel utilizando a classe de exportação
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ContasPagarExport($contas), 'Relatorio_Contas_Pagar.xlsx');
    }

    public function exportExcel(Request $request)
    {
        // Captura os filtros exatamente como no seu método filtro()
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $fornecedorId = $request->fornecedorId;
        $status = $request->status;
        $categoria = $request->categoria;
        $tipo_pagamento = $request->tipo_pagamento;
        $tipo_filtro_data = $request->tipo_filtro_data ?? 1;
        $filial_id = $request->filial_id;
        $conta_id = $request->conta_id;
        $compra_id_filtro = $request->compra_id_filtro;
        $veiculo_id_filtro = $request->veiculo_id_filtro;

        $query = \DB::table('conta_pagars as cp')
            ->join('categoria_contas as cc', 'cp.categoria_id', '=', 'cc.id')
            ->leftJoin('fornecedors as forn_direto', 'cp.fornecedor_id', '=', 'forn_direto.id')
            ->leftJoin('compras as comp', 'cp.compra_id', '=', 'comp.id')
            ->leftJoin('fornecedors as forn_compra', 'comp.fornecedor_id', '=', 'forn_compra.id')
            ->leftJoin('filials as fil', 'cp.filial_id', '=', 'fil.id')
            ->leftJoin('item_conta_empresas as ice', 'cp.id', '=', 'ice.conta_pagar_id')
            ->leftJoin('conta_empresas as ce', 'ice.conta_id', '=', 'ce.id')
            ->select(
                'cp.data_emissao',
                'cp.data_pagamento',
                // LÓGICA DO FORNECEDOR: Se não tiver na conta, pega da compra
                \DB::raw("COALESCE(forn_direto.razao_social, forn_compra.razao_social) as fornecedor_nome"),
                'cp.referencia',
                'cc.nome as categoria_nome',
                'cp.tipo_pagamento',
                'ce.nome as conta_empresa',
                'cp.valor_pago',
                \DB::raw('0 as juros'),
                \DB::raw('0 as multa'),
                \DB::raw('0 as desconto'),
                \DB::raw("COALESCE(fil.descricao, 'Matriz') as filial_nome")
            )
            ->where('cp.empresa_id', $this->empresa_id);

        // --- APLICANDO OS FILTROS (Igual à sua tela) ---

        if($dataInicial && $dataFinal){
            $d1 = $this->parseDate($dataInicial);
            $d2 = $this->parseDate($dataFinal, ($tipo_filtro_data == 2));

            if($tipo_filtro_data == 1) $query->whereBetween('cp.data_vencimento', [$d1, $d2]);
            elseif($tipo_filtro_data == 2) $query->whereBetween('cp.created_at', [$d1, $d2]);
            elseif($tipo_filtro_data == 4) $query->whereBetween('cp.data_emissao', [$d1, $d2]);
            else $query->whereBetween('cp.data_pagamento', [$d1 . " 00:00:00", $d2 . " 23:59:59"]);
        }

        if($fornecedorId && $fornecedorId != 'null' && $fornecedorId != 'all'){
            $query->where(function($q) use ($fornecedorId){
                $q->where('cp.fornecedor_id', $fornecedorId)
                    ->orWhere('comp.fornecedor_id', $fornecedorId);
            });
        }

        if($status && $status != 'todos'){
            if($status == 'pago') $query->where('cp.status', true);
            else if($status == 'pendente') $query->where('cp.status', false);
            else if($status == 'vencido') $query->where('cp.status', false)->whereDate('cp.data_vencimento', '<=', date('Y-m-d'));
        }

        if($categoria && $categoria != 'todos') $query->where('cp.categoria_id', $categoria);
        if($conta_id && $conta_id != 'todos') $query->where('ice.conta_id', $conta_id);
        if($compra_id_filtro) $query->where('cp.compra_id', $compra_id_filtro);
        if($veiculo_id_filtro && $veiculo_id_filtro != 'todos') $query->where('cp.veiculo_id', $veiculo_id_filtro);
        if($filial_id && $filial_id != -1) $query->where('cp.filial_id', $filial_id);

        $contas = $query->orderBy('cp.data_vencimento', 'asc')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ContasPagarExport($contas), 'Relatorio_Contas_Pagar.xlsx');
    }
    public function syncNotaFiscal()
    {
        // 1. Seleciona as contas que têm uma compra vinculada
        $contas = ContaPagar::whereNotNull('compra_id')
            ->where('empresa_id', $this->empresa_id)
            ->get();

        $updatedCount = 0;

        foreach ($contas as $conta) {
            // Busca a compra vinculada para pegar os dados originais
            $compra = \App\Models\Compra::find($conta->compra_id);

            if ($compra) {
                $alterou = false;

                // Sincroniza o número da nota se estiver vazio
                if (empty($conta->numero_nota_fiscal) || $conta->numero_nota_fiscal == 0) {
                    $conta->numero_nota_fiscal = $compra->numero_emissao > 0 ? $compra->numero_emissao : $compra->nf;
                    $alterou = true;
                }

                // Sincroniza a DATA DE EMISSÃO (Limpando para o formato DATE YYYY-MM-DD)
                if (empty($conta->data_emissao) && !empty($compra->data_emissao)) {
                    $conta->data_emissao = substr($compra->data_emissao, 0, 10);
                    $alterou = true;
                }

                // Sincroniza o USUÁRIO se estiver vazio
                if (empty($conta->usuario_id) && !empty($compra->usuario_id)) {
                    $conta->usuario_id = $compra->usuario_id;
                    $alterou = true;
                }

                if ($alterou) {
                    if ($conta->save()) {
                        $updatedCount++;
                    }
                }
            }
        } // Fim do foreach

        session()->flash('mensagem_sucesso', "Sincronização concluída! {$updatedCount} conta(s) atualizada(s).");

        $rota = __getRedirect($this->empresa_id, 'contas_pagar');
        return redirect($rota != "" ? $rota : '/contasPagar');
    } // <-- ESTA CHAVE FECHA A FUNÇÃO

    public function detalhes($id)
    {
        // O código continua normalmente aqui...
        // Buscamos a conta garantindo que pertence à empresa logada
        $conta = ContaPagar::with(['usuario', 'usuarioEdicao','usuarioBaixa', 'categoria'])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        return view('contaPagar.detalhes', compact('conta'));
    }

    // Método para salvar o veículo
    public function setVeiculo(Request $request, $id){
        try{
            $conta = \App\Models\ContaPagar::findOrFail($id);
            $conta->veiculo_id = $request->veiculo_id;
            $conta->save();

            session()->flash('mensagem_sucesso', 'Veículo vinculado com sucesso!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    public function imprimirRecibo($id)
    {
        // Limpa qualquer saída anterior para não corromper o PDF
        if (ob_get_contents()) ob_end_clean();

        $conta = ContaPagar::with(['fornecedor', 'usuarioBaixa', 'categoria', 'filial'])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        if ($conta->status == 0) {
            return redirect()->back()->with('mensagem_erro', 'Conta ainda não foi paga!');
        }

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $p = view('relatorios/recibo_pagamento')
            ->with('conta', $conta)
            ->with('config', $config);

        $domPdf = new \Dompdf\Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);
        $domPdf->setPaper("A4");
        $domPdf->render();

        // Retorno limpo para o navegador entender que é um PDF
        return response($domPdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Recibo_'.$id.'.pdf"');
    } // <--- VERIFIQUE SE ESTA CHAVE ESTÁ AQUI

}

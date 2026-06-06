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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContasPagarController extends Controller
{
    protected $empresa_id = null;
    protected $filial_id = null; // <-- ADICIONADO: Trava Global de Filial
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

            // <-- ADICIONADO: Pega a filial logada
            $localPadrao = $value['local_padrao'] ?? null;
            $this->filial_id = (is_numeric($localPadrao) && $localPadrao > 0) ? (int)$localPadrao : null;

            return $next($request);
        });
    }

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
            ->when($this->filial_id != null, function ($q) {
                return $q->where('filial_id', $this->filial_id);
            })
            ->where(function($q) {
                $q->where('valor_inss', '>', 0)
                  ->orWhere('valor_iss', '>', 0)
                  ->orWhere('valor_pis', '>', 0)
                  ->orWhere('valor_cofins', '>', 0)
                  ->orWhere('valor_ir', '>', 0)
                  ->orWhere('valor_csll', '>', 0)
                  ->orWhere('outras_retencoes', '>', 0);
            })
            ->first();
    }

    public function index(Request $request)
    {
        if (!$request->has('fornecedorId') && session()->has('filtros_contas_pagar')) {
            return $this->filtro(new Request(session('filtros_contas_pagar')));
        }

        __saveRedirect($this->empresa_id, '', 'contas_pagar');

        $comRetencoes = $this->comRetencoes();

        $contas = ContaPagar::with(['usuario', 'usuarioEdicao', 'usuarioBaixa', 'veiculo', 'categoria'])
            ->whereBetween('data_vencimento', [
                date("Y-m-d"),
                date('Y-m-d', strtotime('+1 month'))
            ])
            ->where('empresa_id', $this->empresa_id)
            ->when($this->filial_id != null, function ($q) {
                return $q->where('filial_id', $this->filial_id);
            })
            ->orderBy('data_vencimento', 'asc')
            ->get();

        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $somaContas = $this->somaCategoriaDeContas($contas);
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->get();
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->get();
        $tiposPagamento = \App\Models\ContaPagar::tiposPagamento();

        $dataInicial = date('d/m/Y');
        $dataFinal = date('d/m/Y', strtotime('+1 month'));

        // Passa a filial do usuário para a tela bloquear o select se necessário
        $filial_id = $this->filial_id;

        return view('contaPagar/list', compact(
            'contas', 'comRetencoes', 'categorias', 'fornecedores', 'veiculos',
            'somaContas', 'dataInicial', 'dataFinal', 'contasEmpresa', 'tiposPagamento', 'filial_id'
        ))
        ->with('graficoJs', true)
        ->with('infoDados', "Dos próximos 30 dias")
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
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->get();
        $temp = [];
        foreach($categorias as $c){
            array_push($temp, $c->nome);
        }
        return $temp;
    }

    public function filtro(Request $request)
{
    session(['filtros_contas_pagar' => $request->all()]);

    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    __saveRedirect($this->empresa_id, $url, 'contas_pagar');

    $c = ContaPagar::with(['usuario', 'usuarioEdicao', 'usuarioBaixa', 'veiculo', 'categoria'])
        ->select('conta_pagars.*')
        ->leftJoin('item_conta_empresas as ice', 'conta_pagars.id', '=', 'ice.conta_pagar_id')
        ->leftJoin('compras as comp', 'conta_pagars.compra_id', '=', 'comp.id')
        ->where('conta_pagars.empresa_id', $this->empresa_id);

    // Trava de Filial
    $c->where(function($query) use ($request) {
        if ($this->filial_id != null) {
            $query->where('conta_pagars.filial_id', $this->filial_id);
        } elseif ($request->filial_id) {
            if ($request->filial_id == 'matriz' || $request->filial_id == -1) $query->whereNull('conta_pagars.filial_id');
            elseif ($request->filial_id > 0) $query->where('conta_pagars.filial_id', $request->filial_id);
        }
    });

    // Filtros
    if($request->conta_id && $request->conta_id != 'todos') $c->where('ice.conta_id', $request->conta_id);
    if($request->compra_id_filtro) $c->where('conta_pagars.compra_id', $request->compra_id_filtro);
    if($request->veiculo_id_filtro && $request->veiculo_id_filtro != 'todos') $c->where('conta_pagars.veiculo_id', $request->veiculo_id_filtro);

    // Filtro de Fornecedor Unificado (Conta OU Compra)
    if($request->fornecedorId && $request->fornecedorId != "null"){
        $c->where(function($q) use ($request) {
            $q->where('conta_pagars.fornecedor_id', $request->fornecedorId)
              ->orWhere('comp.fornecedor_id', $request->fornecedorId);
        });
    }

    if($request->data_inicial && $request->data_final){
        $d_ini = $this->parseDate($request->data_inicial);
        $d_fim = $this->parseDate($request->data_final);
        if($request->tipo_filtro_data == 1) $c->whereBetween('conta_pagars.data_vencimento', [$d_ini, $d_fim]);
        elseif($request->tipo_filtro_data == 2) $c->whereBetween('conta_pagars.created_at', [$d_ini . " 00:00:00", $d_fim . " 23:59:59"]);
        elseif($request->tipo_filtro_data == 4) $c->whereBetween('conta_pagars.data_emissao', [$d_ini, $d_fim]);
        else $c->whereBetween('conta_pagars.data_pagamento', [$d_ini . " 00:00:00", $d_fim . " 23:59:59"]);
    }

    if($request->status != 'todos' && !empty($request->status)){
        if($request->status == 'pago') $c->where('conta_pagars.status', true);
        else if($request->status == 'pendente') $c->where('conta_pagars.status', false);
        else if($request->status == 'vencido') $c->where('conta_pagars.status', false)->whereDate('data_vencimento', '<=', date('Y-m-d'));
    }

    if($request->categoria != 'todos') $c->where('conta_pagars.categoria_id', $request->categoria);
    if($request->tipo_pagamento) $c->where('conta_pagars.tipo_pagamento', $request->tipo_pagamento);
    if($request->numero_nota_fiscal) $c->where('conta_pagars.numero_nota_fiscal', $request->numero_nota_fiscal);

    // O distinct() remove a duplicidade causada pelo leftJoin com itens de conta
    $contas = $c->distinct()->orderBy('conta_pagars.data_vencimento', 'asc')->get();

    $comRetencoes = $this->comRetencoes();

    // Finalização igual a anterior
    $somaContas = $this->somaCategoriaDeContas($contas);
    $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->get();
    $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
    $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
    $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->get();

    $filial_id = $this->filial_id != null ? $this->filial_id : $request->filial_id;

    return view('contaPagar/list', compact(
        'contas', 'comRetencoes', 'categorias', 'fornecedores', 'veiculos',
        'contasEmpresa', 'somaContas', 'filial_id', 'url'
    ))
    ->with('fornecedorId', $request->fornecedorId)
    ->with('conta_id', $request->conta_id)
    ->with('compra_id_filtro', $request->compra_id_filtro)
    ->with('veiculo_id_filtro', $request->veiculo_id_filtro)
    ->with('dataInicial', $request->data_inicial)
    ->with('dataFinal', $request->data_final)
    ->with('status', $request->status)
    ->with('tipo_filtro_data', $request->tipo_filtro_data)
    ->with('categoria', $request->categoria)
    ->with('tipo_pagamento', $request->tipo_pagamento)
    ->with('numero_nota_fiscal', $request->numero_nota_fiscal)
    ->with('infoDados', "Contas filtradas")
    ->with('title', 'Filtro Contas a Pagar');
}


    public function salvarParcela(Request $request)
    {
        $parcela = $request->parcela;
        $valorParcela = str_replace(",", ".", $parcela['valor_parcela']);
        $valorParcela = str_replace(" ", "", $valorParcela);

        $categoria = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->first();
        if (isset($parcela['categoria_conta_id']) && $parcela['categoria_conta_id']) {
            $categoria = CategoriaConta::findOrFail($parcela['categoria_conta_id']);
        }

        $numeroNota = isset($parcela['numero_nota_fiscal']) ? $parcela['numero_nota_fiscal'] : null;
        if (empty($numeroNota) && isset($parcela['compra_id']) && !empty($parcela['compra_id'])) {
            $compra = \App\Models\Compra::find($parcela['compra_id']);
            if ($compra) $numeroNota = $compra->nf;
        }
        if (empty($numeroNota)) $numeroNota = 0;

        $filialParaSalvar = $this->filial_id != null ? $this->filial_id : ($parcela['filial_id'] != -1 ? $parcela['filial_id'] : null);

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
            'usuario_id'         => session('user_logged')['id'],
            'filial_id'          => $filialParaSalvar,
            'fornecedor_id'      => $parcela['fornecedor_id'],
            'numero_nota_fiscal' => $numeroNota
        ]);
        echo json_encode($parcela);
    }

    public function save(Request $request)
    {
        $this->_validate($request);
        $data = $request->all();
        $data['data_vencimento'] = $this->parseDate($request->vencimento);
        $data['data_emissao'] = $request->data_emissao ? $this->parseDate($request->data_emissao) : date('Y-m-d');
        $data['valor_integral'] = $request->valor_final ? __replace($request->valor_final) : __replace($request->valor);
        $data['usuario_id'] = session('user_logged')['id'];
        $data['empresa_id'] = $this->empresa_id;

        // Aplica a trava de filial no salvamento
        if ($this->filial_id != null) {
            $data['filial_id'] = $this->filial_id;
        } else {
            $data['filial_id'] = ($request->filial_id == -1 || $request->filial_id == 'matriz') ? null : $request->filial_id;
        }

        $data['numero_nota_fiscal'] = $request->numero_nota_fiscal ?? 0;
        $data['tipo_pagamento'] = $request->tipo_pagamento ?? 'PIX';

        $numeroNota = $request->numero_nota_fiscal;
        if (empty($numeroNota) && isset($request->compra_id) && $request->compra_id != "") {
            $compra = \App\Models\Compra::find($request->compra_id);
            if ($compra) $numeroNota = $compra->nf;
        }
        $data['numero_nota_fiscal'] = $numeroNota ?? 0;

        if (isset($request->recorrencia) && strlen($request->recorrencia) == 5) {
            if (!$this->validaRecorrencia($request->recorrencia)) {
                session()->flash('mensagem_erro', 'Valor recorrente inválido!');
                return redirect('/contasPagar/new');
            }
        }

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

        $camposRetencao = ['valor_inss', 'valor_iss', 'valor_pis', 'valor_cofins', 'valor_ir', 'outras_retencoes'];
        foreach ($camposRetencao as $f) {
            $data[$f] = __replace($request->input($f, 0));
        }

        $parcelas = json_decode($request->parcelas ?? '[]');
        if (count($parcelas) > 0) {
            $data['referencia'] = $request->referencia . " - parcela 1/" . (count($parcelas) + 1);
        }

        $conta = ContaPagar::create($data);

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

            if (isset($this->util)) $this->util->atualizaSaldo($item);
        }

        return redirect('/contasPagar')->with('mensagem_sucesso', 'Registro inserido com sucesso!');
    }

    public function update(Request $request)
    {
        $this->_validate($request);

        $conta = ContaPagar::where('id', $request->id)->where('empresa_id', $this->empresa_id)->firstOrFail();

        // Trava: Impede o usuário de editar conta de outra filial
        if ($this->filial_id != null && $conta->filial_id != $this->filial_id) {
            session()->flash('mensagem_erro', 'Acesso negado: Este registro pertence a outra unidade.');
            return redirect('/contasPagar');
        }

        $conta->data_vencimento = $this->parseDate($request->vencimento);
        $conta->data_emissao    = $request->data_emissao ? $this->parseDate($request->data_emissao) : null;
        $conta->referencia       = $request->referencia;
        $conta->observacao       = $request->observacao ?? "";
        $conta->valor_integral   = str_replace(",", ".", $request->valor);
        $conta->categoria_id     = $request->categoria_id;
        $conta->tipo_pagamento   = $request->tipo_pagamento ?? '';
        $conta->veiculo_id = $request->veiculo_id == 'todos' ? null : $request->veiculo_id;
        $conta->usuario_edicao_id = session('user_logged')['id'];

        if (empty($request->numero_nota_fiscal) && $conta->compra_id) {
            $compra = \App\Models\Compra::find($conta->compra_id);
            $conta->numero_nota_fiscal = $compra ? $compra->nf : 0;
        } else {
            $conta->numero_nota_fiscal = $request->numero_nota_fiscal;
        }

        $conta->valor_inss       = $request->valor_inss ? __replace($request->valor_inss) : 0;
        $conta->valor_iss        = $request->valor_iss ? __replace($request->valor_iss) : 0;
        $conta->valor_pis        = $request->valor_pis ? __replace($request->valor_pis) : 0;
        $conta->valor_cofins     = $request->valor_cofins ? __replace($request->valor_cofins) : 0;
        $conta->valor_ir         = $request->valor_ir ? __replace($request->valor_ir) : 0;
        $conta->outras_retencoes = $request->outras_retencoes ? __replace($request->outras_retencoes) : 0;

        // Aplica a trava de filial na atualização
        if ($this->filial_id != null) {
            $conta->filial_id = $this->filial_id;
        } else {
            $conta->filial_id = ($request->filial_id == -1 || $request->filial_id == 'matriz') ? null : $request->filial_id;
        }

        $result = $conta->save();

        if ($result) {
            session()->flash('mensagem_sucesso', 'Registro editado!');
        } else {
            session()->flash('mensagem_erro', 'Ocorreu um erro!');
        }

        $rota = __getRedirect($this->empresa_id, 'contas_pagar');
        if ($rota != "") return redirect($rota);
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
        $veiculos = \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->get();
        $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->where('status', 1)->get();
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->orderBy('nome')->get();

        if(sizeof($categorias) == 0){
            session()->flash('mensagem_alerta', 'Cadastre uma categoria com o tipo pagar!');
            return redirect('/categoriasConta');
        }

        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        if($config == null){
            session()->flash('mensagem_alerta', 'Informe a configuração do emitente!');
            return redirect('/configNF');
        }

        $filial_id = $this->filial_id;

        return view('contaPagar/register')
            ->with('categorias', $categorias)
            ->with('fornecedores', $fornecedores)
            ->with('config', $config)
            ->with('title', 'Cadastrar Contas a Pagar')
            ->with('veiculos', $veiculos)
            ->with('filial_id', $filial_id)
            ->with('contasEmpresa', $contasEmpresa);
    }

    public function edit($id){
        $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->where('status', 1)->get();
        $veiculos = \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->get();
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->orderBy('nome')->get();

        $conta = ContaPagar::where('id', $id)->where('empresa_id', $this->empresa_id)->firstOrFail();

        // Trava Filial
        if ($this->filial_id != null && $conta->filial_id != $this->filial_id) {
            return redirect('/403');
        }

        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();

        if($conta->fornecedor_id == null){
            if($conta->compra->fornecedor){
                $conta->fornecedor_id = $conta->compra->fornecedor_id;
                $conta->save();
            }
        }

        return view('contaPagar/register')
            ->with('conta', $conta)
            ->with('fornecedores', $fornecedores)
            ->with('categorias', $categorias)
            ->with('title', 'Editar Contas a Pagar')
            ->with('veiculos', $veiculos)
            ->with('filial_id', $this->filial_id)
            ->with('contasEmpresa', $contasEmpresa);
    }

    public function pagar($id){
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->get();
        $conta = ContaPagar::where('id', $id)->where('empresa_id', $this->empresa_id)->firstOrFail();

        // Trava Filial
        if ($this->filial_id != null && $conta->filial_id != $this->filial_id) {
            return redirect('/403');
        }

        $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)->where('status', 1)->get();
        return view('contaPagar/pagar')
            ->with('conta', $conta)
            ->with('contasEmpresa', $contasEmpresa)
            ->with('categorias', $categorias)
            ->with('title', 'Pagar Conta');
    }

    public function estorno(Request $request)
    {
        $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();
        if (md5($request->senha) != $config->senha_remover) {
            session()->flash('mensagem_erro', 'Senha de autorização incorreta!');
            return redirect()->back();
        }

        try {
            $conta = ContaPagar::where('id', $request->id)->where('empresa_id', $this->empresa_id)->firstOrFail();

            // Trava Filial
            if ($this->filial_id != null && $conta->filial_id != $this->filial_id) return redirect('/403');

            $itensBancarios = \Illuminate\Support\Facades\DB::select("SELECT * FROM item_conta_empresas WHERE conta_pagar_id = ?", [$conta->id]);

            if(count($itensBancarios) == 0) {
                dd("O SISTEMA NÃO ACHOU NENHUM EXTRATO. Confirme se o ID da conta está na coluna conta_pagar_id.");
            }

            foreach($itensBancarios as $item) {
                if ($item->conta_id) {
                    \Illuminate\Support\Facades\DB::update("UPDATE conta_empresas SET saldo = saldo + ? WHERE id = ?", [$item->valor, $item->conta_id]);
                }
                \Illuminate\Support\Facades\DB::delete("DELETE FROM item_conta_empresas WHERE id = ?", [$item->id]);
            }

            $conta->status = false;
            $conta->valor_pago = 0;
            $conta->data_pagamento = null;
            $conta->usuario_baixa_id = null;
            $conta->save();
        } catch (\Exception $e) { }

        return redirect()->back();
    }

    public function estornoConta(Request $request)
    {
        $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();
        if (md5($request->senha) != $config->senha_remover) {
            session()->flash('mensagem_erro', 'Senha de autorização incorreta!');
            return redirect()->back();
        }

        try {
            $conta = ContaPagar::where('id', $request->id)->where('empresa_id', $this->empresa_id)->firstOrFail();

            // Trava Filial
            if ($this->filial_id != null && $conta->filial_id != $this->filial_id) return redirect('/403');

            $itensBancarios = \Illuminate\Support\Facades\DB::select("SELECT * FROM item_conta_empresas WHERE conta_pagar_id = ?", [$conta->id]);
            $estornouBanco = false;

            if (count($itensBancarios) > 0) {
                foreach($itensBancarios as $item) {
                    if ($item->conta_id) {
                        \Illuminate\Support\Facades\DB::update("UPDATE conta_empresas SET saldo = saldo + ? WHERE id = ?", [$item->valor, $item->conta_id]);
                    }
                    \Illuminate\Support\Facades\DB::delete("DELETE FROM item_conta_empresas WHERE id = ?", [$item->id]);
                    $estornouBanco = true;
                }
            }

            $conta->status = false;
            $conta->valor_pago = 0;
            $conta->data_pagamento = null;
            $conta->usuario_baixa_id = null;
            $conta->estorno = true;
            $conta->motivo_estorno = "Estorno autorizado";
            $conta->save();

            if ($estornouBanco) session()->flash('mensagem_sucesso', 'Estorno realizado! Extrato apagado e saldo devolvido.');
            else session()->flash('mensagem_alerta', 'Conta estornada, MAS não havia registro no extrato bancário.');

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }

        $rota = __getRedirect($this->empresa_id, 'contas_pagar');
        if($rota != "") return redirect($rota);
        return redirect('/contasPagar');
    }

    public function pagarConta(Request $request)
    {
        $conta = ContaPagar::where('id', $request->id)->where('empresa_id', $this->empresa_id)->firstOrFail();

        // Trava Filial
        if ($this->filial_id != null && $conta->filial_id != $this->filial_id) return redirect('/403');

        $valor = str_replace(",", ".", str_replace(".", "", $request->valor));
        $juros = str_replace(",", ".", str_replace(".", "", $request->juros ?? '0,00'));
        $multa = str_replace(",", ".", str_replace(".", "", $request->multa ?? '0,00'));

        $conta->status = true;
        $conta->tipo_pagamento = $request->tipo_pagamento;
        $conta->valor_pago = $valor;
        $conta->juros = $juros;
        $conta->multa = $multa;
        $conta->usuario_baixa_id = session('user_logged')['id'];

        if (strlen($request->data_pagamento) == 10) {
            $dtPag = \Carbon\Carbon::createFromFormat('d/m/Y', $request->data_pagamento)->format('Y-m-d') . " " . date("H:i:s");
        } else {
            $dtPag = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $request->data_pagamento)->format('Y-m-d H:i:s');
        }
        $conta->data_pagamento = $dtPag;
        $result = $conta->save();

        $nomeFornecedor = "N/A";
        if ($conta->compra_id) {
            $compraDoc = \App\Models\Compra::find($conta->compra_id);
            if ($compraDoc && $compraDoc->fornecedor) $nomeFornecedor = $compraDoc->fornecedor->razao_social;
        } elseif ($conta->fornecedor_id) {
            $forn = \App\Models\Fornecedor::find($conta->fornecedor_id);
            if($forn) $nomeFornecedor = $forn->razao_social;
        }

        if (isset($request->conta_id)) {
            $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);
            $data = [
                'conta_id'       => $request->conta_id,
                'descricao'      => "Pgto " . $nomeFornecedor . " | Ref: " . $conta->referencia,
                'tipo_pagamento' => $tipoPagamento,
                'valor'          => $valor,
                'tipo'           => 'saida',
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
            if (isset($itemContaEmpresa)) session()->flash('print_recibo_id', $itemContaEmpresa->id);
            else session()->flash('mensagem_erro', 'Erro!');

            $rota = __getRedirect($this->empresa_id, 'contas_pagar');
            if ($rota != "") return redirect($rota);
            return redirect('/contasPagar');
        }
    }

    public function delete($id){
        $conta = ContaPagar::where('id', $id)->where('empresa_id', $this->empresa_id)->first();
        if(valida_objeto($conta)){
            if ($this->filial_id != null && $conta->filial_id != $this->filial_id) return redirect('/403');

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
        if (empty($date)) return null;
        $date = str_replace("/", "-", $date);
        $timestamp = strtotime($date);
        if ($plusDay) $timestamp = strtotime("+1 day", $timestamp);
        return date('Y-m-d', $timestamp);
    }

    private function parseRecorrencia($rec){
        $temp = explode("/", $rec);
        $rec = "01/".$temp[0]."/20".$temp[1];
        return date('Y-m', strtotime(str_replace("/", "-", $rec)));
    }

    public function relatorio(Request $request){
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $fornecedorId = $request->fornecedorId;
        $status = $request->status;
        $filial_id = $request->filial_id;

        $contas = [];

        $c = ContaPagar::select('conta_pagars.*')
            ->where(function($query) use ($filial_id) {
                if ($this->filial_id != null) {
                    $query->where('conta_pagars.filial_id', $this->filial_id);
                } else {
                    if ($filial_id == 'matriz' || $filial_id == -1) {
                        $query->whereNull('conta_pagars.filial_id');
                    } elseif ($filial_id > 0) {
                        $query->where('conta_pagars.filial_id', $filial_id);
                    }
                }
            });

        if($fornecedorId != "null") $c->where('fornecedor_id', $fornecedorId);

        if($dataInicial && $dataFinal){
            if($request->tipo_filtro_data == 1) $c->whereBetween('conta_pagars.data_vencimento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, 1)]);
            else if($request->tipo_filtro_data == 2) $c->whereBetween('conta_pagars.created_at', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            else $c->whereBetween('conta_pagars.data_pagamento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
        }

        if($status != 'todos'){
            if($status == 'pago') $c->where('status', true);
            else if($status == 'pendente') $c->where('status', false);
            else if($status == 'vencido') $c->where('status', false)->whereDate('data_vencimento', '<=', date('Y-m-d'));
        }

        if($request->tipo_filtro_data == 3) $c->where('status', true);
        if($request->categoria != 'todos') $c->where('conta_pagars.categoria_id', $request->categoria);
        if($request->tipo_pagamento) $c->where('tipo_pagamento', $request->tipo_pagamento);
        $c->where('conta_pagars.empresa_id', $this->empresa_id);

        if($request->tipo_filtro_data == 1) $c->orderBy('conta_pagars.data_vencimento', 'asc');
        if($request->numero_nota_fiscal) $c->where('conta_pagars.numero_nota_fiscal', $request->numero_nota_fiscal);

        $temp = $c->get();
        foreach($temp as $t) array_push($contas, $t);

        $c = ContaPagar::select('conta_pagars.*')
            ->where(function($query) use ($filial_id) {
                if ($this->filial_id != null) {
                    $query->where('conta_pagars.filial_id', $this->filial_id);
                } else {
                    if ($filial_id == 'matriz' || $filial_id == -1) {
                        $query->whereNull('conta_pagars.filial_id');
                    } elseif ($filial_id > 0) {
                        $query->where('conta_pagars.filial_id', $filial_id);
                    }
                }
            });

        if($fornecedorId != "null"){
            $c->join('compras', 'compras.id' , '=', 'conta_pagars.compra_id')
                ->where('compras.fornecedor_id', $fornecedorId);
        }

        if($dataInicial && $dataFinal){
            if($request->tipo_filtro_data == 1) $c->whereBetween('conta_pagars.data_vencimento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal)]);
            elseif($request->tipo_filtro_data == 2) $c->whereBetween('conta_pagars.created_at', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            else $c->whereBetween('conta_pagars.data_pagamento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
        }

        if($status != 'todos'){
            if($status == 'pago') $c->where('status', true);
            else if($status == 'pendente') $c->where('status', false);
            else if($status == 'vencido') $c->where('status', false)->whereDate('data_vencimento', '<=', date('Y-m-d'));
        }

        if($request->tipo_filtro_data == 3) $c->where('status', true);
        if($request->categoria != 'todos') $c->where('categoria_id', $request->categoria);
        if($request->tipo_pagamento) $c->where('tipo_pagamento', $request->tipo_pagamento);
        if($request->numero_nota_fiscal) $c->where('conta_pagars.numero_nota_fiscal', $request->numero_nota_fiscal);
        $c->where('conta_pagars.empresa_id', $this->empresa_id);

        $temp = $c->get();
        foreach($temp as $t){
            if(!$this->validaInArray($t, $contas)) array_push($contas, $t);
        }

        $p = view('relatorios/relatorio_contas_pagar')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('contas', $contas);

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
            $conta = ContaPagar::findOrFail($id);
            if($conta->empresa_id != $this->empresa_id) return redirect()->back()->with('mensagem_erro', 'Erro inesperado!');

            // Trava Filial
            if ($this->filial_id != null && $conta->filial_id != $this->filial_id) return redirect('/403');

            $somaTotal += $conta->valor_integral;
            array_push($contas, $conta);
        }

        $title = 'Pagar contas';
        $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)->where('status', 1)->get();

        return view('contaPagar/pagar_multi', compact('somaTotal', 'title', 'contas', 'ids', 'contasEmpresa'));
    }

    public function pagarMultiploStore(Request $request)
    {
        $valorRecebido  = __replace($request->valor);
        $somaTotal      = $request->somaTotal;
        $tipo_pagamento = $request->tipo_pagamento;

        try {
            if ($request->has('data_pagamento') && !empty($request->data_pagamento)) {
                if (strlen($request->data_pagamento) == 10) {
                    $dtPag = \Carbon\Carbon::createFromFormat('d/m/Y', $request->data_pagamento)->format('Y-m-d') . " " . date("H:i:s");
                } else {
                    $dtPag = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $request->data_pagamento)->format('Y-m-d H:i:s');
                }
            } else {
                $dtPag = date('Y-m-d H:i:s');
            }

            for ($i = 0; $i < sizeof($request->conta_pagar_id); $i++) {
                $conta = ContaPagar::findOrFail($request->conta_pagar_id[$i]);

                if ($conta->empresa_id == $this->empresa_id) {
                    // Trava de Filial
                    if ($this->filial_id != null && $conta->filial_id != $this->filial_id) continue;

                    $conta->status         = true;
                    $conta->valor_pago     = $conta->valor_integral;
                    $conta->tipo_pagamento = $request->tipo_pagamento;
                    $conta->data_pagamento = $dtPag;
                    $conta->save();

                    if (isset($request->conta_id)) {
                        $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);
                        $data = [
                            'conta_id'       => $request->conta_id,
                            'descricao'      => "Pagamento da conta " . $conta->referencia,
                            'tipo_pagamento' => $tipoPagamento,
                            'valor'          => $conta->valor_integral,
                            'tipo'           => 'saida',
                            'data_pagamento' => \Carbon\Carbon::parse($dtPag)->format('Y-m-d'),
                            'categoria_id'   => $conta->categoria_id,
                            'user_id'        => session('user_logged')['id'],
                            'origem'         => 'Conta Pagar',
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

    public function exportExcel(Request $request)
    {
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
                'cp.id',
                \DB::raw("COALESCE(forn_direto.razao_social, forn_compra.razao_social) as fornecedor_razao"),
                \DB::raw("COALESCE(forn_direto.cpf_cnpj, forn_compra.cpf_cnpj) as fornecedor_cpf_cnpj"),
                'cc.nome as categoria_nome',
                'cp.referencia',
                'cp.observacao',
                'cp.valor_integral',
                'cp.valor_pago',
                'cp.juros',
                'cp.multa',
                \DB::raw('0 as desconto'),
                'cp.data_vencimento',
                'cp.data_pagamento',
                'cp.status',
                'cp.tipo_pagamento',
                'ce.nome as conta_empresa',
                'cp.numero_nota_fiscal',
                'cp.data_emissao as data_emissao_nfe',
                \DB::raw("COALESCE(fil.descricao, 'Matriz') as filial_nome")
            )
            ->where('cp.empresa_id', $this->empresa_id);

        if ($this->filial_id != null) {
            $query->where('cp.filial_id', $this->filial_id);
        } else {
            if ($filial_id == 'matriz' || $filial_id == -1) $query->whereNull('cp.filial_id');
            elseif ($filial_id > 0) $query->where('cp.filial_id', $filial_id);
        }

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

        $contas = $query->orderBy('cp.data_vencimento', 'asc')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ContasPagarExport($contas), 'Relatorio_Contas_Pagar.xlsx');
    }

  public function syncNotaFiscal()
    {
        $contas = ContaPagar::whereNotNull('compra_id')
            ->where('empresa_id', $this->empresa_id)
            ->when($this->filial_id != null, function ($q) {
                return $q->where('filial_id', $this->filial_id);
            })
            ->get();

        $updatedCount = 0;

        foreach ($contas as $conta) {
            $compra = \App\Models\Compra::find($conta->compra_id);

            if ($compra) {
                $alterou = false;

                if (empty($conta->numero_nota_fiscal) || $conta->numero_nota_fiscal == 0) {
                    $conta->numero_nota_fiscal = $compra->numero_emissao > 0 ? $compra->numero_emissao : $compra->nf;
                    $alterou = true;
                }

                if (empty($conta->data_emissao) && !empty($compra->data_emissao)) {
                    $conta->data_emissao = substr($compra->data_emissao, 0, 10);
                    $alterou = true;
                }

                if (empty($conta->usuario_id) && !empty($compra->usuario_id)) {
                    $conta->usuario_id = $compra->usuario_id;
                    $alterou = true;
                }

                if ($alterou && $conta->save()) $updatedCount++;
            }
        }

        session()->flash('mensagem_sucesso', "Sincronização concluída! {$updatedCount} conta(s) atualizada(s).");
        $rota = __getRedirect($this->empresa_id, 'contas_pagar');
        return redirect($rota != "" ? $rota : '/contasPagar');
    }

    public function detalhes($id)
    {
        $conta = ContaPagar::with(['usuario', 'usuarioEdicao','usuarioBaixa', 'categoria'])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        if ($this->filial_id != null && $conta->filial_id != $this->filial_id) return redirect('/403');

     	$title = 'Detalhes da Conta a Pagar';

        return view('contaPagar.detalhes', compact('conta', 'title'));
    }

    public function setVeiculo(Request $request, $id){
        try{
            $conta = \App\Models\ContaPagar::where('id', $id)->where('empresa_id', $this->empresa_id)->firstOrFail();

            if ($this->filial_id != null && $conta->filial_id != $this->filial_id) return redirect('/403');

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
        if (ob_get_contents()) ob_end_clean();

        $conta = ContaPagar::with(['fornecedor', 'usuarioBaixa', 'categoria', 'filial'])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        if ($this->filial_id != null && $conta->filial_id != $this->filial_id) return redirect('/403');

        if ($conta->status == 0) return redirect()->back()->with('mensagem_erro', 'Conta ainda não foi paga!');

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $p = view('relatorios/recibo_pagamento')
            ->with('conta', $conta)
            ->with('config', $config);

        $domPdf = new \Dompdf\Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);
        $domPdf->setPaper("A4");
        $domPdf->render();

        return response($domPdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Recibo_'.$id.'.pdf"');
    }

    public function retencoes(Request $request)
    {
        $data_inicio = $request->data_inicio;
        $data_final = $request->data_final;
        $fornecedor_nome = $request->fornecedor;

        $query = ContaPagar::with(['fornecedor'])
            ->where('empresa_id', $this->empresa_id)
            ->when($this->filial_id != null, function ($q) {
                return $q->where('filial_id', $this->filial_id);
            })
            ->where(function($q) {
                $q->where('valor_iss', '>', 0)
                    ->orWhere('valor_ir', '>', 0)
                    ->orWhere('valor_pis', '>', 0)
                    ->orWhere('valor_cofins', '>', 0)
                    ->orWhere('valor_csll', '>', 0)
                    ->orWhere('valor_inss', '>', 0);
            });

        if ($data_inicio && $data_final) $query->whereBetween('data_emissao', [$data_inicio, $data_final]);

        if ($fornecedor_nome) {
            $query->whereHas('fornecedor', function($q) use ($fornecedor_nome) {
                $q->where('razao_social', 'LIKE', "%$fornecedor_nome%");
            });
        }

        $data = $query->orderBy('data_emissao', 'desc')->paginate(30);

        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->get();

        return view('retencoes.index', compact('data', 'fornecedores', 'categorias'));
    }

    public function fecharMesRetencoes(Request $request)
    {
        try {
            DB::beginTransaction();

            $contasPagar = ContaPagar::whereMonth('data_emissao', $request->mes)
                ->whereYear('data_emissao', $request->ano)
                ->where('retencoes_processadas', 0)
                ->where('empresa_id', $this->empresa_id)
                ->when($this->filial_id != null, function ($q) {
                    return $q->where('filial_id', $this->filial_id);
                })
                ->get();

            if ($contasPagar->isEmpty()) return response()->json("Nenhuma retenção pendente encontrada para este período.", 400);

            $iss  = $contasPagar->sum('valor_iss');
            $irrf = $contasPagar->sum('valor_ir');
            $pcc  = $contasPagar->sum('valor_pis') + $contasPagar->sum('valor_cofins') + $contasPagar->sum('valor_csll');

            $vencimento = "{$request->ano}-" . str_pad($request->mes + 1, 2, '0', STR_PAD_LEFT) . "-20";

            if ($iss > 0) $this->criarContaAgrupada($iss, "RETENÇÃO ISS CONSOLIDADA - EMISSÃO {$request->mes}/{$request->ano}", $vencimento, $request->fornecedor_iss_id, $request->categoria_iss_id);
            if ($pcc > 0) $this->criarContaAgrupada($pcc, "RETENÇÃO PCC CONSOLIDADA - EMISSÃO {$request->mes}/{$request->ano}", $vencimento, $request->fornecedor_pcc_id, $request->categoria_pcc_id);
            if ($irrf > 0) $this->criarContaAgrupada($irrf, "RETENÇÃO IRRF PJ CONSOLIDADA - EMISSÃO {$request->mes}/{$request->ano}", $vencimento, $request->fornecedor_ir_id, $request->categoria_ir_id);

            ContaPagar::whereIn('id', $contasPagar->pluck('id'))->update(['retencoes_processadas' => 1]);

            DB::commit();
            return response()->json("Fechamento concluído com sucesso!", 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json("Erro no fechamento: " . $e->getMessage(), 500);
        }
    }

    private function criarContaAgrupada($valor, $referencia, $vencimento, $fornecedor_id, $categoria_id)
    {
        if(!$fornecedor_id || !$categoria_id) throw new \Exception("Dados de fornecedor/categoria incompletos.");

        ContaPagar::create([
            'fornecedor_id'   => $fornecedor_id,
            'data_vencimento' => $vencimento,
            'data_emissao'    => date('Y-m-d'),
            'valor_integral'  => $valor,
            'valor_original'  => $valor,
            'status'          => false,
            'referencia'      => $referencia,
            'categoria_id'    => $categoria_id,
            'empresa_id'      => $this->empresa_id,
            'filial_id'       => $this->filial_id, // Atrela à filial atual
            'usuario_id'      => session('user_logged')['id']
        ]);
    }

    public function gerarContasRetencao(Request $request)
    {
    }

    public function baixarParcial(Request $request)
    {
        try {
            DB::beginTransaction();

            $conta = ContaPagar::where('id', $request->id)->where('empresa_id', $this->empresa_id)->firstOrFail();
            if ($this->filial_id != null && $conta->filial_id != $this->filial_id) throw new \Exception("Acesso negado.");

            $valorPago = str_replace(',', '.', $request->valor_pago);
            $valorRestante = round($conta->valor_integral - $valorPago, 2);

            if ($valorRestante <= 0) throw new \Exception("Para baixa total, utilize a função de pagamento normal.");

            $residuo = $conta->replicate();
            $residuo->valor_integral = $valorRestante;
            $residuo->valor_original = $valorRestante;
            $residuo->status = false;
            $residuo->data_vencimento = $request->nova_data_vencimento;
            $residuo->conta_id_origem = $conta->id;
            $residuo->referencia .= " (Resíduo de Baixa Parcial)";
            $residuo->save();

            $conta->status = true;
            $conta->valor_pago = $valorPago;
            $conta->data_pagamento = $request->data_pagamento;
            $conta->usuario_baixa_id = session('user_logged')['id'];
            $conta->save();

            $nomeForn = $conta->fornecedor->razao_social ?? 'N/A';
            \App\Models\ItemContaEmpresa::create([
                'conta_id' => $request->conta_bancaria_id,
                'conta_pagar_id' => $conta->id,
                'valor' => $valorPago,
                'tipo' => 'saida',
                'descricao' => "Pgto parcial Forn: {$nomeForn} | NF: {$conta->numero_nota_fiscal}",
                'data_pagamento' => $request->data_pagamento,
                'usuario_id' => session('user_logged')['id'],
                'user_id' => session('user_logged')['id']
            ]);

            DB::commit();
            return response()->json("Baixa parcial realizada! Resíduo gerado para {$request->nova_data_vencimento}", 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function estornarBaixa(Request $request)
    {
        try {
            DB::beginTransaction();

            $conta = ContaPagar::where('id', $request->id)->where('empresa_id', $this->empresa_id)->firstOrFail();
            if ($this->filial_id != null && $conta->filial_id != $this->filial_id) throw new \Exception("Acesso negado.");

            $residuo = ContaPagar::where('conta_id_origem', $conta->id)->where('status', false)->first();
            if ($residuo) $residuo->delete();

            \App\Models\ItemContaEmpresa::where('conta_pagar_id', $conta->id)->delete();

            $conta->status = false;
            $conta->valor_pago = 0;
            $conta->data_pagamento = null;
            $conta->save();

            DB::commit();
            return response()->json("Estorno concluído. O resíduo foi removido e a conta voltou a ficar pendente.", 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json("Erro ao estornar: " . $e->getMessage(), 500);
        }
    }
}

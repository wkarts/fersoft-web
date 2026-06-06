<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaReceber;
use App\Models\CategoriaConta;
use App\Models\Cliente;
use App\Models\ConfigNota;
use App\Models\Cidade;
use Dompdf\Dompdf;
use App\Imports\ProdutoImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Utils\ContaEmpresaUtil;
use App\Models\ContaEmpresa;
use App\Models\ItemContaEmpresa;
use App\Exports\ContasReceberExport;
use NFePHP\DA\NFe\Danfe;


class ContaReceberController extends Controller
{
    protected $empresa_id = null;
    protected $util;

    public function __construct(ContaEmpresaUtil $util){
        $this->util = $util;

        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');

            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index(){
        __saveRedirect($this->empresa_id, '', 'contas_receber');
        $permissaoAcesso = __getLocaisUsarioLogado();
        $local_padrao = __get_local_padrao();
        if($local_padrao == -1){
            $local_padrao = null;
        }
        $contas = ContaReceber::
        where('empresa_id', $this->empresa_id)
            ->whereBetween('data_vencimento', [date("Y-m-d"),
                date('Y-m-d', strtotime('+1 month'))])
            ->orderBy('data_vencimento', 'desc')
            ->where(function($query) use ($permissaoAcesso){
                if($permissaoAcesso != null){
                    foreach ($permissaoAcesso as $value) {
                        if($value == -1){
                            $value = null;
                        }
                        $query->orWhere('filial_id', $value);
                    }
                }
            })
            ->when($local_padrao != NULL, function ($query) use ($local_padrao) {
                $query->where('filial_id', $local_padrao);
            })
            ->get();

        $categorias = CategoriaConta::
        where('empresa_id', $this->empresa_id)
            ->where('tipo', 'receber')
            ->get();

        $somaContas = $this->somaCategoriaDeContas($contas);

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->get();

        $comRetencoes = false;
        $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->get();

        return view('contaReceber/list')
            ->with('contas', $contas)
            ->with('contasEmpresa', $contasEmpresa)
            ->with('graficoJs', true)
            ->with('categorias', $categorias)
            ->with('clientes', $clientes)
            ->with('somaContas', $somaContas)
            ->with('infoDados', "Dos próximos 30 dias")
            ->with('comRetencoes', $comRetencoes)
            ->with('title', 'Contas a Receber')
            ->with('dataInicial', isset($dataInicial) ? $dataInicial : date("d/m/Y"))
            ->with('dataFinal', isset($dataFinal) ? $dataFinal : date('d/m/Y', strtotime('+1 month')));
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
            ->where('tipo', 'receber')
            ->get();
        $temp = [];
        foreach($categorias as $c){
            array_push($temp, $c->nome);
        }

        return $temp;
    }

// =========================================================================
// CORREÇÃO DO FILTRO (Unificado em uma única Query para eliminar duplicados)
// =========================================================================
    public function filtro(Request $request){
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $clienteId = $request->clienteId;
        $status = $request->status;
        $filial_id = $request->filial_id;
        $numero_pedido = $request->numero_pedido;
        $conta_id = $request->conta_id;
        $venda_id_filtro = $request->venda_id_filtro;

        if($request->tipo_pagamento && $request->tipo_pagamento == 'Pix'){
            $request->tipo_pagamento = 'Pagamento Instantâneo (PIX)';
        }

        $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        __saveRedirect($this->empresa_id, $url, 'contas_receber');
        $permissaoAcesso = __getLocaisUsarioLogado();

        // Query única e otimizada
        $query = ContaReceber::select('conta_recebers.*')
            ->leftJoin('item_conta_empresas as ice', 'conta_recebers.id', '=', 'ice.conta_receber_id')
            ->where('conta_recebers.empresa_id', $this->empresa_id);

        // Filtro de permissão de locais (Filiais)
        $query->where(function($q) use ($permissaoAcesso){
            if($permissaoAcesso != null){
                foreach ($permissaoAcesso as $value) {
                    $value = $value == -1 ? null : $value;
                    $q->orWhere('conta_recebers.filial_id', $value);
                }
            }
        });

        // Filtro por Filial selecionada
        if($filial_id){
            $filial_id = $filial_id == -1 ? null : $filial_id;
            $query->where('conta_recebers.filial_id', $filial_id);
        }

        // Filtro inteligente de Cliente (busca na conta OU na venda vinculada)
        if($clienteId != 'null'){
            $query->leftJoin('vendas', 'vendas.id', '=', 'conta_recebers.venda_id')
                ->where(function($q) use ($clienteId) {
                    $q->where('conta_recebers.cliente_id', $clienteId)
                        ->orWhere('vendas.cliente_id', $clienteId);
                });
        }

        if($conta_id && $conta_id != 'todos'){
            $query->where('ice.conta_id', $conta_id);
        }

        if($venda_id_filtro){
            $query->where(function($q) use ($venda_id_filtro){
                $q->where('conta_recebers.venda_id', $venda_id_filtro)
                    ->orWhere('conta_recebers.venda_caixa_id', $venda_id_filtro);
            });
        }

        if($dataInicial && $dataFinal){
            if($request->tipo_filtro_data == 1){
                $query->whereBetween('conta_recebers.data_vencimento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal)]);
            }elseif($request->tipo_filtro_data == 2){
                $query->whereBetween('conta_recebers.created_at', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            }else{
                $d1 = str_replace("/", "-", $dataInicial);
                $d2 = str_replace("/", "-", $dataFinal);
                $query->whereBetween('conta_recebers.data_recebimento', [
                    \Carbon\Carbon::parse($d1)->format('Y-m-d') . " 00:00:00",
                    \Carbon\Carbon::parse($d2)->format('Y-m-d') . " 23:59:59"
                ]);
            }
        }

        if($status != 'todos'){
            if($status == 'pago'){
                $query->where('conta_recebers.status', true);
            } else if($status == 'pendente'){
                $query->where('conta_recebers.status', false);
            }else if($status == 'vencido'){
                $query->where('conta_recebers.status', false)
                    ->whereDate('conta_recebers.data_vencimento', '<=', date('Y-m-d'));
            }
        }

        if($request->tipo_filtro_data == 3){
            $query->where('conta_recebers.status', true);
        }

        if($request->numero_nota_fiscal){
            $query->where('conta_recebers.numero_nota_fiscal', $request->numero_nota_fiscal);
        }

        if($request->categoria != 'todos'){
            $query->where('conta_recebers.categoria_id', $request->categoria);
        }

        if($request->tipo_pagamento){
            $query->where('conta_recebers.tipo_pagamento', $request->tipo_pagamento);
        }

        if($numero_pedido){
            // Garante que se já não foi feito o join de vendas acima, faça agora de forma segura
            if($clienteId == 'null') {
                $query->join('vendas', 'vendas.id', '=', 'conta_recebers.venda_id');
            }
            $query->where('vendas.id', $numero_pedido);
        }

        // Executa a busca trazendo os dados agrupados e sem repetições
        $contas = $query->groupBy('conta_recebers.id')
            ->orderBy('conta_recebers.data_vencimento', 'desc')
            ->get();

        $somaContas = $this->somaCategoriaDeContas($contas);
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'receber')->get();
        $clientes = Cliente::where('empresa_id', $this->empresa_id)->where('inativo', false)->get();
        $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->get();

        return view('contaReceber/list')
            ->with('contas', $contas)
            ->with('comRetencoes', false)
            ->with('clienteId', $clienteId)
            ->with('clientes', $clientes)
            ->with('categorias', $categorias)
            ->with('contasEmpresa', $contasEmpresa)
            ->with('conta_id', $conta_id)
            ->with('venda_id_filtro', $venda_id_filtro)
            ->with('tipo_filtro_data', $request->tipo_filtro_data)
            ->with('categoria', $request->categoria)
            ->with('tipo_pagamento', $request->tipo_pagamento)
            ->with('dataInicial', $dataInicial ?? date("d/m/Y"))
            ->with('dataFinal', $dataFinal ?? date('d/m/Y', strtotime('+1 month')))
            ->with('status', $status)
            ->with('filial_id', $filial_id)
            ->with('numero_pedido', $numero_pedido)
            ->with('somaContas', $somaContas)
            ->with('graficoJs', true)
            ->with('numero_nota_fiscal', $request->numero_nota_fiscal)
            ->with('paraImprimir', true)
            ->with('infoDados', "Contas filtradas")
            ->with('title', 'Filtro Contas a Receber');
    }

    private function validaInArray($ct, $contas){
        foreach($contas as $c){
            if($c->id == $ct->id) return true;
        }
        return false;
    }

    public function salvarParcela(Request $request){
        $parcela = $request->parcela;

        $valorParcela = str_replace(".", "", $parcela['valor_parcela']);
        $valorParcela = str_replace(",", ".", $valorParcela);

        $categoria = CategoriaConta::
        where('empresa_id', $this->empresa_id)
            ->where('tipo', 'receber')
            ->first();

        $result = ContaReceber::create([
            'venda_id' => $parcela['compra_id'],
            'data_vencimento' => $this->parseDate($parcela['vencimento']),
            'data_recebimento' => $this->parseDate($parcela['vencimento']),
            'valor_integral' => $valorParcela,
            'valor_recebido' => 0,
            'juros' => $request->juros ? str_replace(",", ".", $request->juros) : 0,
            'multa' => $request->multa ? str_replace(",", ".", $request->multa) : 0,
            'desconto' => $request->desconto ? str_replace(",", ".", $request->desconto) : 0,
            'status' => false,
            'referencia' => $parcela['referencia'],
            'categoria_id' => $categoria->id,
            'empresa_id' => $this->empresa_id
        ]);
        echo json_encode($parcela);
    }

    // =========================================================================
// CORREÇÃO DO SALVAR (Gravando nas duas colunas: numero_nota_fiscal e nf_numero)
// =========================================================================
    public function save(Request $request){
        if(strlen($request->recorrencia) == 5){
            $valid = $this->validaRecorrencia($request->recorrencia);
            if(!$valid){
                session()->flash('mensagem_erro', 'Valor recorrente inválido!');
                return redirect('/contasReceber/new');
            }
        }

        $clienteId = $request->cliente_id != "" ? $request->cliente_id : NULL;
        $request->merge(['filial_id' => $request->filial_id == -1 ? null : $request->filial_id]);
        $this->_validate($request);

        $parcelas = json_decode($request->parcelas) ?? [];
        $dataPagamento = $request->data_pagamento ? $this->parseDate($request->data_pagamento) : $this->parseDate($request->vencimento);
        $numNota = $request->numero_nota_fiscal ?? 0;

        $conta = ContaReceber::create([
            'venda_id' => null,
            'data_vencimento' => $this->parseDate($request->vencimento),
            'data_recebimento' => $request->status ? $dataPagamento : $this->parseDate($request->vencimento),
            'valor_integral' => str_replace(",", ".", $request->valor),
            'valor_recebido' => $request->status ? str_replace(",", ".", $request->valor_recebido) : 0,
            'status' => $request->status ? true : false,
            'referencia' => $request->referencia . (sizeof($parcelas) > 0 ? " - parcela 1" . "/".(sizeof($parcelas)+1) : ""),
            'tipo_pagamento' => $request->tipo_pagamento ?? '',
            'observacao' => $request->observacao ?? '',
            'categoria_id' => $request->categoria_id,
            'empresa_id' => $this->empresa_id,
            'cliente_id' => $clienteId,
            'filial_id' => $request->filial_id,
            'numero_nota_fiscal' => $numNota,
            'nf_numero' => $numNota, // <--- SALVANDO NA SEGUNDA COLUNA SOLICITADA
            'usuario_id' => session('user_logged')['id'],
            'usuario_baixa_id' => $request->status ? session('user_logged')['id'] : null
        ]);

        if($conta->status && $request->conta_id){
            $item = ItemContaEmpresa::create([
                'conta_id' => $request->conta_id,
                'descricao' => "Recebimento: " . ($conta->cliente->razao_social ?? 'Cliente') . " | Ref: " . $conta->referencia,
                'tipo_pagamento' => $conta->tipo_pagamento ?? 'Dinheiro',
                'valor' => $conta->valor_recebido,
                'tipo' => 'entrada',
                'data_pagamento' => $dataPagamento,
                'categoria_id' => $conta->categoria_id,
                'user_id' => session('user_logged')['id'],
                'origem' => 'Conta Receber',
                'conta_receber_id' => $conta->id
            ]);
            if (isset($this->util)) { $this->util->atualizaSaldo($item); }
        }

        if(sizeof($parcelas) > 0){
            foreach($parcelas as $key => $p){
                $contaP = ContaReceber::create([
                    'venda_id' => null,
                    'data_vencimento' => $p->vencimento,
                    'data_recebimento' => $request->status ? $dataPagamento : $p->vencimento,
                    'valor_integral' => str_replace(",", ".", $p->valor),
                    'valor_recebido' => $request->status ? str_replace(",", ".", $p->valor) : 0,
                    'status' => $request->status ? true : false,
                    'tipo_pagamento' => $request->tipo_pagamento ?? '',
                    'cliente_id' => $clienteId,
                    'observacao' => $request->observacao ?? '',
                    'numero_nota_fiscal' => $numNota,
                    'nf_numero' => $numNota, // <--- ADICIONADO PARA AS PARCELAS TAMBÉM
                    'referencia' => $request->referencia . " - parcela " .($key+2) . "/".(sizeof($parcelas)+1),
                    'categoria_id' => $request->categoria_id,
                    'empresa_id' => $this->empresa_id,
                    'usuario_id' => session('user_logged')['id'],
                    'usuario_edit_id' => NULL,
                    'usuario_baixa_id' => $request->status ? session('user_logged')['id'] : NULL
                ]);

                if($contaP->status && $request->conta_id){
                    $itemP = ItemContaEmpresa::create([
                        'conta_id' => $request->conta_id,
                        'descricao' => "Recebimento: " . ($contaP->cliente->razao_social ?? 'Cliente') . " | Ref: " . $contaP->referencia,
                        'tipo_pagamento' => $contaP->tipo_pagamento ?? 'Dinheiro',
                        'valor' => $contaP->valor_recebido,
                        'tipo' => 'entrada',
                        'data_pagamento' => $dataPagamento,
                        'categoria_id' => $contaP->categoria_id,
                        'user_id' => session('user_logged')['id'],
                        'origem' => 'Conta Receber',
                        'conta_receber_id' => $contaP->id
                    ]);
                    if (isset($this->util)) { $this->util->atualizaSaldo($itemP); }
                }
            }
        }

        session()->flash('mensagem_sucesso', 'Registro inserido!');
        return redirect('/contasReceber');
    }

    public function update(Request $request){
        $this->_validate($request);
        $conta = ContaReceber::
        where('id', $request->id)
            ->first();

        $request->merge([
            'filial_id' => $request->filial_id == -1 ? null : $request->filial_id
        ]);

        $conta->data_vencimento = $this->parseDate($request->vencimento);
        $conta->referencia = $request->referencia;
        $conta->tipo_pagamento = $request->tipo_pagamento ?? '';
        $conta->observacao = $request->observacao ?? '';
        $conta->valor_integral = str_replace(",", ".", $request->valor);
        $conta->categoria_id = $request->categoria_id;
        $conta->filial_id = $request->filial_id;
        $conta->numero_nota_fiscal = $request->numero_nota_fiscal ?? 0;
        $conta->nf_numero          = $request->numero_nota_fiscal ?? 0;
        $conta->usuario_edit_id = session('user_logged')['id']; // ID de quem está editando agora
        $conta->save();
        if(isset($request->cliente_id)){
            $conta->cliente_id = $request->cliente_id;
        }

        $result = $conta->save();

        if($result){
            session()->flash('mensagem_sucesso', 'Registro atualizado!');
        }else{
            session()->flash('mensagem_erro', 'Ocorreu um erro!');
        }

        $rota = __getRedirect($this->empresa_id, 'contas_receber');
        if($rota != ""){
            return redirect($rota);
        }

        return redirect('/contasReceber');
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
            'cliente_id' => $request->id == 0 ? 'required' : '',
            'referencia' => 'required',
            'valor' => 'required',
            'observacao' => 'nullable|string',
            'categoria_id' => 'required',
            'vencimento' => 'required',
        ];

        $messages = [
            'cliente_id.required' => 'O campo cliente é obrigatório.',
            'referencia.required' => 'O campo referencia é obrigatório.',
            'valor.required' => 'O campo valor é obrigatório.',
            'categoria_id.required' => 'O campo categoria é obrigatório.',
            'vencimento.required' => 'O campo vencimento é obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function new(){
        $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->get();

        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)
            ->where('tipo', 'receber')
            ->orderBy('nome')
            ->get();

        if(sizeof($categorias) == 0){
            session()->flash('mensagem_alerta', 'Cadastre uma categoria com o tipo receber!');
            return redirect('/categoriasConta');
        }

        $clientes = Cliente::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->get();

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        return view('contaReceber/register')
            ->with('categorias', $categorias)
            ->with('clientes', $clientes)
            ->with('config', $config)
            ->with('contasEmpresa', $contasEmpresa)
            ->with('title', 'Cadastrar Contas a Receber');
    }

    public function edit($id){
        $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->get();

        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)
            ->where('tipo', 'receber')
            ->orderBy('nome')
            ->get();

        $conta = ContaReceber::where('id', $id)->first();

        $clientes = Cliente::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->get();

        return view('contaReceber/register')
            ->with('conta', $conta)
            ->with('categorias', $categorias)
            ->with('clientes', $clientes)
            ->with('contasEmpresa', $contasEmpresa)
            ->with('title', 'Editar Conta a Receber');
    }

    public function estorno($id){
        $conta = ContaReceber::findOrFail($id);
        if(valida_objeto($conta)){
            return view('contaReceber/estorno')
                ->with('conta', $conta)
                ->with('title', 'Estornar Conta');
        }else{
            return redirect('/403');
        }
    }

    public function estornoConta(Request $request){
        $conta = ContaReceber::findOrFail($request->id);

        try {
            // 1. Remove o lançamento do Extrato Bancário (ItemContaEmpresa)
            // Só vai funcionar se você tiver a coluna conta_receber_id no banco
            $itemBancario = ItemContaEmpresa::where('conta_receber_id', $conta->id)->first();

            if ($itemBancario) {
                // Se o seu sistema usa o helper de atualizar saldo, chamamos ele antes de deletar
                if (isset($this->util)) {
                    // Passamos o valor negativo para subtrair do saldo o que foi deletado
                    $this->util->atualizaSaldo($itemBancario, true);
                }
                $itemBancario->delete();
            }

            // 2. Volta a conta para pendente
            $conta->status = false;
            $conta->valor_recebido = 0;
            $conta->estorno = true;
            $conta->motivo_estorno = $request->motivo;
            $conta->save();

            session()->flash('mensagem_sucesso', 'Conta estornada e lançamento bancário removido!');

            $rota = __getRedirect($this->empresa_id, 'contas_receber');
            return redirect($rota != "" ? $rota : '/contasReceber');

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao estornar: ' . $e->getMessage());
            return redirect('/contasReceber');
        }
    }

    // MÉTODO RECEBER CORRIGIDO (Removido log e duplicidade)
    // 1. Abre a tela de recebimento (Chamada pelo botão da lista - GET)
    public function receber($id){
        $conta = ContaReceber::find($id);

        $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->get();

        if(valida_objeto($conta)){
            return view('contaReceber/receber')
                ->with('conta', $conta)
                ->with('contasEmpresa', $contasEmpresa)
                ->with('title', 'Receber Conta');
        } else {
            return redirect('/403');
        }
    }

    // 2. Processa a baixa da conta (Chamada pelo formulário - POST)
    // Renomeada para receberConta para corrigir o erro de "Method does not exist"
    public function receberConta(Request $request){
        $conta = ContaReceber::find($request->id);

        // Converte os valores para float (evita erro no number_format)
        $valor_recebido = (float)str_replace(',', '.', str_replace('.', '', $request->valor_recebido));
        $juros = (float)str_replace(',', '.', str_replace('.', '', $request->juros));
        $multa = (float)str_replace(',', '.', str_replace('.', '', $request->multa));
        $desconto = (float)str_replace(',', '.', str_replace('.', '', $request->desconto));

        $conta->status = true;
        $conta->valor_recebido = $valor_recebido;
        $conta->juros = $juros;
        $conta->multa = $multa;
        $conta->desconto = $desconto;
        $conta->usuario_baixa_id = session('user_logged')['id']; // Grava quem fez a baixa
        $conta->save();

        $dataPagamento = $request->data_pagamento ? $this->parseDate($request->data_pagamento) : date('Y-m-d');
        $conta->data_recebimento = $dataPagamento;
        $conta->tipo_pagamento = $request->tipo_pagamento;
        $conta->save();

        // Lançamento no Extrato (Conta Empresa)
        if (isset($request->conta_id)) {
            $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);

            $itemContaEmpresa = ItemContaEmpresa::create([
                'conta_id'       => $request->conta_id,
                'descricao'      => "Recebimento: " . ($conta->cliente->razao_social ?? 'Cliente') . " | Ref: " . $conta->referencia,
                'tipo_pagamento' => $tipoPagamento,
                'valor'          => $valor_recebido,
                'tipo'           => 'entrada',
                'data_pagamento' => $dataPagamento,
                'categoria_id'   => $conta->categoria_id,
                'user_id'        => session('user_logged')['id'],
                'origem'         => 'Conta Receber',
                'conta_receber_id' => $conta->id,
            ]);

            if($this->util){
                $this->util->atualizaSaldo($itemContaEmpresa);
            }
        }

        session()->flash('mensagem_sucesso', 'Conta recebida com sucesso!');
        return redirect('/contasReceber');
    }

    public function delete($id){
        $conta = ContaReceber::where('id', $id)->first();
        if($conta->venda_id != null){
            session()->flash('mensagem_erro', 'Esta conta esta vinculada a uma venda!');
            return redirect('/contasReceber');
        }

        if($conta->boleto){
            session()->flash('mensagem_erro', 'Conta já possui boleto emitido!');
            return redirect('/contasReceber');
        }

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

    private function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    private function parseRecorrencia($rec){
        $temp = explode("/", $rec);
        $rec = "01/".$temp[0]."/20".$temp[1];
        return date('Y-m', strtotime(str_replace("/", "-", $rec)));
    }

    public function relatorio(Request $request){
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $cliente = $request->cliente;
        $status = $request->status;
        $filial_id = $request->filial_id;

        $permissaoAcesso = __getLocaisUsarioLogado();

        $contas = ContaReceber::
        select('conta_recebers.*')
            ->where(function($query) use ($permissaoAcesso){
                if($permissaoAcesso != null){
                    foreach ($permissaoAcesso as $value) {
                        if($value == -1){
                            $value = null;
                        }
                        $query->orWhere('conta_recebers.filial_id', $value);
                    }
                }
            })
            ->when($filial_id, function ($query) use ($filial_id) {
                $filial_id = $filial_id == -1 ? null : $filial_id;
                return $query->where('conta_recebers.filial_id', $filial_id);
            });

        if($cliente != 'null'){
            $contas->join('clientes', 'clientes.id' , '=', 'conta_recebers.cliente_id');
            $contas->where('conta_recebers.cliente_id', $cliente);
        }

        if($dataInicial && $dataFinal){
            if($request->tipo_filtro_data == 1){
                $contas->whereBetween('conta_recebers.data_vencimento',
                    [
                        $this->parseDate($dataInicial),
                        $this->parseDate($dataFinal)
                    ]
                );
            }elseif($request->tipo_filtro_data == 2){
                $contas->whereBetween('conta_recebers.created_at',
                    [
                        $this->parseDate($dataInicial),
                        $this->parseDate($dataFinal, true)
                    ]
                );
            }else{
                $d1 = str_replace("/", "-", $dataInicial);
                $d2 = str_replace("/", "-", $dataFinal);
                $contas->whereBetween('conta_recebers.data_recebimento',
                    [
                        \Carbon\Carbon::parse($d1)->format('Y-m-d'),
                        \Carbon\Carbon::parse($d2)->format('Y-m-d')
                    ]
                );
            }
        }

        if($status != 'todos'){
            if($status == 'pago'){
                $contas->where('status', true);
            } else if($status == 'pendente'){
                $contas->where('status', false);
            }else if($status == 'vencido'){
                $contas->where('status', false)
                    ->whereDate('data_vencimento', '<=', date('Y-m-d'));
            }
        }

        if($request->tipo_filtro_data == 3) $contas->where('status', true);
        $contas->where('conta_recebers.empresa_id', $this->empresa_id);
        $contas->orderBy('conta_recebers.data_vencimento', 'asc');

        if($request->categoria != 'todos') $contas->where('categoria_id', $request->categoria);
        if($request->numero_nota_fiscal) $contas->where('conta_recebers.numero_nota_fiscal', $request->numero_nota_fiscal);

        $contas = $contas->get();
        $p = view('relatorios/relatorio_contas_receber')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('contas', $contas);

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);
        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatorio de Contas a Receber.pdf", array("Attachment" => false));
    }

    public function receberSomente(Request $request){
        $conta = ContaReceber::find($request->id);
        $conta->status = true;
        $conta->valor_recebido = $request->valor;
        $conta->data_recebimento = date("Y-m-d") . " " . date('H:i:s');
        $conta->tipo_pagamento = $request->tipo_pagamento;

        if($conta->save()){
            session()->flash('mensagem_sucesso', 'Conta recebida!');
        }else{
            session()->flash('mensagem_erro', 'Erro!');
        }
        return redirect('/contasReceber');
    }

    public function receberComDivergencia(Request $request){
        $conta = ContaReceber::find($request->id);
        $valor = __replace($request->valor);

        $res = ContaReceber::create([
            'venda_id' => $conta->venda_id,
            'venda_caixa_id' => $conta->venda_caixa_id,
            'cliente_id' => $conta->cliente_id,
            'data_vencimento' => $conta->data_vencimento,
            'data_recebimento' => $conta->data_recebimento,
            'valor_integral' => $conta->valor_integral - $valor,
            'valor_recebido' => 0,
            'status' => false,
            'referencia' => $conta->referencia,
            'categoria_id' => $conta->categoria_id,
            'empresa_id' => $this->empresa_id,
        ]);

        $conta->status = true;
        $conta->valor_recebido = $request->valor;
        $conta->valor_integral = $request->valor;
        $conta->tipo_pagamento = $request->tipo_pagamento;
        $conta->data_recebimento = date("Y-m-d") . " " . date('H:i:s');

        if($conta->save()){
            session()->flash('mensagem_sucesso', 'Conta recebida parcialmente, uma nova foi criada com ID: ' . $res->id);
        }else{
            session()->flash('mensagem_erro', 'Erro!');
        }
        return redirect('/contasReceber');
    }

    public function receberComOutros(Request $request){
        $conta = ContaReceber::find($request->id);
        $valor = $request->valor;
        $temp = "";
        $somaParaTroco = $conta->valor_integral;
        try{
            if(isset($request->contas)){
                $contasMais = explode(",", $request->contas);
                foreach($contasMais as $key => $c){
                    $ctemp = ContaReceber::find($c);
                    $ctemp->status = true;
                    $ctemp->valor_recebido = $ctemp->valor_integral;
                    $ctemp->data_recebimento = date("Y-m-d") . " " . date('H:i:s');
                    $ctemp->save();
                    $temp .= " $c" . (sizeof($contasMais)-1 > $key ? "," : "");
                    $somaParaTroco += $ctemp->valor_integral;
                }
            }

            $conta->status = true;
            $conta->valor_recebido = $conta->valor_integral;
            $conta->data_recebimento = date("Y-m-d") . " " . date('H:i:s');
            $conta->save();

            $troco = $valor - $somaParaTroco;
            $msg = "Sucesso conta(s) com ID: $conta->id, " . $temp . " recebida(s)";

            if($troco > 0) $msg .= " , valor de troco: R$ " . number_format($troco, 2);
            session()->flash('mensagem_sucesso', $msg);
            return redirect('/contasReceber');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Ocorreu um erro ao receber: ' . $e->getMessage());
        }
    }

    public function detalhesVenda($contaId){
        $conta = ContaReceber::find($contaId);
        if(valida_objeto($conta)){
            if($conta->venda_id != null){
                return redirect('/vendas/detalhar/'.$conta->venda_id);
            }else{
                return redirect('/nfce/detalhes/'.$conta->venda_caixa_id);
            }
        }else{
            return redirect('/403');
        }
    }

    public function pendentes(){
        $clientes = Cliente::where('empresa_id', $this->empresa_id)->where('inativo', false)->get();
        $title = 'Contas pendentes';
        return view('contaReceber/pendentes', compact('clientes', 'title'));
    }

    public function filtroPendente(Request $request){
        if(!$request->clienteId){
            session()->flash('mensagem_erro', 'Informe o cliente');
            return redirect()->back();
        }

        $clientes = Cliente::where('empresa_id', $this->empresa_id)->where('inativo', false)->get();
        $title = 'Contas pendentes';
        $dataInicial = $request->data_inicial;
        $DataFinal = $request->data_final;
        $clienteId = $request->clienteId;
        $tipo_pagamento = $request->tipo_pagamento;

        $contas = ContaReceber::where('empresa_id', $this->empresa_id)
            ->where('cliente_id', $clienteId)
            ->orderBy('data_vencimento', 'desc')
            ->where('status', 0);

        if($tipo_pagamento) $contas->where('tipo_pagamento', $request->tipo_pagamento);
        $contas = $contas->get();

        return view('contaReceber/pendentes', compact('clientes', 'title', 'contas', 'dataInicial', 'DataFinal', 'tipo_pagamento', 'clienteId'));
    }

    public function receberMultiplos($ids){
        $temp = explode(",", $ids);
        $contas = [];
        $somaTotal = 0;


        foreach($temp as $i){
            $conta = ContaReceber::find($i);
            if($conta && $conta->empresa_id == $this->empresa_id){
                $conta->status = 1;
                $conta->usuario_baixa_id = session('user_logged')['id']; // Grava quem baixou no lote
                $conta->save();
            }
            foreach($temp as $i){
                $conta = ContaReceber::find($i);
                if($conta->empresa_id != $this->empresa_id){
                    session()->flash('mensagem_erro', "Erro inesperado!");
                    return redirect()->back();
                }
                $somaTotal += $conta->valor_integral;
                array_push($contas, $conta);
            }

            if(sizeof($contas) <= 1){
                session()->flash('mensagem_erro', "É necessário selecionar mais de uma conta!");
                return redirect()->back();
            }
            $title = 'Receber contas';
            $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)->where('status', 1)->get();

            return view('contaReceber/receber_multi', compact('somaTotal', 'title', 'contas', 'ids', 'contasEmpresa'));
        }
    }
    public function receberMulti(Request $request){
        $dtReceb = \Carbon\Carbon::parse(str_replace("/", "-", $request->data_pagamento))->format('Y-m-d');
        $dtReceb .= " " . date("H:i:s");
        $temp = explode(",", $request->ids);
        $valorRecebido = __replace($request->valor);
        $tipo_pagamento = $request->tipo_pagamento;

        $somaPagamento = 0;
        $diferenca = 0;
        foreach($temp as $i){
            $conta = ContaReceber::find($i);
            if($conta && $conta->empresa_id == $this->empresa_id){
                $somaPagamento += $conta->valor_integral;
                $conta->status = 1;
                $conta->valor_recebido = $conta->valor_integral;
                $conta->tipo_pagamento = $tipo_pagamento;
                $conta->data_recebimento = $dtReceb;
                $conta->juros = $request->juros ? __replace($request->juros) : 0;
                $conta->multa = $request->multa ? __replace($request->multa) : 0;
                $conta->desconto = $request->desconto ? __replace($request->desconto) : 0;
                $conta->save();

                if(isset($request->conta_id)){
                    $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);
                    $data = [
                        'conta_id'       => $request->conta_id,
                        'descricao'      => "Recebimento: " . ($conta->cliente->razao_social ?? 'Cliente') . " | Ref: " . $conta->referencia,
                        'tipo_pagamento' => $tipoPagamento,
                        'valor'          => $conta->valor_integral,
                        'tipo'           => 'entrada',
                        'categoria_id'   => $conta->categoria_id,
                        'user_id'        => session('user_logged')['id'],
                        'origem'         => 'Conta Receber',
                        'conta_receber_id' => $conta->id,
                        'data_pagamento' => $dtReceb
                    ];
                    $itemContaEmpresa = ItemContaEmpresa::create($data);
                    $this->util->atualizaSaldo($itemContaEmpresa);
                }
            }
        }

        session()->flash('mensagem_sucesso', "Contas recebidas!");
        return redirect('/contasReceber');
    }

    public function importacao(){
        if (!extension_loaded('zip')) {
            session()->flash('mensagem_erro', "Por favor instale/habilite o PHP zip para importar");
            return redirect()->back();
        }
        return view('contaReceber/importacao')->with('title', 'Importação de conta receber');
    }

    public function downloadModelo(){
        try{
            return response()->download(public_path('files/') . 'import_conta_receber_csv_template.xlsx');
        }catch(\Exception $e){
            echo $e->getMessage();
        }
    }

    public function importacaoStore(Request $request){
        if ($request->hasFile('file')) {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $filial_id = $request->filial_id;
            $rows = Excel::toArray(new ProdutoImport, $request->file);
            $retornoErro = $this->validaArquivo($rows);

            if($retornoErro == ""){
                $cont = 0;
                foreach($rows as $row){
                    foreach($row as $key => $r){
                        if($key > 0){
                            $objeto = $this->preparaObjeto($r, $filial_id);
                            if($objeto != null){
                                ContaReceber::create($objeto);
                                $cont++;
                            }
                        }
                    }
                }
                session()->flash('mensagem_sucesso', "Contas inseridas: $cont");
                return redirect('/contasReceber');
            }else{
                session()->flash('mensagem_erro', $retornoErro);
                return redirect()->back();
            }
        }
    }

    private function preparaObjeto($r, $filial_id){
        if(trim($r[1]) == "") return null;
        $documento = trim(preg_replace('/[^0-9]/', '', $r[1]));
        $cliente = Cliente::where('cpf_cnpj', $documento)->first();
        if($cliente == null){
            $mask = strlen($documento) == 14 ? "##.###.###/####-##" : "###.###.###-##";
            $documento = $this->__mask($documento, $mask);
            $cliente = Cliente::where('cpf_cnpj', $documento)->first();
        }
        if($cliente == null) $cliente = $this->cadastrarCliente($r);

        $v = \Carbon\Carbon::parse(str_replace("/", "-", $r[12]))->format('Y-m-d') . " " . date('H:i:s');
        return [
            'venda_id' => null,
            'data_vencimento' => $v,
            'data_recebimento' => $v,
            'valor_integral' => __replace($r[11]),
            'valor_recebido' => $r[14] != '' ? __replace($r[11]) : 0,
            'referencia' => $r[13] != '' ? $r[13] : '',
            'categoria_id' => CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'receber')->first()->id,
            'status' => $r[14] != '' ? 1 : 0,
            'empresa_id' => $this->empresa_id,
            'cliente_id' => $cliente->id,
            'juros' => 0, 'multa' => 0, 'observacao' => '', 'tipo_pagamento' => '',
            'filial_id' => $filial_id == -1 ? null : $filial_id,
            'entrada' => 0
        ];
    }

    private function cadastrarCliente($r){
        $cidade = Cidade::where('nome', $r[7])->where('uf', $r[8])->first();
        return Cliente::create([
            'razao_social' => $r[0],
            'cpf_cnpj' => trim(preg_replace('/[^0-9]/', '', $r[1])),
            'ie_rg' => $r[2] != '' ? $r[2] : '',
            'rua' => $r[4], 'numero' => $r[5], 'bairro' => $r[6], 'cep' => $r[9],
            'email' => $r[10] != '' ? $r[10] : '',
            'cidade_id' => $cidade ? $cidade->id : 1,
            'consumidor_final' => 1, 'limite_venda' => 0,
            'contribuinte' => $r[2] != '' ? 1 : 0,
            'empresa_id' => $this->empresa_id
        ]);
    }

    private function validaArquivo($rows){
        $cont = 1; $msgErro = "";
        foreach($rows as $row){
            foreach($row as $key => $r){
                if($key > 0){
                    if(strlen($r[0]) == 0) $msgErro .= "Coluna nome em branco na linha: $cont | ";
                    if(strlen($r[4]) == 0) $msgErro .= "Coluna rua em branco na linha: $cont | ";
                    if(strlen($r[5]) == 0) $msgErro .= "Coluna numero em branco na linha: $cont | ";
                    if(strlen($r[11]) == 0) $msgErro .= "Coluna valor em branco na linha: $cont | ";
                    if(strlen($r[12]) == 0) $msgErro .= "Coluna vencimento em branco na linha: $cont | ";
                    if($msgErro != "") return $msgErro;
                    $cont++;
                }
            }
        }
        return $msgErro;
    }

    private function __mask($val, $mask){
        $maskared = ''; $k = 0;
        for ($i = 0; $i <= strlen($mask) - 1; ++$i) {
            if ($mask[$i] == '#') {
                if (isset($val[$k])) $maskared .= $val[$k++];
            } else {
                if (isset($mask[$i])) $maskared .= $mask[$i];
            }
        }
        return $maskared;
    }

    public function exportExcel(Request $request)
    {
        // Captura os filtros
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $clienteId = $request->clienteId;
        $status = $request->status;
        $categoria = $request->categoria;
        $tipo_pagamento = $request->tipo_pagamento;
        $tipo_filtro_data = $request->tipo_filtro_data ?? 1;
        $filial_id = $request->filial_id;
        $conta_id = $request->conta_id;
        $venda_id_filtro = $request->venda_id_filtro;

        $query = \DB::table('conta_recebers as cr')
            ->join('categoria_contas as cc', 'cr.categoria_id', '=', 'cc.id')
            ->leftJoin('clientes as cli', 'cr.cliente_id', '=', 'cli.id')
            ->leftJoin('filials as fil', 'cr.filial_id', '=', 'fil.id')
            ->leftJoin('item_conta_empresas as ice', 'cr.id', '=', 'ice.conta_receber_id')
            ->leftJoin('conta_empresas as ce', 'ice.conta_id', '=', 'ce.id')
            ->select(
                'cr.id', // Crucial para não dar erro de undefined
                'cr.numero_nota_fiscal',
                'cr.nf_data_emissao as data_emissao_nfe', // Nome padronizado para a view
                'cr.data_vencimento',
                'cr.data_recebimento',
                'cli.razao_social as cliente_razao',
                'cli.cpf_cnpj as cliente_cpf_cnpj', // Adicionado documento
                'cr.referencia',
                'cr.observacao', // Adicionado observação
                'cc.nome as categoria_nome',
                'cr.tipo_pagamento',
                'ce.nome as conta_empresa',
                'cr.valor_integral', // Adicionado valor original
                'cr.valor_recebido',
                'cr.juros',
                'cr.multa',
                'cr.desconto',
                'cr.status', // Adicionado para lógica na view
                \DB::raw("COALESCE(fil.descricao, 'Matriz') as filial_nome")
            )
            ->where('cr.empresa_id', $this->empresa_id);

        // Filtros de Data
        if($dataInicial && $dataFinal){
            $d1 = $this->parseDate($dataInicial);
            $d2 = $this->parseDate($dataFinal, ($tipo_filtro_data == 2));

            if($tipo_filtro_data == 1) $query->whereBetween('cr.data_vencimento', [$d1, $d2]);
            elseif($tipo_filtro_data == 2) $query->whereBetween('cr.created_at', [$d1, $d2]);
            elseif($tipo_filtro_data == 3) $query->whereBetween('cr.data_recebimento', [$d1 . " 00:00:00", $d2 . " 23:59:59"]);
        }

        if($clienteId && $clienteId != 'null') $query->where('cr.cliente_id', $clienteId);

        if($status && $status != 'todos'){
            if($status == 'pago') $query->where('cr.status', true);
            else if($status == 'pendente') $query->where('cr.status', false);
            else if($status == 'vencido') $query->where('cr.status', false)->whereDate('cr.data_vencimento', '<=', date('Y-m-d'));
        }

        if($categoria && $categoria != 'todos') $query->where('cr.categoria_id', $categoria);
        if($tipo_pagamento) $query->where('cr.tipo_pagamento', $tipo_pagamento);
        if($filial_id && $filial_id != -1) $query->where('cr.filial_id', $filial_id);

        if($conta_id && $conta_id != 'todos') $query->where('ice.conta_id', $conta_id);

        if($venda_id_filtro){
            $query->where(function($q) use ($venda_id_filtro){
                $q->where('cr.venda_id', $venda_id_filtro)
                    ->orWhere('cr.venda_caixa_id', $venda_id_filtro);
            });
        }

        $contas = $query->orderBy('cr.data_vencimento', 'asc')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ContasReceberExport($contas), 'Relatorio_Contas_Receber.xlsx');
    }

    // =========================================================================
// CORREÇÃO DO SINCRONISMO (Adicionado nf_numero, numero_nota_fiscal e nf_chave)
// =========================================================================
    public function syncNotaFiscal()
    {
        $updatedCount = 0;
        $empresa_id = $this->empresa_id;

        // 1. Busca TODAS as contas da empresa para passar o pente fino e corrigir tudo
        $contas = \App\Models\ContaReceber::where('empresa_id', $empresa_id)->get();

        foreach ($contas as $conta) {
            $alterou = false;
            $venda = null;

            // ESTRATÉGIA A: Se a conta já tem o ID da venda, usamos ele direto
            if (!empty($conta->venda_id)) {
                $venda = \App\Models\Venda::where('empresa_id', $empresa_id)
                    ->where('id', $conta->venda_id)
                    ->first();
            }

            // ESTRATÉGIA B: Se não tem ID, mas tem texto na referência (Ex: "Venda 998" ou "da Venda 1002")
            if (!$venda && !empty($conta->referencia)) {
                // Extrai qualquer número que venha logo após a palavra "Venda" ou "venda"
                if (preg_match('/[Vv]enda\s*(\d+)/', $conta->referencia, $matches)) {
                    $vendaIdExtraido = $matches[1];

                    $venda = \App\Models\Venda::where('empresa_id', $empresa_id)
                        ->where('id', $vendaIdExtraido)
                        ->first();
                }
            }

            // ESTRATÉGIA C: Se ainda não achou, tenta pelo número que estiver em "numero_nota_fiscal" ou "nf_numero"
            if (!$venda) {
                $numeroNota = $conta->numero_nota_fiscal > 0 ? $conta->numero_nota_fiscal : $conta->nf_numero;
                if ($numeroNota > 0) {
                    $venda = \App\Models\Venda::where('empresa_id', $empresa_id)
                        ->where('NfNumero', $numeroNota)
                        ->first();
                }
            }

            // Se encontrou a venda por qualquer uma das estratégias, atualiza os dados fiscais
            if ($venda) {
                $conta->venda_id = $venda->id;

                // Se a venda tem nota gerada (NfNumero > 0), grava nas duas colunas e pega a chave
                if ($venda->NfNumero > 0) {
                    $conta->numero_nota_fiscal = $venda->NfNumero;
                    $conta->nf_numero          = $venda->NfNumero;
                    $conta->nf_chave           = $venda->chave ?? null;

                    // Atualiza a referência para o padrão bonito de nota sincronizada
                    $conta->referencia = "VENDA DE MERCADORIAS E NFe - " . $venda->NfNumero;
                } else {
                    // Se a venda existe mas ainda não tem nota emitida (ex: é só um pedido/orçamento)
                    // Mantemos o número em 0 ou limpo para não inventar dados fiscais
                    $conta->numero_nota_fiscal = 0;
                    $conta->nf_numero          = 0;
                    $conta->nf_chave           = null;
                }

                if (!$conta->nf_data_emissao) {
                    $conta->nf_data_emissao = $venda->data_emissao ?? $venda->created_at;
                }

                $alterou = true;
            }

            // SE NÃO FOR VENDA, MANTÉM A VERIFICAÇÃO DO CT-E LOGO ABAIXO
            if (!$venda) {
                $numeroNota = $conta->numero_nota_fiscal > 0 ? $conta->numero_nota_fiscal : $conta->nf_numero;
                if ($numeroNota > 0) {
                    $cte = \App\Models\Cte::where('empresa_id', $empresa_id)
                        ->where('cte_numero', $numeroNota)
                        ->first();

                    if ($cte) {
                        $conta->cte_id             = $cte->id;
                        $conta->numero_nota_fiscal = $numeroNota;
                        $conta->nf_numero          = $numeroNota;
                        $conta->nf_chave           = $cte->chave ?? null;

                        if (!$conta->nf_data_emissao) {
                            $conta->nf_data_emissao = $cte->data_emissao;
                        }
                        $conta->referencia = "TRANSPORTE E CTe - " . $numeroNota;
                        $alterou = true;
                    }
                }
            }

            // Se houve alteração real nos campos do banco de dados, salva
            if ($alterou) {
                $conta->save();
                $updatedCount++;
            }
        }

        session()->flash("mensagem_sucesso", "Sincronismo concluído: $updatedCount registros associados e atualizados.");
        return redirect()->back();
    }


    public function imprimirRecibo($id)
    {
        if (ob_get_contents()) ob_end_clean();

        // Carrega a conta com o cliente e a filial
        $conta = \App\Models\ContaReceber::with(['cliente', 'usuarioBaixa', 'categoria', 'filial'])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        if ($conta->status == 0) {
            return redirect()->back()->with('mensagem_erro', 'Conta ainda não foi recebida!');
        }

        $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $p = view('relatorios/recibo_recebimento')
            ->with('conta', $conta)
            ->with('config', $config);

        $domPdf = new \Dompdf\Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);
        $domPdf->setPaper("A4");
        $domPdf->render();

        return response($domPdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Recibo_Recebimento_'.$id.'.pdf"');
    }

    public function visualizarDanfe($id)
    {
        // 1. Busca a conta a receber
        $conta = \App\Models\ContaReceber::where('empresa_id', $this->empresa_id)->findOrFail($id);
        $numero = $conta->numero_nota_fiscal > 0 ? $conta->numero_nota_fiscal : $conta->nf_numero;

        if (!$numero || $numero == 0) {
            return redirect()->back()->with('mensagem_erro', 'Esta conta não possui número de nota fiscal!');
        }

        // 2. Define qual chave usar (puxa da conta ou busca da tabela de vendas se houver vínculo)
        $chaveNota = !empty($conta->nf_chave) ? $conta->nf_chave : null;
        if (!$chaveNota && !empty($conta->venda_id)) {
            $venda = \App\Models\Venda::where('empresa_id', $this->empresa_id)->find($conta->venda_id);
            if ($venda) { $chaveNota = $venda->chave; }
        }

        $caminhoXml = null;

        // 3. Limpa o nome do arquivo para evitar erros de extensão dupla (.xml.xml)
        if (!empty($chaveNota)) {
            $nomeArquivo = str_replace('.xml', '', $chaveNota) . '.xml';
            if (file_exists(public_path('xml_nfe/' . $nomeArquivo))) {
                $caminhoXml = public_path('xml_nfe/' . $nomeArquivo);
            }
        }

        // Se não achou pela chave, tenta pelo número puro da nota
        if (!$caminhoXml && file_exists(public_path('xml_nfe/' . $numero . '.xml'))) {
            $caminhoXml = public_path('xml_nfe/' . $numero . '.xml');
        }

        // 4. Se localizou o arquivo fiscal oficial no servidor, renderiza
        if ($caminhoXml) {
            if (ob_get_contents()) ob_end_clean();

            $xmlString = safe_file_get_contents($caminhoXml);
            $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $logo = ($config && $config->logo)
                ? 'data://text/plain;base64,' . base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo))
                : null;

            try {
                $danfe = new \Danfe($xmlString);
                $pdf = $danfe->render($logo);
                return response($pdf)->header('Content-Type', 'application/pdf');
            } catch (\Exception $e) {
                return redirect()->back()->with('mensagem_erro', 'Erro ao renderizar: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('mensagem_erro', 'O arquivo XML oficial desta nota não foi localizado na pasta public/xml_nfe/.');
    }
}

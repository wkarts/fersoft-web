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
use Illuminate\Support\Facades\DB;

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
        $contas = ContaReceber::with(['usuario', 'usuarioEdit', 'usuarioBaixa', 'categoria', 'cliente', 'filial'])
            ->where('empresa_id', $this->empresa_id)
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
                if($c->categoria && $c->categoria->nome == $a){
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
    // FILTRO UNIFICADO
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

        $query = ContaReceber::with(['usuario', 'usuarioEdit', 'usuarioBaixa', 'categoria', 'cliente', 'filial'])
            ->select('conta_recebers.*')
            ->leftJoin('item_conta_empresas as ice', 'conta_recebers.id', '=', 'ice.conta_receber_id')
            ->where('conta_recebers.empresa_id', $this->empresa_id);

        $query->where(function($q) use ($permissaoAcesso){
            if($permissaoAcesso != null){
                foreach ($permissaoAcesso as $value) {
                    $value = $value == -1 ? null : $value;
                    $q->orWhere('conta_recebers.filial_id', $value);
                }
            }
        });

        if($filial_id){
            $filial_id = $filial_id == -1 ? null : $filial_id;
            $query->where('conta_recebers.filial_id', $filial_id);
        }

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
            $query->where(function($q) use ($request) {
                $q->where('conta_recebers.numero_nota_fiscal', $request->numero_nota_fiscal)
                    ->orWhere('conta_recebers.nf_numero', $request->numero_nota_fiscal);
            });
        }

        if($request->categoria != 'todos'){
            $query->where('conta_recebers.categoria_id', $request->categoria);
        }

        if($request->tipo_pagamento){
            $query->where('conta_recebers.tipo_pagamento', $request->tipo_pagamento);
        }

        if($numero_pedido){
            if($clienteId == 'null') {
                $query->join('vendas', 'vendas.id', '=', 'conta_recebers.venda_id');
            }
            $query->where('vendas.id', $numero_pedido);
        }

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

        $numNota = isset($parcela['numero_nota_fiscal']) ? $parcela['numero_nota_fiscal'] : 0;

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
            'empresa_id' => $this->empresa_id,
            'numero_nota_fiscal' => $numNota,
            'nf_numero' => $numNota,
            'usuario_id' => session('user_logged')['id']
        ]);
        echo json_encode($parcela);
    }

    // =========================================================================
    // MÉTODOS SAVE & UPDATE (Gravação em numero_nota_fiscal e nf_numero)
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
        $dataEmissao = $request->data_emissao ? $this->parseDate($request->data_emissao) . " " . date('H:i:s') : date('Y-m-d H:i:s');

        $conta = ContaReceber::create([
            'venda_id' => null,
            'data_vencimento' => $this->parseDate($request->vencimento),
            'data_recebimento' => $request->status ? $dataPagamento : null,
            'nf_data_emissao' => $dataEmissao,
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
            'nf_numero' => $numNota,
            'usuario_id' => session('user_logged')['id'],
            'usuario_baixa_id' => $request->status ? session('user_logged')['id'] : null
        ]);

        if($conta->status && $request->conta_id){
            $item = ItemContaEmpresa::create([
                'conta_id' => $request->conta_id,
                'empresa_id' => $this->empresa_id,
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
                    'data_recebimento' => $request->status ? $dataPagamento : null,
                    'nf_data_emissao' => $dataEmissao,
                    'valor_integral' => str_replace(",", ".", $p->valor),
                    'valor_recebido' => $request->status ? str_replace(",", ".", $p->valor) : 0,
                    'status' => $request->status ? true : false,
                    'tipo_pagamento' => $request->tipo_pagamento ?? '',
                    'cliente_id' => $clienteId,
                    'observacao' => $request->observacao ?? '',
                    'numero_nota_fiscal' => $numNota,
                    'nf_numero' => $numNota,
                    'referencia' => $request->referencia . " - parcela " .($key+2) . "/".(sizeof($parcelas)+1),
                    'categoria_id' => $request->categoria_id,
                    'empresa_id' => $this->empresa_id,
                    'filial_id' => $request->filial_id,
                    'usuario_id' => session('user_logged')['id'],
                    'usuario_edit_id' => NULL,
                    'usuario_baixa_id' => $request->status ? session('user_logged')['id'] : NULL
                ]);

                if($contaP->status && $request->conta_id){
                    $itemP = ItemContaEmpresa::create([
                        'conta_id' => $request->conta_id,
                        'empresa_id' => $this->empresa_id,
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
        $conta = ContaReceber::where('id', $request->id)->where('empresa_id', $this->empresa_id)->firstOrFail();

        $request->merge([
            'filial_id' => $request->filial_id == -1 ? null : $request->filial_id
        ]);

        $numNota = $request->numero_nota_fiscal ?? 0;

        $conta->data_vencimento = $this->parseDate($request->vencimento);
        if($request->data_emissao){
            $conta->nf_data_emissao = $this->parseDate($request->data_emissao) . " " . date('H:i:s');
        }
        $conta->referencia = $request->referencia;
        $conta->tipo_pagamento = $request->tipo_pagamento ?? '';
        $conta->observacao = $request->observacao ?? '';
        $conta->valor_integral = str_replace(",", ".", $request->valor);
        $conta->categoria_id = $request->categoria_id;
        $conta->filial_id = $request->filial_id;
        $conta->numero_nota_fiscal = $numNota;
        $conta->nf_numero          = $numNota;
        $conta->usuario_edit_id   = session('user_logged')['id'];

        if(isset($request->cliente_id) && !empty($request->cliente_id)){
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

    // =========================================================================
    // NOVOS MÉTODOS: DETALHES E BAIXA PARCIAL
    // =========================================================================
    public function detalhes($id){
        $conta = ContaReceber::with(['usuario', 'usuarioEdit', 'usuarioBaixa', 'categoria', 'cliente', 'filial'])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        $title = 'Detalhes da Conta a Receber';
        return view('contaReceber/detalhes', compact('conta', 'title'));
    }

    public function baixarParcial(Request $request){
        try {
            DB::beginTransaction();

            $conta = ContaReceber::where('id', $request->id)->where('empresa_id', $this->empresa_id)->firstOrFail();

            $valorRecebido = str_replace(',', '.', str_replace('.', '', $request->valor_recebido));
            $valorRestante = round($conta->valor_integral - $valorRecebido, 2);

            if ($valorRestante <= 0) {
                throw new \Exception("Para baixa total, utilize a função de recebimento normal.");
            }

            // Replica a conta criando o resíduo
            $residuo = $conta->replicate();
            $residuo->valor_integral = $valorRestante;
            $residuo->valor_recebido = 0;
            $residuo->status = false;
            $residuo->data_vencimento = $request->nova_data_vencimento;
            $residuo->referencia .= " (Resíduo de Baixa Parcial)";
            $residuo->created_at = now();
            $residuo->updated_at = now();
            $residuo->save();

            // Baixa a conta original pelo valor informado
            $conta->status = true;
            $conta->valor_recebido = $valorRecebido;
            $conta->valor_integral = $valorRecebido;
            $conta->data_recebimento = $request->data_recebimento;
            $conta->usuario_baixa_id = session('user_logged')['id'];
            $conta->save();

            // Registra no extrato da conta bancária
            if ($request->conta_bancaria_id) {
                $nomeCliente = $conta->cliente->razao_social ?? 'N/A';
                ItemContaEmpresa::create([
                    'empresa_id' => $this->empresa_id,
                    'conta_id' => $request->conta_bancaria_id,
                    'conta_receber_id' => $conta->id,
                    'valor' => $valorRecebido,
                    'tipo' => 'entrada',
                    'descricao' => "Recebimento parcial Cliente: {$nomeCliente} | Ref: {$conta->referencia}",
                    'data_pagamento' => $request->data_recebimento,
                    'categoria_id' => $conta->categoria_id,
                    'user_id' => session('user_logged')['id']
                ]);
            }

            DB::commit();
            return response()->json("Baixa parcial realizada! Resíduo gerado para " . \Carbon\Carbon::parse($request->nova_data_vencimento)->format('d/m/Y'), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    private function parseRecorrencia($rec){
        $temp = explode("/", $rec);
        $rec = "01/".$temp[0]."/20".$temp[1];
        return date('Y-m', strtotime(str_replace("/", "-", $rec)));
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
            $itemBancario = ItemContaEmpresa::where('conta_receber_id', $conta->id)->first();

            if ($itemBancario) {
                if (isset($this->util)) {
                    $this->util->atualizaSaldo($itemBancario, true);
                }
                $itemBancario->delete();
            }

            $conta->status = false;
            $conta->valor_recebido = 0;
            $conta->estorno = true;
            $conta->motivo_estorno = $request->motivo;
            $conta->usuario_baixa_id = null;
            $conta->save();

            session()->flash('mensagem_sucesso', 'Conta estornada e lançamento bancário removido!');

            $rota = __getRedirect($this->empresa_id, 'contas_receber');
            return redirect($rota != "" ? $rota : '/contasReceber');

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao estornar: ' . $e->getMessage());
            return redirect('/contasReceber');
        }
    }

    public function receber($id){
        $conta = ContaReceber::find($id);

        if(valida_objeto($conta)){
            // Filtra as contas de banco pertencentes à unidade da conta a receber (Matriz ou Filial)
            $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
                ->where('status', 1)
                ->where(function($query) use ($conta) {
                    if ($conta->filial_id != null) {
                        $query->where('filial_id', $conta->filial_id);
                    } else {
                        $query->whereNull('filial_id');
                    }
                })
                ->get();

            return view('contaReceber/receber')
                ->with('conta', $conta)
                ->with('contasEmpresa', $contasEmpresa)
                ->with('title', 'Receber Conta');
        } else {
            return redirect('/403');
        }
    }

    public function receberConta(Request $request){
        $conta = ContaReceber::find($request->id);
        $usarAdiantamento = $request->has('usar_adiantamento');

        $valor_recebido = (float)str_replace(',', '.', str_replace('.', '', $request->valor_recebido));
        $juros = (float)str_replace(',', '.', str_replace('.', '', $request->juros));
        $multa = (float)str_replace(',', '.', str_replace('.', '', $request->multa));
        $desconto = (float)str_replace(',', '.', str_replace('.', '', $request->desconto));

        $conta->status = true;
        $conta->valor_recebido = $valor_recebido;
        $conta->juros = $juros;
        $conta->multa = $multa;
        $conta->desconto = $desconto;
        $conta->usuario_baixa_id = session('user_logged')['id'];

        $dataPagamento = $request->data_pagamento ? $this->parseDate($request->data_pagamento) : date('Y-m-d');
        $conta->data_recebimento = $dataPagamento;

        $conta->tipo_pagamento = $usarAdiantamento ? 'Adiantamento de Cliente' : $request->tipo_pagamento;
        $conta->save();

        if ($usarAdiantamento) {
            \App\Http\Controllers\AdiantamentoController::baixarAdiantamento(
                $conta->cliente_id,
                'cliente',
                $valor_recebido,
                $this->empresa_id,
                $conta->id,
                session('user_logged')['id'],
                $conta->filial_id
            );
        } else {
            if (isset($request->conta_id)) {
                $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);

                $itemContaEmpresa = ItemContaEmpresa::create([
                    'conta_id'       => $request->conta_id,
                    'empresa_id'     => $this->empresa_id,
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
        $conta->usuario_baixa_id = session('user_logged')['id'];

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
            'numero_nota_fiscal' => $conta->numero_nota_fiscal,
            'nf_numero' => $conta->nf_numero,
            'usuario_id' => session('user_logged')['id']
        ]);

        $conta->status = true;
        $conta->valor_recebido = $request->valor;
        $conta->valor_integral = $request->valor;
        $conta->tipo_pagamento = $request->tipo_pagamento;
        $conta->data_recebimento = date("Y-m-d") . " " . date('H:i:s');
        $conta->usuario_baixa_id = session('user_logged')['id'];

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
                    $ctemp->usuario_baixa_id = session('user_logged')['id'];
                    $ctemp->save();
                    $temp .= " $c" . (sizeof($contasMais)-1 > $key ? "," : "");
                    $somaParaTroco += $ctemp->valor_integral;
                }
            }

            $conta->status = true;
            $conta->valor_recebido = $conta->valor_integral;
            $conta->data_recebimento = date("Y-m-d") . " " . date('H:i:s');
            $conta->usuario_baixa_id = session('user_logged')['id'];
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
        $contas = ContaReceber::whereIn('id', $temp)
            ->where('empresa_id', $this->empresa_id)
            ->get();

        if($contas->isEmpty() || $contas->count() <= 1){
            session()->flash('mensagem_erro', "Selecione pelo menos duas contas válidas!");
            return redirect()->back();
        }

        // Pega a filial do primeiro documento do lote
        $filialIdDocumento = $contas->first()->filial_id;

        $somaTotal = $contas->sum('valor_integral');
        $title = 'Receber contas';

        // Busca apenas as contas bancárias da unidade do lote (Matriz ou Filial)
        $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->where(function($query) use ($filialIdDocumento) {
                if ($filialIdDocumento != null) {
                    $query->where('filial_id', $filialIdDocumento);
                } else {
                    $query->whereNull('filial_id');
                }
            })
            ->get();

        return view('contaReceber/receber_multi', compact('somaTotal', 'title', 'contas', 'ids', 'contasEmpresa'));
    }

    public function receberMulti(Request $request){
        $dtReceb = \Carbon\Carbon::parse(str_replace("/", "-", $request->data_pagamento))->format('Y-m-d H:i:s');
        $temp = explode(",", $request->ids);
        $usarAdiantamento = $request->has('usar_adiantamento');

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($temp, $dtReceb, $request, $usarAdiantamento) {
                foreach($temp as $i){
                    $conta = ContaReceber::find($i);
                    if($conta && $conta->empresa_id == $this->empresa_id){

                        $conta->status = 1;
                        $conta->valor_recebido = $conta->valor_integral;
                        $conta->data_recebimento = $dtReceb;
                        $conta->usuario_baixa_id = session('user_logged')['id'];

                        $conta->tipo_pagamento = $usarAdiantamento ? 'Adiantamento de Cliente' : $request->tipo_pagamento;

                        $conta->juros = $request->juros ? __replace($request->juros) : 0;
                        $conta->multa = $request->multa ? __replace($request->multa) : 0;
                        $conta->desconto = $request->desconto ? __replace($request->desconto) : 0;
                        $conta->save();

                        if ($usarAdiantamento) {
                            \App\Http\Controllers\AdiantamentoController::baixarAdiantamento(
                                $conta->cliente_id, 'cliente', $conta->valor_integral,
                                $this->empresa_id, $conta->id, session('user_logged')['id'], $conta->filial_id
                            );
                        } else if(isset($request->conta_id)){
                            $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);
                            $data = [
                                'conta_id'       => $request->conta_id,
                                'empresa_id'     => $this->empresa_id,
                                'descricao'      => "Recebimento Multi: " . ($conta->cliente->razao_social ?? 'Cliente') . " | Ref: " . $conta->referencia,
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
            });

            session()->flash('mensagem_sucesso', "Contas recebidas com sucesso!");
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', "Erro ao processar lote: " . $e->getMessage());
        }

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
            'entrada' => 0,
            'usuario_id' => session('user_logged')['id']
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
                'cr.id',
                'cr.numero_nota_fiscal',
                'cr.nf_data_emissao as data_emissao_nfe',
                'cr.data_vencimento',
                'cr.data_recebimento',
                'cli.razao_social as cliente_razao',
                'cli.cpf_cnpj as cliente_cpf_cnpj',
                'cr.referencia',
                'cr.observacao',
                'cc.nome as categoria_nome',
                'cr.tipo_pagamento',
                'ce.nome as conta_empresa',
                'cr.valor_integral',
                'cr.valor_recebido',
                'cr.juros',
                'cr.multa',
                'cr.desconto',
                'cr.status',
                \DB::raw("COALESCE(fil.descricao, 'Matriz') as filial_nome")
            )
            ->where('cr.empresa_id', $this->empresa_id);

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

    public function syncNotaFiscal()
    {
        $updatedCount = 0;
        $empresa_id = $this->empresa_id;

        $contas = \App\Models\ContaReceber::where('empresa_id', $empresa_id)->get();

        foreach ($contas as $conta) {
            $alterou = false;
            $venda = null;

            if (!empty($conta->venda_id)) {
                $venda = \App\Models\Venda::where('empresa_id', $empresa_id)
                    ->where('id', $conta->venda_id)
                    ->first();
            }

            if (!$venda && !empty($conta->referencia)) {
                if (preg_match('/[Vv]enda\s*(\d+)/', $conta->referencia, $matches)) {
                    $vendaIdExtraido = $matches[1];

                    $venda = \App\Models\Venda::where('empresa_id', $empresa_id)
                        ->where('id', $vendaIdExtraido)
                        ->first();
                }
            }

            if (!$venda) {
                $numeroNota = $conta->numero_nota_fiscal > 0 ? $conta->numero_nota_fiscal : $conta->nf_numero;
                if ($numeroNota > 0) {
                    $venda = \App\Models\Venda::where('empresa_id', $empresa_id)
                        ->where('NfNumero', $numeroNota)
                        ->first();
                }
            }

            if ($venda) {
                $conta->venda_id = $venda->id;

                if ($venda->NfNumero > 0) {
                    $conta->numero_nota_fiscal = $venda->NfNumero;
                    $conta->nf_numero          = $venda->NfNumero;
                    $conta->nf_chave           = $venda->chave ?? null;
                    $conta->referencia         = "VENDA DE MERCADORIAS E NFe - " . $venda->NfNumero;
                } else {
                    $conta->numero_nota_fiscal = 0;
                    $conta->nf_numero          = 0;
                    $conta->nf_chave           = null;
                }

                if (!$conta->nf_data_emissao) {
                    $conta->nf_data_emissao = $venda->data_emissao ?? $venda->created_at;
                }

                $alterou = true;
            }

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
                // CORRIGIDO: Instanciando via Namespace correto da NFePHP
                $danfe = new \NFePHP\DA\NFe\Danfe($xmlString);
                $pdf = $danfe->render($logo);
                return response($pdf)->header('Content-Type', 'application/pdf');
            } catch (\Exception $e) {
                return redirect()->back()->with('mensagem_erro', 'Erro ao renderizar: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('mensagem_erro', 'O arquivo XML oficial desta nota não foi localizado na pasta public/xml_nfe/.');
    }
}

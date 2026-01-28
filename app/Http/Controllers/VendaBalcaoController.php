<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendaBalcao;
use App\Models\ItemVendaBalcao;
use App\Models\FaturaVendaBalcao;
use App\Models\ListaPreco;
use App\Models\Cliente;
use App\Models\Frete;
use App\Models\Venda;
use App\Models\ItemVenda;
use App\Models\ContaReceber;
use App\Models\Produto;
use App\Models\VendaCaixa;
use App\Models\ItemVendaCaixa;
use App\Models\FaturaFrenteCaixa;
use App\Models\Usuario;
use App\Models\ConfigNota;
use App\Models\Transportadora;
use App\Models\CategoriaConta;
use App\Models\FormaPagamento;
use App\Models\NaturezaOperacao;
use Illuminate\Support\Str;
use App\Helpers\StockMove;
use App\Models\ComissaoVenda;
use App\Services\LogService;

class VendaBalcaoController extends Controller
{

    protected $empresa_id = null;
    protected $filial_id = null;
    protected $usuario_id = null;

    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;

            $this->usuario_id = session('user_logged')['id'] ?? null;
            if (!$this->usuario_id) {
                return redirect('/login');
            }

            $this->filial_id = $request->get('filial_id')
                ?? session('user_logged.local_padrao')
                ?? null;

            $this->logService = new LogService($this->empresa_id, $this->usuario_id, $this->filial_id);

            return $next($request);
        });
    }

    public function numeroSequencial(){
        $verify = VendaBalcao::where('empresa_id', $this->empresa_id)
        ->where('numero_sequencial', 0)
        ->first();

        if($verify){
            $vendas = VendaBalcao::where('empresa_id', $this->empresa_id)
            ->get();

            $n = 1;
            foreach($vendas as $v){
                $v->numero_sequencial = $n;
                $n++;
                $v->save();
            }
        }
    }

    public function index(Request $request){

        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $filial_id = $request->filial_id;
        $estado = $request->estado;
        $cliente = $request->cliente;
        $codigo_venda = $request->codigo_venda;

        $permissaoAcesso = __getLocaisUsarioLogado();
        $local_padrao = __get_local_padrao();

        // echo $local_padrao;
        if($local_padrao == -1){
            $local_padrao = null;
        }
        $vendas = VendaBalcao::
        select('venda_balcaos.*')
        ->where('venda_balcaos.empresa_id', $this->empresa_id)
        ->where(function($query) use ($permissaoAcesso){
            if($permissaoAcesso != null){
                foreach ($permissaoAcesso as $value) {
                    if($value == -1){
                        $value = null;
                    }
                    $query->orWhere('filial_id', $value);
                }
            }
        })->where('filial_id', $local_padrao)
        ->when($estado != '', function ($q) use ($estado) {
        })
        ->when(!$estado, function ($q) use ($estado) {
            return $q->where('estado', 0);
        })
        ->when($codigo_venda != '', function ($q) use ($codigo_venda) {
            return $q->where('codigo_venda', $codigo_venda);
        })
        ->when($dataInicial != '', function ($q) use ($dataInicial) {
            return $q->whereDate('created_at', '>=', $this->parseDate($dataInicial));
        })
        ->when($dataFinal != '', function ($q) use ($dataFinal) {
            return $q->whereDate('created_at', '<=', $this->parseDate($dataFinal));
        })
        ->when($cliente != '', function ($q) use ($cliente) {
            return $q->where('venda_balcaos.cliente_id', $cliente);
        })
        ->orderBy('id', 'desc')
        ->paginate(30);

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', 0)
        ->get();

        return view("vendas-balcao/index")
        ->with('vendas', $vendas)
        ->with('estado', $estado)
        ->with('clientes', $clientes)
        ->with('dataInicial', $dataInicial)
        ->with('dataFinal', $dataFinal)
        ->with('cliente', $cliente)
        ->with('codigo_venda', $codigo_venda)
        ->with('links', true)
        ->with('title', 'Vendas Balcão');

    }

    public function create(){

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();
        if($config == null){
            return redirect('configNF');
        }
        $transportadoras = Transportadora::
        where('empresa_id', $this->empresa_id)
        ->get();

        $listaPreco = ListaPreco::where('empresa_id', $this->empresa_id)
        ->get();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', 0)
        ->get();

        $usuario = Usuario::find(get_id_user());
        $tiposPagamento = VendaBalcao::tiposPagamento();

        $formasPagamento = FormaPagamento::
        where('empresa_id', $this->empresa_id)
        ->where('status', true)
        ->get();

        $usuarios = Usuario::where('empresa_id', $this->empresa_id)
        ->where('ativo', 1)
        ->orderBy('nome', 'asc')
        ->get();

        $vendedores = [];
        foreach($usuarios as $u){
            if($u->funcionario){
                array_push($vendedores, $u);
            }
        }

        return view("vendas-balcao/create")
        ->with('transportadoras', $transportadoras)
        ->with('listaPreco', $listaPreco)
        ->with('clientes', $clientes)
        ->with('usuario', $usuario)
        ->with('vendedores', $vendedores)
        ->with('config', $config)
        ->with('formasPagamento', $formasPagamento)
        ->with('tiposPagamento', $tiposPagamento)
        ->with('title', 'Nova Venda Balcão');
    }

    public function edit($id){

        $venda = VendaBalcao::with(['itens', 'fatura'])->findOrFail($id);
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();
        if($config == null){
            return redirect('configNF');
        }
        $transportadoras = Transportadora::
        where('empresa_id', $this->empresa_id)
        ->get();

        $listaPreco = ListaPreco::where('empresa_id', $this->empresa_id)
        ->get();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', 0)
        ->get();

        $usuario = Usuario::find(get_id_user());
        $tiposPagamento = VendaBalcao::tiposPagamento();

        $formasPagamento = FormaPagamento::
        where('empresa_id', $this->empresa_id)
        ->where('status', true)
        ->get();

        return view("vendas-balcao/edit")
        ->with('transportadoras', $transportadoras)
        ->with('listaPreco', $listaPreco)
        ->with('clientes', $clientes)
        ->with('venda', $venda)
        ->with('usuario', $usuario)
        ->with('config', $config)
        ->with('formasPagamento', $formasPagamento)
        ->with('tiposPagamento', $tiposPagamento)
        ->with('title', 'Editar Venda Balcão');
    }

    public function store(Request $request){
        $data = $request->data;

        $numero_sequencial = 0;
        $last = VendaBalcao::where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')
        ->first();

        $totalVenda = str_replace(",", ".", $data['total']);

        $numero_sequencial = $last != null ? ($last->numero_sequencial + 1) : 1;

        $desconto = 0;
        if($data['desconto']){
            $desconto = str_replace(".", "", $data['desconto']);
            $desconto = str_replace(",", ".", $desconto);
        }

        $acrescimo = 0;
        if($data['acrescimo']){
            $acrescimo = str_replace(".", "", $data['acrescimo']);
            $acrescimo = str_replace(",", ".", $acrescimo);
        }

        //frete
        $valorFrete = str_replace(".", "", $data['valorFrete'] ?? 0);
        $valorFrete = str_replace(",", ".", $valorFrete );
        $vol = $data['volume'];

        if($vol['pesoL']){
            $pesoLiquido = str_replace(",", ".", $vol['pesoL']);
        }else{
            $pesoLiquido = 0;
        }

        if($vol['pesoB']){
            $pesoBruto = str_replace(",", ".", $vol['pesoB']);
        }else{
            $pesoBruto = 0;
        }

        if($vol['qtdVol']){
            $qtdVol = str_replace(",", ".", $vol['qtdVol']);
        }else{
            $qtdVol = 0;
        }

        $venda = VendaBalcao::create([
            'empresa_id' => $this->empresa_id,
            'codigo_venda' => Str::random(6),
            'numero_sequencial' => $numero_sequencial,
            'cliente_id' => $data['cliente_id'],
            'cliente_nome' => $data['cliente_nome'],
            'vendedor_id' => $data['vendedor_id'],
            'usuario_id' => get_id_user(),
            'transportadora_id' => $data['transportadora_id'],
            'valor_total' => $totalVenda,
            'desconto' => $desconto,
            'acrescimo' => $acrescimo,
            'forma_pagamento' => $data['formaPagamento'],
            'tipo_pagamento' => $data['tipoPagamento'],
            'observacao' => $data['observacao'] ?? '',
            'bandeira_cartao' => $data['bandeira_cartao'],
            'cAut_cartao' => $data['cAut_cartao'] ?? '',
            'cnpj_cartao' => $data['cnpj_cartao'] ?? '',
            'descricao_pag_outros' => $data['descricao_pag_outros'] ?? '',
            'filial_id' => $data['filial_id'] != -1 ? $data['filial_id'] : null,
            'placa' => $data['placaVeiculo'] ?? '',
            'valor' => $valorFrete ?? 0,
            'tipo' => (int)$data['frete'],
            'qtdVolumes' => $qtdVol?? 0,
            'uf' => $data['ufPlaca'] ?? '',
            'numeracaoVolumes' => $vol['numeracaoVol'] ?? '0',
            'especie' => $vol['especie'] ?? '*',
            'peso_liquido' => $pesoLiquido ?? 0,
            'peso_bruto' => $pesoBruto ?? 0
        ]);
        $stockMove = new StockMove();

        $itens = $data['itens'];
        foreach ($itens as $i) {
            ItemVendaBalcao::create([
                'venda_balcao_id' => $venda->id,
                'produto_id' => (int) $i['codigo'],
                'quantidade' => (float) __replace($i['quantidade']),
                'valor' => (float) __replace($i['valor']),
                'sub_total' => (float) __replace($i['valor']) * (float) __replace($i['quantidade']),
            ]);

            $produto = Produto::findOrFail((int) $i['codigo']);
            if($produto->gerenciar_estoque){
                $stockMove->downStock(
                    $produto->id,
                    (float) __replace($i['quantidade']),
                    $data['filial_id']
                );
            }
        }

        $fatura = isset($data['fatura']) ? $data['fatura'] : [];
        foreach ($fatura as $f) {
            FaturaVendaBalcao::create([
                'valor' => __replace($f['valor']),
                'forma_pagamento' => $f['tipo'],
                'venda_balcao_id' => $venda->id,
                'data_vencimento' => $this->parseDate($f['data'])
            ]);
        }

        $this->calculaComissao($data, $venda);

        return response()->json($venda, 200);
    }

    public function update(Request $request, $id){
        $item = VendaBalcao::findOrFail($id);

        $data = $request->data;

        $totalVenda = str_replace(",", ".", $data['total']);

        $desconto = 0;
        if($data['desconto']){
            $desconto = str_replace(".", "", $data['desconto']);
            $desconto = str_replace(",", ".", $desconto);
        }

        $acrescimo = 0;
        if($data['acrescimo']){
            $acrescimo = str_replace(".", "", $data['acrescimo']);
            $acrescimo = str_replace(",", ".", $acrescimo);
        }

        //frete
        $valorFrete = str_replace(".", "", $data['valorFrete'] ?? 0);
        $valorFrete = str_replace(",", ".", $valorFrete );
        $vol = $data['volume'];

        if($vol['pesoL']){
            $pesoLiquido = str_replace(",", ".", $vol['pesoL']);
        }else{
            $pesoLiquido = 0;
        }

        if($vol['pesoB']){
            $pesoBruto = str_replace(",", ".", $vol['pesoB']);
        }else{
            $pesoBruto = 0;
        }

        if($vol['qtdVol']){
            $qtdVol = str_replace(",", ".", $vol['qtdVol']);
        }else{
            $qtdVol = 0;
        }

        $venda = [
            'cliente_id' => $data['cliente_id'],
            'transportadora_id' => $data['transportadora_id'],
            'valor_total' => $totalVenda,
            'desconto' => $desconto,
            'acrescimo' => $acrescimo,
            'forma_pagamento' => $data['formaPagamento'],
            'tipo_pagamento' => $data['tipoPagamento'],
            'observacao' => $data['observacao'] ?? '',
            'bandeira_cartao' => $data['bandeira_cartao'],
            'cAut_cartao' => $data['cAut_cartao'] ?? '',
            'cnpj_cartao' => $data['cnpj_cartao'] ?? '',
            'descricao_pag_outros' => $data['descricao_pag_outros'] ?? '',
            'filial_id' => $data['filial_id'] != -1 ? $data['filial_id'] : null,
            'placa' => $data['placaVeiculo'] ?? '',
            'valor' => $valorFrete ?? 0,
            'tipo' => (int)$data['frete'],
            'quantidade_volumes' => $qtdVol?? 0,
            'uf' => $data['ufPlaca'] ?? '',
            'numeracao_volumes' => $vol['numeracaoVol'] ?? '0',
            'especie' => $vol['especie'] ?? '*',
            'peso_liquido' => $pesoLiquido ?? 0,
            'peso_bruto' => $pesoBruto ?? 0
        ];

        $item->update($venda);

        $this->reverteEstoque($item->itens);
        $item->itens()->delete();
        $item->fatura()->delete();
        $stockMove = new StockMove();

        $itens = $data['itens'];
        foreach ($itens as $i) {
            ItemVendaBalcao::create([
                'venda_balcao_id' => $item->id,
                'produto_id' => (int) $i['codigo'],
                'quantidade' => (float) __replace($i['quantidade']),
                'valor' => (float) __replace($i['valor']),
                'sub_total' => (float) __replace($i['valor']) * (float) __replace($i['quantidade']),
            ]);

            $produto = Produto::findOrFail((int) $i['codigo']);
            if($produto->gerenciar_estoque){
                $stockMove->downStock(
                    $produto->id,
                    (float) __replace($i['quantidade']),
                    $data['filial_id']
                );
            }
        }

        $fatura = isset($data['fatura']) ? $data['fatura'] : [];
        foreach ($fatura as $f) {
            FaturaVendaBalcao::create([
                'valor' => __replace($f['valor']),
                'forma_pagamento' => $f['tipo'],
                'venda_balcao_id' => $item->id,
                'data_vencimento' => $this->parseDate($f['data'])
            ]);
        }

        return response()->json($item, 200);
    }

    private function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    public function destroy($id){
        $item = VendaBalcao::findOrFail($id);
        $this->reverteEstoque($item->itens);
        $item->delete();
        session()->flash("mensagem_sucesso", "Venda balcão removida!");
        return redirect()->back();
    }

    private function reverteEstoque($itens){
        $stockMove = new StockMove();
        foreach($itens as $i){
            if($i->produto->gerenciar_estoque){
                $stockMove->pluStock($i->produto_id, $i->quantidade,-1, $itens[0]->venda->filial_id);
            }
        }
    }

    public function find(Request $request){
        $item = VendaBalcao::findOrFail($request->id);
        $naturezas = NaturezaOperacao::where('empresa_id', $this->empresa_id)->get();
        return view('vendas-balcao.finalizar', compact('item', 'naturezas'));
    }

    private function calculaComissao($data, $venda){
        if($data['vendedor_id']){
            $usr = Usuario::find($data['vendedor_id']);
            if($usr->funcionario){
                $percentual_comissao = $usr->funcionario->percentual_comissao;

                $valorComissao = $this->calcularComissaoVenda($venda, $percentual_comissao);
                if($valorComissao > 0){
                    ComissaoVenda::create(
                        [
                            'funcionario_id' => $usr->funcionario->id,
                            'venda_id' => $venda->id,
                            'tabela' => 'balcao',
                            'valor' => $valorComissao,
                            'status' => 0,
                            'empresa_id' => $this->empresa_id
                        ]
                    );
                }
            }
        }else{
            $usuario = Usuario::find(get_id_user());
            if($usuario->funcionario){
                $percentual_comissao = $usuario->funcionario->percentual_comissao;

                $valorComissao = $this->calcularComissaoVenda($venda, $percentual_comissao);
                if($valorComissao > 0){
                    ComissaoVenda::create(
                        [
                            'funcionario_id' => $usuario->funcionario->id,
                            'venda_id' => $venda->id,
                            'tabela' => 'balcao',
                            'valor' => $valorComissao,
                            'status' => 0,
                            'empresa_id' => $this->empresa_id
                        ]
                    );
                }
            }
        }
    }

    private function calcularComissaoVenda($venda, $percentual_comissao){
        $valorRetorno = 0;
        foreach($venda->itens as $i){
            if($i->produto->perc_comissao > 0){
                $valorRetorno += ($i->sub_total * $i->produto->perc_comissao) / 100;
            }
        }
        if($valorRetorno == 0){
            $valorRetorno = (($venda->valor_total) * $percentual_comissao) / 100;
        }
        return $valorRetorno;
    }


    public function storePedido(Request $request){
        $data = $request->data;
        $item = VendaBalcao::findOrFail($data['venda_id']);
        $item->estado = 1;
        $item->tipo_venda = 'nfe';
        $natureza_id = $data['natureza_id'];
        $fatura = $data['fatura'];

        if(!$natureza_id){
            $nat = NaturezaOperacao::where('empresa_id', $this->empresa_id)->first();
            $natureza_id = $nat->id;
        }

        $numero_sequencial = 0;
        $last = Venda::where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')
        ->first();

        $numero_sequencial = $last != null ? ($last->numero_sequencial + 1) : 1;

        $frete = null;
        if($item->tipo != '9'){
            $frete = Frete::create([
                'placa' => $item->placa,
                'valor' => $item->placa,
                'tipo' => $item->tipo,
                'qtdVolumes' => $item->quantidade_volumes,
                'uf' => $item->uf,
                'numeracaoVolumes' => $item->numeracao_volumes,
                'especie' => $item->especie,
                'peso_liquido' => $item->peso_liquido,
                'peso_bruto' => $item->peso_bruto
            ]);
        }
        $result = Venda::create([
            'cliente_id' => $item->cliente_id,
            'transportadora_id' => $item->transportadora_id,
            'forma_pagamento' => $item->forma_pagamento,
            'tipo_pagamento' => $item->tipo_pagamento,
            'usuario_id' => $item->usuario_id,
            'valor_total' => $item->valor_total,
            'desconto' => $item->desconto,
            'acrescimo' => $item->acrescimo,
            'frete_id' => $frete != null ? $frete->id : null,
            'NfNumero' => 0,
            'natureza_id' => $natureza_id,
            'path_xml' => '',
            'chave' => '',
            'sequencia_cce' => 0,
            'observacao' => $item->observacao,
            'data_entrega' => null,
            'data_retroativa' => null,
            'estado' => 'DISPONIVEL',
            'empresa_id' => $this->empresa_id,
            'bandeira_cartao' => $item->bandeira_cartao,
            'cAut_cartao' => $item->cAut_cartao,
            'cnpj_cartao' => $item->cnpj_cartao,
            'descricao_pag_outros' => $item->descricao_pag_outros,
            'credito_troca' => 0,
            'vendedor_id' => null,
            'numero_sequencial' => $numero_sequencial,
            'filial_id' => $item->filial_id

        ]);
        $item->venda_id = $result->id;
        $item->save();

        foreach($item->itens as $i){
            ItemVenda::create([
                'produto_id' => $i->produto_id,
                'venda_id' => $result->id,
                'quantidade' => $i->quantidade,
                'valor' => $i->valor
            ]);
        }

        $catCrediario = $this->categoriaCrediario();

        foreach($fatura as $key => $f){
            $tipos = Venda::tiposPagamento();
            ContaReceber::create([
                'venda_id' => $result->id,
                'data_vencimento' => $f['vencimento'],
                'data_recebimento' => $f['vencimento'],
                'valor_integral' => __replace($f['valor_parcela']),
                'cliente_id' => $item->cliente_id,
                'valor_recebido' => 0,
                'status' => false,
                'tipo_pagamento' => $tipos[$f['forma_pagamento']],
                'referencia' => "Parcela ".($key+1)."/" . sizeof($fatura) .", da Venda " . $result->id,
                'categoria_id' => $catCrediario,
                'empresa_id' => $this->empresa_id
            ]);
        }

        return response()->json($result, 200);
    }

    private function categoriaCrediario(){
        $cat = CategoriaConta::
        where('empresa_id', $this->empresa_id)
        ->where('nome', 'Crediário')
        ->first();
        if($cat != null) return $cat->id;
        $cat = CategoriaConta::create([
            'nome' => 'Crediário',
            'empresa_id' => $this->empresa_id,
            'tipo'=> 'receber'
        ]);
        return $cat->id;
    }

    public function storeNfce(Request $request){
        $data = $request->data;
        $item = VendaBalcao::findOrFail($data['venda_id']);
        $item->estado = 1;
        $item->tipo_venda = 'nfe';
        $natureza_id = $data['natureza_id'];
        $fatura = $data['fatura'];

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        if(!$natureza_id){
            $natureza_id = $config->nat_op_padrao;
        }

        $result = VendaCaixa::create([
            'cliente_id' => $item->cliente_id,
            'transportadora_id' => $item->transportadora_id,
            'forma_pagamento' => $item->forma_pagamento,
            'tipo_pagamento' => $item->tipo_pagamento,
            'usuario_id' => $item->usuario_id,
            'valor_total' => $item->valor_total,
            'dinheiro_recebido' => $item->valor_total,
            'desconto' => $item->desconto,
            'acrescimo' => $item->acrescimo,
            'NFcNumero' => 0,
            'natureza_id' => $natureza_id,
            'path_xml' => '',
            'chave' => '',
            'observacao' => $item->observacao,
            'data_entrega' => null,
            'data_retroativa' => null,
            'estado' => 'DISPONIVEL',
            'empresa_id' => $this->empresa_id,
            'bandeira_cartao' => $item->bandeira_cartao,
            'cAut_cartao' => $item->cAut_cartao,
            'cnpj_cartao' => $item->cnpj_cartao,
            'descricao_pag_outros' => $item->descricao_pag_outros,
            'credito_troca' => 0,
            'vendedor_id' => null,
            'numero_sequencial' => VendaCaixa::lastNumero($this->empresa_id),
            'filial_id' => $item->filial_id

        ]);
        $item->venda_id = $result->id;
        $item->save();

        foreach($item->itens as $i){
            ItemVendaCaixa::create([
                'produto_id' => $i->produto_id,
                'venda_caixa_id' => $result->id,
                'quantidade' => $i->quantidade,
                'valor' => $i->valor
            ]);
        }

        $catCrediario = $this->categoriaCrediario();

        foreach($fatura as $key => $f){
            if($f['vencimento'] != ''){
                FaturaFrenteCaixa::create([
                    'valor' => __replace($f['valor_parcela']),
                    'forma_pagamento' => $f['forma_pagamento'],
                    'venda_caixa_id' => $result->id,
                    'data_vencimento' => $f['vencimento']
                ]);
            }
        }

        return response()->json($result, 200);
    }

}


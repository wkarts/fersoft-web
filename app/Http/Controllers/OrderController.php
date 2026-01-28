<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdemServico;
use App\Models\ServicoOs;
use App\Models\EstadoOs;
use App\Models\FuncionarioOs;
use App\Models\Funcionario;
use App\Models\RelatorioOs;
use App\Models\Servico;
use App\Models\Cliente;
use App\Models\Acessor;
use App\Models\ConfigNota;
use App\Models\ProdutoOs;
use App\Models\AberturaCaixa;
use App\Models\ItemVendaCaixa;
use App\Models\Cidade;
use App\Models\Pais;
use App\Models\GrupoCliente;
use App\Models\Usuario;
use App\Models\VendaCaixa;
use App\Models\Certificado;
use App\Models\Categoria;
use App\Models\ConfigCaixa;
use App\Helpers\StockMove;
use \Carbon\Carbon;
use Dompdf\Dompdf;
use App\Models\Venda;
use App\Models\ItemVenda;
use App\Models\ContaReceber;
use App\Models\NaturezaOperacao;
use App\Models\Produto;
use App\Models\CategoriaConta;
use Illuminate\Support\Facades\DB;
use NFePHP\DA\NFe\PedidoIfood;

class OrderController extends Controller
{
    protected $empresa_id = null;
    public function __construct(){
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
        $this->numeroSequencial();
        $orders = OrdemServico::
        where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')
        ->paginate(20);
        return view('os/list')
        ->with('orders', $orders)
        ->with('print', true)
        ->with('links', true)
        ->with('title', 'Orders de Serviço');
    }

    public function filtro(Request $request){

        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $cliente = $request->cliente;
        $nome_fantasia = $request->nome_fantasia;
        $estado = $request->estado;
        $orders = [];

        $orders = OrdemServico::orderBy('id', 'desc')
        ->select('ordem_servicos.*')
        ->where('ordem_servicos.empresa_id', $this->empresa_id)
        ->where('ordem_servicos.estado', $estado)
        ->join('clientes', 'clientes.id', '=', 'ordem_servicos.cliente_id')
        ->when(!empty($cliente), function ($query) use ($cliente) {
            return $query->where('clientes.razao_social', 'like', "%$cliente%");
        })
        ->when(!empty($nome_fantasia), function ($query) use ($nome_fantasia) {
            return $query->where('clientes.nome_fantasia', 'like', "%$nome_fantasia%");
        })
        ->when(!empty($data_inicial), function ($query) use ($data_inicial) {
            return $query->whereDate('ordem_servicos.created_at', '>=', $data_inicial);
        })
        ->when(!empty($data_final), function ($query) use ($data_final) {
            return $query->whereDate('ordem_servicos.created_at', '<=', $data_final);
        })
        ->get();

        return view('os/list')
        ->with('orders', $orders)
        ->with('cliente', $cliente)
        ->with('data_inicial', $data_inicial)
        ->with('data_final', $data_final)
        ->with('nome_fantasia', $nome_fantasia)
        ->with('estado', $estado)
        ->with('title', 'Filtro Ordem de Serviço');
    }

    public function numeroSequencial(){
        $verify = OrdemServico::where('empresa_id', $this->empresa_id)
        ->where('numero_sequencial', 0)
        ->first();

        if($verify){
            $os = OrdemServico::where('empresa_id', $this->empresa_id)
            ->get();

            $n = 1;
            foreach($os as $v){
                $v->numero_sequencial = $n;
                $n++;
                $v->save();
            }
        }
    }

    public function new(){
        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->orderBy('razao_social')->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($config == null){
            session()->flash("mensagem_erro", "É necessário configurar o emitente!");
            return redirect('configNF');
        }

        return view('os/register')
        ->with('client', true)
        ->with('clientes', $clientes)
        ->with('config', $config)
        ->with('estados', EstadoOs::values())
        ->with('title', 'Nova Ordem de Serviço');
    }

    public function delete($id){
        $ordem = OrdemServico::find($id);
        if(valida_objeto($ordem)){
            $this->removeItens($ordem);
            if($ordem->delete()){
                session()->flash("mensagem_sucesso", "Ordem de serviço removida!");
            }else{
                session()->flash("mensagem_erro", "Erro ao remover!");
            }
            return redirect("/ordemServico");
        }else{
            return redirect('/403');
        }

    }

    private function removeItens($ordem){
        foreach($ordem->servicos as $s){
            $s->delete();
        }
        foreach($ordem->relatorios as $s){
            $s->delete();
        }

        foreach($ordem->produtos as $s){
            $s->delete();
        }
        foreach($ordem->funcionarios as $s){
            $s->delete();
        }
    }

    public function save(Request $request){
        $this->_validate($request);

        $order = new OrdemServico();
        $request->merge([ 'valor' =>str_replace(",", ".", $request->input('valor'))]);

        $cliente = $request->input('cliente');
        $cliente = explode("-", $cliente);
        $cliente = $cliente[0];

        $numero_sequencial = 0;
        $last = OrdemServico::where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')
        ->first();

        $numero_sequencial = $last != null ? ($last->numero_sequencial + 1) : 1;

        $result = $order->create([
            'descricao' => $request->input('descricao'),
            'usuario_id' => get_id_user(),
            'cliente_id' => $cliente,
            'filial_id' => $request->filial_id > 0 ? $request->filial_id : null,
            'empresa_id' => $this->empresa_id,
            'numero_sequencial' => $numero_sequencial,
            'forma_pagamento' => '',
            'descricao' => $request->descricao ?? ''
        ]);

        if($result){
            session()->flash("mensagem_sucesso", "OS gerada!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao gerar OS!');
        }

        return redirect("/ordemServico/servicosordem/$result->id");
    }

    public function servicosordem($ordemId){
        $ordem = OrdemServico::
        where('id', $ordemId)
        ->first();

        if(valida_objeto($ordem)){
            $servicos = Servico::
            where('empresa_id', $this->empresa_id)
            ->get();

            $funcionarios = Funcionario::
            where('empresa_id', $this->empresa_id)
            ->get();

            $temServicos = count(Servico::where('empresa_id', $this->empresa_id)->get()) > 0;
            $temFuncionarios = count(Funcionario::where('empresa_id', $this->empresa_id)->get()) > 0;
         // echo json_encode($ordem->servicos);
            return view('os/detalhes')
            ->with('ordem', $ordem)

            ->with('servicos', $servicos)
            ->with('funcionarios', $funcionarios)
            ->with('temServicos', $temServicos)
            ->with('temFuncionarios', $temFuncionarios)
            ->with('title', 'Detalhes da OS')
            ->with('servicoJs', true);
        }else{
            return redirect('/403');
        }
    }

    public function storeServico(Request $request){
        try{
            $item = ServicoOs::create([
                'quantidade' => __replace($request->qtd_servico),
                'ordem_servico_id' => $request->ordem_servico_id,
                'servico_id' => $request->servico_id,
                'valor_unitario' => __replace($request->valor_servico),
                'sub_total' => __replace($request->qtd_servico)*__replace($request->valor_servico)
            ]);

            $os = OrdemServico::findOrFail($request->ordem_servico_id);
            $data = [
                'view' => view('os.tr_servico', compact('item'))->render(),
                'total' => $os->servicos->sum('sub_total')
            ];
            return response()->json($data, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 403);
        }
    }

    public function storeProduto(Request $request){
        try{

            $item = ProdutoOs::create([
                'produto_id' => $request->produto_id,
                'ordem_servico_id' => $request->ordem_servico_id,
                'quantidade' => __replace($request->qtd_produto),
                'valor_unitario' => __replace($request->valor_produto),
                'sub_total' => __replace($request->qtd_produto) * __replace($request->valor_produto),
            ]);

            $os = OrdemServico::findOrFail($request->ordem_servico_id);
            $data = [
                'view' => view('os.tr_produto', compact('item'))->render(),
                'total' => $os->produtos->sum('sub_total')
            ];
            return response()->json($data, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 403);
        }
    }

    public function addServico(Request $request){
        $this->_validateServicoOs($request);

        try{
            ServicoOs::create([
                'quantidade' => __replace($request->quantidade),
                'ordem_servico_id' => $request->ordem_servico_id,
                'servico_id' => $request->servico,
                'valor_unitario' => __replace($request->valor_unitario),
                'sub_total' => __replace($request->quantidade)*__replace($request->valor_unitario)
            ]);

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao adicionar!');
        }
        return redirect()->back();
    }

    public function deleteServico($id){
        $obj = ServicoOs
        ::where('id', $id)
        ->first();
        $id = $obj->ordemServico->id;

        if(valida_objeto($obj->ordemServico)){
            $ordem = OrdemServico::
            where('id', $id)
            ->first();

            $servico = Servico::
            where('id', $obj->servico->id)
            ->first();

            $ordem->valor -= $obj->quantidade * $servico->valor;
            $ordem->save();

            $delete = $obj->delete();
            if($delete){
                session()->flash('mensagem_sucesso', 'Serviço removido!');
            }else{
                session()->flash('mensagem_erro', 'Erro!');
            }

            return redirect("/ordemServico/servicosordem/$id");
        }else{
            return redirect('/403');
        }
    }

    public function addRelatorio($id){
        $ordem = OrdemServico::
        where('id', $id)
        ->first();

        if(valida_objeto($ordem)){
            return view('os/addRelatorio')
            ->with('ordem', $ordem)
            ->with('title', 'Novo Relatório');
        }else{
            return redirect('/403');
        }
    }

    public function editRelatorio($id){
        $relatorio = RelatorioOs::
        where('id', $id)
        ->first();
        if(valida_objeto($relatorio->ordemServico)){
            $ordem = $relatorio->ordemServico;
            return view('os/addRelatorio')
            ->with('ordem', $ordem)
            ->with('relatorio', $relatorio)
            ->with('title', 'Editar Relatório');
        }else{
            return redirect('/403');
        }
    }

    public function alterarEstado($id){
        $ordem = OrdemServico::findOrFail($id);

        $categoriasConta = CategoriaConta::
        where('empresa_id', $this->empresa_id)
        ->where('tipo', 'receber')
        ->get();

        if(valida_objeto($ordem)){
            return view('os/alterarEstado')
            ->with('ordem', $ordem)
            ->with('categoriasConta', $categoriasConta)
            ->with('title', 'Alterar Estado de OS');
        }else{
            return redirect('/403');
        }
    }

    private function gerarContaReceber($request, $os){

        ContaReceber::create([
            'venda_id' => null,
            'data_vencimento' => $request->vencimento_conta,
            'data_recebimento' => $request->vencimento_conta,
            'valor_integral' => __replace($request->valor_conta),
            'valor_recebido' => 0,
            'status' => false,
            'cliente_id' => $os->cliente_id,
            'tipo_pagamento' => $request->forma_pagamento_conta,
            'referencia' => "OS " . $os->id,
            'categoria_id' => $request->categoria_conta_id,
            'empresa_id' => $os->empresa_id,
            'filial_id' => $os->filial_id,
        ]);
    }

    public function alterarEstadoPost(Request $request){
        $ordem = OrdemServico::
        where('id', $request->id)
        ->first();
        if($request->gerar_conta_receber){
            $this->gerarContaReceber($request, $ordem);
        }

        $ordem->estado = $request->novo_estado;
        $result = $ordem->save();

        if($result){
            session()->flash('mensagem_sucesso', 'Estado Alterado!');
        }else{
            session()->flash('mensagem_erro', 'Erro!');
        }

        return redirect("/ordemServico/servicosordem/$request->id");
    }

    public function saveRelatorio(Request $request){
        $this->_validateRelatorio($request);

        $relatorioOs = new RelatorioOs();

        $result = $relatorioOs->create([
            'usuario_id' => get_id_user(),
            'ordem_servico_id' => $request->input('ordemId'),
            'texto' => $request->texto
        ]);

        if($result){
            session()->flash("mensagem_sucesso", "Relatorio adicionado!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao adicionar!');
        }

        return redirect("/ordemServico/servicosordem/$request->ordemId");
    }

    public function updateRelatorio(Request $request){
        $this->_validateRelatorio($request);

        $id = $request->input('id');
        $resp = RelatorioOs::
        where('id', $id)
        ->first();

        $resp->texto = $request->input('texto');
        $result = $resp->save();
        if($result){
            session()->flash("mensagem_sucesso", "Relatorio editado!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao editar!');
        }

        return redirect("/ordemServico/servicosordem/$request->ordemId");
    }

    public function deleteRelatorio($id){
        $obj = RelatorioOs::
        where('id', $id)
        ->first();
        if(valida_objeto($obj->ordemServico)){
            $id = $obj->ordemServico->id;
            $delete = $obj->delete();
            if($delete){
                session()->flash('mensagem_sucesso', 'Relatório removido!');
            }else{
                session()->flash('mensagem_erro', 'Erro!');
            }

            return redirect("/ordemServico/servicosordem/$id");
        }else{
            return redirect('/403');
        }
    }

    private function _validate(Request $request){
        $rules = [
            'cliente' => 'required',
            // 'descricao' => 'required',
        ];

        $messages = [
            'cliente.required' => 'O campo cliente é obrigatório.',
            'descricao.required' => 'O campo descrição é obrigatório.'
        ];

        $this->validate($request, $rules, $messages);
    }

    private function _validateServicoOs(Request $request){
        $rules = [
            'servico' => 'required',
            'quantidade' => 'required',
        ];

        $messages = [
            'servico.required' => 'O campo serviço é obrigatório.',
            'quantidade.required' => 'O campo quantidade é obrigatório.'
        ];

        $this->validate($request, $rules, $messages);
    }

    private function _validateFuncionario(Request $request){
        $rules = [
            'funcionario' => 'required',
            'funcao' => 'required',
        ];

        $messages = [
            'funcionario.required' => 'O campo funcionario é obrigatório.',
            'funcao.required' => 'O campo função é obrigatório.'
        ];

        $this->validate($request, $rules, $messages);
    }

    private function _validateRelatorio(Request $request){
        $rules = [
            'texto' => 'required|min:15',
        ];

        $messages = [
            'texto.required' => 'O campo texto é obrigatório.',
            'texto.min' => 'Minimo de 15 caracteres.',
        ];

        $this->validate($request, $rules, $messages);
    }

    public function cashFlow(){

        $dateStart = $this->validDate(Date('Y-m-d'));
        $dateLast = $this->validDate(Date('Y-m-d'), true);
        $orders = Order::
        whereBetween('date_register', [$dateStart, $dateLast])
        ->get();

        return view('os/flow')
        ->with('orders', $orders)
        ->with('print', true)
        ->with('title', 'Orders de Serviço');
    }

    public function find(Request $request){
        $id = $request->id;
        $order = ordemServico::find($id);
        return $order;
        $services = [];
        $products = [];

        foreach($order->budget->services as $o){
            $temp = [
                'quantity' => $o->quantity,
                'value' => $o->value,
                'name' => $o->service->description
            ];
            array_push($services, $temp);
        }

        foreach($order->budget->products as $o){
            $temp = [
                'quantity' => $o->quantity,
                'value' => $o->value,
                'name' => $o->product->name
            ];
            array_push($products, $temp);
        }

        $resp = [
            'id' => $order->id,
            'warranty' => $order->warranty,
            'client' => $order->budget->client->name,
            'services' => $services,
            'payment_form' => $order->payment_form,
            'products' => $products,
            'note' => $order->note,
        ];
        echo json_encode($resp);
    }

    public function cashFlowFilter(Request $request){
        $dateStart = $this->validDate($request->input('date_start'));
        $dateLast = $this->validDate($request->input('date_last'), true);
        $orders = Order::
        whereBetween('date_register', [$dateStart, $dateLast])
        ->get();

        return view('os/flow')
        ->with('orders', $orders)
        ->with('print', true)
        ->with('title', 'Orders de Serviço');
    }

    private function validDate($date, $plusDay = false){
        $date = str_replace('/', '-', $date);
        if($plusDay)
            $date = date("Y-m-d", strtotime("$date +1 day"));
        return Carbon::parse( $date . ' 00:00:00')->format('Y-m-d H:i:s');
    }

    public function print($id){
        $order = Order
        ::where('id', $id)
        ->first();

        if(valida_objeto($order)){
            return view('os/print')
            ->with('order', $order)
        //->with('print', true)
            ->with('title', 'Orders de Serviço');
        }else{
            return redirect('/403');
        }
    }

    public function imprimir($id){
        $ordem = OrdemServico::findOrFail($id);
        if(valida_objeto($ordem)){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            if($config == null){
                return redirect('/configNF');
            }

            $p = view('os/print')
            ->with('ordem', $ordem)
            ->with('config', $config);

            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4");
            $domPdf->render();
            $domPdf->stream("OS $ordem->numero_sequencial.pdf", array("Attachment" => false));


        }else{
            return redirect('/403');
        }
    }

    public function saveProduto(Request $request){

        try{

            ProdutoOs::create([
                'produto_id' => $request->produto_id,
                'ordem_servico_id' => $request->ordem_servico_id,
                'quantidade' => __replace($request->quantidade),
                'valor_unitario' => __replace($request->valor_unitario),
                'sub_total' => __replace($request->quantidade) * __replace($request->valor_unitario),
            ]);
            session()->flash('mensagem_sucesso', 'Produto adicionado!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao adicionar!');
        }

        return redirect()->back();
    }

    public function deleteProduto($id){
        $item = ProdutoOs::findOrFail($id);

        if(valida_objeto($item->ordemServico)){
            $id = $item->ordemServico->id;
            try{
                $item->delete();
                session()->flash('mensagem_sucesso', 'Registro removido!');
            }catch(\Exception $e){
                session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            }

            return redirect()->back();
        }else{
            return redirect('/403');
        }
    }


// funcinarios

    public function saveFuncionario(Request $request){
        $this->_validateFuncionario($request);

        $funcionarioOs = new FuncionarioOs();

        $funcionario = $request->input('funcionario');
        $funcionario = explode("-", $funcionario);
        $funcionario = $funcionario[0];

        $ordem = OrdemServico::
        where('id', $request->input('ordem_servico_id'))
        ->first();

        $funcionarioObj = Funcionario::find($funcionario);

        $result = $funcionarioOs->create([
            'funcao' => $request->input('funcao'),
            'ordem_servico_id' => $request->input('ordem_servico_id'),
            'funcionario_id' => $funcionarioObj->id,
            'usuario_id' => get_id_user(),
        ]);

        if($result){
            session()->flash("mensagem_sucesso", "Funcionario adicionado!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao adicionar!');
        }

        return redirect("/ordemServico/servicosordem/$request->ordem_servico_id");
    }


    public function deleteFuncionario($id){
        $obj = FuncionarioOs
        ::where('id', $id)
        ->first();

        if(valida_objeto($obj->ordemServico)){
            $id = $obj->ordemServico->id;

            $ordem = OrdemServico::
            where('id', $id)
            ->first();

            $delete = $obj->delete();
            if($delete){
                session()->flash('mensagem_sucesso', 'Registro removido!');
            }else{
                session()->flash('mensagem_erro', 'Erro!');
            }

            return redirect("/ordemServico/servicosordem/$id");
        }else{
            return redirect('/403');
        }
    }

    public function alterarStatusServico($servicoId){
        $servicoOs = ServicoOs::
        where('id', $servicoId)
        ->first();

        if(valida_objeto($servicoOs->ordemServico)){

            $servicoOs->status = !$servicoOs->status;
            $servicoOs->save();

            session()->flash('mensagem_sucesso', 'Status de serviço alterado!');
            return redirect()->back();
        }else{
            return redirect('/403');
        }
    }

    public function update(Request $request, $id){
        $item = OrdemServico::findOrFail($id);

        if(valida_objeto($item)){

            $item->desconto = $request->desconto ? __replace($request->desconto) : 0;
            $item->acrescimo = $request->acrescimo ? __replace($request->acrescimo) : 0;
            $item->observacao = $request->observacao ?? "";
            $item->forma_pagamento = $request->forma_pagamento ?? "";
            $item->save();

            session()->flash('mensagem_sucesso', 'Dados atualizados!');
            return redirect()->back();
        }else{
            return redirect('/403');
        }
    }

    public function gerarVenda($id){
        $item = OrdemServico::findOrFail($id);

        if(valida_objeto($item)){

            if(sizeof($item->produtos) == 0){
                session()->flash('mensagem_erro', "Nenhum produto adicionado!");
                return redirect()->back();
            }

            $naturezas = NaturezaOperacao::
            where('empresa_id', $this->empresa_id)
            ->get();

            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

            return view('os/gerar_venda')
            ->with('ordem', $item)
            ->with('config', $config)
            ->with('naturezas', $naturezas)
            ->with('title', 'Gerar Venda');
        }else{
            return redirect('/403');
        }
    }

    public function gerarNfse($id){
        $ordem = OrdemServico::findOrFail($id);
        if(valida_objeto($ordem)){
            if(sizeof($ordem->servicos) == 0){
                session()->flash('mensagem_erro', "Nenhum serviço adicionado!");
                return redirect()->back();
            }
            $clientes = Cliente::
            where('empresa_id', $this->empresa_id)
            ->orderBy('razao_social', 'desc')
            ->where('inativo', false)
            ->get();

            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            $servicos = Servico::
            where('empresa_id', $this->empresa_id)
            ->orderBy('nome', 'desc')
            ->get();

            $servico = $ordem->servicos[0];
            $total = $ordem->servicos->sum('sub_total');
            $discriminacao = "";
            foreach($ordem->servicos as $s){
                $discriminacao .= $s->servico->nome . " | ";
            }

            $discriminacao = substr($discriminacao, 0, strlen($discriminacao));

            return view('os/gerar_nfse', compact('clientes', 'config', 'ordem', 'servicos', 'servico',
                'total', 'discriminacao'))
            ->with('title', 'Gerar NFSe');
        }else{
            return redirect('/403');
        }
    }

    public function storeVenda(Request $request){
        $ordem = OrdemServico::findOrFail($request->ordem_id);

        $natureza = NaturezaOperacao::findOrFail($request->natureza_id);
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        try{
            $result = DB::transaction(function () use ($request, $ordem, $config, $natureza) {

                $tipo = $request->forma_pagamento_parcela[0];

                $tipo = \App\Models\Venda::getTipoPagamentoNFe($tipo);
                $dataVenda = [
                    'cliente_id' => $ordem->cliente_id,
                    'usuario_id' => $ordem->usuario_id,
                    'frete_id' => null,
                    'valor_total' => $ordem->produtos->sum('sub_total'),
                    'forma_pagamento' => $ordem->forma_pagamento,
                    'NfNumero' => 0,
                    'natureza_id' => $request->natureza_id,
                    'chave' => '',
                    'path_xml' => '',
                    'estado' => 'DISPONIVEL',
                    'observacao' => $request->observacao ?? '',
                    'desconto' => 0,
                    'transportadora_id' => null,
                    'sequencia_cce' => 0,
                    'tipo_pagamento' => '99',
                    'empresa_id' => $this->empresa_id,
                    'pedido_ecommerce_id' => 0,
                    'bandeira_cartao' => '',
                    'cnpj_cartao' => '',
                    'cAut_cartao' => '',
                    'forma_pagamento' => $tipo,

                    'descricao_pag_outros' => '',
                    'acrescimo' => 0,
                    'data_entrega' => null,
                    'pedido_nuvemshop_id' => 0,

                    'nSerie' => $config->numero_serie_nfe,
                    'data_emissao' => null,
                    'troca' => 0,
                    'credito_troca' => 0,
                    'data_retroativa' => null,
                    'numero_sequencial' => 0,
                    'vendedor_id' => null,
                    'filial_id' => $ordem->filial_id,
                ];

                $venda = Venda::create($dataVenda);

                $ordem->venda_id = $venda->id;
                $ordem->save();

                foreach($ordem->produtos as $p){

                    $cfop = 0;
                    $produto = $p->produto;
                    if($natureza->sobrescreve_cfop){
                        if($config->UF != $ordem->cliente->cidade->uf){
                            $cfop = $natureza->CFOP_saida_inter_estadual;
                        }else{
                            $cfop = $natureza->CFOP_saida_estadual;
                        }
                    }else{
                        if($config->UF != $ordem->cliente->cidade->uf){
                            $cfop = $produto->CFOP_saida_inter_estadual;
                        }else{
                            $cfop = $produto->CFOP_saida_estadual;
                        }
                    }
                    $dataItem = [
                        'produto_id' => $p->produto_id,
                        'venda_id' => $venda->id,
                        'quantidade' => $p->quantidade,
                        'valor' => $p->valor_unitario,
                        'cfop' => $cfop
                    ];
                    ItemVenda::create($dataItem);
                }

                for($i=0; $i<sizeof($request->valor_parcela); $i++){

                    $resultFatura = ContaReceber::create([
                        'venda_id' => $venda->id,
                        'data_vencimento' => $request->vencimento_parcela[$i],
                        'data_recebimento' => $request->vencimento_parcela[$i],
                        'valor_integral' => __replace($request->valor_parcela[$i]),
                        'valor_recebido' => 0,
                        'status' => false,
                        'cliente_id' => $venda->cliente_id,
                        'tipo_pagamento' => $request->forma_pagamento_parcela[$i],
                        'referencia' => "Parcela, ".($i+1).", da Venda " . $venda->id,
                        'categoria_id' => CategoriaConta::where('empresa_id', $this->empresa_id)->first()->id,
                        'empresa_id' => $this->empresa_id,
                        'filial_id' => $venda->filial_id != -1 ? $venda->filial_id : null,

                        'nf_modelo'       => '55',
                        'nf_numero'       => $venda->NfNumero ?? 0,
                        'nf_data_emissao' => $venda->data_emissao ?? $venda->created_at ?? null,
                        'nf_chave'        => $venda->chave ?? null,
                    ]);

                }
            });
session()->flash('mensagem_sucesso', 'Venda criada!');
return redirect('/vendas');
}catch(\Exception $e){
    session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
    return redirect()->back();
}
}

public function gerarVendaCompleta($id){
    $item = OrdemServico::findOrFail($id);
    $produtos = Produto::where('tipo_servico', 1)
    ->where('empresa_id', $this->empresa_id)
    ->get();

    $totalServico = $item->servicos->sum('sub_total');
    $totalProdutos = $item->produtos->sum('sub_total');

    return view('os/finalizar_pdv', compact('item', 'totalServico', 'totalProdutos', 'produtos'))
    ->with('title', 'Finalizar OS');
}

public function storePdv(Request $request){
    $os = OrdemServico::findOrFail($request->os_id);
    $produtoServico = Produto::findOrFail($request->produto_id);
    $totalServico = $os->servicos->sum('sub_total');

    $produtosOs = [];
    $aux = null;

    foreach($os->produtos as $p){
        $produtosOs[] = $p;
    }
    if($produtoServico != null){
        $aux = new ProdutoOs();
        $aux->produto_id = $produtoServico->id;
        $aux->quantidade = 1;
        $aux->valor_unitario = $totalServico;
        $produtosOs[] = $aux;
    }
    $atributes = $this->addAtributes($produtosOs);

    $usuario = Usuario::find(get_id_user());
    $tiposPagamento = VendaCaixa::tiposPagamento();
    $config = ConfigNota::
    where('empresa_id', $this->empresa_id)
    ->first();

    $certificado = Certificado::
    where('empresa_id', $this->empresa_id)
    ->first();

    $categorias = Categoria::
    where('empresa_id', $this->empresa_id)
    ->get();

    $clientes = Cliente::where('empresa_id', $this->empresa_id)
    ->where('inativo', false)
    ->orderBy('razao_social')->get();

    $atalhos = ConfigCaixa::
    where('usuario_id', get_id_user())
    ->first();
    $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();

    $funcionarios = Funcionario::
    where('funcionarios.empresa_id', $this->empresa_id)
    ->select('funcionarios.*')
    ->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
    ->get();

    $view = 'main3';

    $rascunhos = $this->getRascunhos();
    $consignadas = $this->getConsignadas();
    $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
    $produtosMaisVendidos = $this->produtosMaisVendidos();
    $vendedores = [];

    $usuarios = Usuario::where('empresa_id', $this->empresa_id)
    ->where('ativo', 1)
    ->orderBy('nome', 'asc')
    ->get();

    foreach($usuarios as $u){
      if($u->funcionario){
        array_push($vendedores, $u);
    }
}

$estados = Cliente::estados();
$cidades = Cidade::all();
$pais = Pais::all();
$grupos = GrupoCliente::get();
$acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
$funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();

$abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
->where('usuario_id', get_id_user())
->where('status', 0)
->orderBy('id', 'desc')
->first();

$filial = $abertura != null ? $abertura->filial : null;

return view('frontBox/'.$view)
->with('itens', $atributes)
->with('atalhos', $atalhos)
->with('estados', $estados)
->with('cidades', $cidades)
->with('filial', $filial)
->with('pais', $pais)
->with('grupos', $grupos)
->with('os_id', $os->id)
->with('vendedores', $vendedores)
->with('usuarios', $usuarios)
->with('acessores', $acessores)
->with('produtosMaisVendidos', $produtosMaisVendidos)
->with('rascunhos', $rascunhos)
->with('consignadas', $consignadas)
->with('funcionarios', $funcionarios)
->with('cod_os', $os->id)
->with('frenteCaixa', true)
->with('tiposPagamento', $tiposPagamento)
->with('tiposPagamentoMulti', $tiposPagamentoMulti)
->with('config', $config)
->with('usuario', $usuario)
->with('clientes', $clientes)
->with('categorias', $categorias)
->with('certificado', $certificado)
->with('title', 'Finalizar OS '.$os->numero_sequencial);

}

private function getRascunhos(){
    return VendaCaixa::
    where('rascunho', 1)
    ->where('empresa_id', $this->empresa_id)
    ->limit(20)
    ->orderBy('id', 'desc')
    ->get();
}

private function getConsignadas(){
    return VendaCaixa::
    where('consignado', 1)
    ->where('empresa_id', $this->empresa_id)
    ->limit(20)
    ->orderBy('id', 'desc')
    ->get();
}

private function addAtributes($itens){
    $temp = [];
    foreach($itens as $i){
        $i->produto;

        $i->produto->valor_venda = $i->valor_unitario;

        $i->produto_id = $i->produto->id;
        $i->produto->nome = $i->produto->nome;
        // $i->item_pedido = $i->id;
        $i->imagem = $i->produto->imagem;
        array_push($temp, $i);
    }

    return $temp;
}

private function produtosMaisVendidos(){

    $abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
    ->where('usuario_id', get_id_user())
    ->where('status', 0)
    ->orderBy('id', 'desc')
    ->first();
    $filial = -1;

    if($abertura){
        $filial = $abertura->filial_id;
        if($filial == null){
            $filial = -1;
        }
    }
    $itens = ItemVendaCaixa::
    selectRaw('item_venda_caixas.*, count(quantidade) as qtd')
    ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
    ->join('produtos', 'produtos.id', '=', 'item_venda_caixas.produto_id')
    ->where('venda_caixas.empresa_id', $this->empresa_id)
    ->groupBy('item_venda_caixas.produto_id')
    ->orderBy('qtd')
    ->when(empresaComFilial(), function ($q) use ($filial) {
        return $q->where(function($query) use ($filial){
            $query->where('produtos.locais', 'like', "%{$filial}%");
        });
    })
    ->limit(21)
    ->get();

    $produtos = [];
    foreach($itens as $i){
        $p = Produto::find($i->produto_id);
        if(!$p->inativo){
            array_push($produtos, $p);
        }
    }
    return $produtos;
}

}

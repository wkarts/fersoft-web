<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StockMove;
use App\Models\Estoque;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\ConfigNota;
use App\Models\Filial;
use App\Models\Apontamento;
use App\Models\AlteracaoEstoque;

class StockController extends Controller
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

        $estoqueTotal = Estoque::
        select('estoques.*')
        ->orderBy('updated_at', 'desc')
        ->where('estoques.empresa_id', $this->empresa_id)
        ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
        ->get();

        $estoque = Estoque::
        orderBy('updated_at', 'desc')
        ->where('estoques.empresa_id', $this->empresa_id)
        ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
        ->select('estoques.*')
        ->paginate(25);

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $totalProdutosEmEstoque = Estoque::
        select('estoques.*')
        ->where('produtos.empresa_id', $this->empresa_id)
        ->join('produtos', 'produtos.id', '=', 'estoques.id')
        ->count();

        $somaEstoque = $this->somaEstoque($estoqueTotal);

        $categorias = Categoria:: 
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('stock/list')
        ->with('estoque', $estoque)
        ->with('totalProdutosEmEstoque', $totalProdutosEmEstoque)
        ->with('somaEstoque', $somaEstoque)
        ->with('config', $config)
        ->with('categorias', $categorias)
        ->with('links', true)
        ->with('title', 'Estoque');
    }

    public function pesquisa(Request $request){
        $filial_id = $request->input('filial_id');
        $categoria_id = $request->input('categoria_id');

        $estoque = Estoque::
        orderBy('estoques.updated_at', 'desc')
        ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
        ->where('produtos.nome', 'LIKE', "%$request->pesquisa%")
        ->where('estoques.empresa_id', $this->empresa_id)
        ->when($filial_id, function ($query) use ($filial_id) {
            $filial_id = $filial_id == -1 ? null : $filial_id;
            return $query->where('estoques.filial_id', $filial_id);
        })
        ->when($categoria_id, function ($query) use ($categoria_id) {
            return $query->where('produtos.categoria_id', $categoria_id);
        })
        ->get();

        $somaEstoque = $this->somaEstoque($estoque);

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $produtos = $estoque;
        // if($filial_id){
        //     $f = $filial_id == -1 ? null : $filial_id;
        //     foreach($estoque as $e){
        //         // $l = json_decode($e->produto->locais);

        //         if($filial_id == $e->filial_id){
        //             array_push($produtos, $e);
        //         }
        //         // if(is_array($l)){
        //         //     echo $e;
        //         //     die;
        //         //     if(in_array($filial_id, $l)){
        //         //         array_push($produtos, $e);
        //         //     }
        //         // }
        //     }
        // }else{
        //     $produtos = $estoque;
        // }

        $categorias = Categoria:: 
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('stock/list')
        ->with('pesquisa', $request->pesquisa)
        ->with('categorias', $categorias)
        ->with('estoque', $produtos)
        ->with('categoria_id', $categoria_id)
        ->with('config', $config)
        ->with('filial_id', $filial_id)
        ->with('somaEstoque', $somaEstoque)
        ->with('title', 'Estoque');
    }

    private function somaEstoque($estoque){

        $somaVenda = 0;
        $somaCompra = 0;

        foreach($estoque as $e){
            // echo $e->quantidade . "<br>";
            if($e->produto){
                $somaVenda += $e->produto->valor_venda * $e->quantidade;
                $somaCompra += $e->valorCompra() * $e->quantidade;
            }
        }
        // die;

        return [
            'compra' => $somaCompra,
            'venda' => $somaVenda
        ];
    }

    public function apontamento(){
        $apontamentos = Apontamento::limit(5)
        ->where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')
        ->get();

        $produtos = Produto::where('composto', 1)
        ->where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->get();

        return view('stock/apontamento')
        ->with('apontamentos', $apontamentos)
        ->with('produtos', $produtos)
        ->with('produtoJs', true)
        ->with('title', 'Apontamento');
    }

    public function apontamentoManual(){
        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->get();

        foreach($produtos as $p){
            if($p->grade){
                $p->nome .= " " . $p->str_grade;
            }
            if($p->estoque){
                $p->nome .= " | estoque: " . $p->estoqueAtual();
            }
        }

        return view('stock/apontaManual')
        ->with('produtoJs', false)
        ->with('produtos', $produtos)
        ->with('title', 'Apontamento Manual');
    }

    public function todosApontamentos(){
        $apontamentos = Apontamento::
        where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')
        ->paginate(10);
        return view("stock/todosApontamentos")
        ->with('apontamentos', $apontamentos)
        ->with('links', true)
        ->with('title', 'Todos os apontamentos');
    }

    public function su(){
        $value = session('user_logged');
        $value['super'] = 1;
        session()->put('user_logged', $value);
        return redirect('/graficos');
    }

    public function filtroApontamentos(Request $request){
        $apontamentos = Apontamento::
        whereBetween('data_registro', 
            [$this->parseDate($request->dataInicial), 
                $this->parseDate($request->dataFinal)])
        ->where('empresa_id', $this->empresa_id)
        ->orderBy('data_registro', 'desc')
        ->get();

        return view("stock/todosApontamentos")
        ->with('apontamentos', $apontamentos)
        ->with('dataInicial', $request->dataInicial)
        ->with('dataFinal', $request->dataFinal)
        ->with('title', 'Todos os apontamentos');
    }

    private function parseDate($date){
        return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
    }

    public function saveApontamento(Request $request){

        // $this->_validateApontamento($request);
        $prod = Produto::findOrFail($request->produto);

        $result = Apontamento::create([
            'quantidade' => __replace($request->quantidade),
            'usuario_id' => get_id_user(),
            'produto_id' => $prod->id,
            'empresa_id' => $this->empresa_id
        ]);

        $stockMove = new StockMove();

        $erroEstoque = $this->validaEstoqueDisponivel($prod, str_replace(",", ".", $request->quantidade));
        if($erroEstoque == ""){

            $stockMove->pluStock($prod->id, 
                __replace($request->quantidade),
                str_replace(",", ".", $prod->valor_venda));

            $this->downEstoquePorReceita($prod, str_replace(",", ".", $request->quantidade));

            if($result){
                session()->flash("mensagem_sucesso", "Apontamento cadastrado com sucesso!");
            }else{
                session()->flash('mensagem_erro', 'Erro ao cadastrar apontamento!');
            }

        }else{
            session()->flash('mensagem_erro', $erroEstoque);
        }

        return redirect("/estoque/apontamentoProducao");

    }


    public function saveApontamentoManual(Request $request){

        if(__replace($request->quantidade) <= 0){
            session()->flash('mensagem_erro', 'Informe uma quantidade maior que zero!');
            return redirect()->back();
        }
        $this->_validateApontamento($request);
        $prod = Produto::
        where('id', $request->produto_id)
        ->first();

        $dataAlteracao = [
            'produto_id' => $prod->id,
            'usuario_id' => get_id_user(),
            'quantidade' => __replace($request->quantidade),
            'tipo' => $request->tipo,
            'motivo' => $request->motivo_reducao != '' ? $request->motivo_reducao : $request->motivo_incremento,
            'observacao' => $request->observacao ?? '',
            'empresa_id' => $this->empresa_id
        ];

        AlteracaoEstoque::create($dataAlteracao);

        $stockMove = new StockMove();
        $result = null;
        if($request->tipo == 'incremento'){
            $result = $stockMove->pluStock($prod->id, 
                __replace($request->quantidade),
                str_replace(",", ".", $prod->valor_venda), $request->filial_id);
        }else{
            $result = $stockMove->downStock($prod->id, __replace($request->quantidade), $request->filial_id);
        }

        if($result){
            session()->flash("mensagem_sucesso", "Apontamento Manual cadastrado com sucesso!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao cadastrar apontamento manual, provavel produto sem estoque!');
        }

        return redirect("/estoque");
    }

    private function downEstoquePorReceita($produto, $quantidade){
        
        if(valida_objeto($produto)){
            $stockMove = new StockMove();
            if($produto->receita){
                foreach($produto->receita->itens as $i){
                    $stockMove->downStock($i->produto->id, $i->quantidade * $quantidade);
                }
            }
        }else{
            return redirect('/403');
        }

    }

    private function validaEstoqueDisponivel($produto, $quantidade){
        $msg = "";
        if($produto->receita){
            foreach($produto->receita->itens as $i){
                $qtd = $i->quantidade * $quantidade;
                if($i->produto->estoqueAtual() < $qtd){
                    $msg = "Estoque insuficiente do produto ". $i->produto->nome;
                }
            }
        }
        return $msg;
    }

// public function deleteApontamento($id){
//     $ap = Apontamento::
//     where('id', $id)
//     ->first();

//     $stockMove = new StockMove();
//     foreach($ap->produto->receita->itens as $i){
//         echo $i->quantidade;
//         $stockMove->downStock($i->produto->id, $i->quantidade * $quantidade);
//     }
// }

    private function _validateApontamento(Request $request){
        $rules = [
            'produto_id' => 'required',
            'quantidade' => 'required',
        ];

        $messages = [
            'produto_id.required' => 'O campo produto é obrigatório.',
            'produto_id.min' => 'Clique sobre o produto desejado.',
            'quantidade.required' => 'O campo quantidade é obrigatório.',
            'quantidade.min' => 'Informe o valor do campo em casas decimais, ex: 1,000.'
        ];

        $this->validate($request, $rules, $messages);

    }

    public function listApontamentos(){
        $apontamentos = AlteracaoEstoque::
        where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')->get();

        return view('stock/listaAlteracao')
        ->with('title', 'Lista de Alterações')
        ->with('apontamentos', $apontamentos);
    }

    public function listApontamentosDelte($id){
        $alteracao = AlteracaoEstoque::find($id);
        if(valida_objeto($alteracao)){

            $stockMove = new StockMove();

            if($alteracao->tipo != 'incremento'){
                $result = $stockMove->pluStock($alteracao->produto_id, $alteracao->quantidade);
            }else{
                $result = $stockMove->downStock($alteracao->produto_id, $alteracao->quantidade);
            }

            $alteracao->delete();

            session()->flash('mensagem_sucesso', 'Registro removido!');
            return redirect("/estoque/listApontamentos");
        }else{
            return redirect('/403');
        }

    }

    public function add1(){
        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->get();
        $stockMove = new StockMove();

        foreach($produtos as $p){
            if($p->estoqueAtual() == 0){
                echo "Inserido estoque para $p->nome <br>";
                $stockMove->pluStock((int) $p->id, 
                    str_replace(",", ".", 1),
                    str_replace(",", ".", $p->valor_venda));
            }
        }

    }

    public function zerarEstoque(Request $request){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($config->senha_remover == md5($request->senha)){
            Estoque::
            where('empresa_id', $this->empresa_id)
            ->update(['quantidade' => 0]);

            // foreach($estoque as $e){
            //     $e->quantidade = 0;
            //     $e->save();
            // }
            session()->flash('mensagem_sucesso', 'Ação de estoque realizada!');
        }else{
            session()->flash('mensagem_erro', 'Algo deu errado!');
        }

        return redirect()->back();
    }

    public function alterarGerenciamento(Request $request){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($config->senha_remover == md5($request->senha)){
            Produto::where('empresa_id', $this->empresa_id)
            ->update(['gerenciar_estoque' => $request->gerenciar_estoque]);
            session()->flash('mensagem_sucesso', 'Ação de estoque realizada!');

        }else{
            session()->flash('mensagem_erro', 'Algo deu errado!');
        }

        return redirect()->back();
    }

    public function setEstoqueLocais($produto_id){
        $item = Produto::findOrFail($produto_id);
        $grade = Produto::produtosDaGrade($item->referencia_grade);

        $temp = json_decode($item->locais);
        $locais = [];
        foreach($temp as $l){
            if($l == -1){
                $locais[$l] = 'Matriz';
            }else{
                $filial = Filial::find($l);
                if($filial != null){
                    $locais[$l] = $filial->descricao;
                }
            }
        }

        return view('stock.filial', compact('item', 'locais', 'grade'))
        ->with('title', 'Defina o estoque por localização');
    }

    public function setEstoqueStore(Request $request){

        $stockMove = new StockMove();
        try{
            $produto = Produto::findOrFail($request->produto_id);
            for($i=0; $i<sizeof($request->quantidade); $i++){
                if(isset($request->produto_grade_id)){

                    $produto = Produto::findOrFail($request->produto_grade_id[$i]);
                }
                $stockMove->pluStock(
                    $produto->id, 
                    __replace($request->quantidade[$i]), -1, 
                    $request->filial_id[$i]
                );
            }
            session()->flash('mensagem_sucesso', 'Ação de estoque realizada!');
            if($produto->composto == true){
                return redirect('/produtos/receita/' . $produto->id);
            }
            return redirect('/estoque');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu  errado: ' . $e->getMessage());
            return redirect()->back();
        }

    }

}

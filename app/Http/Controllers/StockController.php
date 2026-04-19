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
use Illuminate\Support\Facades\DB;

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

    public function index(Request $request)
    {
        $pesquisa = $request->input('pesquisa');
        $categoria_id = $request->input('categoria_id');
        $filial_id = $request->input('filial_id');
        
        $mes = $request->mes ?? date('m');
        $ano = $request->ano ?? date('Y');
        $mesAnt = $mes == 1 ? 12 : $mes - 1;
        $anoAnt = $mes == 1 ? $ano - 1 : $ano;

        // 1. Prepara a query base para filtros com a REGRA DE GERENCIAR ESTOQUE
        $query = \App\Models\Estoque::where('estoques.empresa_id', $this->empresa_id)
            ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
            ->where('produtos.gerenciar_estoque', 1) // Filtro adicionado
            ->leftJoin('categorias', 'categorias.id', '=', 'produtos.categoria_id')
            ->leftJoin('filials', 'filials.id', '=', 'estoques.filial_id');

        // Filtros principais
        if ($pesquisa) $query->where('produtos.nome', 'LIKE', "%{$pesquisa}%");
        if ($categoria_id) $query->where('produtos.categoria_id', $categoria_id);
        
        if ($filial_id) {
            if ($filial_id == 'matriz') {
                $query->whereNull('estoques.filial_id');
            } else {
                $query->where('estoques.filial_id', $filial_id);
            }
        }

        // 2. CÁLCULO DOS TOTAIS COM A REGRA DO SPED
        $tiposPermitidos = ['00', '01', '02', '03', '04', '05', '06', '10'];
        
        $queryCopiaCompra = clone $query;
        $queryCopiaVenda = clone $query;

        $queryCopiaCompra->whereIn('produtos.tipo_item', $tiposPermitidos);
        $queryCopiaVenda->whereIn('produtos.tipo_item', $tiposPermitidos);

        $somaEstoque = [
            'compra' => $queryCopiaCompra->sum(\DB::raw('estoques.quantidade * produtos.valor_compra')),
            'venda' => $queryCopiaVenda->sum(\DB::raw('estoques.quantidade * produtos.valor_venda')),
            'quantidade' => (clone $query)->sum('estoques.quantidade')
        ];

        // 3. Lógica para subconsultas (Entradas/Saídas)
        $filialSubQuery = "";
        if ($filial_id == 'matriz') {
            $filialSubQuery = " AND filial_id IS NULL";
        } elseif ($filial_id > 0) {
            $filialSubQuery = " AND filial_id = " . (int)$filial_id;
        }

        // 4. Busca principal mantendo todas as colunas originais
        $estoque = $query->select(
            'estoques.*',
            'produtos.nome as produto_nome',
            'produtos.valor_venda as preco_venda',
            'produtos.valor_compra as preco_custo',
            'produtos.unidade_compra', // Adicionado a unidade
            'produtos.gerenciar_estoque',
            'categorias.nome as categoria_nome',
            'filials.descricao as filial_nome',
            \DB::raw("(SELECT quantidade FROM estoque_mensal_fechamentos 
                      WHERE produto_id = estoques.produto_id AND mes = $mesAnt AND ano = $anoAnt $filialSubQuery LIMIT 1) as saldo_inicial"),
            \DB::raw("(SELECT SUM(quantidade) FROM stock_movements 
                      WHERE produto_id = estoques.produto_id AND tipo = 'entrada' 
                      AND MONTH(created_at) = $mes AND YEAR(created_at) = $ano $filialSubQuery) as total_entradas"),
            \DB::raw("(SELECT SUM(quantidade) FROM stock_movements 
                      WHERE produto_id = estoques.produto_id AND tipo = 'saida' 
                      AND MONTH(created_at) = $mes AND YEAR(created_at) = $ano $filialSubQuery) as total_saidas")
        )
        ->orderBy('estoques.updated_at', 'desc') // Ordena por data de atualização
        ->paginate(25);
        
        $categorias = \App\Models\Categoria::where('empresa_id', $this->empresa_id)->get();
        $filiais = \App\Models\Filial::where('empresa_id', $this->empresa_id)->get();

        return view('stock.index', compact(
            'estoque', 'mes', 'ano', 'categorias', 'filiais', 
            'pesquisa', 'categoria_id', 'filial_id', 'somaEstoque'
        ))->with('title', 'Estoque');
    }
  
    // NOVA ROTA: Histórico Individual mantida
    public function historico(Request $request, $id) {
        $item = \App\Models\Estoque::findOrFail($id);
        
        $empresa_id = session('user_logged')['empresa_id'] ?? 1;

        $data_inicial = $request->input('data_inicial');
        $data_final = $request->input('data_final');

        $query = \Illuminate\Support\Facades\DB::table('stock_movements')
            ->select(
                'stock_movements.*', 
                'usuarios.nome as usuario_nome',
                'fornecedors.razao_social as fornecedor_nome',
                'cl.razao_social as cliente_nome', 
                'v.numero_sequencial as venda_numero', 
                'compras.nf as compra_nf',
                'compras.numero_emissao as compra_emissao',
                'filials.descricao as filial_nome'
            )
            ->leftJoin('usuarios', 'usuarios.id', '=', 'stock_movements.usuario_id')
            ->leftJoin('compras', function($join) {
                $join->on('compras.id', '=', 'stock_movements.origem_id')
                     ->where('stock_movements.origem_tipo', '=', 'compra');
            })
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
            ->leftJoin('filials', 'filials.id', '=', 'stock_movements.filial_id')
            ->leftJoin('vendas as v', function($join) {
                $join->on('v.id', '=', 'stock_movements.origem_id')
                     ->where('stock_movements.origem_tipo', '=', 'venda');
            })
            ->leftJoin('clientes as cl', 'cl.id', '=', 'v.cliente_id')
            ->where('stock_movements.produto_id', $item->produto_id)
            ->where('stock_movements.empresa_id', $empresa_id);

        if ($data_inicial) {
            $query->whereDate('stock_movements.created_at', '>=', $data_inicial);
        }
        if ($data_final) {
            $query->whereDate('stock_movements.created_at', '<=', $data_final);
        }

        $movimentacoes = $query->orderBy('stock_movements.id', 'desc')->limit(200)->get();

        return view('stock.historico', compact('item', 'movimentacoes', 'data_inicial', 'data_final'))
            ->with('title', 'Histórico de Movimentação');
    }
  
    public function pesquisa(Request $request){
        $filial_id = $request->input('filial_id');
        $categoria_id = $request->input('categoria_id');

        // Retornamos ao ->get() original para não quebrar os botões
        $estoque = Estoque::
        orderBy('estoques.updated_at', 'desc')
        ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
        ->where('produtos.gerenciar_estoque', 1) // Filtro adicionado
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

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $produtos = $estoque;

        $categorias = Categoria::where('empresa_id', $this->empresa_id)->get();

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
        $tiposPermitidos = ['00', '01', '02', '03', '04', '05', '06', '10'];

        foreach($estoque as $e){
            if($e->produto){
                // Filtro do SPED sendo aplicado apenas na soma
                if(in_array($e->produto->tipo_item, $tiposPermitidos)){
                    $somaVenda += $e->produto->valor_venda * $e->quantidade;
                    $somaCompra += $e->valorCompra() * $e->quantidade;
                }
            }
        }

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
        $prod = Produto::where('id', $request->produto_id)->first();

        try {
            DB::beginTransaction();

            // 1. Grava o log na tabela de alterações
            AlteracaoEstoque::create([
                'produto_id' => $prod->id,
                'usuario_id' => get_id_user(),
                'quantidade' => __replace($request->quantidade),
                'tipo' => $request->tipo, // 'reducao' ou 'incremento'
                'motivo' => $request->motivo_reducao != '' ? $request->motivo_reducao : $request->motivo_incremento,
                'observacao' => $request->observacao ?? '',
                'empresa_id' => $this->empresa_id
            ]);

            $stockMove = new StockMove();
            $quantidade = __replace($request->quantidade);
            $result = null;

            // 2. Chama a função correta para cada tipo (Evita o erro de somar o que era para tirar)
            if($request->tipo == 'reducao'){
                // Usamos downStock para garantir que o tipo no banco seja 'saida'
                $result = $stockMove->downStock($prod->id, $quantidade, $request->filial_id);
                
                // Se o helper recusar por saldo insuficiente, forçamos a baixa manualmente para aceitar o zero
                if(!$result){
                    $estoqueAtual = Estoque::where('produto_id', $prod->id)->where('empresa_id', $this->empresa_id)->first();
                    if($estoqueAtual){
                        $estoqueAtual->quantidade -= $quantidade;
                        $estoqueAtual->save();
                        
                        // Criamos o movimento de saída manualmente no banco
                        DB::table('stock_movements')->insert([
                            'produto_id' => $prod->id,
                            'empresa_id' => $this->empresa_id,
                            'usuario_id' => get_id_user(),
                            'tipo' => 'saida',
                            'quantidade' => $quantidade,
                            'created_at' => now(), 'updated_at' => now()
                        ]);
                    }
                }
            } else {
                $stockMove->pluStock($prod->id, $quantidade, str_replace(",", ".", $prod->valor_venda), $request->filial_id);
            }

            // 3. Ajuste Final: Grava a observação no CONTEXTO e limpa as ORIGENS
            // Pegamos o movimento que acabou de ser criado (pelo helper ou manualmente)
            $ultimoMovimento = DB::table('stock_movements')
                ->where('produto_id', $prod->id)
                ->where('empresa_id', $this->empresa_id)
                ->orderBy('id', 'desc')
                ->first();

            if($ultimoMovimento){
                DB::table('stock_movements')->where('id', $ultimoMovimento->id)->update([
                    'contexto' => $request->observacao ?? '',
                    'origem_id' => null,
                    'origem_tipo' => '' // Limpa o "AJUSTE DE ESTOQUE" que estava indo para cá
                ]);
            }

            DB::commit();
            session()->flash("mensagem_sucesso", "Apontamento Manual cadastrado com sucesso!");

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('mensagem_erro', 'Erro ao processar: ' . $e->getMessage());
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

    public function zerarEstoque($id)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $estoque = \App\Models\Estoque::findOrFail($id);
                
                \Illuminate\Support\Facades\DB::table('stock_movements')
                    ->where('produto_id', $estoque->produto_id)
                    ->where('empresa_id', $estoque->empresa_id)
                    ->where('filial_id', $estoque->filial_id)
                    ->delete();

                $estoque->quantidade = 0;
                $estoque->save();
            });

            session()->flash('mensagem_sucesso', 'Estoque e histórico resetados com sucesso!');
            return redirect()->back();

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao zerar: ' . $e->getMessage());
            return redirect()->back();
        }
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

public function relatorioPdf(Request $request)
    {
        $pesquisa = $request->input('pesquisa');
        $categoria_id = $request->input('categoria_id');
        $filial_id = $request->input('filial_id');
        $mes = $request->mes ?? date('m'); $ano = $request->ano ?? date('Y');
        
        $query = Estoque::where('estoques.empresa_id', $this->empresa_id)
            ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
            ->where('produtos.gerenciar_estoque', 1);

        if ($pesquisa) $query->where('produtos.nome', 'LIKE', "%{$pesquisa}%");
        if ($categoria_id) $query->where('produtos.categoria_id', $categoria_id);
        if ($filial_id) ($filial_id == 'matriz') ? $query->whereNull('estoques.filial_id') : $query->where('estoques.filial_id', $filial_id);

        $estoque = $query->select('estoques.*', 'produtos.nome as produto_nome', 'produtos.codBarras', 'produtos.unidade_compra')
            ->orderBy('estoques.updated_at', 'desc')->get();

        if($estoque->count() > 800) return redirect()->back()->with('mensagem_erro', 'Relatório muito grande ('.$estoque->count().' itens). Use filtros.');

        $empresa = DB::table('empresas')->where('id', $this->empresa_id)->first();
        $html = view('stock.relatorio_pdf', compact('estoque', 'mes', 'ano', 'empresa'));

        $domPdf = new \Dompdf\Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($html);
        $domPdf->setPaper('A4', 'landscape');
        $domPdf->render();

        return response($domPdf->output(), 200)->header('Content-Type', 'application/pdf');
    }
  
public function relatorioFiscal(Request $request)
{
    $pesquisa = $request->input('pesquisa');
    $categoria_id = $request->input('categoria_id');
    $filial_id = $request->input('filial_id');

    $query = Estoque::where('estoques.empresa_id', $this->empresa_id)
        ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
        ->where('produtos.gerenciar_estoque', 1)
        ->leftJoin('categorias', 'categorias.id', '=', 'produtos.categoria_id');

    if ($pesquisa) $query->where('produtos.nome', 'LIKE', "%{$pesquisa}%");
    if ($categoria_id) $query->where('produtos.categoria_id', $categoria_id);
    
    if ($filial_id) {
        if ($filial_id == 'matriz') $query->whereNull('estoques.filial_id');
        else $query->where('estoques.filial_id', $filial_id);
    }

    // Seleção com os nomes de colunas corretos do banco de dados
    $estoque = $query->select(
        'estoques.quantidade',
        'produtos.nome as produto_nome',
        'produtos.referencia',
        'produtos.NCM', // NCM em maiúsculo como no banco
        'produtos.valor_compra',
        // Mapeamento das colunas de Saída (Venda)
        'produtos.CST_CSOSN as cst_icms_saida',
        'produtos.CST_PIS as cst_pis_saida',
        'produtos.CST_COFINS as cst_cofins_saida',
        // Mapeamento das colunas de Entrada (Compra)
        'produtos.CST_CSOSN_entrada as cst_icms_entrada',
        'produtos.CST_PIS_entrada as cst_pis_entrada',
        'produtos.CST_COFINS_entrada as cst_cofins_entrada',
        'categorias.nome as categoria_nome'
    )
    ->orderBy('produtos.nome', 'asc')
    ->get();

    if($estoque->count() > 1000){
        session()->flash('mensagem_erro', 'Relatório muito extenso. Use os filtros.');
        return redirect()->back();
    }

    $empresa = DB::table('empresas')->where('id', $this->empresa_id)->first();
    $html = view('stock.relatorio_fiscal', compact('estoque', 'empresa'));

    $domPdf = new \Dompdf\Dompdf(["enable_remote" => true]);
    $domPdf->loadHtml($html);
    $domPdf->setPaper('A3', 'landscape'); 
    $domPdf->render();

    return response($domPdf->output(), 200)->header('Content-Type', 'application/pdf');
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
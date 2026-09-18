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

// 1. Extendendo o BaseController
class StockController extends BaseController
{
    // 2. Definindo as propriedades padrão do Base
    protected $model = Estoque::class;
    protected $formTitle = 'Estoque';
    protected $redirectPage = '/estoque';
    protected $listView = 'stock.index';

    public function __construct()
    {
        // O construtor do BaseController já cuida do empresa_id, usuario_id e filial_id
        parent::__construct();
    }

    // 3. Implementando os métodos abstratos obrigatórios do Base
    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    public function index(Request $request)
    {
        $pesquisa = $request->input('pesquisa');
        $categoria_id = $request->input('categoria_id');
        $filial_filtro = $request->input('filial_id'); // Filtro que vem da tela

        $mes = $request->mes ?? date('m');
        $ano = $request->ano ?? date('Y');
        $mesAnt = $mes == 1 ? 12 : $mes - 1;
        $anoAnt = $mes == 1 ? $ano - 1 : $ano;

        $query = Estoque::where('estoques.empresa_id', $this->empresa_id)
            ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
            ->where('produtos.gerenciar_estoque', 1)
            ->leftJoin('categorias', 'categorias.id', '=', 'produtos.categoria_id')
            ->leftJoin('filials', 'filials.id', '=', 'estoques.filial_id');

        // --- TRAVA DE SEGURANÇA POR USUÁRIO ---
        // Se o usuário logado pertence a uma filial (filial_id != null), ele só vê ela.
        if ($this->filial_id != null) {
            $query->where('estoques.filial_id', $this->filial_id);
            $filial_id = $this->filial_id; // Força o ID para os cálculos de subquery
        } else {
            // Se for admin/matriz, obedece o filtro da tela
            if ($filial_filtro == 'matriz') {
                $query->whereNull('estoques.filial_id');
                $filial_id = 'matriz';
            } elseif ($filial_filtro > 0) {
                $query->where('estoques.filial_id', $filial_filtro);
                $filial_id = $filial_filtro;
            } else {
                $query->whereNull('estoques.filial_id');
                $filial_id = 'matriz';
            }
        }

        if ($pesquisa) $query->where('produtos.nome', 'LIKE', "%{$pesquisa}%");
        if ($categoria_id) $query->where('produtos.categoria_id', $categoria_id);

        // Cálculos de Totais
        $tiposPermitidos = ['00', '01', '02', '03', '04', '05', '06', '10'];
        $somaEstoque = [
            'compra' => (clone $query)->whereIn('produtos.tipo_item', $tiposPermitidos)->sum(DB::raw('estoques.quantidade * produtos.valor_compra')),
            'venda' => (clone $query)->whereIn('produtos.tipo_item', $tiposPermitidos)->sum(DB::raw('estoques.quantidade * produtos.valor_venda')),
            'quantidade' => (clone $query)->sum('estoques.quantidade')
        ];

        // Lógica de Subconsultas (Entradas/Saídas)
        $filialSubQuery = ($filial_id == 'matriz') ? " AND filial_id IS NULL" : " AND filial_id = " . (int)$filial_id;

        $estoque = $query->select(
            'estoques.*',
            'produtos.nome as produto_nome',
            'produtos.valor_venda as preco_venda',
            'produtos.valor_compra as preco_custo',
            'categorias.nome as categoria_nome',
            DB::raw("(SELECT quantidade FROM estoque_mensal_fechamentos WHERE produto_id = estoques.produto_id AND mes = $mesAnt AND ano = $anoAnt $filialSubQuery LIMIT 1) as saldo_inicial"),
            DB::raw("(SELECT SUM(quantidade) FROM stock_movements WHERE produto_id = estoques.produto_id AND tipo = 'entrada' AND MONTH(movimentado_em) = $mes AND YEAR(movimentado_em) = $ano $filialSubQuery) as total_entradas"),
            DB::raw("(SELECT SUM(quantidade) FROM stock_movements WHERE produto_id = estoques.produto_id AND tipo = 'saida' AND MONTH(movimentado_em) = $mes AND YEAR(movimentado_em) = $ano $filialSubQuery) as total_saidas")
        )
            ->orderBy('produtos.nome', 'asc')
            ->paginate(25);

        foreach($estoque as $e) {
            $e->saldo_no_periodo = ($e->saldo_inicial ?? 0) + ($e->total_entradas ?? 0) - ($e->total_saidas ?? 0);
        }

        $categorias = Categoria::where('empresa_id', $this->empresa_id)->get();
        // Na view, o select de filiais também deve respeitar a trava
        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->when($this->filial_id != null, function($q) {
                return $q->where('id', $this->filial_id);
            })->get();

        return view($this->listView, compact(
            'estoque', 'mes', 'ano', 'categorias', 'filiais',
            'pesquisa', 'categoria_id', 'filial_id', 'somaEstoque'
        ))->with('title', 'Estoque');
    }

    public function historico(Request $request, $id) {
        $item = Estoque::findOrFail($id);

        // Proteção: Se o item clicado não for da filial do usuário, bloqueia
        if($this->filial_id != null && $item->filial_id != $this->filial_id){
            return redirect('/403');
        }

        $data_inicial = $request->input('data_inicial');
        $data_final = $request->input('data_final');

        $query = DB::table('stock_movements')
            ->select('stock_movements.*', 'usuarios.nome as usuario_nome', 'fornecedors.razao_social as fornecedor_nome',
                'cl.razao_social as cliente_nome', 'v.numero_sequencial as venda_numero',
                'compras.nf as compra_nf', 'compras.numero_emissao as compra_emissao', 'filials.descricao as filial_nome')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'stock_movements.usuario_id')
            ->leftJoin('compras', function($join) {
                $join->on('compras.id', '=', 'stock_movements.origem_id')->where('stock_movements.origem_tipo', '=', 'compra');
            })
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
            ->leftJoin('filials', 'filials.id', '=', 'stock_movements.filial_id')
            ->leftJoin('vendas as v', function($join) {
                $join->on('v.id', '=', 'stock_movements.origem_id')->where('stock_movements.origem_tipo', '=', 'venda');
            })
            ->leftJoin('clientes as cl', 'cl.id', '=', 'v.cliente_id')
            ->where('stock_movements.produto_id', $item->produto_id)
            ->where('stock_movements.empresa_id', $this->empresa_id);

        // Filtro inteligente de Matriz (NULL ou 0) vs Filial específica
        if ($item->filial_id == null) {
            $query->where(function($q) { $q->whereNull('stock_movements.filial_id')->orWhere('stock_movements.filial_id', 0); });
        } else {
            $query->where('stock_movements.filial_id', $item->filial_id);
        }

        if ($data_inicial) $query->whereDate('stock_movements.created_at', '>=', $data_inicial);
        if ($data_final) $query->whereDate('stock_movements.created_at', '<=', $data_final);

        $movimentacoes = $query->orderBy('stock_movements.id', 'desc')->limit(200)->get();

        return view('stock.historico', compact('item', 'movimentacoes', 'data_inicial', 'data_final'))
            ->with('title', 'Histórico de Movimentação');
    }


    public function pesquisa(Request $request){
        $filial_id = $request->input('filial_id');
        $categoria_id = $request->input('categoria_id');
        $pesquisa = $request->pesquisa;

        $query = Estoque::where('estoques.empresa_id', $this->empresa_id)
            ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
            ->where('produtos.gerenciar_estoque', 1)
            ->where('produtos.nome', 'LIKE', "%{$pesquisa}%");

        // FILTRO DE FILIAL: Crucial para não duplicar
        if ($filial_id) {
            if ($filial_id == -1 || $filial_id == 'matriz') {
                $query->whereNull('estoques.filial_id');
            } else {
                $query->where('estoques.filial_id', $filial_id);
            }
        }

        if ($categoria_id) {
            $query->where('produtos.categoria_id', $categoria_id);
        }

        // Selecionamos as colunas explicitamente para evitar sobreposição de IDs
        $estoque = $query->select('estoques.*', 'produtos.nome as produto_nome')->get();

        // Reutiliza a lógica de soma que você já tem
        $somaEstoque = $this->somaEstoque($estoque);
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $categorias = Categoria::where('empresa_id', $this->empresa_id)->get();

        return view('stock/list')
            ->with('pesquisa', $pesquisa)
            ->with('categorias', $categorias)
            ->with('estoque', $estoque)
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

        // NOVO: Busca as filiais da empresa ordenadas pela descrição
        $filiais = \App\Models\Filial::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get();

        return view('stock/apontamento')
            ->with('apontamentos', $apontamentos)
            ->with('produtos', $produtos)
            ->with('filiais', $filiais) // NOVO: Envia as filiais para a tela acabar com o erro
            ->with('produtoJs', true)
            ->with('title', 'Apontamento');
    }

    public function apontamentoManual(){
        // Buscamos apenas produtos ativos e da empresa logada
        // Removendo o loop manual para ganhar performance
        $produtos = Produto::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->orderBy('nome', 'asc')
            ->get();

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

    public function saveApontamento(Request $request) {
        $prod = Produto::findOrFail($request->produto);
        $quantidade = __replace($request->quantidade);

        // 1. Definição obrigatória da variável no início do método
        $dataLancamento = $request->data ? $this->parseDate($request->data) : \Carbon\Carbon::now();

        $filial_id = $request->filial_id > 0 ? (int)$request->filial_id : null;

        // 2. Validação
        $alerta = $this->verificarDisponibilidadeEstoque($prod, $quantidade);
        if($alerta != "" && !$request->has('confirmar_negativo')){
            session()->flash('mensagem_erro', $alerta);
            return redirect()->back()->withInput();
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $result = Apontamento::create([
                'quantidade' => $quantidade,
                'usuario_id' => get_id_user(),
                'produto_id' => $prod->id,
                'empresa_id' => $this->empresa_id,
                'filial_id'  => $filial_id,
                'created_at' => $dataLancamento
            ]);

            $stockMove = new StockMove();

            // 3. Entrada do Produto
            $stockMove->pluStock(
                $prod->id,
                $quantidade,
                str_replace(",", ".", $prod->valor_venda),
                $filial_id,
                'Apontamento',
                $result->id,
                $dataLancamento // Passando a variável definida no topo
            );

            // 4. Alteração Estoque
            $alteracaoEntrada = new \App\Models\AlteracaoEstoque();
            $alteracaoEntrada->empresa_id = $this->empresa_id;
            $alteracaoEntrada->filial_id  = $filial_id;
            $alteracaoEntrada->usuario_id = get_id_user();
            $alteracaoEntrada->produto_id = $prod->id;
            $alteracaoEntrada->quantidade = $quantidade;
            $alteracaoEntrada->tipo       = 'incremento';
            $alteracaoEntrada->observacao = 'Apontamento de Produção #' . $result->id;
            $alteracaoEntrada->motivo     = 'Produção';

            $alteracaoEntrada->created_at = $dataLancamento; // Usando a variável definida
            $alteracaoEntrada->updated_at = $dataLancamento; // Usando a variável definida
            $alteracaoEntrada->save();

            // 5. Saída da Matéria-Prima
            $this->downEstoquePorReceita($prod, $quantidade, $result->id, $filial_id, $dataLancamento);

            \Illuminate\Support\Facades\DB::commit();
            session()->flash("mensagem_sucesso", "Apontamento cadastrado com sucesso!");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            // Agora, se o erro ocorrer aqui, a variável $dataLancamento já existe e o log será claro
            session()->flash('mensagem_erro', 'Erro ao cadastrar apontamento: ' . $e->getMessage());
        }

        return redirect("/estoque/apontamentoProducao");
    }

    public function saveApontamentoManual(Request $request){
        if(__replace($request->quantidade) <= 0){
            session()->flash('mensagem_erro', 'Informe uma quantidade maior que zero!');
            return redirect()->back();
        }

        $this->_validateApontamento($request);
        $prod = Produto::findOrFail($request->produto_id);

        // Data informada ou agora
        $dataLancamento = $request->data ? $this->parseDate($request->data) : date('Y-m-d');
        $quantidade = __replace($request->quantidade);
        $filial_id = $request->filial_id > 0 ? $request->filial_id : null;

        try {
            DB::beginTransaction();

            $stockMove = new StockMove();
            if($request->tipo == 'reducao'){
                // Passamos 'Ajuste' como origem_tipo e a data escolhida
                $stockMove->downStock($prod->id, $quantidade, $filial_id, 'Ajuste', null, $dataLancamento);
            } else {
                // Passamos 'Ajuste' como origem_tipo e a data escolhida
                $stockMove->pluStock($prod->id, $quantidade, $prod->valor_compra, $filial_id, 'Ajuste', null, $dataLancamento);
            }

            // ------------------------------------------------------------------
            // INÍCIO DA GRAVAÇÃO NA TABELA alteracao_estoques
            // ------------------------------------------------------------------

            // Formatando a data do lançamento para injetar a hora atual, evitando ficar 00:00:00
            $dataLancamentoCarbon = \Carbon\Carbon::parse($dataLancamento);
            if ($dataLancamentoCarbon->format('H:i:s') === '00:00:00') {
                $dataLancamentoCarbon->setTimeFrom(\Carbon\Carbon::now());
            }

            $alteracao = new \App\Models\AlteracaoEstoque();

            // Se o seu StockController não estender o BaseController,
            // talvez você precise trocar $this->empresa_id por auth()->user()->empresa_id
            $alteracao->empresa_id = $this->empresa_id;
            $alteracao->filial_id  = $filial_id;
            $alteracao->usuario_id = $this->usuario_id;

            $alteracao->produto_id = $prod->id;
            $alteracao->quantidade = $quantidade;

            // Verifica o tipo para salvar o nome correto na tabela (entrada ou saida)
            $alteracao->tipo       = $request->tipo == 'reducao' ? 'reducao' : 'incremento';

            $alteracao->observacao = $request->observacao;
            $alteracao->motivo     = $request->motivo;

            // Força a data de criação e atualização
            $alteracao->created_at = $dataLancamentoCarbon;
            $alteracao->updated_at = $dataLancamentoCarbon;

            $alteracao->save();
            // ------------------------------------------------------------------
            // FIM DA GRAVAÇÃO NA TABELA alteracao_estoques
            // ------------------------------------------------------------------

            DB::commit();
            session()->flash("mensagem_sucesso", "Estoque ajustado com sucesso!");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
        return redirect("/estoque");
    }

    // Adicione o = null para não quebrar outros lugares que chamam essa função
    private function downEstoquePorReceita($produto, $quantidade, $apontamento_id = null, $filial_id = null, $dataLancamento = null){

        if(valida_objeto($produto)){
            $stockMove = new StockMove();

            if($produto->receita){
                foreach($produto->receita->itens as $i){
                    $qtdIngrediente = $i->quantidade * $quantidade;

                    // 1. Remove do estoque
                    $stockMove->downStock(
                        $i->produto->id,
                        $qtdIngrediente,
                        $filial_id,
                        'Apontamento',
                        $apontamento_id,
                        $dataLancamento // Agora a variável existe!
                    );

                    // 2. Salva a alteração de estoque
                    $alteracaoSaida = new \App\Models\AlteracaoEstoque();
                    $alteracaoSaida->empresa_id = $this->empresa_id;
                    $alteracaoSaida->filial_id  = $filial_id;
                    $alteracaoSaida->usuario_id = get_id_user();
                    $alteracaoSaida->produto_id = $i->produto->id;
                    $alteracaoSaida->quantidade = $qtdIngrediente;
                    $alteracaoSaida->tipo       = 'reducao';
                    $alteracaoSaida->observacao = 'Consumo de matéria-prima no Apontamento #' . $apontamento_id;
                    $alteracaoSaida->motivo     = 'Produção';

                    // CORREÇÃO: Use a variável que veio do parâmetro, não o Carbon::now()
                    $alteracaoSaida->created_at = $dataLancamento ?? \Carbon\Carbon::now();
                    $alteracaoSaida->updated_at = $dataLancamento ?? \Carbon\Carbon::now();
                    $alteracaoSaida->save();
                }
            }
        } else {
            return redirect('/403');
        }
    }

    /* private function validaEstoqueDisponivel($produto, $quantidade){
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
 */

    private function verificarDisponibilidadeEstoque($produto, $quantidade) {
        $insuficientes = [];
        if ($produto->receita) {
            foreach ($produto->receita->itens as $i) {
                if ($i->produto->estoqueAtual() < ($i->quantidade * $quantidade)) {
                    $insuficientes[] = $i->produto->nome;
                }
            }
        }

        if (!empty($insuficientes)) {
            return "Atenção: A produção causará saldo negativo nos itens: " . implode(", ", $insuficientes) . ". Marque a opção de confirmar para prosseguir.";
        }
        return "";
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
        $mes = $request->mes ?? date('m');
        $ano = $request->ano ?? date('Y');

        $mesAnt = $mes == 1 ? 12 : $mes - 1;
        $anoAnt = $mes == 1 ? $ano - 1 : $ano;

        $filialSubQuery = "";
        if ($filial_id == 'matriz') {
            $filialSubQuery = " AND filial_id IS NULL";
        } elseif ($filial_id > 0) {
            $filialSubQuery = " AND filial_id = " . (int)$filial_id;
        }

        $query = \App\Models\Estoque::where('estoques.empresa_id', $this->empresa_id)
            ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
            ->where('produtos.gerenciar_estoque', 1);

        if ($pesquisa) $query->where('produtos.nome', 'LIKE', "%{$pesquisa}%");
        if ($categoria_id) $query->where('produtos.categoria_id', $categoria_id);

        if ($filial_id) {
            if ($filial_id == 'matriz') $query->whereNull('estoques.filial_id');
            else $query->where('estoques.filial_id', $filial_id);
        }

        // Seleção com as subqueries para resolver o problema do saldo zerado no PDF
        $estoque = $query->select(
            'estoques.*',
            'produtos.nome as produto_nome',
            'produtos.valor_venda as preco_venda',
            'produtos.valor_compra as preco_custo',
            \DB::raw("(SELECT quantidade FROM estoque_mensal_fechamentos
                  WHERE produto_id = estoques.produto_id AND mes = $mesAnt AND ano = $anoAnt $filialSubQuery LIMIT 1) as saldo_inicial"),
            \DB::raw("(SELECT SUM(quantidade) FROM stock_movements
                  WHERE produto_id = estoques.produto_id AND tipo = 'entrada'
                  AND MONTH(movimentado_em) = $mes AND YEAR(movimentado_em) = $ano $filialSubQuery) as total_entradas"),
            \DB::raw("(SELECT SUM(quantidade) FROM stock_movements
                  WHERE produto_id = estoques.produto_id AND tipo = 'saida'
                  AND MONTH(movimentado_em) = $mes AND YEAR(movimentado_em) = $ano $filialSubQuery) as total_saidas")
        )
            ->orderBy('produtos.nome', 'asc')
            ->get();

        // Calcula o saldo final do período para cada item
        foreach($estoque as $e) {
            // Cálculo do saldo final daquele mês específico
            $e->saldo_no_periodo = ($e->saldo_inicial ?? 0) + ($e->total_entradas ?? 0) - ($e->total_saidas ?? 0);
        }

        if($estoque->count() > 800) {
            return redirect()->back()->with('mensagem_erro', 'Relatório muito grande. Use filtros.');
        }

        $empresa = \DB::table('empresas')->where('id', $this->empresa_id)->first();
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

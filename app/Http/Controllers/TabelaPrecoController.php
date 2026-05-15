<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TabelaPreco;
use App\Models\TabelaPrecoItem;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;

class TabelaPrecoController extends BaseController
{
    public function __construct()
    {
        $this->model        = TabelaPreco::class;
        $this->formTitle    = 'Tabela de Preços';
        $this->redirectPage = '/tabelas-precos';
        $this->listView     = 'tabelas_precos.list';
        $this->registerView = 'tabelas_precos.register';
        
        parent::__construct();
    }

    protected function rules(): array { return ['descricao' => 'required']; }
    protected function messages(): array { return ['descricao.required' => 'O nome da tabela é obrigatório.']; }

    private function getEmpresaId() {
        return $this->empresa_id ?? session('user_logged')['empresa'] ?? session('user_logged')['empresa_id'] ?? 1;
    }

    public function list(Request $request)
    {
        $empresaId = $this->getEmpresaId();
        $records = TabelaPreco::where('empresa_id', $empresaId)->get();
        return view($this->listView, ['records' => $records, 'title' => 'Tabelas de Preços']);
    }

    public function register($id = null)
    {
        $empresaId = $this->getEmpresaId();
        
        $data = $id ? TabelaPreco::with('itens.produto')->where('empresa_id', $empresaId)->findOrFail($id) : null;
        $title = $id ? "Editar Tabela de Preços" : "Nova Tabela de Preços";

        return view($this->registerView, compact('data', 'title'));
    }

    public function save(Request $request)
    {
        if (!$this->validateRequest($request)) return redirect()->back()->withInput();

        DB::beginTransaction();
        try {
            $empresaId = $this->getEmpresaId();

            $tabelaData = [
                'empresa_id' => $empresaId,
                'descricao' => $request->descricao
            ];

            if ($request->id) {
                $tabela = TabelaPreco::findOrFail($request->id);
                $tabela->update($tabelaData);
                TabelaPrecoItem::where('tabela_preco_id', $tabela->id)->delete();
                $mensagem = "Tabela atualizada com sucesso!";
            } else {
                $tabela = TabelaPreco::create($tabelaData);
                $mensagem = "Tabela criada com sucesso!";
            }

            if ($request->has('produto_id')) {
                foreach ($request->produto_id as $index => $produtoId) {
                    if(empty($produtoId)) continue; 

                    $valorFormatado = str_replace(['R$', '.', ' '], '', $request->valor_kg[$index]);
                    $valorFormatado = str_replace(',', '.', $valorFormatado);

                    TabelaPrecoItem::create([
                        'tabela_preco_id' => $tabela->id,
                        'produto_id' => $produtoId,
                        'tipo_frete' => $request->tipo_frete[$index],
                        'valor_kg' => (float) $valorFormatado
                    ]);
                }
            }

            DB::commit();
            session()->flash('mensagem_sucesso', $mensagem);
            return redirect($this->redirectPage);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('mensagem_erro', 'Erro ao salvar tabela: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    // BUSCA AJAX COM O FILTRO CORRIGIDO
    public function searchProduto(Request $request)
    {
        try {
            $term = $request->get('term', '');
            $empresaId = $this->getEmpresaId();

            $produtos = Produto::where('empresa_id', $empresaId)
                ->where('controla_pesagem', 1) 
                ->where(function ($query) use ($term) {
                    $query->where('nome', 'like', "%{$term}%")
                          ->orWhere('referencia', 'like', "%{$term}%")
                          ->orWhere('codBarras', 'like', "%{$term}%");
                })
                ->limit(30)
                ->get(); // <-- A SOLUÇÃO: Não limitamos as colunas aqui, para evitar que o Laravel falhe ao carregar atributos virtuais do Produto.

            $results = [];
            foreach ($produtos as $produto) {
                $ref = !empty($produto->referencia) ? "Ref: {$produto->referencia} | " : "";
                $cod = !empty($produto->codBarras) ? " | Cód: {$produto->codBarras}" : "";
                
                $results[] = [
                    'id' => $produto->id,
                    'text' => $ref . $produto->nome . $cod
                ];
            }

            return response()->json(['results' => $results]);

        } catch (\Exception $e) {
            \Log::error("Erro no searchProduto: " . $e->getMessage());
            return response()->json(['error' => 'Erro interno', 'message' => $e->getMessage()], 500);
        }
    }
}
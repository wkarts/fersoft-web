<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\TabelaPrecoNfe;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TabelaPrecoNfeController extends BaseController
{
    protected $redirectPage = '/tabelaPrecoNfe';
    protected $formTitle = 'Tabela de Preços para NF-e';

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function index()
    {
        $produtos = Produto::query()
            ->where('empresa_id', $this->empresa_id)
            ->where(function ($query): void {
                $query->whereNull('inativo')->orWhere('inativo', 0);
            })
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $precos_nfe = TabelaPrecoNfe::query()
            ->join('produtos', function ($join): void {
                $join->on('produtos.id', '=', 'tabela_preco_nfes.produto_id')
                    ->on('produtos.empresa_id', '=', 'tabela_preco_nfes.empresa_id');
            })
            ->where('tabela_preco_nfes.empresa_id', $this->empresa_id)
            ->select('tabela_preco_nfes.*', 'produtos.nome as produto_nome')
            ->orderBy('produtos.nome')
            ->get();

        return view('tabela_preco_nfe.index', compact('produtos', 'precos_nfe'))
            ->with('title', $this->formTitle);
    }

    public function save(Request $request)
    {
        $dados = $request->validate([
            'produto_id' => [
                'required',
                'integer',
                Rule::exists('produtos', 'id')->where(fn ($query) => $query->where('empresa_id', $this->empresa_id)),
            ],
            'preco_nfe' => ['required', 'string', 'max:30'],
        ]);

        $preco = $this->normalizarValor($dados['preco_nfe']);
        if ($preco < 0) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'O preço da NF-e não pode ser negativo.');
        }

        TabelaPrecoNfe::query()->updateOrCreate(
            [
                'empresa_id' => $this->empresa_id,
                'produto_id' => (int) $dados['produto_id'],
            ],
            [
                'filial_id' => $this->filial_id,
                'usuario_id' => $this->usuario_id,
                'preco_nfe' => $preco,
            ]
        );

        return redirect()->back()->with('mensagem_sucesso', 'Preço de NF-e salvo com sucesso.');
    }

    public function delete($id)
    {
        $preco = TabelaPrecoNfe::query()
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        $preco->delete();

        return redirect()->back()->with('mensagem_sucesso', 'Preço removido com sucesso.');
    }

    private function normalizarValor(string $valor): float
    {
        $valor = trim($valor);
        if ($valor === '') {
            return 0;
        }

        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return round((float) preg_replace('/[^0-9.\-]/', '', $valor), 2);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\TabelaPrecoNfe;
use App\Models\Produto;

class TabelaPrecoNfeController extends Controller
{
    public function index()
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];

        // Preserva a seleção original de sucatas ativas e acrescenta somente
        // os produtos que já têm preço cadastrado nesta mesma empresa.
        // Assim, preços existentes podem ser editados mesmo após inativação
        // ou alteração do nome do produto.
        $produtosComPreco = TabelaPrecoNfe::where('empresa_id', $empresa_id)
            ->select('produto_id');

        $produtos = Produto::where('empresa_id', $empresa_id)
            ->where(function ($query) use ($produtosComPreco) {
                $query->where(function ($q) {
                    $q->where('inativo', 0)->where('nome', 'like', '%sucata%');
                })->orWhereIn('id', $produtosComPreco);
            })
            ->orderBy('nome')
            ->get();

        // Busca os preços já cadastrados
        $precos_nfe = TabelaPrecoNfe::join('produtos', 'produtos.id', '=', 'tabela_preco_nfes.produto_id')
            ->where('tabela_preco_nfes.empresa_id', $empresa_id)
            ->where('produtos.empresa_id', $empresa_id)
            ->select('tabela_preco_nfes.*', 'produtos.nome as produto_nome', 'produtos.referencia as produto_referencia')
            ->get();

        return view('tabela_preco_nfe.index', compact('produtos', 'precos_nfe'))->with('title', 'Tabela de Preços NF-e');
    }

    public function save(Request $request)
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];

        $request->validate([
            'produto_id' => 'required|integer',
            'preco_nfe' => 'required|string',
        ]);

        // Confirma que o produto pertence à empresa do usuário autenticado.
        $produto = Produto::where('empresa_id', $empresa_id)
            ->findOrFail($request->produto_id);

        $existente = TabelaPrecoNfe::where('empresa_id', $empresa_id)
            ->where('produto_id', $produto->id)
            ->exists();

        // Novos cadastros continuam limitados às sucatas ativas. Edição
        // de um preço já existente não é bloqueada por inativação/renomeação.
        if (!$existente && ((int) $produto->inativo !== 0
            || stripos((string) $produto->nome, 'sucata') === false)) {
            throw ValidationException::withMessages([
                'produto_id' => 'Selecione um produto de sucata ativo para cadastrar o preço.',
            ]);
        }

        $preco_limpo = $this->normalizarPreco((string) $request->preco_nfe);
        if ($preco_limpo === null) {
            throw ValidationException::withMessages([
                'preco_nfe' => 'Informe um preço válido, com no máximo duas casas decimais.',
            ]);
        }

        // Mantém o fluxo existente: uma referência por empresa e produto.
        // Nenhuma migração ou remoção automática de dados preexistentes.
        TabelaPrecoNfe::updateOrCreate(
            ['empresa_id' => $empresa_id, 'produto_id' => $produto->id],
            ['preco_nfe' => $preco_limpo]
        );

        return redirect()->route('tabela-preco-nfe.index')
            ->with('sucesso', 'Preço de NF-e salvo com sucesso!');
    }

    /**
     * Interpreta preços brasileiros (1.250,50; 12,90) e decimais simples
     * (12.90) sem transformar acidentalmente 12.90 em 1290.
     * DECIMAL(15,2) suporta até 13 dígitos inteiros.
     */
    private function normalizarPreco(string $valor): ?string
    {
        $valor = trim($valor);
        $valor = preg_replace('/^R\$\s*/u', '', $valor);
        $valor = preg_replace('/[\s\x{00A0}]+/u', '', $valor);

        if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?$/D', $valor)) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (preg_match('/^\d+(?:,\d{1,2})?$/D', $valor)) {
            $valor = str_replace(',', '.', $valor);
        } elseif (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $valor)) {
            return null;
        }

        $partes = explode('.', $valor, 2);
        $inteiro = ltrim($partes[0], '0');
        $inteiro = $inteiro === '' ? '0' : $inteiro;
        if (strlen($inteiro) > 13) {
            return null;
        }

        $centavos = str_pad($partes[1] ?? '', 2, '0', STR_PAD_RIGHT);
        return $inteiro . '.' . $centavos;
    }

    // Retorna o preço em JSON para preenchimento automático na Compra em Lote.
    public function getPrecoJson($produto_id)
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];

        $tabela = TabelaPrecoNfe::where('empresa_id', $empresa_id)
            ->where('produto_id', $produto_id)
            ->first();

        return response()->json([
            'preco' => $tabela ? number_format($tabela->preco_nfe, 2, ',', '.') : ''
        ]);
    }

    public function delete($id)
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];

        $preco = TabelaPrecoNfe::where('empresa_id', $empresa_id)->findOrFail($id);
        $preco->delete();

        return redirect()->route('tabela-preco-nfe.index')->with('sucesso', 'Preço removido!');
    }
}

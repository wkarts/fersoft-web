<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TabelaPrecoNfe;
use App\Models\Produto;

class TabelaPrecoNfeController extends Controller
{
    public function index()
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];

        // Busca apenas os produtos ativos que contêm "sucata" no nome
        $produtos = Produto::where('empresa_id', $empresa_id)
            ->where('inativo', 0)
            ->where('nome', 'like', '%sucata%')
            ->orderBy('nome')
            ->get();

        // Busca os preços já cadastrados
        $precos_nfe = TabelaPrecoNfe::join('produtos', 'produtos.id', '=', 'tabela_preco_nfes.produto_id')
            ->where('tabela_preco_nfes.empresa_id', $empresa_id)
            ->select('tabela_preco_nfes.*', 'produtos.nome as produto_nome', 'produtos.referencia as produto_referencia')
            ->get();

        return view('tabela_preco_nfe.index', compact('produtos', 'precos_nfe'))->with('title', 'Tabela de Preços NF-e');
    }

    public function save(Request $request)
    {
        $sessionData = session('user_logged');
        $empresa_id = $sessionData['empresa'];

        $request->validate([
            'produto_id' => 'required',
            'preco_nfe' => 'required'
        ]);

        $preco_limpo = str_replace(',', '.', str_replace('.', '', $request->preco_nfe));

        TabelaPrecoNfe::updateOrCreate(
            ['empresa_id' => $empresa_id, 'produto_id' => $request->produto_id],
            ['preco_nfe' => $preco_limpo]
        );

        return redirect()->back()->with('sucesso', 'Preço de NF-e salvo com sucesso!');
    }

    // NOVO: Retorna o preço em JSON para preenchimento automático na Compra em Lote
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
        $preco = TabelaPrecoNfe::findOrFail($id);
        $preco->delete();

        return redirect()->back()->with('sucesso', 'Preço removido!');
    }
}

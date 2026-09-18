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

        // Busca os produtos para aparecerem no select
        $produtos = Produto::where('empresa_id', $empresa_id)
            ->where('inativo', 0)
            ->orderBy('nome')
            ->get();

        // Busca os preços já cadastrados, trazendo o nome do produto junto
        $precos_nfe = TabelaPrecoNfe::join('produtos', 'produtos.id', '=', 'tabela_preco_nfes.produto_id')
            ->where('tabela_preco_nfes.empresa_id', $empresa_id)
            ->select('tabela_preco_nfes.*', 'produtos.nome as produto_nome')
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

        // Limpa a formatação do valor (ex: de "12,90" para "12.90")
        $preco_limpo = str_replace(',', '.', str_replace('.', '', $request->preco_nfe));

        // O updateOrCreate é mágico: se já existir preço para esse produto, ele atualiza. Se não, ele cria um novo.
        TabelaPrecoNfe::updateOrCreate(
            ['empresa_id' => $empresa_id, 'produto_id' => $request->produto_id],
            ['preco_nfe' => $preco_limpo]
        );

        return redirect()->back()->with('sucesso', 'Preço de NF-e salvo com sucesso!');
    }

    public function delete($id)
    {
        $preco = TabelaPrecoNfe::findOrFail($id);
        $preco->delete();

        return redirect()->back()->with('sucesso', 'Preço removido!');
    }
}
<?php

namespace App\Http\Controllers\PDVA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\ListaPreco;

class ProdutoController extends Controller
{
    public function produtos(Request $request){
        $updated_at = $request->updated_at;
        $data = Produto::where('empresa_id', $request->empresa_id)
        ->select('id', 'nome', 'valor_venda AS valor_unitario', 'categoria_id', 
            'codBarras AS codigo_barras', 'imagem', 'gerenciar_estoque')
        ->with(['categoria', 'estoque'])
        ->where('inativo', 0)
        ->get();
        return response()->json($data, 200);
    }

    public function categorias(Request $request){
        $data = Categoria::where('empresa_id', $request->empresa_id)
        ->get();
        return response()->json($data, 200);
    }

    public function listaPreco(Request $request){
        $data = ListaPreco::where('empresa_id', $request->empresa_id)
        ->with('itens')
        ->get();
        return response()->json($data, 200);
    }
}

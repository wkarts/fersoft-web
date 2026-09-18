<?php

namespace App\Http\Controllers;

use App\Models\GrupoCategoria;
use App\Models\CategoriaConta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrupoCategoriaController extends Controller
{
    public function index()
    {
        $empresaId = request()->empresa_id ?? auth()->user()->empresa_id;

        $grupos = GrupoCategoria::with('categorias')
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        $title = 'Grupos de Categorias';

        return view('grupo_categorias.index', compact('grupos', 'title'));
    }

    public function create()
    {
        $empresaId = request()->empresa_id ?? auth()->user()->empresa_id;

        $categorias = CategoriaConta::where('empresa_id', $empresaId)
            ->whereNull('deleted_at')
            ->orderBy('nome')
            ->get();

        $title = 'Novo Grupo de Categorias';

        return view('grupo_categorias.create', compact('categorias', 'title'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:100',
            'categorias' => 'nullable|array',
        ]);

        $empresaId = $request->empresa_id ?? auth()->user()->empresa_id;

        DB::transaction(function () use ($request, $empresaId) {
            $grupo = GrupoCategoria::create([
                'nome' => $request->nome,
                'descricao' => $request->descricao,
                'empresa_id' => $empresaId,
                'filial_id' => $request->filial_id ?? null,
                'usuario_id' => auth()->id(),
            ]);

            if ($request->has('categorias')) {
                $grupo->categorias()->sync($request->categorias);
            }
        });

        return redirect('/grupo-categorias')->with('success', 'Grupo cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $empresaId = request()->empresa_id ?? auth()->user()->empresa_id;

        $grupo = GrupoCategoria::with('categorias')
            ->where('empresa_id', $empresaId)
            ->findOrFail($id);

        $categorias = CategoriaConta::where('empresa_id', $empresaId)
            ->whereNull('deleted_at')
            ->orderBy('nome')
            ->get();

        $categoriasSelecionadas = $grupo->categorias->pluck('id')->toArray();

        $title = 'Editar Grupo de Categorias';

        return view('grupo_categorias.edit', compact('grupo', 'categorias', 'categoriasSelecionadas', 'title'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required|string|max:100',
            'categorias' => 'nullable|array',
        ]);

        $empresaId = $request->empresa_id ?? auth()->user()->empresa_id;
        $grupo = GrupoCategoria::where('empresa_id', $empresaId)->findOrFail($id);

        DB::transaction(function () use ($request, $grupo) {
            $grupo->update([
                'nome' => $request->nome,
                'descricao' => $request->descricao,
            ]);

            $grupo->categorias()->sync($request->input('categorias', []));
        });

        return redirect('/grupo-categorias')->with('success', 'Grupo atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $empresaId = request()->empresa_id ?? auth()->user()->empresa_id;
        $grupo = GrupoCategoria::where('empresa_id', $empresaId)->findOrFail($id);
        $grupo->delete();

        return redirect('/grupo-categorias')->with('success', 'Grupo removido com sucesso!');
    }
}
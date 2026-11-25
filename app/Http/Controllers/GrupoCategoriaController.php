<?php

namespace App\Http\Controllers;

use App\Models\GrupoCategoria;
use App\Models\CategoriaConta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrupoCategoriaController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->empresa_id ?? auth()->user()->empresa_id;

        $grupos = GrupoCategoria::with('categorias')
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        // Busca as categorias sem grupo vinculadas à empresa atual
        $categoriasSemGrupo = DB::table('categoria_contas as c')
            ->leftJoin('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
            ->where('c.empresa_id', $empresaId)
            ->whereNull('ccg.categoria_conta_id')
            ->select('c.id', 'c.nome')
            ->orderBy('c.nome')
            ->get();

        $title = 'Grupos de Categorias';

        return view('grupo_categorias.index', compact('grupos', 'categoriasSemGrupo', 'title'));
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

        $userLogged = session('user_logged');
        $empresaId = $request->empresa_id ?? ($userLogged['empresa'] ?? auth()->user()->empresa_id ?? null);

        // Se for matriz ou vier -1/vazio, salva null na filial
        $filialId = $request->filial_id;
        if (!$filialId || $filialId == -1 || $filialId == '-1') {
            $filialId = null;
        }

        $usuarioId = $userLogged['id'] ?? auth()->id() ?? null;

        DB::transaction(function () use ($request, $empresaId, $filialId, $usuarioId) {
            $grupo = GrupoCategoria::create([
                'nome' => $request->nome,
                'descricao' => $request->descricao,
                'empresa_id' => $empresaId,
                'filial_id' => $filialId,
                'usuario_id' => $usuarioId,
            ]);

            if ($request->has('categorias') && is_array($request->categorias)) {
                // Prepara o array com o empresa_id para cada categoria na tabela pivot
                $syncData = [];
                foreach ($request->categorias as $categoriaId) {
                    $syncData[$categoriaId] = [
                        'empresa_id' => $empresaId,
                    ];
                }
                $grupo->categorias()->sync($syncData);
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

        $userLogged = session('user_logged');
        $empresaId = $request->empresa_id ?? ($userLogged['empresa'] ?? auth()->user()->empresa_id ?? null);

        $grupo = GrupoCategoria::where('empresa_id', $empresaId)->findOrFail($id);

        DB::transaction(function () use ($request, $grupo, $empresaId) {
            $grupo->update([
                'nome' => $request->nome,
                'descricao' => $request->descricao,
            ]);

            $categorias = $request->input('categorias', []);
            $syncData = [];
            if (is_array($categorias)) {
                foreach ($categorias as $categoriaId) {
                    $syncData[$categoriaId] = [
                        'empresa_id' => $empresaId,
                    ];
                }
            }

            $grupo->categorias()->sync($syncData);
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

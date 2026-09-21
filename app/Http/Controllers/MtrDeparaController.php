<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\MtrResiduo;

class MtrDeparaController extends Controller
{
    private function getDadosSessao()
    {
        $session = session('user_logged');
        return [
            'empresa_id' => $session['empresa'] ?? null,
            'usuario_id' => $session['id'] ?? null,
            'filial_id'  => session('filial_id') ?? null
        ];
    }

    public function index(Request $request)
    {
        $sessao = $this->getDadosSessao();

        $deparas = DB::table('mtr_depara_residuos')
            ->leftJoin('produtos', 'mtr_depara_residuos.produto_id', '=', 'produtos.id')
            ->leftJoin('sub_categorias', 'mtr_depara_residuos.sub_categoria_id', '=', 'sub_categorias.id')
            ->leftJoin('categorias', 'mtr_depara_residuos.categoria_id', '=', 'categorias.id')
            ->where('mtr_depara_residuos.empresa_id', $sessao['empresa_id'])
            ->select(
                'mtr_depara_residuos.*',
                'produtos.nome as produto_nome',
                'produtos.NCM as produto_ncm',
                'sub_categorias.nome as subcategoria_nome',
                'categorias.nome as categoria_nome'
            )
            ->orderBy('mtr_depara_residuos.id', 'desc')
            ->get();
		$residuos = DB::table('mtr_residuos')->paginate(50);
      
        $title = 'De-Para de Resíduos (MTR)';
      
        return view('mtr.depara.index', compact('deparas', 'title'));
    }

    public function create(Request $request)
    {
        $sessao = $this->getDadosSessao();

        // Busca subcategorias vinculando através da tabela categorias para filtrar pela empresa
        $subcategorias = DB::table('sub_categorias')
            ->join('categorias', 'sub_categorias.categoria_id', '=', 'categorias.id')
            ->where('categorias.empresa_id', $sessao['empresa_id'])
            ->select('sub_categorias.id', 'sub_categorias.nome', 'categorias.nome as categoria_nome')
            ->orderBy('sub_categorias.nome')
            ->get();

        $produtos = DB::table('produtos')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'nome', 'NCM')
            ->orderBy('nome')
            ->get();

        $categorias = DB::table('categorias')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'nome')
            ->orderBy('nome')
            ->get();
      
      	$residuos = DB::table('mtr_residuos')->orderBy('res_codigo_ibama')->get();

        $title = 'Novo Vínculo De-Para Resíduo';
        return view('mtr.depara.create', compact('subcategorias', 'produtos', 'categorias', 'residuos', 'title'));
    }

    public function store(Request $request)
    {
        $sessao = $this->getDadosSessao();

        try {
            DB::table('mtr_depara_residuos')->insert([
                'empresa_id'          => $sessao['empresa_id'],
                'filial_id'           => $sessao['filial_id'],
                'usuario_id'          => $sessao['usuario_id'],
                'orgao'               => $request->orgao,
                'produto_id'          => $request->filled('produto_id') ? $request->produto_id : null,
                'sub_categoria_id'    => $request->filled('sub_categoria_id') ? $request->sub_categoria_id : null,
                'categoria_id'        => $request->filled('categoria_id') ? $request->categoria_id : null,
                'ncm'                 => $request->filled('ncm') ? $request->ncm : null,
                'cod_ibama'           => $request->cod_ibama,
                'descricao_residuo'   => $request->descricao_residuo,
                'classe_residuo'      => $request->classe_residuo,
                'estado_fisico'       => $request->input('estado_fisico', 'SOLIDO'),
                'acondicionamento_id' => $request->acondicionamento_id,
                'tratamento_id'       => $request->tratamento_id,
                'unidade_medida'      => $request->input('unidade_medida', 'Kg'),
                'fator_conversao'     => $request->input('fator_conversao', 1.0000),
                'created_at'          => now(),
                'updated_at'          => now()
            ]);

            return redirect()->route('mtr.depara.index')->with('sucesso', 'Regra De-Para salva com sucesso!');

        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('erro', 'Erro ao salvar: ' . $e->getMessage());
        }
    }

    public function edit(Request $request, $id)
    {
        $sessao = $this->getDadosSessao();

        $depara = DB::table('mtr_depara_residuos')
            ->where('id', $id)
            ->where('empresa_id', $sessao['empresa_id'])
            ->first();

        if (!$depara) {
            return redirect()->route('mtr.depara.index')->with('erro', 'Registro não encontrado.');
        }

        $subcategorias = DB::table('sub_categorias')
            ->join('categorias', 'sub_categorias.categoria_id', '=', 'categorias.id')
            ->where('categorias.empresa_id', $sessao['empresa_id'])
            ->select('sub_categorias.id', 'sub_categorias.nome', 'categorias.nome as categoria_nome')
            ->orderBy('sub_categorias.nome')
            ->get();

        $produtos = DB::table('produtos')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'nome', 'NCM')
            ->orderBy('nome')
            ->get();

        $categorias = DB::table('categorias')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'nome')
            ->orderBy('nome')
            ->get();

        $title = 'Editar De-Para de Resíduo';
        return view('mtr.depara.edit', compact('depara', 'subcategorias', 'produtos', 'categorias', 'title'));
    }

    public function update(Request $request, $id)
    {
        $sessao = $this->getDadosSessao();

        try {
            DB::table('mtr_depara_residuos')
                ->where('id', $id)
                ->where('empresa_id', $sessao['empresa_id'])
                ->update([
                    'usuario_id'          => $sessao['usuario_id'],
                    'orgao'               => $request->orgao,
                    'produto_id'          => $request->filled('produto_id') ? $request->produto_id : null,
                    'sub_categoria_id'    => $request->filled('sub_categoria_id') ? $request->sub_categoria_id : null,
                    'categoria_id'        => $request->filled('categoria_id') ? $request->categoria_id : null,
                    'ncm'                 => $request->filled('ncm') ? $request->ncm : null,
                    'cod_ibama'           => $request->cod_ibama,
                    'descricao_residuo'   => $request->descricao_residuo,
                    'classe_residuo'      => $request->classe_residuo,
                    'estado_fisico'       => $request->input('estado_fisico', 'SOLIDO'),
                    'acondicionamento_id' => $request->acondicionamento_id,
                    'tratamento_id'       => $request->tratamento_id,
                    'unidade_medida'      => $request->input('unidade_medida', 'Kg'),
                    'fator_conversao'     => $request->input('fator_conversao', 1.0000),
                    'updated_at'          => now()
                ]);

            return redirect()->route('mtr.depara.index')->with('sucesso', 'Regra De-Para atualizada!');

        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('erro', 'Erro ao atualizar: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        $sessao = $this->getDadosSessao();

        DB::table('mtr_depara_residuos')
            ->where('id', $id)
            ->where('empresa_id', $sessao['empresa_id'])
            ->delete();

        return redirect()->route('mtr.depara.index')->with('sucesso', 'Vínculo removido com sucesso!');
    }
}
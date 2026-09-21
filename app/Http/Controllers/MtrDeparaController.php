<?php

namespace App\Http\Controllers;

use App\Models\MtrDeparaResiduo;
use App\Models\MtrResiduo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MtrDeparaController extends BaseController
{
    protected $model = MtrDeparaResiduo::class;
    protected $redirectPage = '/mtr/depara-residuos';
    protected $formTitle = 'De-Para de Resíduos';

    protected function rules(): array
    {
        return [
            'orgao' => 'required',
            'cod_ibama' => 'required',
            'descricao_residuo' => 'required',
            'classe_residuo' => 'required',
            'unidade_medida' => 'required',
        ];
    }

    protected function messages(): array
    {
        return [
            'orgao.required' => 'Informe o órgão.',
            'cod_ibama.required' => 'Informe o código IBAMA.',
            'descricao_residuo.required' => 'Informe a descrição do resíduo.',
            'classe_residuo.required' => 'Informe a classe do resíduo.',
            'unidade_medida.required' => 'Informe a unidade de medida.',
        ];
    }

    public function index(Request $request)
    {
        $deparas = MtrDeparaResiduo::query()
            ->with(['produto', 'categoria', 'subCategoria.categoria'])
            ->where('empresa_id', $this->empresa_id)
            ->orderByDesc('id')
            ->get();

        $produtos = DB::table('produtos')
            ->where('empresa_id', $this->empresa_id)
            ->pluck('nome', 'id');

        return view('mtr.depara.index', [
            'deparas' => $deparas,
            'produtosMapa' => $produtos,
            'title' => 'De-Para de Resíduos (MTR)',
        ]);
    }

    public function create(Request $request)
    {
        return view('mtr.depara.create', $this->formData() + [
            'title' => 'Novo Vínculo De-Para Resíduo',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules(), $this->messages());

        try {
            MtrDeparaResiduo::create($this->payload($request));

            return redirect()->route('mtr.depara.index')
                ->with('sucesso', 'Regra De-Para salva com sucesso!');
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar De-Para MTR.', [
                'empresa_id' => $this->empresa_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function edit(Request $request, $id)
    {
        $depara = MtrDeparaResiduo::where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        return view('mtr.depara.edit', $this->formData() + [
            'depara' => $depara,
            'title' => 'Editar De-Para de Resíduo',
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->rules(), $this->messages());

        try {
            $depara = MtrDeparaResiduo::where('empresa_id', $this->empresa_id)
                ->findOrFail($id);
            $depara->fill($this->payload($request));
            $depara->save();

            return redirect()->route('mtr.depara.index')
                ->with('sucesso', 'Regra De-Para atualizada!');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        $depara = MtrDeparaResiduo::where('empresa_id', $this->empresa_id)
            ->findOrFail($id);
        $depara->delete();

        return redirect()->route('mtr.depara.index')
            ->with('sucesso', 'Vínculo removido com sucesso!');
    }

    private function formData(): array
    {
        return [
            'subcategorias' => DB::table('sub_categorias')
                ->join('categorias', 'sub_categorias.categoria_id', '=', 'categorias.id')
                ->where('categorias.empresa_id', $this->empresa_id)
                ->select('sub_categorias.id', 'sub_categorias.nome', 'categorias.nome as categoria_nome')
                ->orderBy('sub_categorias.nome')
                ->get(),
            'produtos' => DB::table('produtos')
                ->where('empresa_id', $this->empresa_id)
                ->select('id', 'nome', 'NCM')
                ->orderBy('nome')
                ->get(),
            'categorias' => DB::table('categorias')
                ->where('empresa_id', $this->empresa_id)
                ->select('id', 'nome')
                ->orderBy('nome')
                ->get(),
            'residuos' => MtrResiduo::query()
                ->where('empresa_id', $this->empresa_id)
                ->orderBy('res_codigo_ibama')
                ->get(),
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'orgao' => strtoupper((string) $request->orgao),
            'produto_id' => $request->filled('produto_id') ? $request->produto_id : null,
            'sub_categoria_id' => $request->filled('sub_categoria_id') ? $request->sub_categoria_id : null,
            'categoria_id' => $request->filled('categoria_id') ? $request->categoria_id : null,
            'ncm' => $request->filled('ncm') ? $request->ncm : null,
            'cod_ibama' => $request->cod_ibama,
            'descricao_residuo' => $request->descricao_residuo,
            'classe_residuo' => $request->classe_residuo,
            'estado_fisico' => $request->input('estado_fisico', 1),
            'acondicionamento_id' => $request->acondicionamento_id,
            'tratamento_id' => $request->tratamento_id,
            'unidade_medida' => $request->input('unidade_medida', 'Kg'),
            'fator_conversao' => $request->input('fator_conversao', 1.0000),
        ];
    }
}

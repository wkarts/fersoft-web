<?php

namespace App\Http\Controllers;

use App\Models\MtrConfig;
use App\Services\SinirIemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MtrUnidadeController extends BaseController
{
    protected $model = MtrConfig::class;
    protected $redirectPage = '/mtr/unidades';
    protected $formTitle = 'Credencial MTR';

    protected function rules(): array
    {
        return [
            'orgao' => 'required',
            'cpf_cnpj' => 'required',
            'unidade_id' => 'required',
            'ambiente' => 'required',
            'perfil' => 'required',
        ];
    }

    protected function messages(): array
    {
        return [
            'orgao.required' => 'Informe o órgão do MTR.',
            'cpf_cnpj.required' => 'Informe o CPF/CNPJ da unidade.',
            'unidade_id.required' => 'Informe a unidade do portal MTR.',
            'ambiente.required' => 'Informe o ambiente.',
            'perfil.required' => 'Informe o perfil.',
        ];
    }

    public function index(Request $request)
    {
        $unidades = MtrConfig::query()
            ->where('empresa_id', $this->empresa_id)
            ->orderByDesc('id')
            ->get();

        $filiais = DB::table('filials')
            ->where('empresa_id', $this->empresa_id)
            ->pluck('descricao', 'id');

        return view('mtr.unidades.index', [
            'unidades' => $unidades,
            'filiais' => $filiais,
            'title' => 'Unidades e Credenciais MTR',
        ]);
    }

    public function create(Request $request)
    {
        return view('mtr.unidades.create', $this->formData() + [
            'title' => 'Nova Credencial / Unidade MTR',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules(), $this->messages());

        if ($request->input('perfil') === 'Gerador' && !$request->filled('senha')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['senha' => 'A senha/token do Portal MTR é obrigatória para o perfil Gerador.']);
        }

        try {
            MtrConfig::create($this->payload($request, true));

            return redirect()->route('mtr.unidades.index')
                ->with('sucesso', 'Unidade cadastrada com sucesso!');
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar credencial MTR.', [
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()
                ->with('erro', 'Erro ao salvar unidade: ' . $e->getMessage());
        }
    }

    public function edit(Request $request, $id)
    {
        $unidade = MtrConfig::where('empresa_id', $this->empresa_id)->findOrFail($id);

        return view('mtr.unidades.edit', $this->formData() + [
            'unidade' => $unidade,
            'title' => 'Editar Credencial MTR',
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->rules(), $this->messages());

        try {
            $unidade = MtrConfig::where('empresa_id', $this->empresa_id)->findOrFail($id);
            $unidade->fill($this->payload($request, false));
            $unidade->save();

            return redirect()->route('mtr.unidades.index')
                ->with('sucesso', 'Credenciais atualizadas com sucesso!');
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar credencial MTR.', [
                'empresa_id' => $this->empresa_id,
                'registro_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()
                ->with('erro', 'Erro ao atualizar credenciais: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        $unidade = MtrConfig::where('empresa_id', $this->empresa_id)->findOrFail($id);
        $unidade->delete();

        return redirect()->route('mtr.unidades.index')
            ->with('sucesso', 'Credencial removida com sucesso!');
    }

    public function testarConexao(Request $request)
    {
        try {
            $orgao = $request->input('orgao', 'SINIR');
            $ambiente = $request->input('ambiente', 'homologacao');
            $cpfCnpj = preg_replace('/\D+/', '', (string) $request->input('cpf_cnpj'));
            $cpfUsuario = preg_replace('/\D+/', '', (string) $request->input('cpf_usuario'));
            $senha = (string) $request->input('senha', '');
            $unidade = (string) $request->input('unidade_id', '1');

            if ($senha === '' && $request->filled('unidade_db_id')) {
                $config = MtrConfig::where('empresa_id', $this->empresa_id)
                    ->findOrFail($request->unidade_db_id);
                $senha = (string) $config->senha;
                $cpfCnpj = $cpfCnpj ?: preg_replace('/\D+/', '', (string) $config->cpf_cnpj);
                $cpfUsuario = $cpfUsuario ?: preg_replace('/\D+/', '', (string) $config->cpf_usuario);
            }

            if ($senha === '') {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Informe a senha/token do Portal MTR para realizar o teste.',
                ], 422);
            }

            $service = new SinirIemaService($orgao, $ambiente);
            $service->getToken($cpfCnpj, $senha, $unidade, $cpfUsuario ?: null);

            return response()->json([
                'sucesso' => true,
                'mensagem' => "Autenticação realizada com sucesso no {$orgao}!",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => $e->getMessage(),
            ], 400);
        }
    }

    private function formData(): array
    {
        return [
            'filiais' => DB::table('filials')
                ->where('empresa_id', $this->empresa_id)
                ->select('id', 'descricao')
                ->orderBy('descricao')
                ->get(),
            'clientes' => DB::table('clientes')
                ->where('empresa_id', $this->empresa_id)
                ->select('id', 'razao_social', 'cpf_cnpj')
                ->orderBy('razao_social')
                ->get(),
            'produtos' => DB::table('produtos')
                ->where('empresa_id', $this->empresa_id)
                ->select('id', 'nome', 'NCM')
                ->orderBy('nome')
                ->get(),
        ];
    }

    private function payload(Request $request, bool $creating): array
    {
        $data = [
            'filial_id' => $request->filled('filial_id') ? $request->filial_id : null,
            'orgao' => strtoupper((string) $request->orgao),
            'cpf_cnpj' => preg_replace('/\D+/', '', (string) $request->cpf_cnpj),
            'cpf_usuario' => preg_replace('/\D+/', '', (string) $request->input('cpf_usuario', '')),
            'unidade_id' => $request->unidade_id,
            'perfil' => $request->input('perfil', 'Gerador'),
            'descricao' => $request->input('descricao', ''),
            'ambiente' => $request->ambiente,
            'ativo' => $request->boolean('ativo'),
        ];

        if ($request->filled('senha')) {
            $data['senha'] = $request->senha;
        } elseif ($creating) {
            $data['senha'] = null;
        }

        return $data;
    }
}

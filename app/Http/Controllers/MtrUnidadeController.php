<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SinirIemaService;
use Exception;

class MtrUnidadeController extends Controller
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

        $unidades = DB::table('mtr_configs')
            ->leftJoin('filials', 'mtr_configs.filial_id', '=', 'filials.id')
            ->leftJoin('usuarios', 'mtr_configs.usuario_id', '=', 'usuarios.id')
            ->where('mtr_configs.empresa_id', $sessao['empresa_id'])
            ->select(
                'mtr_configs.*',
                'filials.descricao as filial_nome',
                'usuarios.nome as usuario_nome'
            )
            ->orderBy('mtr_configs.id', 'desc')
            ->get();

        $title = 'Unidades e Credenciais MTR';
        return view('mtr.unidades.index', compact('unidades', 'title'));
    }

    public function create(Request $request)
    {
        $sessao = $this->getDadosSessao();

        $filiais = DB::table('filials')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'descricao')
            ->get();

        $clientes = DB::table('clientes')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'razao_social', 'cpf_cnpj')
            ->orderBy('razao_social')
            ->get();

        $produtos = DB::table('produtos')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'nome', 'NCM')
            ->orderBy('nome')
            ->get();

        $title = 'Nova Credencial / Unidade MTR';
        return view('mtr.unidades.create', compact('filiais', 'clientes', 'produtos', 'title'));
    }

    public function store(Request $request)
    {
        $regras = [
            'orgao'      => 'required',
            'cpf_cnpj'   => 'required',
            'unidade_id' => 'required',
            'ambiente'   => 'required',
            'perfil'     => 'required',
        ];

        if ($request->input('perfil') === 'Gerador') {
            $regras['senha'] = 'required';
        }

        $request->validate($regras, [
            'senha.required' => 'A senha do Portal MTR é obrigatória para o perfil Gerador.'
        ]);

        $sessao = $this->getDadosSessao();

        try {
            DB::table('mtr_configs')->insert([
                'empresa_id'  => $sessao['empresa_id'],
                'filial_id'   => $request->filled('filial_id') ? $request->filial_id : $sessao['filial_id'],
                'usuario_id'  => $sessao['usuario_id'],
                'orgao'       => $request->orgao,
                'cpf_cnpj'    => preg_replace('/\D/', '', $request->cpf_cnpj),
                'cpf_usuario' => preg_replace('/\D/', '', $request->input('cpf_usuario', '')),
                'senha'       => $request->senha ?? '',
                'unidade_id'  => $request->unidade_id,
                'perfil'      => $request->input('perfil', 'Gerador'),
                'descricao'   => $request->input('descricao', ''),
                'ambiente'    => $request->ambiente,
                'ativo'       => $request->has('ativo') ? 1 : 0,
                'created_at'  => now(),
                'updated_at'  => now()
            ]);

            return redirect()->route('mtr.unidades.index')
                ->with('sucesso', 'Unidade cadastrada com sucesso!');

        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('erro', 'Erro ao salvar unidade: ' . $e->getMessage());
        }
    }

    public function edit(Request $request, $id)
    {
        $sessao = $this->getDadosSessao();

        $unidade = DB::table('mtr_configs')
            ->where('id', $id)
            ->where('empresa_id', $sessao['empresa_id'])
            ->first();

        if (!$unidade) {
            return redirect()->route('mtr.unidades.index')->with('erro', 'Registro não encontrado.');
        }

        $filiais = DB::table('filials')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'descricao')
            ->get();

        $clientes = DB::table('clientes')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'razao_social', 'cpf_cnpj')
            ->get();

        $produtos = DB::table('produtos')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'nome', 'NCM')
            ->get();

        $title = 'Editar Credencial MTR';
        return view('mtr.unidades.edit', compact('unidade', 'filiais', 'clientes', 'produtos', 'title'));
    }

    public function update(Request $request, $id)
    {
        $sessao = $this->getDadosSessao();

        try {
            $dadosUpdate = [
                'filial_id'   => $request->filled('filial_id') ? $request->filial_id : $sessao['filial_id'],
                'usuario_id'  => $sessao['usuario_id'],
                'orgao'       => $request->orgao,
                'cpf_cnpj'    => preg_replace('/\D/', '', $request->cpf_cnpj),
                'cpf_usuario' => preg_replace('/\D/', '', $request->input('cpf_usuario', '')),
                'unidade_id'  => $request->unidade_id,
                'perfil'      => $request->input('perfil', 'Gerador'),
                'descricao'   => $request->input('descricao', ''),
                'ambiente'    => $request->ambiente,
                'ativo'       => $request->has('ativo') ? 1 : 0,
                'updated_at'  => now()
            ];

            // Preserva a senha existente se o usuário não preencheu uma nova na tela
            if ($request->filled('senha')) {
                $dadosUpdate['senha'] = $request->senha;
            }

            DB::table('mtr_configs')
                ->where('id', $id)
                ->where('empresa_id', $sessao['empresa_id'])
                ->update($dadosUpdate);

            return redirect()->route('mtr.unidades.index')->with('sucesso', 'Credenciais atualizadas com sucesso!');

        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('erro', 'Erro ao atualizar credenciais: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        $sessao = $this->getDadosSessao();

        DB::table('mtr_configs')
            ->where('id', $id)
            ->where('empresa_id', $sessao['empresa_id'])
            ->delete();

        return redirect()->route('mtr.unidades.index')->with('sucesso', 'Credencial removida com sucesso!');
    }

    public function testarConexao(Request $request)
    {
        try {
            $sessao     = $this->getDadosSessao();
            $orgao      = $request->input('orgao', 'SINIR');
            $ambiente   = $request->input('ambiente', 'homologacao');
            $cpfUsuario = preg_replace('/\D/', '', $request->input('cpf_usuario', $request->input('cpf_cnpj')));
            $senha      = $request->input('senha');
            $unidade    = $request->input('unidade_id', '1');
            $unidadeDb  = $request->input('unidade_db_id');

            // Se a senha veio vazia na tela de edição, busca a senha salva no banco
            if (empty($senha) && !empty($unidadeDb)) {
                $configSalva = DB::table('mtr_configs')
                    ->where('id', $unidadeDb)
                    ->where('empresa_id', $sessao['empresa_id'])
                    ->first();

                if ($configSalva && !empty($configSalva->senha)) {
                    $senha = $configSalva->senha;
                }
            }

            if (empty($senha)) {
                return response()->json([
                    'sucesso'  => false, 
                    'mensagem' => 'Informe a Senha do Portal MTR para realizar o teste de login.'
                ], 400);
            }

            $service = new SinirIemaService($orgao, $ambiente);
            $token = $service->getToken($cpfUsuario, $senha, $unidade);

            return response()->json([
                'sucesso'  => true,
                'mensagem' => "Autenticação realizada com sucesso no {$orgao}!"
            ]);

        } catch (Exception $e) {
            return response()->json(['sucesso' => false, 'mensagem' => $e->getMessage()], 400);
        }
    }
}
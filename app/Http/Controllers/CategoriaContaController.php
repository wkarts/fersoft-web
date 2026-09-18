<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaConta;

class CategoriaContaController extends Controller
{
    protected $empresa_id = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $value = session('user_logged');

            if (!$value) {
                return redirect('/login');
            }

            // Mantém compatibilidade com request, mas prioriza a empresa real da sessão
            $this->empresa_id = __empresa_id_logada() ?? $request->empresa_id;

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = CategoriaConta::where('empresa_id', $this->empresa_id);

        // Aplica filtro por empresa + filial/local permitido do usuário
        __aplicar_filtro_empresa_filial($query);

        $categorias = $query
            ->orderBy('nome', 'asc')
            ->get();

        return view('categoriasConta/list')
            ->with('categorias', $categorias)
            ->with('title', 'Categoria de Contas');
    }

    public function new()
    {
        return view('categoriasConta/register')
            ->with('title', 'Cadastrar Categoria de Conta');
    }

    public function save(Request $request)
    {
        $this->_validate($request);

        $filialRequest = $request->input('filial_id', '-1');

        if (!__filial_solicitada_valida_para_usuario($filialRequest === null ? 'matriz' : $filialRequest)) {
            session()->flash('mensagem_erro', 'Você não possui permissão para utilizar este local.');
            return redirect()->back()->withInput();
        }

        $data = $request->all();

        $data['empresa_id'] = $this->empresa_id;
        $data['usuario_id'] = get_id_user();
        $data['filial_id'] = __normaliza_filial_banco($filialRequest);
        $data['incluir_resultado'] = $request->has('incluir_resultado') ? 1 : 0;

        $data['ignora_terceiro'] = $request->has('ignora_terceiro') ? 1 : 0;
        $data['gera_provisao'] = $request->has('gera_provisao') ? 1 : 0;
        $data['int_ctb'] = $request->input('int_ctb');
        // NOVO CAMPO: Captura o CTB para Serviços
        $data['int_ctb_servico'] = $request->input('int_ctb_servico');

        $result = CategoriaConta::create($data);

        if ($result) {
            session()->flash('mensagem_sucesso', 'Categoria cadastrada com sucesso.');
        } else {
            session()->flash('mensagem_erro', 'Erro ao cadastrar categoria.');
        }

        return redirect('/categoriasConta');
    }

    public function edit($id)
    {
        $resp = CategoriaConta::where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!$resp) {
            return redirect('/404');
        }

        if (!valida_objeto($resp)) {
            return redirect('/403');
        }

        // Segurança adicional por local
        if (!$this->usuarioPodeAcessarFilialRegistro($resp->filial_id)) {
            return redirect('/403');
        }

        return view('categoriasConta/register')
            ->with('categoria', $resp)
            ->with('title', 'Editar Categoria de Conta');
    }

    public function update(Request $request)
    {
        $id = $request->input('id');

        $resp = CategoriaConta::where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!$resp) {
            return redirect('/404');
        }

        if (!valida_objeto($resp)) {
            return redirect('/403');
        }

        if (!$this->usuarioPodeAcessarFilialRegistro($resp->filial_id)) {
            return redirect('/403');
        }

        $this->_validate($request);

        $filialRequest = $request->input('filial_id', '-1');

        if (!__filial_solicitada_valida_para_usuario($filialRequest === null ? 'matriz' : $filialRequest)) {
            session()->flash('mensagem_erro', 'Você não possui permissão para utilizar este local.');
            return redirect()->back()->withInput();
        }

        $resp->nome = $request->input('nome');
        $resp->tipo = $request->input('tipo');
        $resp->dre_grupo = $request->input('dre_grupo');
        $resp->incluir_resultado = $request->has('incluir_resultado') ? 1 : 0;
        $resp->filial_id = __normaliza_filial_banco($filialRequest);
        $resp->usuario_id = get_id_user();

        $resp->conta_contabil_despesa_id = $request->input('conta_contabil_despesa_id');
        $resp->conta_contabil_provisao_id = $request->input('conta_contabil_provisao_id');
        $resp->ignora_terceiro = $request->has('ignora_terceiro') ? 1 : 0;
        $resp->gera_provisao = $request->has('gera_provisao') ? 1 : 0;
        $resp->int_ctb = $request->input('int_ctb');
        // NOVO CAMPO: Atualiza o CTB para Serviços
        $resp->int_ctb_servico = $request->input('int_ctb_servico');

        $result = $resp->save();

        if ($result) {
            session()->flash('mensagem_sucesso', 'Categoria editada com sucesso!');
        } else {
            session()->flash('mensagem_erro', 'Erro ao editar categoria!');
        }

        return redirect('/categoriasConta');
    }

    public function delete($id)
    {
        $resp = CategoriaConta::where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!$resp) {
            return redirect('/404');
        }

        if (!valida_objeto($resp)) {
            return redirect('/403');
        }

        if (!$this->usuarioPodeAcessarFilialRegistro($resp->filial_id)) {
            return redirect('/403');
        }

        if ($resp->delete()) {
            session()->flash('mensagem_sucesso', 'Registro removido!');
        } else {
            session()->flash('mensagem_erro', 'Erro!');
        }

        return redirect('/categoriasConta');
    }

    private function usuarioPodeAcessarFilialRegistro($filialId): bool
    {
        if (__usuario_pode_ver_todos_locais()) {
            return true;
        }

        $locaisPermitidos = __usuario_locais_ids_logado();

        if ($filialId === null || $filialId == 0) {
            return in_array(-1, $locaisPermitidos, true) || in_array('-1', $locaisPermitidos, true);
        }

        return in_array((int)$filialId, $locaisPermitidos, true) || in_array((string)$filialId, $locaisPermitidos, true);
    }

    private function _validate(Request $request)
    {
        $rules = [
            'nome' => 'required|max:50',
            'tipo' => 'required',
        ];

        $messages = [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => '50 caracteres maximos permitidos.',
            'tipo.required' => 'O campo tipo é obrigatório.',
        ];

        $this->validate($request, $rules, $messages);
    }
}

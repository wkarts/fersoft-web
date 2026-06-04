<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfiguracaoContabil;
use App\Models\PlanoContasContabil;

class ContabilidadeConfigController extends BaseController
{

    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    public function __construct() {
        $this->formTitle = 'Configurações Contábeis';
        $this->redirectPage = '/contabilidade/configuracoes';
        parent::__construct();
    }

    public function index() {
        // CORREÇÃO: Buscando pela chave correta 'empresa'
        $empresa_id = session('user_logged')['empresa'] ?? null;

        if (!$empresa_id) {
            return back()->with('erro', 'Erro: Identificação da empresa não encontrada na sessão.');
        }

        $config = ConfiguracaoContabil::firstOrCreate(['empresa_id' => $empresa_id]);
        $planoContas = PlanoContasContabil::where('empresa_id', $empresa_id)->get();

        return view('contabilidade.configuracoes', [
            'config' => $config,
            'planoContas' => $planoContas,
            'title' => $this->formTitle
        ]);
    }

    public function save(Request $request) {
        $empresa_id = session('user_logged')['empresa'] ?? null;

        if (!$empresa_id) {
            return back()->with('erro', 'Erro: Identificação da empresa não encontrada.');
        }

        // 1. Buscamos ou criamos o registro na tabela de configurações contábeis
        $config = ConfiguracaoContabil::firstOrCreate(['empresa_id' => $empresa_id]);

        // 2. Preenchemos todos os campos, incluindo os novos
        $config->codigo_prosoft = $request->input('codigo_prosoft');
        $config->conta_estoque = $request->input('conta_estoque');

        // Preenche os demais campos dinamicamente (filtro dos impostos/financeiro)
        $data = $request->except(['_token', 'codigo_prosoft', 'conta_estoque']);
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $config->$key = $value;
            }
        }

        $config->save();

        return redirect()->route('contabilidade.configuracoes.index')
            ->with('sucesso', 'Configurações salvas com sucesso!');
    }

}

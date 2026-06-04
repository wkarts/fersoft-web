<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaEmpresa;
use App\Models\PlanoConta;
use App\Models\ItemContaEmpresa;
use App\Models\ConfigNota;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf; // <-- IMPORTANTE: Adicionado para a impressão do Extrato
use App\Models\MultiEmpresaTrait;

class ContaEmpresaController extends BaseController
{
    use MultiEmpresaTrait;

    /**
     * Propriedades exigidas pelo BaseController para o funcionamento padrão.
     */
    protected $model = ContaEmpresa::class;
    protected $formTitle = 'Conta Empresa';
    protected $redirectPage = 'contas-empresa.index';
    protected $listView = 'conta_empresa/index';
    protected $registerView = 'conta_empresa/register';

    /**
     * Define as regras de validação (Exigido pelo BaseController).
     */
    public function rules(): array
    {
        return [
            'nome' => 'required|max:50',
            'saldo_inicial' => 'required',
            'plano_conta_id' => 'required',
        ];
    }

    /**
     * Define as mensagens de validação (Exigido pelo BaseController).
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Campo obrigatório.',
            'nome.max' => '50 caracteres máximos permitidos.',
            'saldo_inicial.required' => 'Campo obrigatório.',
            'plano_conta_id.required' => 'Campo obrigatório.',
        ];
    }

    public function index(Request $request)
    {
        $query = ContaEmpresa::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa_id);

        if (function_exists('__aplicar_filtro_empresa_filial')) {
            __aplicar_filtro_empresa_filial($query);
        }

        $data = $query
            ->orderBy('nome', 'asc')
            ->get();

        return view('conta_empresa/index', compact('data'));
    }

    public function create()
    {
        // 1. Busca os planos de conta do sistema
        $planos = PlanoConta::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao', 'asc')
            ->get();

        if (sizeof($planos) == 0) {
            session()->flash('mensagem_erro', 'Defina o plano de contas');
            return redirect()->route('plano-contas.index');
        }

        // 2. Busca as contas contábeis para a integração Prosoft
        $planoContasContabeis = \App\Models\PlanoContasContabil::where('empresa_id', $this->empresa_id)->get(); 

        // 3. Envia tudo para a view
        return view('conta_empresa/register', compact('planos', 'planoContasContabeis'));
    }

    public function edit($id)
    {
        // 1. Busca a conta empresa que será editada
        $item = ContaEmpresa::withoutGlobalScopes()
            ->where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->firstOrFail();

        if (!$this->usuarioPodeAcessarFilialRegistro($item->filial_id)) {
            return redirect('/403');
        }

        // 2. Busca os planos de conta do sistema
        $planos = PlanoConta::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao', 'asc')
            ->get();

        if (sizeof($planos) == 0) {
            session()->flash('mensagem_erro', 'Defina o plano de contas');
            return redirect()->route('plano-contas.index');
        }

        // 3. Busca as contas contábeis para a integração Prosoft
        $planoContasContabeis = \App\Models\PlanoContasContabil::where('empresa_id', $this->empresa_id)->get();

        // 4. Envia o item e as listas para a view
        return view('conta_empresa/register', compact('planos', 'item', 'planoContasContabeis'));
    }

    public function store(Request $request)
    {
        $this->validate($request, $this->rules(), $this->messages());

        try {
            $filialRequest = $request->input('filial_id', $request->input('local', '-1'));

            if (!__filial_solicitada_valida_para_usuario($filialRequest === null ? 'matriz' : $filialRequest)) {
                session()->flash('mensagem_erro', 'Você não possui permissão para utilizar este local.');
                return redirect()->back()->withInput();
            }

            // Lógica para definir se é Matriz (NULL) ou Filial (ID)
            $filial_final = null;
            if ($filialRequest != '-1' && $filialRequest != 'matriz' && $filialRequest != '') {
                $filial_final = (int)$filialRequest;
            }

            // Criamos o objeto manualmente para garantir que nenhum Trait sobrescreva
            $item = new ContaEmpresa();
            $item->nome = $request->nome;
            $item->banco = $request->banco;
            $item->agencia = $request->agencia;
            $item->conta = $request->conta;
            $item->plano_conta_id = $request->plano_conta_id;
            $item->saldo = __replace($request->saldo_inicial);
            $item->saldo_inicial = __replace($request->saldo_inicial);
            $item->status = $request->status ?? 1;
            $item->exibir_dashboard_analitico = $request->has('exibir_dashboard_analitico') ? 1 : 0;
            $item->empresa_id = $this->empresa_id;
            $item->usuario_id = $this->usuario_id ?? get_id_user();
            $item->filial_id = $filial_final; // Atribuição direta e forçada
          	$item->conta_contabil_id = $request->conta_contabil_id;

            $item->save();

            session()->flash('mensagem_sucesso', 'Conta cadastrada!');
            return redirect()->route($this->redirectPage);

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = ContaEmpresa::withoutGlobalScopes()
                ->where('id', $id)
                ->where('empresa_id', $this->empresa_id)
                ->firstOrFail();

            if (!$this->usuarioPodeAcessarFilialRegistro($item->filial_id)) {
                return redirect('/403');
            }

            // Pega o ID que veio do Select
            $filialRequest = $request->input('filial_id', $request->input('local', '-1'));

            // Validação de permissão
            if (!__filial_solicitada_valida_para_usuario($filialRequest === null ? 'matriz' : $filialRequest)) {
                session()->flash('mensagem_erro', 'Você não possui permissão para utilizar este local.');
                return redirect()->back()->withInput();
            }

            // Lógica forçada para o ID da filial
            $filial_final = null;
            if ($filialRequest != '-1' && $filialRequest != 'matriz' && $filialRequest != '') {
                $filial_final = (int)$filialRequest;
            }

            // ATRIBUIÇÃO DIRETA (Não usamos fill para a filial, para evitar bloqueios do Eloquent)
            $item->nome = $request->nome;
            $item->banco = $request->banco;
            $item->agencia = $request->agencia;
            $item->conta = $request->conta;
            $item->plano_conta_id = $request->plano_conta_id;
            $item->saldo_inicial = __replace($request->saldo_inicial);
            $item->status = $request->status;
            $item->exibir_dashboard_analitico = $request->has('exibir_dashboard_analitico') ? 1 : 0;
            $item->filial_id = $filial_final; // <--- Forçamos o ID (ex: 8) aqui!
            $item->usuario_id = $this->usuario_id ?? get_id_user();
          	$item->conta_contabil_id = $request->conta_contabil_id;

            $item->save();

            session()->flash('mensagem_sucesso', 'Conta atualizada!');
            return redirect()->route($this->redirectPage);

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy($id)
    {
        $item = ContaEmpresa::withoutGlobalScopes()
            ->where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->firstOrFail();

        if (!$this->usuarioPodeAcessarFilialRegistro($item->filial_id)) {
            return redirect('/403');
        }

        $item->delete();

        session()->flash('mensagem_sucesso', 'Conta removida');
        return redirect()->back();
    }

    /**
     * Alias para o método show, caso a rota procure por "extrato"
     */
    public function extrato(Request $request, $id = null)
    {
        return $this->show($request, $id);
    }

    public function show(Request $request, $id = null)
    {
        // Se o ID não veio na rota, tenta pegar do Query String
        $id = $id ?? $request->id;

        $data_inicio = $request->data_inicio ?? date('Y-m-d');
        $data_final = $request->data_final ?? date('Y-m-d');
        $tipo = $request->tipo;

        $item = ContaEmpresa::withoutGlobalScopes()
            ->where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->firstOrFail();

        if (!$this->usuarioPodeAcessarFilialRegistro($item->filial_id)) {
            return redirect('/403');
        }

        $categoriasQuery = DB::table('categoria_contas')->where('empresa_id', $this->empresa_id);
        if (function_exists('__aplicar_filtro_empresa_filial')) {
            __aplicar_filtro_empresa_filial($categoriasQuery);
        }
        $categorias = $categoriasQuery->get();

        $contasQuery = ContaEmpresa::withoutGlobalScopes()->where('empresa_id', $this->empresa_id);
        if (function_exists('__aplicar_filtro_empresa_filial')) {
            __aplicar_filtro_empresa_filial($contasQuery);
        }
        $contas = $contasQuery->orderBy('nome', 'asc')->get();

        $saldo_anterior = $item->saldo_inicial;
        $entradas = ItemContaEmpresa::where('conta_id', $id)->where('tipo', 'entrada')
            ->whereRaw('COALESCE(data_pagamento, created_at) < ?', [$data_inicio])->sum('valor');
        $saidas = ItemContaEmpresa::where('conta_id', $id)->where('tipo', 'saida')
            ->whereRaw('COALESCE(data_pagamento, created_at) < ?', [$data_inicio])->sum('valor');

        $saldo_anterior += ($entradas - $saidas);

        $data = ItemContaEmpresa::where('conta_id', $id)
            ->orderByRaw('COALESCE(data_pagamento, created_at) ASC')
            ->when($data_inicio, function ($q) use ($data_inicio) { return $q->whereRaw('COALESCE(data_pagamento, created_at) >= ?', [$data_inicio]); })
            ->when($data_final, function ($q) use ($data_final) { return $q->whereRaw('COALESCE(data_pagamento, created_at) <= ?', [$data_final]); })
            ->when($tipo, function ($q) use ($tipo) { return $q->where('tipo', $tipo); })
            ->paginate(50);

        return view('conta_empresa/show', compact('data', 'item', 'data_inicio', 'data_final', 'tipo', 'saldo_anterior', 'categorias', 'contas'));
    }

    /**
     * VERSÃO CORRIGIDA COM DOMPDF PARA IMPRIMIR EM BRANCO (OU COM DADOS)
     */
    public function imprimirExtrato(Request $request, $id = null)
    {
        $id = $id ?? $request->id;
        $item = ContaEmpresa::withoutGlobalScopes()->where('id', $id)->where('empresa_id', $this->empresa_id)->firstOrFail();

        if (!$this->usuarioPodeAcessarFilialRegistro($item->filial_id)) { return redirect('/403'); }

        if (ob_get_contents()) { ob_end_clean(); }

        $empresa = Empresa::find($this->empresa_id) ?? Empresa::first();
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        // --- LÓGICA DA LOGO ---
        $nomeLogo = '';
        if ($item->filial_id) {
            // Se a conta é de uma filial, busca na tabela filials
            $nomeLogo = DB::table('filials')->where('id', $item->filial_id)->value('logo');
        } else {
            // Se for da matriz/empresa, busca na config_notas
            $nomeLogo = $config->logo ?? '';
        }

        $caminhoLogo = '';
        if ($nomeLogo) {
            $caminhoFisico = public_path('logos/' . $nomeLogo);
            if (file_exists($caminhoFisico)) {
                $tipo = pathinfo($caminhoFisico, PATHINFO_EXTENSION);
                $data = file_get_contents($caminhoFisico);
                $caminhoLogo = 'data:image/' . $tipo . ';base64,' . base64_encode($data);
            }
        }
        // -----------------------

        $data_inicio = $request->data_inicio;
        $data_final = $request->data_final;
        $saldo_anterior = $item->saldo_inicial;

        if ($data_inicio) {
            $entradas = ItemContaEmpresa::where('conta_id', $id)->where('tipo', 'entrada')->whereRaw('COALESCE(data_pagamento, created_at) < ?', [$data_inicio])->sum('valor');
            $saidas = ItemContaEmpresa::where('conta_id', $id)->where('tipo', 'saida')->whereRaw('COALESCE(data_pagamento, created_at) < ?', [$data_inicio])->sum('valor');
            $saldo_anterior += ($entradas - $saidas);
        }

        $movimentacoes = ItemContaEmpresa::where('conta_id', $id)
            ->orderByRaw('COALESCE(data_pagamento, created_at) ASC')
            ->when($data_inicio, function ($q) use ($data_inicio) { return $q->whereRaw('COALESCE(data_pagamento, created_at) >= ?', [$data_inicio]); })
            ->when($data_final, function ($q) use ($data_final) { return $q->whereRaw('COALESCE(data_pagamento, created_at) <= ?', [$data_final]); })
            ->get();

        $p = view('conta_empresa.print', compact('item', 'movimentacoes', 'saldo_anterior', 'data_inicio', 'data_final', 'empresa', 'config', 'caminhoLogo'));

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);
        $domPdf->setPaper("A4", "portrait");
        $domPdf->render();

        return response($domPdf->output())->header('Content-Type', 'application/pdf');
    }

    public function imprimirTransacao($id)
    {
        if (ob_get_contents()) { ob_end_clean(); }
        $transacao = ItemContaEmpresa::findOrFail($id);
        $item = ContaEmpresa::withoutGlobalScopes()->where('id', $transacao->conta_id)->where('empresa_id', $this->empresa_id)->firstOrFail();
        if (!$this->usuarioPodeAcessarFilialRegistro($item->filial_id)) { return redirect('/403'); }

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first() ?? (object)['cnpj' => 'Não configurado'];
        $empresa = DB::table('empresas')->where('id', $this->empresa_id)->first();
        $p = view('relatorios/recibo_transacao', compact('transacao', 'item', 'config', 'empresa'));
        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);
        $domPdf->setPaper("A4");
        $domPdf->render();
        return response($domPdf->output())->header('Content-Type', 'application/pdf');
    }

    public function deleteLancamento(Request $request, $id = null)
    {
        $id_lancamento = $id ?? $request->id;
        $item = ItemContaEmpresa::findOrFail($id_lancamento);

        $conta = ContaEmpresa::withoutGlobalScopes()->where('id', $item->conta_id)->where('empresa_id', $this->empresa_id)->firstOrFail();
        if (!$this->usuarioPodeAcessarFilialRegistro($conta->filial_id)) { return redirect('/403'); }
        if ($item->origem != 'manual') { session()->flash('mensagem_erro', 'Lançamentos automáticos não podem ser excluídos.'); return redirect()->back(); }

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first() ?? ConfigNota::first();
        if (!$config || md5($request->senha) != $config->senha_remover) { session()->flash('mensagem_erro', 'Senha incorreta!'); return redirect()->back(); }

        try {
            DB::transaction(function () use ($item, $conta) {
                if ($item->tipo == 'entrada') { $conta->saldo -= $item->valor; }
                else { $conta->saldo += $item->valor; }
                $conta->save();
                $item->delete();
            });
            session()->flash('mensagem_sucesso', 'Excluído com sucesso!');
        } catch (\Exception $e) { session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage()); }
        return redirect()->back();
    }

    private function usuarioPodeAcessarFilialRegistro($filialId): bool
    {
        if (function_exists('__usuario_pode_ver_todos_locais')) {
            if (__usuario_pode_ver_todos_locais()) { return true; }
        }
        if (function_exists('__usuario_locais_ids_logado')) {
            $locaisPermitidos = (array) __usuario_locais_ids_logado();
            if ($filialId === null || $filialId == 0) {
                return in_array(-1, $locaisPermitidos, true) || in_array('-1', $locaisPermitidos, true);
            }
            return in_array((int)$filialId, $locaisPermitidos, true) || in_array((string)$filialId, $locaisPermitidos, true);
        }
        return true;
    }

    public function sincronizar(Request $request, $id)
    {
        // 1. Definição do Período (Pega do request ou usa o mês atual)
        $data_inicial = $request->data_inicial ? $this->parseDate($request->data_inicial) : date('Y-m-01');
        $data_final   = $request->data_final ? $this->parseDate($request->data_final) : date('Y-m-d');

        // 2. Localiza a conta destino (Itaú ou Caixa)
        $conta = \App\Models\ContaEmpresa::where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->firstOrFail();

        \DB::transaction(function () use ($conta, $data_inicial, $data_final) {

            $nomeLower = strtolower($conta->nome);
            $bancoLower = strtolower($conta->banco);

            // --- LÓGICA DE FILTRO POR TIPO DE PAGAMENTO ---
            // Se for Caixa/Dinheiro/Fundo Fixo: Pega apenas 'Dinheiro'
            if (str_contains($nomeLower, 'caixa') || str_contains($bancoLower, 'fundo fixo')) {
                $tiposPermitidos = ['Dinheiro'];
                $comparacao = 'in';
            } else {
                // Se for Itaú ou outros: Pega tudo EXCETO Dinheiro, Crédito e Adiantamento
                $tiposPermitidos = ['Dinheiro', 'Crédito', 'Cartão de Crédito', 'Adiantamento'];
                $comparacao = 'not_in';
            }

            // 3. BUSCA PAGAMENTOS (Contas a Pagar)
            $pagamentos = \DB::table('conta_pagars as cp')
                ->leftJoin('fornecedores as f', 'f.id', '=', 'cp.fornecedor_id')
                ->where('cp.empresa_id', $this->empresa_id)
                ->where('cp.status', 1)
                ->whereBetween('cp.data_pagamento', [$data_inicial, $data_final])
                ->where(function($q) use ($tiposPermitidos, $comparacao) {
                    if ($comparacao == 'in') $q->whereIn('cp.tipo_pagamento', $tiposPermitidos);
                    else $q->whereNotIn('cp.tipo_pagamento', $tiposPermitidos);
                })
                ->where(function($q) use ($conta) {
                    // Respeita Matriz (null) e Filial
                    return ($conta->filial_id === null) ? $q->whereNull('cp.filial_id') : $q->where('cp.filial_id', $conta->filial_id);
                })
                ->select('cp.*', 'f.razao_social as nome_entidade')
                ->get();

            // 4. BUSCA RECEBIMENTOS (Contas a Receber)
            $recebimentos = \DB::table('conta_recebers as cr')
                ->leftJoin('clientes as c', 'c.id', '=', 'cr.cliente_id')
                ->where('cr.empresa_id', $this->empresa_id)
                ->where('cr.status', 1)
                ->whereBetween('cr.data_recebimento', [$data_inicial, $data_final])
                ->where(function($q) use ($tiposPermitidos, $comparacao) {
                    if ($comparacao == 'in') $q->whereIn('cr.tipo_pagamento', $tiposPermitidos);
                    else $q->whereNotIn('cr.tipo_pagamento', $tiposPermitidos);
                })
                ->where(function($q) use ($conta) {
                    return ($conta->filial_id === null) ? $q->whereNull('cr.filial_id') : $q->where('cr.filial_id', $conta->filial_id);
                })
                ->select('cr.*', 'c.razao_social as nome_entidade')
                ->get();

            // 5. INSERÇÃO DOS LANÇAMENTOS FALTANTES
            foreach ($pagamentos as $p) {
                // Verifica se já existe para não duplicar
                $existe = \DB::table('item_conta_empresas')->where('conta_pagar_id', $p->id)->exists();
                if (!$existe) {
                    \App\Models\ItemContaEmpresa::create([
                        'conta_id' => $conta->id,
                        'descricao' => "Sinc Pgto: " . ($p->nome_entidade ?? 'Fornecedor'),
                        'tipo_pagamento' => $p->tipo_pagamento,
                        'valor' => $p->valor_integral,
                        'data_pagamento' => $p->data_pagamento,
                        'tipo' => 'saida',
                        'origem' => 'conta pagar',
                        'conta_pagar_id' => $p->id,
                        'categoria_id' => $p->categoria_id,
                        'empresa_id' => $this->empresa_id,
                        'user_id' => session('user_logged')['id']
                    ]);
                }
            }

            foreach ($recebimentos as $r) {
                $existe = \DB::table('item_conta_empresas')->where('conta_receber_id', $r->id)->exists();
                if (!$existe) {
                    \App\Models\ItemContaEmpresa::create([
                        'conta_id' => $conta->id,
                        'descricao' => "Sinc Rec: " . ($r->nome_entidade ?? 'Cliente'),
                        'tipo_pagamento' => $r->tipo_pagamento,
                        'valor' => $r->valor_integral,
                        'data_pagamento' => $r->data_recebimento,
                        'tipo' => 'entrada',
                        'origem' => 'conta a receber',
                        'conta_receber_id' => $r->id,
                        'categoria_id' => $r->categoria_id,
                        'empresa_id' => $this->empresa_id,
                        'user_id' => session('user_logged')['id']
                    ]);
                }
            }

            // 6. ATUALIZAÇÃO DO SALDO FINAL DA CONTA
            $itens = \App\Models\ItemContaEmpresa::where('conta_id', $conta->id)->get();
            $novoSaldo = $conta->saldo_inicial;
            foreach($itens as $i) {
                $novoSaldo = ($i->tipo == 'entrada') ? ($novoSaldo + $i->valor) : ($novoSaldo - $i->valor);
                $i->saldo_atual = $novoSaldo;
                $i->save();
            }
            $conta->saldo = $novoSaldo;
            $conta->save();
        });

        session()->flash('mensagem_sucesso', 'Sincronização realizada com sucesso!');
        return redirect()->back();
		}
}

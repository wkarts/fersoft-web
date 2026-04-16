<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaEmpresa;
use App\Models\PlanoConta;
use App\Models\ItemContaEmpresa;
use App\Models\ConfigNota;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use App\Models\MultiEmpresaTrait;

class ContaEmpresaController extends Controller
{
    use MultiEmpresaTrait;

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
        $query = ContaEmpresa::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa_id);

        __aplicar_filtro_empresa_filial($query);

        $data = $query
            ->orderBy('nome', 'asc')
            ->get();

        return view('conta_empresa/index', compact('data'));
    }

    public function create()
    {
        $planos = PlanoConta::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao', 'asc')
            ->get();

        if (sizeof($planos) == 0) {
            session()->flash('mensagem_erro', 'Defina o plano de contas');
            return redirect()->route('plano-contas.index');
        }

        return view('conta_empresa/register', compact('planos'));
    }

    public function edit($id)
    {
        $item = ContaEmpresa::withoutGlobalScopes()
            ->where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->firstOrFail();

        if (!$this->usuarioPodeAcessarFilialRegistro($item->filial_id)) {
            return redirect('/403');
        }

        $planos = PlanoConta::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao', 'asc')
            ->get();

        if (sizeof($planos) == 0) {
            session()->flash('mensagem_erro', 'Defina o plano de contas');
            return redirect()->route('plano-contas.index');
        }

        return view('conta_empresa/register', compact('planos', 'item'));
    }

    public function store(Request $request)
    {
        $this->_validate($request);

        try {
            $filialRequest = $request->input('filial_id', $request->input('local', '-1'));

            if (!__filial_solicitada_valida_para_usuario($filialRequest === null ? 'matriz' : $filialRequest)) {
                session()->flash('mensagem_erro', 'Você não possui permissão para utilizar este local.');
                return redirect()->back()->withInput();
            }

            $request->merge([
                'saldo' => __replace($request->saldo_inicial),
                'saldo_inicial' => __replace($request->saldo_inicial),
                'filial_id' => __normaliza_filial_banco($filialRequest),
                'empresa_id' => $this->empresa_id,
                'usuario_id' => get_id_user(),
            ]);

            ContaEmpresa::create($request->all());

            session()->flash('mensagem_sucesso', 'Conta cadastrada!');
            return redirect()->route('contas-empresa.index');
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

            $filialRequest = $request->input('filial_id', $request->input('local', '-1'));

            if (!__filial_solicitada_valida_para_usuario($filialRequest === null ? 'matriz' : $filialRequest)) {
                session()->flash('mensagem_erro', 'Você não possui permissão para utilizar este local.');
                return redirect()->back()->withInput();
            }

            $request->merge([
                'saldo_inicial' => __replace($request->saldo_inicial),
                'filial_id' => __normaliza_filial_banco($filialRequest),
                'empresa_id' => $this->empresa_id,
                'usuario_id' => get_id_user(),
            ]);

            $item->fill($request->all())->save();

            session()->flash('mensagem_sucesso', 'Conta atualizada!');
            return redirect()->route('contas-empresa.index');
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

    public function show(Request $request, $id)
    {
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

        $categoriasQuery = DB::table('categoria_contas')
            ->where('empresa_id', $this->empresa_id);

        __aplicar_filtro_empresa_filial($categoriasQuery);

        $categorias = $categoriasQuery->get();

        $contasQuery = ContaEmpresa::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa_id);

        __aplicar_filtro_empresa_filial($contasQuery);

        $contas = $contasQuery->orderBy('nome', 'asc')->get();

        $saldo_anterior = $item->saldo_inicial;

        if ($data_inicio) {
            $entradas = ItemContaEmpresa::where('conta_id', $id)
                ->where('tipo', 'entrada')
                ->whereRaw('COALESCE(data_pagamento, created_at) < ?', [$data_inicio])
                ->sum('valor');

            $saidas = ItemContaEmpresa::where('conta_id', $id)
                ->where('tipo', 'saida')
                ->whereRaw('COALESCE(data_pagamento, created_at) < ?', [$data_inicio])
                ->sum('valor');

            $saldo_anterior += ($entradas - $saidas);
        }

        $data = ItemContaEmpresa::where('conta_id', $id)
            ->orderByRaw('COALESCE(data_pagamento, created_at) ASC')
            ->when($data_inicio, function ($q) use ($data_inicio) {
                return $q->whereRaw('COALESCE(data_pagamento, created_at) >= ?', [$data_inicio]);
            })
            ->when($data_final, function ($q) use ($data_final) {
                return $q->whereRaw('COALESCE(data_pagamento, created_at) <= ?', [$data_final]);
            })
            ->when($tipo, function ($q) use ($tipo) {
                return $q->where('tipo', $tipo);
            })
            ->paginate(50);

        return view('conta_empresa/show', compact(
            'data',
            'item',
            'data_inicio',
            'data_final',
            'tipo',
            'saldo_anterior',
            'categorias',
            'contas'
        ));
    }

    public function imprimirExtrato(Request $request, $id)
    {
        $item = ContaEmpresa::withoutGlobalScopes()
            ->where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->firstOrFail();

        if (!$this->usuarioPodeAcessarFilialRegistro($item->filial_id)) {
            return redirect('/403');
        }

        $empresaIdLogada = $this->empresa_id ?? session('user_logged')['empresa'] ?? 1;

        $empresa = Empresa::find($empresaIdLogada);
        if (!$empresa) {
            $empresa = Empresa::first();
        }

        $config = ConfigNota::where('empresa_id', $empresaIdLogada)->first();

        $data_inicio = $request->data_inicio;
        $data_final = $request->data_final;

        $saldo_anterior = $item->saldo_inicial;

        if ($data_inicio) {
            $entradas = ItemContaEmpresa::where('conta_id', $id)
                ->where('tipo', 'entrada')
                ->whereRaw('COALESCE(data_pagamento, created_at) < ?', [$data_inicio])
                ->sum('valor');

            $saidas = ItemContaEmpresa::where('conta_id', $id)
                ->where('tipo', 'saida')
                ->whereRaw('COALESCE(data_pagamento, created_at) < ?', [$data_inicio])
                ->sum('valor');

            $saldo_anterior += ($entradas - $saidas);
        }

        $movimentacoes = ItemContaEmpresa::where('conta_id', $id)
            ->orderByRaw('COALESCE(data_pagamento, created_at) ASC')
            ->when($data_inicio, function ($q) use ($data_inicio) {
                return $q->whereRaw('COALESCE(data_pagamento, created_at) >= ?', [$data_inicio]);
            })
            ->when($data_final, function ($q) use ($data_final) {
                return $q->whereRaw('COALESCE(data_pagamento, created_at) <= ?', [$data_final]);
            })
            ->get();

        return view('conta_empresa.print', compact(
            'item',
            'movimentacoes',
            'saldo_anterior',
            'data_inicio',
            'data_final',
            'empresa',
            'config'
        ));
    }

    public function imprimirTransacao($id)
    {
        if (ob_get_contents()) {
            ob_end_clean();
        }

        $transacao = ItemContaEmpresa::findOrFail($id);
        $item = ContaEmpresa::withoutGlobalScopes()
            ->where('id', $transacao->conta_id)
            ->where('empresa_id', $this->empresa_id)
            ->firstOrFail();

        if (!$this->usuarioPodeAcessarFilialRegistro($item->filial_id)) {
            return redirect('/403');
        }

        $empresaIdLogada = $this->empresa_id ?? session('user_logged')['empresa'] ?? 1;

        $config = ConfigNota::where('empresa_id', $empresaIdLogada)->first();
        $empresa = DB::table('empresas')->where('id', $empresaIdLogada)->first();

        if (!$config) {
            $config = (object) [
                'razao_social' => null,
                'cnpj' => 'Não configurado',
                'logo' => null,
                'cidade' => null,
                'uf' => null
            ];
        }

        $p = view('relatorios/recibo_transacao', compact('transacao', 'item', 'config', 'empresa'));

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);
        $domPdf->setPaper("A4");
        $domPdf->render();

        return response($domPdf->output())->header('Content-Type', 'application/pdf');
    }

    public function deleteLancamento(Request $request)
    {
        $item = ItemContaEmpresa::findOrFail($request->id);

        $conta = ContaEmpresa::withoutGlobalScopes()
            ->where('id', $item->conta_id)
            ->where('empresa_id', $this->empresa_id)
            ->firstOrFail();

        if (!$this->usuarioPodeAcessarFilialRegistro($conta->filial_id)) {
            return redirect('/403');
        }

        if ($item->origem != 'manual') {
            session()->flash('mensagem_erro', 'Lançamentos automáticos não podem ser excluídos por aqui.');
            return redirect()->back();
        }

        $id_busca = $this->empresa_id ?? (session('user_logged')['empresa'] ?? 1);
        $config = ConfigNota::where('empresa_id', $id_busca)->first() ?? ConfigNota::first();

        if (!$config || md5($request->senha) != $config->senha_remover) {
            session()->flash('mensagem_erro', 'Senha de autorização incorreta!');
            return redirect()->back();
        }

        try {
            DB::transaction(function () use ($item, $conta) {
                if ($item->tipo == 'entrada') {
                    $conta->saldo -= $item->valor;
                } else {
                    $conta->saldo += $item->valor;
                }

                $conta->save();
                $item->delete();
            });

            session()->flash('mensagem_sucesso', 'Lançamento manual excluído com sucesso!');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
        }

        return redirect()->back();
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
            'saldo_inicial' => 'required',
            'plano_conta_id' => 'required',
        ];

        $messages = [
            'nome.required' => 'Campo obrigatório.',
            'nome.max' => '50 caracteres maximos permitidos.',
            'saldo_inicial.required' => 'Campo obrigatório.',
            'plano_conta_id.required' => 'Campo obrigatório.',
        ];

        $this->validate($request, $rules, $messages);
    }
}

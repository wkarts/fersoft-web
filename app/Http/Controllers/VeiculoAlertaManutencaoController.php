<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VeiculoAlertaManutencao;
use App\Models\Veiculo;
use Illuminate\Support\Facades\DB;

class VeiculoAlertaManutencaoController extends BaseController
{
    protected $model = VeiculoAlertaManutencao::class;
    protected $resource = 'veiculo_alertas_manutencao';
    protected $table = 'veiculo_alertas_manutencao';
    protected $formTitle = 'Torre de Controle de Manutenções';
    protected $listView = 'manutencoes.alertas_painel';
    protected $registerView = 'manutencoes.alertas_painel';
    protected $redirectPage = '/manutencoes/painel-alertas';

    public function rules(): array { return []; }
    public function messages(): array { return []; }

    // Sobrescreve para impedir que o BaseController use a tabela antiga
    protected function getTenantRecords()
    {
        return DB::table('veiculo_alertas_manutencao')
            ->leftJoin('veiculos', 'veiculo_alertas_manutencao.veiculo_id', '=', 'veiculos.id')
            ->where('veiculo_alertas_manutencao.empresa_id', $this->empresa_id)
            ->select([
                'veiculo_alertas_manutencao.*',
                'veiculos.placa',
                'veiculos.marca',
                'veiculos.modelo',
                'veiculos.quilometragem'
            ]);
    }

    public function index(Request $request)
    {
        return $this->painel($request);
    }

    public function list(Request $request)
    {
        return $this->painel($request);
    }

    public function painel(Request $request)
    {
        $user_logged = session('user_logged');
        $empresaId = $this->empresa_id ?: ($user_logged['empresa_id'] ?? 1);

        $query = DB::table('veiculo_alertas_manutencao')
            ->leftJoin('veiculos', 'veiculo_alertas_manutencao.veiculo_id', '=', 'veiculos.id')
            ->where('veiculo_alertas_manutencao.empresa_id', $empresaId)
            ->select([
                'veiculo_alertas_manutencao.*',
                'veiculos.placa',
                'veiculos.marca',
                'veiculos.modelo',
                'veiculos.quilometragem'
            ]);

        if ($request->filled('fase')) {
            $query->where('veiculo_alertas_manutencao.fase', $request->fase);
        }
        if ($request->filled('tipo')) {
            $query->where('veiculo_alertas_manutencao.tipo', $request->tipo);
        }
        if ($request->filled('veiculo_id')) {
            $query->where('veiculo_alertas_manutencao.veiculo_id', $request->veiculo_id);
        }

        // Variável com nome exclusivo da oficina
        $alertasOficina = $query->orderBy('veiculo_alertas_manutencao.id', 'desc')->paginate(20);
        $veiculos = DB::table('veiculos')->where('empresa_id', $empresaId)->orderBy('placa')->get();
        $title = $this->formTitle;

        return view($this->listView, compact('alertasOficina', 'veiculos', 'title'));
    }

    public function salvarAlerta(Request $request)
    {
        $user_logged = session('user_logged');
        $empresaId = $this->empresa_id ?: ($user_logged['empresa_id'] ?? 1);

        DB::table('veiculo_alertas_manutencao')->insert([
            'empresa_id'         => $empresaId,
            'filial_id'          => $user_logged['filial_id'] ?? null,
            'veiculo_id'         => $request->veiculo_id,
            'tipo'               => $request->tipo ?? 'corretiva',
            'titulo'             => $request->titulo,
            'descricao'          => $request->descricao,
            'prioridade'         => $request->prioridade ?? 'Alta',
            'fase'               => $request->fase ?? 'identificado',
            'impedir_circulacao' => $request->has('impedir_circulacao') ? 1 : 0,
            'valor_estimado'     => (float) str_replace(',', '.', $request->valor_estimado ?? 0),
            'previsao_conclusao' => $request->previsao_conclusao,
            'created_at'         => now(),
            'updated_at'         => now()
        ]);

        session()->flash('mensagem_sucesso', 'Alerta de oficina registrado com sucesso!');
        return redirect($this->redirectPage);
    }

    public function aprovar(Request $request, $id)
    {
        $user_logged = session('user_logged');
        $empresaId = $this->empresa_id ?: ($user_logged['empresa_id'] ?? 1);

        DB::table('veiculo_alertas_manutencao')
            ->where('id', $id)
            ->where('empresa_id', $empresaId)
            ->update([
                'fase'                 => 'aguardando_peca',
                'usuario_aprovador_id' => $user_logged['id'] ?? $user_logged['usuario_id'],
                'data_aprovacao'       => now(),
                'updated_at'           => now()
            ]);

        session()->flash('mensagem_sucesso', 'Manutenção aprovada com sucesso!');
        return redirect($this->redirectPage);
    }

    public function relatorioNecessidades(Request $request)
    {
        $user_logged = session('user_logged');
        $empresaId = $this->empresa_id ?: ($user_logged['empresa_id'] ?? 1);

        $pendencias = DB::table('veiculo_alertas_manutencao')
            ->leftJoin('veiculos', 'veiculo_alertas_manutencao.veiculo_id', '=', 'veiculos.id')
            ->where('veiculo_alertas_manutencao.empresa_id', $empresaId)
            ->where('veiculo_alertas_manutencao.fase', '!=', 'resolvido')
            ->select('veiculo_alertas_manutencao.*', 'veiculos.placa', 'veiculos.marca', 'veiculos.modelo', 'veiculos.quilometragem')
            ->orderBy('veiculo_alertas_manutencao.impedir_circulacao', 'desc')
            ->orderBy('veiculo_alertas_manutencao.prioridade', 'desc')
            ->get();

        $title = 'Relatório de Veículos Bloqueados';
        return view('manutencoes.relatorios.necessidades', compact('pendencias', 'title'));
    }

    public function relatorioAprovacoes(Request $request)
    {
        $user_logged = session('user_logged');
        $empresaId = $this->empresa_id ?: ($user_logged['empresa_id'] ?? 1);

        $aguardando = DB::table('veiculo_alertas_manutencao')
            ->leftJoin('veiculos', 'veiculo_alertas_manutencao.veiculo_id', '=', 'veiculos.id')
            ->where('veiculo_alertas_manutencao.empresa_id', $empresaId)
            ->whereIn('veiculo_alertas_manutencao.fase', ['aguardando_cotacao', 'aguardando_autorizacao'])
            ->select('veiculo_alertas_manutencao.*', 'veiculos.placa', 'veiculos.marca', 'veiculos.modelo')
            ->get();

        $totalEstimado = $aguardando->sum('valor_estimado');
        $title = 'Relatório de Aprovações Pendentes';

        return view('manutencoes.relatorios.aprovacoes', compact('aguardando', 'totalEstimado', 'title'));
    }
}

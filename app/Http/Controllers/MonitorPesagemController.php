<?php

namespace App\Http\Controllers;

use App\Models\Filial;
use App\Models\Pesagem;
use App\Services\MonitorPesagemService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitorPesagemController extends BaseController
{
    protected $redirectPage = '/monitor/pesagens';

    /**
     * Como este controller é apenas de MONITOR (não faz save/update via BaseController),
     * implementamos rules/messages como vazio apenas para satisfazer o contrato do BaseController.
     */
    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function index(Request $request, MonitorPesagemService $monitorPesagemService)
    {
        $filtros = $this->resolveFiltros($request);

        $pesagens = $this->buildQuery($filtros)
            ->orderBy('updated_at', 'desc')
            ->limit(200)
            ->get();

        $eventos = $pesagens->map(function (Pesagem $pesagem) use ($monitorPesagemService) {
            $tipoEvento = $monitorPesagemService->inferTipoEvento($pesagem);
            $timestamp = $pesagem->updated_at ?? $pesagem->created_at ?? now();

            return $monitorPesagemService->buildPayload($pesagem, $tipoEvento, $timestamp);
        })->values();

        $resumo = $this->montarResumo($pesagens);

        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get();

        return view('monitor.pesagens', [
            'title' => 'Painel ao Vivo - Pesagens',
            'eventos' => $eventos,
            'resumo' => $resumo,
            'filiais' => $filiais,
            'filtros' => $filtros,
            'empresaId' => $this->empresa_id,
        ]);
    }

    public function data(Request $request, MonitorPesagemService $monitorPesagemService)
    {
        $filtros = $this->resolveFiltros($request);

        $pesagens = $this->buildQuery($filtros)
            ->orderBy('updated_at', 'desc')
            ->limit(200)
            ->get();

        $eventos = $pesagens->map(function (Pesagem $pesagem) use ($monitorPesagemService) {
            $tipoEvento = $monitorPesagemService->inferTipoEvento($pesagem);
            $timestamp = $pesagem->updated_at ?? $pesagem->created_at ?? now();

            return $monitorPesagemService->buildPayload($pesagem, $tipoEvento, $timestamp);
        })->values();

        return response()->json([
            'eventos' => $eventos,
            'resumo' => $this->montarResumo($pesagens),
        ]);
    }

    private function resolveFiltros(Request $request): array
    {
        $dataSelecionada = $request->input('date');
        $data = $dataSelecionada
            ? Carbon::createFromFormat('Y-m-d', $dataSelecionada)
            : Carbon::today();

        $filialId = $request->filled('filial_id')
            ? (int) $request->input('filial_id')
            : $this->filial_id;

        return [
            'data' => $data,
            'filial_id' => $filialId,
            'search' => $request->input('search'),
        ];
    }

    private function buildQuery(array $filtros)
    {
        $query = Pesagem::with([
            'filial',
            'cliente',
            'fornecedor',
            'motorista',
            'tickets.produto',
            'venda.itens',
            'compra.itens',
        ])
            ->where('empresa_id', $this->empresa_id)
            ->whereDate('created_at', $filtros['data']->format('Y-m-d'));

        if (!empty($filtros['filial_id'])) {
            $query->where('filial_id', $filtros['filial_id']);
        }

        if (!empty($filtros['search'])) {
            $term = $filtros['search'];
            $query->where(function ($q) use ($term) {
                $q->where('placa_veiculo', 'like', "%{$term}%")
                    ->orWhere('placa_carreta', 'like', "%{$term}%")
                    ->orWhere('motorista_nome', 'like', "%{$term}%")
                    ->orWhereHas('cliente', function ($qc) use ($term) {
                        $qc->where('razao_social', 'like', "%{$term}%")
                            ->orWhere('nome_fantasia', 'like', "%{$term}%");
                    })
                    ->orWhereHas('fornecedor', function ($qf) use ($term) {
                        $qf->where('razao_social', 'like', "%{$term}%")
                            ->orWhere('nome_fantasia', 'like', "%{$term}%");
                    });
            });
        }

        return $query;
    }

    private function montarResumo($pesagens): array
    {
        $totalKg = $pesagens->sum(function (Pesagem $pesagem) {
            $pesoFinal = $pesagem->peso_final ?? 0;
            return $pesoFinal > 0 ? $pesoFinal : ($pesagem->peso ?? 0);
        });

        $totalValor = $pesagens->sum(function (Pesagem $pesagem) {
            if ($pesagem->venda) {
                return (float) $pesagem->venda->valor_total;
            }
            if ($pesagem->compra) {
                return (float) $pesagem->compra->valor;
            }
            return 0;
        });

        return [
            'total_pesagens' => $pesagens->count(),
            'total_kg' => $totalKg,
            'total_valor' => $totalValor,
        ];
    }
}

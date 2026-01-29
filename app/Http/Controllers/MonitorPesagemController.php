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
        $analiticoProdutos = $this->montarAnaliticoProdutos($pesagens);
        $analiticoParceiros = $this->montarAnaliticoParceiros($pesagens);
        $filiais = Filial::where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get();

        return view('monitor.pesagens', [
            'title' => 'Painel ao Vivo - Pesagens',
            'eventos' => $eventos,
            'resumo' => $resumo,
            'analiticoProdutos' => $analiticoProdutos,
            'analiticoParceiros' => $analiticoParceiros,
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
            'analitico_produtos' => $this->montarAnaliticoProdutos($pesagens),
            'analitico_parceiros' => $this->montarAnaliticoParceiros($pesagens),
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

    private function montarAnaliticoProdutos($pesagens): array
    {
        $produtos = [];

        foreach ($pesagens as $pesagem) {
            $direcao = $pesagem->tipo === 'compra' ? 'entrada' : ($pesagem->tipo === 'venda' ? 'saida' : null);
            if (!$direcao) {
                continue;
            }

            foreach ($pesagem->tickets as $ticket) {
                if (empty($ticket->produto_id)) {
                    continue;
                }

                $peso = (float) ($ticket->peso ?? 0);
                $pesoBag = (float) ($ticket->peso_bag ?? 0);
                $pesoLiquido = max(0, $peso - $pesoBag);

                if ($pesoLiquido <= 0) {
                    continue;
                }

                $key = (string) $ticket->produto_id;
                if (!isset($produtos[$key])) {
                    $produtos[$key] = [
                        'produto_id' => (int) $ticket->produto_id,
                        'produto_nome' => $ticket->produto->nome ?? '—',
                        'entrada' => 0,
                        'saida' => 0,
                        'total' => 0,
                    ];
                }

                $produtos[$key][$direcao] += $pesoLiquido;
                $produtos[$key]['total'] = $produtos[$key]['entrada'] + $produtos[$key]['saida'];
            }
        }

        $resultado = array_values($produtos);
        usort($resultado, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $resultado;
    }

    private function montarAnaliticoParceiros($pesagens): array
    {
        $parceiros = [];

        foreach ($pesagens as $pesagem) {
            $direcao = $pesagem->tipo === 'compra' ? 'entrada' : ($pesagem->tipo === 'venda' ? 'saida' : null);
            if (!$direcao) {
                continue;
            }

            $peso = (float) ($pesagem->peso_final ?? $pesagem->peso ?? 0);
            if ($peso <= 0) {
                continue;
            }

            $parceiroId = null;
            $parceiroNome = 'Sem cadastro';
            $parceiroTipo = '—';

            if ($pesagem->tipo === 'compra') {
                $parceiroId = $pesagem->fornecedor_id;
                $parceiroNome = $pesagem->fornecedor->razao_social
                    ?? $pesagem->fornecedor->nome_fantasia
                    ?? 'Fornecedor';
                $parceiroTipo = 'Fornecedor';
            } elseif ($pesagem->tipo === 'venda') {
                $parceiroId = $pesagem->cliente_id;
                $parceiroNome = $pesagem->cliente->razao_social
                    ?? $pesagem->cliente->nome_fantasia
                    ?? 'Cliente';
                $parceiroTipo = 'Cliente';
            }

            $key = $parceiroTipo . '-' . ($parceiroId ?? 'sem');
            if (!isset($parceiros[$key])) {
                $parceiros[$key] = [
                    'parceiro_id' => $parceiroId,
                    'parceiro_nome' => $parceiroNome,
                    'parceiro_tipo' => $parceiroTipo,
                    'entrada' => 0,
                    'saida' => 0,
                    'total' => 0,
                    'total_pesagens' => 0,
                ];
            }

            $parceiros[$key][$direcao] += $peso;
            $parceiros[$key]['total'] = $parceiros[$key]['entrada'] + $parceiros[$key]['saida'];
            $parceiros[$key]['total_pesagens']++;
        }

        $resultado = array_values($parceiros);
        usort($resultado, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $resultado;
    }
}

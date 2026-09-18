<?php

namespace App\Http\Controllers;

use App\Exports\PesagensAnaliticoExport;
use App\Http\Controllers\Controller;
use App\Models\Filial;
use App\Models\Funcionario;
use App\Models\Pesagem;
use App\Models\Veiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class PesagensAnaliticoController extends Controller
{
    /**
     * Tela com filtros + grid.
     */
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id ?? null;

        // Filtros
        $dataInicial   = $request->input('data_inicial');
        $dataFinal     = $request->input('data_final');
        $tipoOperacao  = $request->input('tipo_operacao'); // entrada|saida|null
        $filialId      = $request->input('filial_id');
        $motoristaId   = $request->input('motorista_id');
        $veiculoId     = $request->input('veiculo_id');
        $status        = $request->input('status'); // em andamento|concluído|...
        $associacao    = $request->input('associacao'); // todos|com|sem

        $query = Pesagem::query()
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->with([
                'venda',
                'compra',
                'veiculo',
                'motorista',
                'filial',
                'tickets.produto',
            ]);

        /**
         * Período
         * Usando dt_registro como data-base oficial de registro da pesagem.
         */
        if ($dataInicial) {
            $query->whereDate('dt_registro', '>=', $dataInicial);
        }
        if ($dataFinal) {
            $query->whereDate('dt_registro', '<=', $dataFinal);
        }

        /**
         * Tipo de operação (mapear para campo 'tipo' da tabela):
         * - entrada => tipo = 'compra'
         * - saida   => tipo = 'venda'
         */
        if ($tipoOperacao === 'entrada') {
            $query->where('tipo', 'compra');
        } elseif ($tipoOperacao === 'saida') {
            $query->where('tipo', 'venda');
        }

        // Filial
        if ($filialId !== null && $filialId !== '') {
            $query->where('filial_id', $filialId);
        }

        // Motorista (Funcionario)
        if ($motoristaId) {
            $query->where('motorista_id', $motoristaId);
        }

        // Veículo
        if ($veiculoId) {
            $query->where('veiculo_id', $veiculoId);
        }

        // Situação / Status (em andamento, concluído)
        if ($status) {
            $query->where('status', $status);
        }

        // Com ou sem venda/compra associada
        if ($associacao === 'com') {
            $query->where(function ($q) {
                $q->whereNotNull('venda_id')
                    ->orWhereNotNull('compra_id');
            });
        } elseif ($associacao === 'sem') {
            $query->whereNull('venda_id')
                ->whereNull('compra_id');
        }

        // Ordenação padrão por data de registro desc + id desc
        $query->orderBy('dt_registro', 'desc')->orderBy('id', 'desc');

        $pesagens = $query->paginate(50)->withQueryString();

        /**
         * Combos
         * - Filiais: usa nome_fantasia (coluna existente)
         * - Motoristas: Funcionario
         * - Veículos
         */
        $filiaisQuery = Filial::query();
        if ($empresaId) {
            $filiaisQuery->where('empresa_id', $empresaId);
        }
        $filiais = $filiaisQuery
            ->orderBy('nome_fantasia')
            ->get();

        $motoristasQuery = Funcionario::query();
        if ($empresaId) {
            $motoristasQuery->where('empresa_id', $empresaId);
        }
        $motoristas = $motoristasQuery
            ->orderBy('nome')
            ->get();

        $veiculosQuery = Veiculo::query();
        if ($empresaId) {
            $veiculosQuery->where('empresa_id', $empresaId);
        }
        $veiculos = $veiculosQuery
            ->orderBy('placa')
            ->get();
        $RelTitle = 'Relatório Analítico de Pesagens';
        return view('relatorios.pesagens.analitico_index', [
            'title'       => $RelTitle,
            'pesagens'    => $pesagens,
            'filiais'     => $filiais,
            'motoristas'  => $motoristas,
            'veiculos'    => $veiculos,
            'filtros'     => [
                'data_inicial'  => $dataInicial,
                'data_final'    => $dataFinal,
                'tipo_operacao' => $tipoOperacao,
                'filial_id'     => $filialId,
                'motorista_id'  => $motoristaId,
                'veiculo_id'    => $veiculoId,
                'status'        => $status,
                'associacao'    => $associacao,
            ],
        ]);
    }

    /**
     * Exportação para PDF ou Excel.
     *
     * ?tipo=pdf|xlsx (default xlsx)
     */
    public function export(Request $request)
    {
        $tipo  = $request->input('tipo', 'xlsx');
        $query = $this->buildQuery($request);
        $pesagens = $query->get();

        if ($tipo === 'pdf') {
            $pdf = PDF::loadView('relatorios.pesagens.analitico_pdf', [
                'pesagens' => $pesagens,
                'filtros'  => $request->all(),
            ])->setPaper('a4', 'landscape');

            $nomeArquivo = 'relatorio_pesagens_analitico_' . now()->format('Ymd_His') . '.pdf';
            return $pdf->download($nomeArquivo);
        }

        // Excel (xlsx)
        $nomeArquivo = 'relatorio_pesagens_analitico_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new PesagensAnaliticoExport($query), $nomeArquivo);
    }

    /**
     * Monta a query base reaproveitável entre index/export.
     */
    protected function buildQuery(Request $request)
    {
        $empresaId = Auth::user()->empresa_id ?? null;

        $dataInicial   = $request->input('data_inicial');
        $dataFinal     = $request->input('data_final');
        $tipoOperacao  = $request->input('tipo_operacao');
        $filialId      = $request->input('filial_id');
        $motoristaId   = $request->input('motorista_id');
        $veiculoId     = $request->input('veiculo_id');
        $status        = $request->input('status');
        $associacao    = $request->input('associacao');

        $query = Pesagem::query()
            ->when($empresaId, function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->with(['venda', 'compra', 'veiculo', 'motorista', 'filial', 'tickets.produto']);

        if ($dataInicial) {
            $query->whereDate('dt_registro', '>=', $dataInicial);
        }
        if ($dataFinal) {
            $query->whereDate('dt_registro', '<=', $dataFinal);
        }

        if ($tipoOperacao === 'entrada') {
            $query->where('tipo', 'compra');
        } elseif ($tipoOperacao === 'saida') {
            $query->where('tipo', 'venda');
        }

        if ($filialId !== null && $filialId !== '') {
            $query->where('filial_id', $filialId);
        }

        if ($motoristaId) {
            $query->where('motorista_id', $motoristaId);
        }

        if ($veiculoId) {
            $query->where('veiculo_id', $veiculoId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($associacao === 'com') {
            $query->where(function ($q) {
                $q->whereNotNull('venda_id')
                    ->orWhereNotNull('compra_id');
            });
        } elseif ($associacao === 'sem') {
            $query->whereNull('venda_id')
                ->whereNull('compra_id');
        }

        return $query->orderBy('dt_registro', 'desc')->orderBy('id', 'desc');
    }
}

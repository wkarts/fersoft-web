<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaPagar;
use App\Models\Veiculo;
use DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustosVeiculosExport;

class CustoVeiculoController extends Controller
{
    /**
     * Função auxiliar para capturar o empresa_id da sessão do Fersoft ERP
     */
    /**
     * Função auxiliar segura para capturar o empresa_id no Fersoft ERP
     */
    private function getEmpresaId()
    {
        // 1. Tenta pegar da sessão do ERP (session('user_logged'))
        if (session()->has('user_logged')) {
            $user = session('user_logged');

            // Se for Objeto
            if (is_object($user)) {
                if (isset($user->empresa_id)) return $user->empresa_id;
                if (isset($user->empresa)) return is_object($user->empresa) ? $user->empresa->id : $user->empresa;
            }

            // Se for Array
            if (is_array($user)) {
                if (isset($user['empresa_id'])) return $user['empresa_id'];
                if (isset($user['empresa'])) return is_array($user['empresa']) ? $user['empresa']['id'] : $user['empresa'];
            }
        }

        // 2. Tenta a chave direta de empresa da sessão
        if (session()->has('empresa')) {
            $empresa = session('empresa');
            if (is_object($empresa) && isset($empresa->id)) return $empresa->id;
            if (is_array($empresa) && isset($empresa['id'])) return $empresa['id'];
            if (is_numeric($empresa)) return $empresa;
        }

        // 3. Fallback para Auth nativo caso utilizado
        if (auth()->check()) {
            return auth()->user()->empresa_id ?? null;
        }

        return null;
    }

    public function index(Request $request)
    {
        $empresaId = $this->getEmpresaId();

        if (!$empresaId) {
            return redirect('/login')->with('error', 'Sessão expirada. Faça login novamente.');
        }

        $title      = 'Apuração de Custos por Veículo';
        $dataInicio = $request->input('data_inicio', date('Y-m-01'));
        $dataFim    = $request->input('data_fim', date('Y-m-t'));
        $regime     = $request->input('regime', 'competencia');
        $tipoVisao  = $request->input('tipo_visao', 'resumido');
        $veiculoId  = $request->input('veiculo_id');
        $filialId   = $request->input('filial_id');

        // Traz os dados calculados com os filtros aplicados
        $dados = $this->buscarDadosApuracao($empresaId, $dataInicio, $dataFim, $regime, $tipoVisao, $veiculoId, $filialId);

        // Traz a lista de todos os veículos da empresa para preencher o select
        $veiculos = Veiculo::where('empresa_id', $empresaId)
            ->whereNull('deleted_at')
            ->orderBy('placa', 'ASC')
            ->get();

        $filiais = DB::table('filials')->where('empresa_id', $empresaId)->get();

        return view('relatorios.custos_veiculos.index', compact(
            'title', 'dados', 'veiculos', 'filiais', 'dataInicio', 'dataFim',
            'regime', 'tipoVisao', 'veiculoId', 'filialId'
        ));
    }

    private function buscarDadosApuracao($empresaId, $dataInicio, $dataFim, $regime, $tipoVisao, $veiculoId = null, $filialId = null)
    {
        $query = ContaPagar::query()
            ->join('veiculos', 'conta_pagars.veiculo_id', '=', 'veiculos.id')
            ->join('categoria_contas', 'conta_pagars.categoria_id', '=', 'categoria_contas.id')
            ->where('conta_pagars.empresa_id', $empresaId)
            ->where('conta_pagars.estorno', 0)
            ->whereNotNull('conta_pagars.veiculo_id');

        if (!empty($filialId)) {
            $query->where('conta_pagars.filial_id', $filialId);
        }

        // Filtro por Veículo Selecionado
        if (!empty($veiculoId)) {
            $query->where('conta_pagars.veiculo_id', $veiculoId);
        }

        // Filtro de Regime
        if ($regime === 'caixa') {
            $query->where('conta_pagars.status', 1)
                ->whereBetween(DB::raw('DATE(conta_pagars.data_pagamento)'), [$dataInicio, $dataFim]);
            $campoValor = 'conta_pagars.valor_pago';
        } else {
            $query->whereBetween('conta_pagars.data_vencimento', [$dataInicio, $dataFim]);
            $campoValor = 'conta_pagars.valor_integral';
        }

        if ($tipoVisao === 'resumido') {
            return $query->select(
                'veiculos.id as veiculo_id',
                'veiculos.placa',
                'veiculos.marca',
                'veiculos.modelo',
                DB::raw("SUM($campoValor) as total_custo"),
                DB::raw("COUNT(conta_pagars.id) as total_titulos")
            )
                ->groupBy('veiculos.id', 'veiculos.placa', 'veiculos.marca', 'veiculos.modelo')
                ->orderBy('total_custo', 'DESC')
                ->get();
        }

        if ($tipoVisao === 'categoria') {
            return $query->select(
                'veiculos.placa',
                'veiculos.marca',
                'veiculos.modelo',
                'categoria_contas.nome as categoria_nome',
                DB::raw("SUM($campoValor) as total_custo"),
                DB::raw("COUNT(conta_pagars.id) as total_titulos")
            )
                ->groupBy('veiculos.placa', 'veiculos.marca', 'veiculos.modelo', 'categoria_contas.nome')
                ->orderBy('veiculos.placa')
                ->orderBy('total_custo', 'DESC')
                ->get()
                ->groupBy('placa');
        }

        if ($tipoVisao === 'lancamentos') {
            return $query->select(
                'conta_pagars.id',
                'conta_pagars.referencia',
                'conta_pagars.numero_nota_fiscal',
                'conta_pagars.data_vencimento',
                'conta_pagars.data_pagamento',
                'conta_pagars.valor_integral',
                'conta_pagars.valor_pago',
                'conta_pagars.status',
                'veiculos.placa',
                'veiculos.marca',
                'veiculos.modelo',
                'categoria_contas.nome as categoria_nome'
            )
                ->orderBy('veiculos.placa')
                ->orderBy('conta_pagars.data_vencimento', 'ASC')
                ->get()
                ->groupBy('placa');
        }
    }

    public function exportarExcel(Request $request)
    {
        $empresaId  = $this->getEmpresaId();
        $dataInicio = $request->input('data_inicio');
        $dataFim    = $request->input('data_fim');
        $regime     = $request->input('regime');
        $tipoVisao  = $request->input('tipo_visao');
        $veiculoId  = $request->input('veiculo_id');
        $filialId   = $request->input('filial_id');

        $dados = $this->buscarDadosApuracao($empresaId, $dataInicio, $dataFim, $regime, $tipoVisao, $veiculoId, $filialId);

        return Excel::download(new CustosVeiculosExport($dados, $tipoVisao, $regime, $dataInicio, $dataFim), "apuracao_custos_veiculos_{$regime}.xlsx");
    }

    public function gerarPdf(Request $request)
    {
        $empresaId  = $this->getEmpresaId();
        $dataInicio = $request->input('data_inicio');
        $dataFim    = $request->input('data_fim');
        $regime     = $request->input('regime');
        $tipoVisao  = $request->input('tipo_visao');
        $veiculoId  = $request->input('veiculo_id');
        $filialId   = $request->input('filial_id');

        $dados = $this->buscarDadosApuracao($empresaId, $dataInicio, $dataFim, $regime, $tipoVisao, $veiculoId, $filialId);

        $pdf = PDF::loadView('relatorios.custos_veiculos.pdf', compact('dados', 'tipoVisao', 'regime', 'dataInicio', 'dataFim'));
        return $pdf->stream("apuracao_custos_veiculos.pdf");
    }
}

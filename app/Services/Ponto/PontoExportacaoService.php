<?php

namespace App\Services\Ponto;

use App\Models\PontoMarcacao;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PontoExportacaoService
{
    /**
     * Gera o arquivo AFD no layout oficial da Portaria 671/2021 MTE (REP-P)
     */
    public function gerarAfd(int $empresaId, string $dataInicio, string $dataFim, ?int $funcionarioId = null): string
    {
        // 1. Busca dados cadastrais da empresa
        $empresa = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
        if (!$empresa) {
            $empresa = DB::table('filials')->where('id', $empresaId)->first();
        }

        $cnpjLimpo = preg_replace('/\D/', '', $empresa->cnpj ?? '00000000000000');
        $razaoSocial = strtoupper($empresa->razao_social ?? 'EMPRESA');

        // 2. Busca as marcações processadas do período
        $query = PontoMarcacao::with('funcionario')
            ->where('empresa_id', $empresaId)
            ->whereBetween('data_hora_marcacao', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
            ->where('status', '!=', 'desconsiderada')
            ->orderBy('data_hora_marcacao', 'asc');

        if ($funcionarioId) {
            $query->where('funcionario_id', $funcionarioId);
        }

        $marcacoes = $query->get();

        $linhas = [];
        $nsr = 1; // Número Sequencial de Registro

        // -------------------------------------------------------------------------
        // HEADER - REGISTRO TIPO 1 (Cabeçalho do Arquivo AFD)
        // Posição 01-09: NSR (000000001)
        // Posição 10: Tipo Registro (1)
        // Posição 11: Tipo Empregador (1=CNPJ, 2=CPF)
        // Posição 12-25: CNPJ/CPF da Empresa (14 dígitos)
        // Posição 26-37: CEI/CNO (12 posições, preencher com espaços)
        // Posição 38-187: Razão Social (150 posições)
        // Posição 188-195: Data Inicial (DDMMAAAA)
        // Posição 196-203: Data Final (DDMMAAAA)
        // Posição 204-219: Data/Hora Geração Arquivo (DDMMAAAAHHMM)
        // -------------------------------------------------------------------------
        $linhas[] = sprintf(
            "%09d11%-14s%-12s%-150s%8s%8s%12s",
            $nsr++,
            str_pad(substr($cnpjLimpo, 0, 14), 14, ' ', STR_PAD_RIGHT),
            str_pad('', 12, ' '),
            substr(str_pad($razaoSocial, 150, ' '), 0, 150),
            Carbon::parse($dataInicio)->format('dmY'),
            Carbon::parse($dataFim)->format('dmY'),
            Carbon::now()->format('dmYHi')
        );

        // -------------------------------------------------------------------------
        // DETALHE - REGISTRO TIPO 3 (Marcação de Ponto Eletrônico)
        // Posição 01-09: NSR (Sequencial contínuo)
        // Posição 10: Tipo Registro (3)
        // Posição 11-18: Data Marcação (DDMMAAAA)
        // Posição 19-22: Hora Marcação (HHMM)
        // Posição 23-34: PIS do Trabalhador (11 dígitos, com zeros à esquerda ou CPF se novo formato)
        // -------------------------------------------------------------------------
        foreach ($marcacoes as $m) {
            $dataHora = Carbon::parse($m->data_hora_marcacao);

            // Pega o PIS ou CPF caso o PIS não esteja preenchido
            $documento = preg_replace('/\D/', '', $m->funcionario->pis ?? $m->funcionario->cpf ?? '');
            if (empty($documento)) {
                continue; // Marcação sem PIS/CPF não pode entrar no AFD fiscal
            }

            $linhas[] = sprintf(
                "%09d3%08s%04s%011s",
                $nsr++,
                $dataHora->format('dmY'),
                $dataHora->format('Hi'),
                str_pad(substr($documento, 0, 11), 11, '0', STR_PAD_LEFT)
            );
        }

        // -------------------------------------------------------------------------
        // TRAILER - REGISTRO TIPO 9 (Totalizador e Encerramento)
        // Posição 01-09: NSR
        // Posição 10: Tipo Registro (9)
        // -------------------------------------------------------------------------
        $linhas[] = sprintf("%09d9", $nsr);

        return implode("\r\n", $linhas);
    }
}

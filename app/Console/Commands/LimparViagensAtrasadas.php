<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MovimentacaoVeiculo;
use App\Http\Controllers\MovimentacaoVeiculoController;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class LimparViagensAtrasadas extends Command
{
    protected $signature = 'frota:limpar-atrasadas';
    protected $description = 'Sincroniza automaticamente no Traccar as viagens agendadas ou iniciadas e atualiza os horários na agenda.';

    public function handle()
    {
        // Busca todas as viagens agendadas (que já entraram na janela de saída) ou em percurso (iniciado)
        $viagensPendentes = MovimentacaoVeiculo::whereIn('status', ['agendado', 'iniciado'])
            ->where('is_terceirizado', 0)
            ->whereNotNull('data_hora_saida')
            ->where('data_hora_saida', '<=', Carbon::now()->addMinutes(30))
            ->orderBy('data_hora_saida', 'asc')
            ->get();

        if ($viagensPendentes->isEmpty()) {
            return self::SUCCESS;
        }

        $controller = app(MovimentacaoVeiculoController::class);
        $sincronizadas = 0;

        foreach ($viagensPendentes as $mov) {
            try {
                $atualizou = $controller->sincronizarRetroativoTraccar($mov->id);
                if ($atualizou) {
                    $sincronizadas++;
                }
            } catch (Throwable $e) {
                Log::error("ROBÔ LIMPEZA FROTA: Erro ao sincronizar viagem #{$mov->id}: " . $e->getMessage());
            }
        }

        if ($sincronizadas > 0) {
            Log::info("ROBÔ LIMPEZA FROTA: {$sincronizadas} viagem(ns) sincronizada(s) automaticamente com o Traccar.");
        }

        return self::SUCCESS;
    }
}

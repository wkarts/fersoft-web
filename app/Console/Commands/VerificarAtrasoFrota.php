<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MovimentacaoVeiculo;
use App\Models\Funcionario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificarAtrasoFrota extends Command
{
    protected $signature = 'frota:verificar-atrasos';
    protected $description = 'Verifica saídas atrasadas na frota e dispara aviso único no WhatsApp';

    public function handle()
    {
        // 1. Proteção de Horário Comercial (não dispara de madrugada)
        $horaAtual = (int) date('H');
        if ($horaAtual < 6 || $horaAtual > 19) {
            return 0;
        }

        // Busca todas as configurações de logística ativas
        $empresasParam = DB::table('parametros_agendamento_logistica')
            ->where('notificar_atraso_whatsapp', 1)
            ->get();

        $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);

        foreach ($empresasParam as $param) {
            $empresaId = $param->empresa_id;
            $toleranciaMinutos = (int) ($param->minutos_tolerancia_atraso ?? 20);

            // Janela de atraso: atrasado há pelo menos X minutos e no máximo 3 horas
            $dataLimiteAtraso = now()->subMinutes($toleranciaMinutos);
            $dataLimiteCorte  = now()->subHours(3);

            // Busca rigorosamente as movimentações que AINDA NÃO receberam alerta
            $movimentacoesAtrasadas = MovimentacaoVeiculo::with(['veiculo', 'motorista', 'destinos.cliente'])
                ->where('empresa_id', $empresaId)
                ->where('status', 'agendado')
                ->whereBetween('data_hora_saida', [$dataLimiteCorte, $dataLimiteAtraso])
                ->whereNull('alerta_atraso_enviado_at') // 🔒 Garante disparo ÚNICO por agenda
                ->get();

            if ($movimentacoesAtrasadas->isEmpty()) {
                continue;
            }

            // Gestor da frota configurado no parâmetro
            $gestor = $param->funcionario_gestor_frota_id
                ? Funcionario::find($param->funcionario_gestor_frota_id)
                : null;

            foreach ($movimentacoesAtrasadas as $mov) {
                // Marca imediatamente no banco para travar concorrência
                $mov->alerta_atraso_enviado_at = now();
                $mov->save();

                $placa = $mov->veiculo->placa ?? 'Sem Placa';
                $motoristaNome = $mov->motorista->nome ?? 'Não informado';
                $horaPrevista = date('H:i \d\e d/m', strtotime($mov->data_hora_saida));

                // 1. WhatsApp para o Motorista (Lembrete)
                if ($mov->motorista && (!empty($mov->motorista->celular) || !empty($mov->motorista->telefone))) {
                    $numMotorista = preg_replace('/[^0-9]/', '', $mov->motorista->celular ?: $mov->motorista->telefone);
                    if (strlen($numMotorista) >= 10) {
                        if (!str_starts_with($numMotorista, '55')) $numMotorista = '55' . $numMotorista;

                        $msgMotorista = "⏰ *LEMBRETE DE SAÍDA - FROTA*\n\n"
                            . "Olá, *{$motoristaNome}*!\n"
                            . "A viagem com o veículo *{$placa}* estava prevista para *{$horaPrevista}* e ainda consta como agendada.\n"
                            . "Favor iniciar o checklist ou comunicar o encarregado.";

                        try {
                            if (method_exists($instanciaWhats, 'sendMessage')) {
                                $instanciaWhats->sendMessage($numMotorista, $msgMotorista, $empresaId);
                            }
                        } catch (\Throwable $e) {
                            Log::error("Erro envio whats motorista atraso #{$mov->id}: " . $e->getMessage());
                        }
                    }
                }

                // 2. WhatsApp para o Gestor da Frota (Alerta Gerencial)
                if ($gestor && (!empty($gestor->celular) || !empty($gestor->telefone))) {
                    $numGestor = preg_replace('/[^0-9]/', '', $gestor->celular ?: $gestor->telefone);
                    if (strlen($numGestor) >= 10) {
                        if (!str_starts_with($numGestor, '55')) $numGestor = '55' . $numGestor;

                        $msgGestor = "⚠️ *ALERTA: SAÍDA ATRASADA NA FROTA*\n\n"
                            . "O veículo *{$placa}* ultrapassou {$toleranciaMinutos} min do horário previsto de saída.\n\n"
                            . "📌 *Saída Prevista:* {$horaPrevista}\n"
                            . "👤 *Motorista:* {$motoristaNome}\n"
                            . "🔢 *ID Movimentação:* #{$mov->id}";

                        try {
                            if (method_exists($instanciaWhats, 'sendMessage')) {
                                $instanciaWhats->sendMessage($numGestor, $msgGestor, $empresaId);
                            }
                        } catch (\Throwable $e) {
                            Log::error("Erro envio whats gestor atraso #{$mov->id}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        return 0;
    }
}

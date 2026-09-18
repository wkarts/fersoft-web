<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Funcionario;
use App\Models\PontoMarcacao;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\PontoWhatsAppController;

class PontoVerificarPendenciasCommand extends Command
{
    protected $signature = 'ponto:verificar-pendencias';
    protected $description = 'Envia alertas no WhatsApp para quem esqueceu de registrar o almoço ou a saída do dia.';

    public function handle()
    {
        $hoje = Carbon::now()->toDateString();
        $horaAtual = Carbon::now()->format('H:i');

        $this->info("Iniciando verificação de pendências de ponto às {$horaAtual}...");

        // Pega todos os funcionários que bateram ENTRADA hoje
        $entradasHoje = PontoMarcacao::whereDate('data_hora_marcacao', $hoje)
            ->where('tipo_marcacao', 'entrada')
            ->where('status', 'processada')
            ->pluck('funcionario_id')
            ->unique();

        if ($entradasHoje->isEmpty()) {
            $this->info('Nenhum colaborador com entrada registrada hoje.');
            return 0;
        }

        $whatsAppController = app(PontoWhatsAppController::class);

        foreach ($entradasHoje as $funcId) {
            $func = Funcionario::find($funcId);
            if (!$func) continue;

            // Extrai o celular formatado com 55
            $celular = preg_replace('/\D/', '', $func->celular ?: $func->telefone);
            if (empty($celular)) continue;
            if (!str_starts_with($celular, '55')) {
                $celular = '55' . $celular;
            }

            // Batidas do colaborador hoje
            $etapas = PontoMarcacao::where('funcionario_id', $funcId)
                ->whereDate('data_hora_marcacao', $hoje)
                ->where('status', 'processada')
                ->pluck('tipo_marcacao')
                ->toArray();

            $primeiroNome = explode(' ', trim($func->nome))[0];

            // -------------------------------------------------------------
            // CENÁRIO 1: Alerta de Almoço (Janela das 12:45 até 14:00)
            // -------------------------------------------------------------
            if ($horaAtual >= '12:45' && $horaAtual <= '14:00') {
                if (!in_array('inicio_almoco', $etapas)) {
                    $msgAlmoco  = "⏰ *LEMBRETE DE PONTO*\n\n";
                    $msgAlmoco .= "Olá, *{$primeiroNome}*! Verificamos que você registrou a entrada mas ainda não registrou sua *Saída para o Almoço*.\n\n";
                    $msgAlmoco .= "Envie *2* para registrar agora.";

                    $whatsAppController->enviarRespostaWhatsApp($func->empresa_id, $celular, $msgAlmoco);
                    $this->info("Alerta de almoço enviado para {$func->nome}");
                }
            }

            // -------------------------------------------------------------
            // CENÁRIO 2: Alerta de Saída / Fim de Expediente (Após as 18:00)
            // -------------------------------------------------------------
            if ($horaAtual >= '18:00') {
                if (!in_array('saida', $etapas)) {
                    $msgSaida  = "🚨 *LEMBRETE DE ENCERRAMENTO*\n\n";
                    $msgSaida .= "Olá, *{$primeiroNome}*! Seu expediente de hoje ainda está em aberto.\n\n";
                    $msgSaida .= "Não se esqueça de registrar a sua *Saída* para manter seu banco de horas regularizado.\n\n";
                    $msgSaida .= "Envie *4* para encerrar o expediente ou *5* se for iniciar Hora Extra.";

                    $whatsAppController->enviarRespostaWhatsApp($func->empresa_id, $celular, $msgSaida);
                    $this->info("Alerta de saída enviado para {$func->nome}");
                }
            }
        }

        $this->info('Verificação de pendências concluída com sucesso!');
        return 0;
    }
}

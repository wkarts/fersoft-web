<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tarefa;
use App\Utils\WhatsAppUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LembreteTarefaCommand extends Command
{
    // O nome do comando que o servidor vai chamar
    protected $signature = 'tarefas:lembrete-5min';

    // A descrição do que ele faz
    protected $description = 'Envia WhatsApp 5 minutos antes da tarefa começar';

    public function handle(WhatsAppUtil $whatsappUtil)
    {
        $agora = Carbon::now();
        
        // Pega a hora exata daqui a 5 minutos
        $daquiA5Minutos = $agora->copy()->addMinutes(5)->format('H:i:00');
        $hoje = $agora->format('Y-m-d');

        // Busca no banco as tarefas pendentes para daqui a 5 minutos
        $tarefas = Tarefa::where('data', $hoje)
            ->where('hora_estimada', $daquiA5Minutos)
            ->where('status', 'pendente')
            ->get();

        if($tarefas->isEmpty()) {
            return;
        }

        foreach ($tarefas as $tarefa) {
            try {
                $mensagem = "🔔 *Lembrete FerSoft ERP* 🔔\n\n";
                $mensagem .= "Olá *{$tarefa->funcionario->nome}*!\n";
                $mensagem .= "Sua tarefa *{$tarefa->titulo}* está programada para começar em 5 minutos (às " . date('H:i', strtotime($tarefa->hora_estimada)) . ").\n\n";
                $mensagem .= "Acesse o sistema para iniciá-la.";

                // Limpa o número de celular do funcionário para enviar
                $numero = preg_replace('/[^0-9]/', '', $tarefa->funcionario->celular); 
                
                if (!empty($numero)) {
                    $numeroFinal = "55" . $numero; 
                    // Envia a mensagem usando o utilitário do seu sistema
                    $whatsappUtil->sendMessage($numeroFinal, $mensagem, $tarefa->empresa_id);
                    Log::info("Lembrete de tarefa enviado para {$numeroFinal}");
                }

            } catch (\Exception $e) {
                Log::error("Erro ao enviar lembrete de tarefa ID {$tarefa->id}: " . $e->getMessage());
            }
        }
    }
}
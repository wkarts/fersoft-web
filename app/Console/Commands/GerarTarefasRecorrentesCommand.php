<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tarefa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GerarTarefasRecorrentesCommand extends Command
{
    // O nome do comando
    protected $signature = 'tarefas:gerar-recorrentes';

    // A descrição
    protected $description = 'Gera automaticamente as tarefas diárias, semanais e mensais';

    public function handle()
    {
        Log::info("Iniciando geração de tarefas recorrentes...");

        $hoje = Carbon::now();
        
        // Busca TODAS as tarefas que são o "molde" (is_recorrente = 1)
        $tarefasRecorrentes = Tarefa::where('is_recorrente', 1)->get();

        $contador = 0;

        foreach ($tarefasRecorrentes as $tarefa) {
            $deveCriarHoje = false;
            $dataOrigem = Carbon::parse($tarefa->data); // A data que a tarefa original foi criada

            // Lógica de Frequência: É hoje o dia de criar?
            if ($tarefa->frequencia == 'diario') {
                $deveCriarHoje = true;
            } elseif ($tarefa->frequencia == 'semanal' && $hoje->dayOfWeek == $dataOrigem->dayOfWeek) {
                // Se hoje for o mesmo dia da semana (ex: Terça-feira) da tarefa original
                $deveCriarHoje = true;
            } elseif ($tarefa->frequencia == 'mensal' && $hoje->day == $dataOrigem->day) {
                // Se hoje for o mesmo dia do mês (ex: dia 05) da tarefa original
                $deveCriarHoje = true;
            }

            if ($deveCriarHoje) {
                // Trava de Segurança: Verifica se o sistema já não gerou essa tarefa hoje (evita duplicidade)
                $jaExiste = Tarefa::where('titulo', $tarefa->titulo)
                    ->where('funcionario_id', $tarefa->funcionario_id)
                    ->where('data', $hoje->format('Y-m-d'))
                    ->where('empresa_id', $tarefa->empresa_id)
                    ->exists();

                if (!$jaExiste) {
                    // O poder do Laravel: Clona a tarefa inteirinha!
                    $novaTarefa = $tarefa->replicate();
                    
                    // Altera só o que importa para a cópia de hoje
                    $novaTarefa->data = $hoje->format('Y-m-d');
                    $novaTarefa->status = 'pendente';
                    $novaTarefa->is_recorrente = 0; // A cópia não é recorrente, ela é só a tarefa do dia
                    $novaTarefa->frequencia = null;
                    
                    $novaTarefa->save();
                    $contador++;
                }
            }
        }

        Log::info("Finalizado! {$contador} novas tarefas geradas para hoje.");
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Funcionario;
use App\Models\Tarefa;
use App\Models\Usuario;
use App\Utils\WhatsAppUtil;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class DedoDuroCommand extends Command
{
    protected $signature = 'tarefas:dedo-duro';

    protected $description = 'Avisa os administradores se o funcionário não iniciou a tarefa no prazo';

    public function handle(WhatsAppUtil $whatsappUtil): int
    {
        $agora = Carbon::now();
        $appName = config('app.name', 'ERP');

        $tarefasDeHoje = Tarefa::query()
            ->with('funcionario')
            ->where('status', 'pendente')
            ->where('aviso_supervisor_enviado', 0)
            ->where('data', $agora->format('Y-m-d'))
            ->whereNotNull('hora_estimada')
            ->get();

        foreach ($tarefasDeHoje as $tarefa) {
            $dataHoraPrevista = Carbon::parse(
                $tarefa->data . ' ' . $tarefa->hora_estimada,
                config('app.timezone')
            );

            $momentoDoAviso = $dataHoraPrevista
                ->copy()
                ->addMinutes(30);

            /*
             * Ainda não passaram os 30 minutos de tolerância.
             */
            if ($agora->lessThan($momentoDoAviso)) {
                continue;
            }

            $administradores = Funcionario::query()
                ->select('funcionarios.telefone')
                ->join(
                    'usuarios',
                    'usuarios.id',
                    '=',
                    'funcionarios.usuario_id'
                )
                ->where('usuarios.empresa_id', $tarefa->empresa_id)
                ->where('usuarios.adm', 1)
                ->whereNotNull('funcionarios.telefone')
                ->where('funcionarios.telefone', '<>', '')
                ->distinct()
                ->get();

            if ($administradores->isEmpty()) {
                $this->warn(
                    "Nenhum administrador com telefone encontrado para a empresa {$tarefa->empresa_id}."
                );

                continue;
            }

            $nomeFuncionario = $tarefa->funcionario?->nome
                ?? 'Funcionário não identificado';

            $horaPrevista = Carbon::parse(
                $tarefa->hora_estimada
            )->format('H:i');

            $mensagem = "⚠️ *ALERTA DE ATRASO - {$appName}*\n\n";
            $mensagem .= "O funcionário *{$nomeFuncionario}* ainda não iniciou a tarefa:\n";
            $mensagem .= "📌 *{$tarefa->titulo}*\n";
            $mensagem .= "⏰ Prevista para: {$horaPrevista}";

            $quantidadeEnviada = 0;
            $quantidadeFalhas = 0;

            foreach ($administradores as $administrador) {
                try {
                    $whatsappUtil->sendMessage(
                        $administrador->telefone,
                        $mensagem,
                        $tarefa->empresa_id
                    );

                    /*
                     * Esta contagem presume que sendMessage()
                     * lança exceção em caso de falha.
                     */
                    $quantidadeEnviada++;
                } catch (Throwable $exception) {
                    $quantidadeFalhas++;

                    report($exception);

                    $this->error(
                        "Falha ao enviar alerta da tarefa {$tarefa->id} " .
                        "para o telefone {$administrador->telefone}: " .
                        $exception->getMessage()
                    );
                }
            }

            /*
             * Marca como avisada quando ao menos um administrador
             * foi processado sem exceção.
             */
            if ($quantidadeEnviada > 0) {
                $tarefa->update([
                    'aviso_supervisor_enviado' => 1,
                ]);

                $this->info(
                    "Alerta da tarefa {$tarefa->id} enviado para " .
                    "{$quantidadeEnviada} administrador(es). " .
                    "Falhas: {$quantidadeFalhas}."
                );

                continue;
            }

            $this->error(
                "Nenhum alerta da tarefa {$tarefa->id} foi enviado com sucesso."
            );
        }

        return self::SUCCESS;
    }
}
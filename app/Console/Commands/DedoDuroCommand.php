<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tarefa;
use App\Models\Funcionario;
use App\Utils\WhatsAppUtil;
use Carbon\Carbon;
use Throwable;

class DedoDuroCommand extends Command
{
    protected $signature = 'tarefas:dedo-duro';

    protected $description = 'Avisa os administradores se o funcionário não iniciou a tarefa no prazo';

    public function handle(WhatsAppUtil $whatsappUtil)
    {
        $agora = Carbon::now();

        /*
         * Busca tarefas:
         *
         * - pendentes;
         * - de hoje ou de ontem;
         * - com horário previsto;
         * - cujo supervisor ainda não foi avisado.
         *
         * Também buscamos o dia anterior para permitir que uma tarefa,
         * por exemplo, prevista para 23:50 seja avisada às 00:20.
         */
        $tarefas = Tarefa::with('funcionario')
            ->where('status', 'pendente')
            ->where('aviso_supervisor_enviado', 0)
            ->whereBetween('data', [
                $agora->copy()->subDay()->toDateString(),
                $agora->toDateString(),
            ])
            ->whereNotNull('hora_estimada')
            ->get();

        foreach ($tarefas as $tarefa) {

            /*
             * Monta a data/hora completa prevista para início.
             */
            try {

                $hora = trim((string) $tarefa->hora_estimada);

                if ($hora === '') {

                    $this->warn(
                        "Tarefa {$tarefa->id} ignorada: hora_estimada vazia."
                    );

                    continue;
                }

                $dataHoraPrevista = Carbon::parse(
                    $tarefa->data . ' ' . $hora
                );

            } catch (Throwable $e) {

                $this->error(
                    "Tarefa {$tarefa->id} ignorada: data/hora inválida. " .
                    $e->getMessage()
                );

                continue;
            }

            /*
             * Supervisor deve ser avisado somente após
             * 30 minutos de atraso.
             */
            $momentoDoAviso = $dataHoraPrevista
                ->copy()
                ->addMinutes(30);

            /*
             * Ainda não completou os 30 minutos.
             */
            if ($agora->lt($momentoDoAviso)) {
                continue;
            }

            /*
             * Busca administradores da mesma empresa
             * que possuam telefone cadastrado.
             */
            $administradores = Funcionario::query()
                ->select('funcionarios.telefone')
                ->join(
                    'usuarios',
                    'usuarios.id',
                    '=',
                    'funcionarios.usuario_id'
                )
                ->where(
                    'usuarios.empresa_id',
                    $tarefa->empresa_id
                )
                ->where(
                    'usuarios.adm',
                    1
                )
                ->whereNotNull(
                    'funcionarios.telefone'
                )
                ->where(
                    'funcionarios.telefone',
                    '<>',
                    ''
                )
                ->get();

            /*
             * Se não existe administrador com telefone,
             * não marcamos como enviado.
             */
            if ($administradores->isEmpty()) {

                $this->warn(
                    "Tarefa {$tarefa->id}: nenhum administrador com telefone " .
                    "encontrado para a empresa {$tarefa->empresa_id}."
                );

                continue;
            }

            $nomeFuncionario =
                $tarefa->funcionario->nome
                ?? 'Funcionário não identificado';

            /*
             * Monta a mensagem apenas uma vez.
             */
            $mensagem = "⚠️ *ALERTA DE ATRASO - FERSOFT ERP*\n\n";

            $mensagem .=
                "O funcionário *{$nomeFuncionario}* ainda não iniciou a tarefa:\n";

            $mensagem .=
                "📌 *{$tarefa->titulo}*\n";

            $mensagem .=
                "⏰ Prevista para: " .
                $dataHoraPrevista->format('d/m/Y H:i');

            $algumEnvioRealizado = false;

            /*
             * Envia para todos os administradores.
             *
             * Uma falha em um telefone não impede
             * o envio aos demais administradores.
             */
            foreach ($administradores as $adm) {

                try {

                    $telefone = trim((string) $adm->telefone);

                    if ($telefone === '') {
                        continue;
                    }

                    $whatsappUtil->sendMessage(
                        $telefone,
                        $mensagem,
                        $tarefa->empresa_id
                    );

                    $algumEnvioRealizado = true;

                } catch (Throwable $e) {

                    $this->error(
                        "Falha ao enviar alerta da tarefa {$tarefa->id} " .
                        "para {$adm->telefone}: {$e->getMessage()}"
                    );
                }
            }

            /*
             * Marca somente se pelo menos um envio
             * terminou sem lançar exceção.
             */
            if ($algumEnvioRealizado) {

                $tarefa->update([
                    'aviso_supervisor_enviado' => 1,
                ]);

                $this->info(
                    "Alerta de atraso enviado para a tarefa {$tarefa->id}."
                );
            }
        }

        return self::SUCCESS;
    }
}

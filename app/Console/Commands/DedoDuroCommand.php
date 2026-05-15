<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tarefa;
use App\Models\Usuario;
use App\Utils\WhatsAppUtil;
use Carbon\Carbon;

class DedoDuroCommand extends Command
{
    protected $signature = 'tarefas:dedo-duro';
    protected $description = 'Avisa os administradores se o funcionário não iniciou a tarefa no prazo';

    public function handle(WhatsAppUtil $whatsappUtil)
    {
        $agora = Carbon::now();
        // Definimos o atraso tolerável (ex: 30 minutos)
        $limiteParaIniciar = $agora->copy()->subMinutes(30);

        // Busca tarefas pendentes de hoje que já passaram 30 min do horário previsto
        $tarefasAtrasadas = Tarefa::where('status', 'pendente')
            ->where('aviso_supervisor_enviado', 0)
            ->where('data', $agora->format('Y-m-d'))
            ->whereNotNull('hora_estimada')
            ->where('hora_estimada', '<', $limiteParaIniciar->format('H:i:s'))
            ->get();

        foreach ($tarefasAtrasadas as $tarefa) {
            // Busca todos os ADMs da mesma empresa que tenham telefone cadastrado
            $administradores = \App\Models\Funcionario::select('funcionarios.telefone')
                ->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
                ->where('usuarios.empresa_id', $tarefa->empresa_id)
                ->where('usuarios.adm', 1)
                ->whereNotNull('funcionarios.telefone')
                ->get();

            $nomeFuncionario = $tarefa->funcionario->nome ?? 'Funcionário não identificado';

            foreach ($administradores as $adm) {
                $mensagem = "⚠️ *ALERTA DE ATRASO - FERSOFT ERP*\n\n";
                $mensagem .= "O funcionário *{$nomeFuncionario}* ainda não iniciou a tarefa:\n";
                $mensagem .= "📌 *{$tarefa->titulo}*\n";
                $mensagem .= "⏰ Prevista para: " . Carbon::parse($tarefa->hora_estimada)->format('H:i');
                
                // AJUSTE AQUI: Inserido o $tarefa->empresa_id como terceiro parâmetro
                $whatsappUtil->sendMessage($adm->telefone, $mensagem, $tarefa->empresa_id);
            }

            // Marca que o aviso já foi enviado para não repetir
            $tarefa->update(['aviso_supervisor_enviado' => 1]);
        }
    }
}
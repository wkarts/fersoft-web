<style type="text/css">
    .select2-selection__choice__remove::before{
        display: none
    }
</style>

@php
    use Carbon\Carbon;

    // --- BLOCO DE ALERTAS INTELIGENTES DE TAREFAS ---
    $usuarioLogadoId = session('user_logged')['id'] ?? null;
    $alertaTarefa = null;
    $tipoAlerta = 'info';

    if ($usuarioLogadoId) {
        // Encontra o funcionário ligado ao usuário logado
        $funcionario = \App\Models\Funcionario::where('usuario_id', $usuarioLogadoId)->first();

        if ($funcionario) {
            // Busca a primeira tarefa pendente agendada para HOJE que possua hora estimada
            $tarefaDeHoje = \App\Models\Tarefa::where('funcionario_id', $funcionario->id)
                ->where('status', 'pendente')
                ->whereDate('data', Carbon::today())
                ->whereNotNull('hora_estimada')
                ->first();

            if ($tarefaDeHoje) {
                $agora = Carbon::now();
                // Une a data de hoje com a hora estimada da atividade
                $horarioTarefa = Carbon::parse(Carbon::today()->format('Y-m-d') . ' ' . $tarefaDeHoje->hora_estimada);

                // Calcula a diferença em minutos (positivo se ainda vai acontecer, negativo se já passou)
                $diferencaMinutos = $agora->diffInMinutes($horarioTarefa, false);

                // Alerta 1: Falta entre 1 e 15 minutos para iniciar a rotina
                if ($diferencaMinutos > 0 && $diferencaMinutos <= 15) {
                    $tipoAlerta = 'info';
                    $alertaTarefa = "⏱️ <b>Próxima Tarefa:</b> Você tem uma atividade agendada para iniciar em breve (<b>{$tarefaDeHoje->titulo}</b> às " . Carbon::parse($tarefaDeHoje->hora_estimada)->format('H:i') . "). Prepare as ferramentas necessárias e lembre-se de dar o 'Play' assim que começar!";
                }
                // Alerta 2: O horário do cronograma passou e o status continua pendente (Atraso de Início)
                elseif ($diferencaMinutos < 0) {
                    $tipoAlerta = 'danger';
                    $alertaTarefa = "🚨 <b>Aviso de Início:</b> A atividade <b>{$tarefaDeHoje->titulo}</b> estava agendada para as " . Carbon::parse($tarefaDeHoje->hora_estimada)->format('H:i') . " e ainda não foi iniciada. Vá até a rotina para dar o início e não se esqueça de finalizá-la ao terminar.";
                }
            }
        }
    }
    // ------------------------------------------------
@endphp

<script>
    // diferença, em minutos, entre UTC e o timezone do servidor
    window.SERVER_OFFSET_MIN = {{ Carbon::now()->getOffset() / 60 }};

    /**
     * Retorna um objeto Date já ajustado para o horário do servidor.
     */
    function getServerDate() {
        const d = new Date();
        const serverOffset = window.SERVER_OFFSET_MIN;
        const localOffset   = -d.getTimezoneOffset();       // sinal invertido
        const diffMin      = serverOffset - localOffset;
        d.setMinutes(d.getMinutes() + diffMin);
        return d;
    }

    /**
     * Formata um Date para a string “YYYY-MM-DDThh:mm:ss”
     * compatível com <input type="datetime-local">
     */
    function formatDateTimeLocal(d) {
        const pad = n => String(n).padStart(2,'0');
        return  d.getFullYear()
            + '-' + pad(d.getMonth()+1)
            + '-' + pad(d.getDate())
            + 'T' + pad(d.getHours())
            + ':' + pad(d.getMinutes())
            + ':' + pad(d.getSeconds());
    }
</script>

@if($alertaTarefa)
    <div class="container-fluid pt-3 px-4">
        <div class="alert alert-{{ $tipoAlerta }} d-flex justify-content-between align-items-center shadow-sm border-0 border-start border-4 {{ $tipoAlerta == 'danger' ? 'border-danger' : 'border-info' }} mb-0" role="alert">
            <div class="d-flex align-items-center">
                <span class="fs-5 me-3">
                    {!! $tipoAlerta == 'danger' ? '⚠️' : '🔔' !!}
                </span>
                <span class="text-dark">{!! $alertaTarefa !!}</span>
            </div>
            <a href="/tarefas/painel" class="btn btn-sm {{ $tipoAlerta == 'danger' ? 'btn-danger' : 'btn-info text-white' }} fw-bold text-nowrap ms-3 shadow-sm">
                <i class="fas fa-arrow-right me-1"></i> Ir para o Painel
            </a>
        </div>
    </div>
@endif

@extends('default/menu_'.$tipoMenu)

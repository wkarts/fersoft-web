<style>
    .kb-card {
        background-color: #fff;
        border-radius: 8px;
        border: 1px solid #ddd;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    .kb-card:hover {
        box-shadow: 0 8px 15px rgba(0,0,0,0.1) !important;
        transform: translateY(-3px);
    }
    .avatar-circle, .avatar-image {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        text-align: center;
        font-size: 13px;
        line-height: 34px;
        font-weight: bold;
        text-transform: uppercase;
        object-fit: cover;
    }
    .avatar-circle { color: #fff; }
</style>

<div class="card kb-card mb-3 shadow-sm border-0 border-start border-4
    {{ $tarefa->prioridade == 'Urgente' ? 'border-danger' : ($tarefa->prioridade == 'Alta' ? 'border-warning' : 'border-primary') }}">

    <div class="card-body p-3">
        <h6 class="card-title fw-bold mb-1 text-dark text-truncate" title="{{ $tarefa->titulo }}">
            {{ $tarefa->titulo }}
        </h6>

        <div class="mb-3" style="font-size: 0.8rem;">
            @php
                $dotColor = 'text-primary';
                if($tarefa->prioridade == 'Alta') $dotColor = 'text-warning';
                if($tarefa->prioridade == 'Urgente') $dotColor = 'text-danger';
                if($tarefa->prioridade == 'Baixa') $dotColor = 'text-muted';
            @endphp
            <i class="fas fa-circle {{ $dotColor }} me-1" style="font-size: 0.6rem;"></i>
            <span class="text-muted fw-bold">
                {{ $tarefa->prioridade }}
                @if($tarefa->status == 'pendente' && $tarefa->tempo_gasto_minutos > 0)
                    <span class="badge bg-secondary text-white ms-1"><i class="fas fa-pause"></i> Pausada</span>
                @endif
            </span>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
            <div class="small text-muted">
                @if($tarefa->data_limite)
                    @php
                        $dataApenasCard = \Carbon\Carbon::parse($tarefa->data_limite)->format('Y-m-d');
                        $limiteCard = \Carbon\Carbon::parse($dataApenasCard . ' ' . ($tarefa->hora_limite ?: '23:59:59'));
                        $estaAtrasadaCard = \Carbon\Carbon::now()->greaterThan($limiteCard) && $tarefa->status != 'concluida';
                    @endphp

                    <i class="fas fa-stopwatch me-1 {!! $tarefa->status == 'em_andamento' ? 'timer-pulse text-info' : '' !!}"></i>

                    <span class="{{ $estaAtrasadaCard ? 'text-danger fw-bold' : 'text-success' }}">
                        {{ \Carbon\Carbon::parse($tarefa->data_limite)->format('d/M') }}
                        {{ $tarefa->hora_limite ? 'às '.\Carbon\Carbon::parse($tarefa->hora_limite)->format('H:i') : '' }}
                    </span>
                @else
                    <i class="fas fa-calendar-minus me-1"></i> Sem prazo
                @endif
            </div>

            @if($tarefa->funcionario)
                @if(!empty($tarefa->funcionario->foto_funcionario))
                    <!-- CORRIGIDO: Aponta diretamente para a pasta public/imgs_funcionarios/ -->
                    <img src="{{ asset('imgs_funcionarios/' . $tarefa->funcionario->foto_funcionario) }}" class="avatar-image shadow-sm border" title="{{ $tarefa->funcionario->nome }}">
                @else
                    <!-- Fallback caso o funcionário não tenha foto cadastrada -->
                    @php
                        $nomeArr = explode(' ', $tarefa->funcionario->nome);
                        $iniciais = strtoupper(substr($nomeArr[0], 0, 1) . (count($nomeArr) > 1 ? substr(end($nomeArr), 0, 1) : ''));
                        $color = substr(md5($tarefa->funcionario->nome), 0, 6);
                    @endphp
                    <div class="avatar-circle shadow-sm" style="background-color: #{{ $color }};" title="{{ $tarefa->funcionario->nome }}">
                        {{ $iniciais }}
                    </div>
                @endif
            @endif
        </div>

        @if(\App\Models\Usuario::find(session('user_logged')['id'])->adm && $tarefa->funcionario)
            <div class="mt-2 text-start">
                <span class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-user me-1"></i> {{ $tarefa->funcionario->nome }}</span>
            </div>
        @endif

        <div class="d-flex justify-content-end align-items-center mt-2 pt-2 border-top">
            <div class="btn-group btn-group-sm" role="group">

                @if(!empty($tarefa->descricao))
                    <button type="button" class="btn btn-light text-warning"
                            onclick="abrirModalManual({{ $tarefa->id }})" title="Ver Guia de Execução">
                        <i class="fas fa-lightbulb"></i> Como Fazer
                    </button>
                @endif

                @if($tarefa->status == 'pendente')
                    <a href="/tarefas/iniciar/{{ $tarefa->id }}" class="btn btn-info text-white p-1 px-2" title="Iniciar Atividade">
                        <i class="fas fa-play" style="font-size: 0.75rem;"></i> Iniciar
                    </a>
                @elseif($tarefa->status == 'em_andamento')
                    @php
                        $isAtrasadaBtn = false;
                        if(!empty($tarefa->data_limite)) {
                            $dataApenasBtn = \Carbon\Carbon::parse($tarefa->data_limite)->format('Y-m-d');
                            $horaBtn = !empty($tarefa->hora_limite) ? $tarefa->hora_limite : '23:59:59';
                            if(\Carbon\Carbon::now()->greaterThan(\Carbon\Carbon::parse($dataApenasBtn . ' ' . $horaBtn))) {
                                $isAtrasadaBtn = true;
                            }
                        }
                    @endphp
                    <a href="/tarefas/pausar/{{ $tarefa->id }}" class="btn btn-warning text-dark p-1 px-2" title="Pausar Atividade">
                        <i class="fas fa-pause" style="font-size: 0.75rem;"></i> Pausar
                    </a>
                    <a href="javascript:void(0)" onclick="finalizarTarefa({{ $tarefa->id }}, {{ $isAtrasadaBtn ? 'true' : 'false' }})" class="btn btn-success text-white p-1 px-2" title="Finalizar Atividade">
                        <i class="fas fa-check-double" style="font-size: 0.75rem;"></i> Finalizar
                    </a>
                @endif

                <a href="/tarefas/edit/{{ $tarefa->id }}" class="btn btn-light text-primary border p-1 px-2" title="Editar Detalhes">
                    <i class="fas fa-pencil-alt" style="font-size: 0.75rem;"></i>
                </a>
            </div>
        </div>

    </div>
</div>

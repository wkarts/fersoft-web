<style>
    .kb-card {
        background-color: #fff;
        border-radius: 8px;
        border: 1px solid #ddd;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    /* Sombra e leve subida no hover */
    .kb-card:hover {
        box-shadow: 0 8px 15px rgba(0,0,0,0.1) !important;
        transform: translateY(-3px);
    }
    
    /* Avatar Circular com Iniciais */
    .avatar-circle {
        width: 32px;
        height: 32px;
        background-color: #d1d4d7; /* Cor neutra padrão */
        color: #fff;
        border-radius: 50%;
        text-align: center;
        font-size: 14px;
        line-height: 32px; /* Centraliza o texto verticalmente */
        font-weight: bold;
        text-transform: uppercase;
    }
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
            <span class="text-muted fw-bold">{{ $tarefa->prioridade }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
            <div class="small text-muted">
                @if($tarefa->data_limite)
                    @php
                        $dataApenasCard = \Carbon\Carbon::parse($tarefa->data_limite)->format('Y-m-d');
                        $limiteCard = \Carbon\Carbon::parse($dataApenasCard . ' ' . ($tarefa->hora_limite ?: '23:59:59'));
                        $estaAtrasadaCard = \Carbon\Carbon::now()->greaterThan($limiteCard);
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
                @php
                    $nomeArr = explode(' ', $tarefa->funcionario->nome);
                    // Pega a primeira letra do primeiro nome e a primeira do último
                    $iniciais = strtoupper(substr($nomeArr[0], 0, 1) . substr(end($nomeArr), 0, 1));
                    
                    // Gera uma cor de fundo única baseada no nome (para avatares coloridos)
                    $hash = md5($tarefa->funcionario->nome);
                    $color = substr($hash, 0, 6); // Pega os primeiros 6 caracteres como hexadecimal
                @endphp
                <div class="avatar-circle shadow-sm" style="background-color: #{{ $color }};" title="{{ $tarefa->funcionario->nome }}">
                    {{ $iniciais }}
                </div>
            @endif
        </div>

        <div class="d-flex justify-content-end align-items-center mt-2">
            @if($tarefa->status == 'pendente')
                <a href="/tarefas/iniciar/{{ $tarefa->id }}" class="btn btn-sm btn-info text-white p-1 px-2 me-1" title="Iniciar Atividade">
                    <i class="fas fa-play" style="font-size: 0.7rem;"></i>
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
                <a href="javascript:void(0)" onclick="finalizarTarefa({{ $tarefa->id }}, {{ $isAtrasadaBtn ? 'true' : 'false' }})" class="btn btn-sm btn-success text-white p-1 px-2 me-1" title="Finalizar Atividade">
                    <i class="fas fa-check-double" style="font-size: 0.7rem;"></i>
                </a>
            @endif
            <a href="/tarefas/edit/{{ $tarefa->id }}" class="btn btn-sm btn-light text-primary p-1 px-2" title="Editar Detalhes">
                <i class="fas fa-pencil-alt" style="font-size: 0.7rem;"></i>
            </a>
        </div>
    </div>
</div>
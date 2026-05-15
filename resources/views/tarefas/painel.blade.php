@extends('default.layout')
@section('content')
<style>
    /* Fundo da página mais limpo */
    body { background-color: #f4f7f6; }
    
    /* Estilo das Colunas Kanban */
    .kanban-col {
        background-color: #e2e4e6; /* Cor base Trello */
        border-radius: 8px;
        padding: 10px;
        min-height: 80vh;
        border: 1px solid #d1d4d7;
    }
    
    /* Cores suaves nos cabeçalhos */
    .kanban-header {
        font-weight: 700;
        font-size: 1.1rem;
        padding: 8px 10px;
        border-radius: 6px;
        margin-bottom: 15px;
        color: #fff;
    }
    .head-pendente { background: linear-gradient(45deg, #6c757d, #a3abb1); }
    .head-andamento { background: linear-gradient(45deg, #0dcaf0, #67e1f9); }
    .head-atrasada { background: linear-gradient(45deg, #dc3545, #f16876); }
    .head-concluida { background: linear-gradient(45deg, #198754, #40c082); }

    /* Efeito Pulse Animado para o Cronómetro */
    @keyframes pulse-blue {
        0% { box-shadow: 0 0 0 0 rgba(13, 202, 240, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(13, 202, 240, 0); }
        100% { box-shadow: 0 0 0 0 rgba(13, 202, 240, 0); }
    }
    .timer-pulse {
        border-radius: 50%;
        animation: pulse-blue 2s infinite;
        padding: 3px;
    }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h3 class="fw-bold text-secondary">
            <i class="fas fa-chalkboard text-primary me-2"></i>Gestão Visual de Atividades
        </h3>
        <div class="d-flex">
            <a href="/tarefas" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-list"></i> Vista em Lista
            </a>
            <a href="/tarefas/register" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nova Tarefa
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="kanban-col shadow-sm">
                <div class="kanban-header head-pendente shadow-sm">
                    <i class="fas fa-folder me-2"></i>Pendentes 
                    <span class="badge bg-white text-secondary float-end mt-1">{{ $pendentes->count() }}</span>
                </div>
                <div class="kanban-body">
                    @forelse($pendentes as $t)
                        @include('tarefas._card_kanban', ['tarefa' => $t])
                    @empty
                        <div class="text-center text-muted py-4 small">Nada pendente. <br> Bom trabalho!</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kanban-col shadow-sm">
                <div class="kanban-header head-andamento shadow-sm">
                    <i class="fas fa-running me-2"></i>Em Execução
                    <span class="badge bg-white text-info float-end mt-1">{{ $andamento->count() }}</span>
                </div>
                <div class="kanban-body">
                    @forelse($andamento as $t)
                        @include('tarefas._card_kanban', ['tarefa' => $t])
                    @empty
                        <div class="text-center text-muted py-4 small">Ninguém a trabalhar agora. <br> Zzz...</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kanban-col shadow-sm border border-danger">
                <div class="kanban-header head-atrasada shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i>Atrasadas 🔥
                    <span class="badge bg-white text-danger float-end mt-1">{{ $atrasadas->count() }}</span>
                </div>
                <div class="kanban-body">
                    @forelse($atrasadas as $t)
                        @include('tarefas._card_kanban', ['tarefa' => $t])
                    @empty
                        <div class="text-center text-muted py-4 small">Tudo em dia! <br> Incrível! 🚀</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kanban-col shadow-sm">
                <div class="kanban-header head-concluida shadow-sm">
                    <i class="fas fa-check-circle me-2"></i>Finalizadas ⭐
                </div>
                <div class="kanban-body">
                    @forelse($concluidas as $t)
                        @include('tarefas._card_kanban', ['tarefa' => $t])
                    @empty
                        <div class="text-center text-muted py-4 small">Nenhuma tarefa concluída ainda.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function finalizarTarefa(id, isAtrasada) {
        let justificativa = '';
        
        if (isAtrasada) {
            justificativa = prompt("⚠️ ATENÇÃO: Esta tarefa está ATRASADA!\n\nPor favor, informe resumidamente o motivo do atraso (obrigatório):");
            
            if (justificativa === null || justificativa.trim() === "") {
                alert("Você precisa informar o motivo para finalizar uma tarefa atrasada.");
                return; 
            }
        }
        
        window.location.href = '/tarefas/finalizar/' + id + '?justificativa=' + encodeURIComponent(justificativa);
    }
</script>
@endsection
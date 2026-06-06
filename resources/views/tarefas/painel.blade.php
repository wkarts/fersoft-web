@extends('default.layout')
@section('content')
<style>
    body { background-color: #f4f7f6; }
    
    .kanban-col {
        background-color: #e2e4e6; 
        border-radius: 8px;
        padding: 10px;
        min-height: 80vh;
        border: 1px solid #d1d4d7;
    }
    
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

    /* Estilo para a foto de perfil do Colaborador no topo do painel */
    .user-panel-avatar {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
    }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div class="d-flex align-items-center">
            @php
                $userLogged = \App\Models\Usuario::find(session('user_logged')['id']);
                $funcLogged = \App\Models\Funcionario::where('usuario_id', $userLogged->id)->first();
            @endphp
            
            @if(!$userLogged->adm && $funcLogged)
                <img src="{{ $funcLogged->foto ? asset('storage/' . $funcLogged->foto) : 'https://cdn-icons-png.flaticon.com/512/149/149071.png' }}" 
                     class="user-panel-avatar shadow-sm me-3" alt="Foto do Colaborador">
                <div>
                    <h3 class="fw-bold text-secondary mb-0">Gestão Visual de Atividades</h3>
                    <small class="text-muted">Colaborador: <b>{{ $funcLogged->nome }}</b></small>
                </div>
            @else
                <h3 class="fw-bold text-secondary mb-0">
                    <i class="fas fa-chalkboard text-primary me-2"></i>Gestão Visual de Atividades (Visão Geral ADM)
                </h3>
            @endif
        </div>
        
        <div class="d-flex">
            <a href="/tarefas" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-list"></i> Vista em Lista
            </a>
            <a href="/tarefas/new" class="btn btn-primary btn-sm">
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
                    <span class="badge bg-white text-success float-end mt-1">{{ $concluidas->count() }}</span>
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

@namespaceTasksModals
@foreach(['pendentes', 'andamento', 'concluidas', 'atrasadas'] as $grupo)
    @foreach($$grupo as $tarefa)
        @if(!empty($tarefa->descricao))
            <div class="modal fade" id="modalManual{{ $tarefa->id }}" tabindex="-1" aria-labelledby="labelModal{{ $tarefa->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered text-start">
                    <div class="modal-content shadow-lg border-0">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title fw-bold" id="labelModal{{ $tarefa->id }}">
                                <i class="fas fa-book-reader me-2"></i> Guia de Execução: {{ $tarefa->titulo }}
                            </h5>
                            <button type="button" class="btn-close" onclick="fecharModalManual({{ $tarefa->id }})" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="font-size: 0.95rem; white-space: pre-wrap; line-height: 1.5; color: #333;">
                            <div class="p-2 bg-light rounded border mb-3 text-muted small">
                                <i class="fas fa-info-circle text-warning"></i> Siga atentamente as instruções operacionais abaixo para realizar esta atividade.
                            </div>
                            <div class="p-1">{!! nl2br(e($tarefa->descricao)) !!}</div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalManual({{ $tarefa->id }})">Fechar Guia</button>
                            @if($tarefa->status == 'pendente')
                                <a href="/tarefas/iniciar/{{ $tarefa->id }}" class="btn btn-info btn-sm text-white fw-bold shadow-sm">
                                    <i class="fas fa-play me-1"></i> Iniciar Agora
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endforeach


<script>
    // Função para Abrir o Modal Forçado
    function abrirModalManual(id) {
        var modalElement = document.getElementById('modalManual' + id);
        
        if (modalElement) {
            // Adiciona as classes e estilos para exibir o modal na tela
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            document.body.classList.add('modal-open');
            
            // Remove qualquer backdrop antigo duplicado para não travar a tela
            var antigoBackdrop = document.getElementById('backdropModal' + id);
            if (antigoBackdrop) antigoBackdrop.remove();

            // Cria o fundo escuro (backdrop) atrás do modal
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'backdropModal' + id;
            document.body.appendChild(backdrop);
        } else {
            alert('Erro: Estrutura do manual não localizada para esta tarefa.');
        }
    }

    // NOVA: Função para Fechar o Modal Forçado
    function fecharModalManual(id) {
        var modalElement = document.getElementById('modalManual' + id);
        
        if (modalElement) {
            // Esconde o modal tirando as classes de exibição
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
            document.body.classList.remove('modal-open');
            
            // Remove o fundo escuro (backdrop) da tela
            var backdrop = document.getElementById('backdropModal' + id);
            if (backdrop) {
                backdrop.remove();
            }
        }
    }

    // Função das tarefas atrasadas (já existente no seu projeto)
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
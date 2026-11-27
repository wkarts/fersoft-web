@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endpush

@section('content')
<div class="ui-admin ui-admin-tarefas">
    <div class="container-fluid">

        @if(session('mensagem_sucesso'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('mensagem_sucesso') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('mensagem_erro'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('mensagem_erro') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="ui-admin-panel mt-3">
            <div class="ui-admin-panel-head d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ $title }}</h4>
                <a href="{{ $newItemUrl }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> {{ $newItemText }}
                </a>
            </div>

            <div class="ui-admin-panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                        <tr>
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                            <th class="text-center" style="width: 220px;">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td class="fw-bold text-muted">#{{ $record->id }}</td>
                                <td class="fw-semibold text-dark">{{ $record->titulo }}</td>
                                <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-user text-primary me-1"></i> {{ $record->funcionario->nome ?? 'Não atribuído' }}
                                </span>
                                </td>

                                <td>
                                    @if($record->prioridade == 'Urgente')
                                        <span class="badge bg-danger shadow-sm">Urgente</span>
                                    @elseif($record->prioridade == 'Alta')
                                        <span class="badge bg-warning text-dark shadow-sm">Alta</span>
                                    @elseif($record->prioridade == 'Baixa')
                                        <span class="badge bg-info text-white shadow-sm">Baixa</span>
                                    @else
                                        <span class="badge bg-primary shadow-sm">Normal</span>
                                    @endif
                                </td>

                                <td><i class="far fa-calendar-alt text-muted me-1"></i>{{ \Carbon\Carbon::parse($record->data)->format('d/m/Y') }}</td>

                                <td>
                                    @if($record->status == 'cancelada')
                                        <span class="badge bg-dark"><i class="fas fa-ban"></i> Cancelada</span>
                                    @elseif($record->status == 'pendente' && $record->tempo_gasto_minutos > 0)
                                        <span class="badge bg-secondary text-white"><i class="fas fa-pause-circle"></i> Pausado</span>
                                    @elseif($record->status == 'pendente')
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pendente</span>
                                    @elseif($record->status == 'em_andamento')
                                        <span class="badge bg-primary"><i class="fas fa-spinner fa-spin"></i> Em Andamento</span>
                                    @else
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Concluída</span>
                                    @endif
                                </td>

                                <td><span class="badge bg-light text-dark border">{{ $record->tempo_gasto_minutos }} min</span></td>

                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @if($record->status == 'pendente')
                                            <a href="/tarefas/iniciar/{{ $record->id }}" class="btn btn-outline-info" title="Iniciar Tarefa">
                                                <i class="la la-play"></i>
                                            </a>
                                        @elseif($record->status == 'em_andamento')
                                            <a href="/tarefas/pausar/{{ $record->id }}" class="btn btn-outline-warning" title="Pausar Tarefa">
                                                <i class="la la-pause"></i>
                                            </a>
                                            <a href="javascript:void(0)" onclick="finalizarTarefa({{ $record->id }}, false)" class="btn btn-outline-success" title="Finalizar Tarefa">
                                                <i class="la la-check"></i>
                                            </a>
                                        @endif

                                        @if($record->status != 'concluida' && $record->status != 'cancelada')
                                            <a href="/tarefas/cancelar/{{ $record->id }}" class="btn btn-outline-danger" onclick="return confirm('Deseja realmente cancelar esta tarefa?');" title="Cancelar Tarefa">
                                                <i class="la la-times-circle"></i>
                                            </a>
                                        @endif

                                        <a href="{{ $editUrl }}/{{ $record->id }}" class="btn btn-outline-primary" title="Editar">
                                            <i class="la la-edit"></i>
                                        </a>

                                        <a href="{{ $deleteUrl }}/{{ $record->id }}" class="btn btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir esta tarefa?');" title="Excluir">
                                            <i class="la la-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Nenhuma tarefa encontrada.</td>
                            </tr>
                        @endforelse

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function finalizarTarefa(id, isAtrasada) {
            let justificativa = '';

            if (isAtrasada) {
                justificativa = prompt("⚠️ Esta tarefa está ATRASADA!\n\nPor favor, informe a justificativa para o atraso (obrigatório):");

                if (justificativa === null || justificativa.trim() === "") {
                    alert("A justificativa é obrigatória para finalizar tarefas atrasadas.");
                    return;
                }
            }

            window.location.href = '/tarefas/finalizar/' + id + '?justificativa=' + encodeURIComponent(justificativa);
        }
    </script>
</div>
@endsection

@extends('default.layout')

@section('content')
<div class="container-fluid">

    <!-- Exibição de Alertas de Sucesso e Erro (padrão do BaseController) -->
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

    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ $title }}</h4>
            <a href="{{ $newItemUrl }}" class="btn btn-success">
                <i class="fas fa-plus"></i> {{ $newItemText }}
            </a>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <!-- Renderiza os cabeçalhos definidos no método headers() do TarefaController -->
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                        <tr>
                            <td>{{ $record->id }}</td>
                            <td>{{ $record->titulo }}</td>
                          	<td>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-user"></i> {{ $record->funcionario->nome ?? 'Não atribuído' }}
                                </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($record->data)->format('d/m/Y') }}</td>
                            
                            <!-- Badges de Status -->
                            <td>
                                @if($record->status == 'pendente')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pendente</span>
                                @elseif($record->status == 'em_andamento')
                                    <span class="badge bg-primary"><i class="fas fa-spinner fa-spin"></i> Em Andamento</span>
                                @else
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Concluída</span>
                                @endif
                            </td>
                            
                            <td>{{ $record->tempo_gasto_minutos }} min</td>
                            
                            <td class="text-center">
                                @if($record->status == 'pendente')
                                    <a href="/tarefas/iniciar/{{ $record->id }}" class="btn btn-sm btn-info text-white" title="Iniciar Tarefa">
                                        <i class="fas fa-play"></i> Iniciar
                                    </a>
                                @elseif($record->status == 'em_andamento')
                                    
                                    @php
                                        $isAtrasada = false;
                                        if(!empty($record->data_limite)) {
                                            // Extrai só a data para evitar o erro de dupla hora
                                            $dataApenas = \Carbon\Carbon::parse($record->data_limite)->format('Y-m-d');
                                            $hora = !empty($record->hora_limite) ? $record->hora_limite : '23:59:59';
                                            $limite = \Carbon\Carbon::parse($dataApenas . ' ' . $hora);
                                            
                                            if(\Carbon\Carbon::now()->greaterThan($limite)) {
                                                $isAtrasada = true;
                                            }
                                        }
                                    @endphp
                                    
                                    <a href="javascript:void(0)" onclick="finalizarTarefa({{ $record->id }}, {{ $isAtrasada ? 'true' : 'false' }})" class="btn btn-sm btn-success text-white" title="Finalizar Tarefa">
                                        <i class="fas fa-check-double"></i> Finalizar
                                    </a>
                                   
                                    @endif

                                <a href="{{ $editUrl }}/{{ $record->id }}" class="btn btn-sm btn-primary" title="Editar">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                
                                <a href="{{ $deleteUrl }}/{{ $record->id }}" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta tarefa?');" title="Excluir">
                                    <i class="fas fa-trash"></i> Excluir
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ count($headers) + 1 }}" class="text-center">Nenhuma tarefa encontrada.</td>
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
@endsection
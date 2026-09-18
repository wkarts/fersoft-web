@php
    $title = $title ?? 'Grupos de Categorias';
@endphp
@extends('default.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-layer-group text-primary me-2"></i> Grupos de Categorias Financeiras</h4>
        <a href="{{ route('grupo-categorias.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Novo Grupo
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Grupo</th>
                            <th>Descrição</th>
                            <th>Categorias Vinculadas</th>
                            <th class="text-end me-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grupos as $grupo)
                            <tr>
                                <td><strong>{{ $grupo->nome }}</strong></td>
                                <td>{{ $grupo->descricao ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        {{ $grupo->categorias->count() }} Categoria(s)
                                    </span>
                                    <small class="text-muted d-block text-truncate style-max-w" style="max-width: 300px;">
                                        {{ $grupo->categorias->pluck('nome')->implode(', ') }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('grupo-categorias.edit', $grupo->id) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('grupo-categorias.destroy', $grupo->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Deseja realmente excluir este grupo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    Nenhum grupo cadastrado até o momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
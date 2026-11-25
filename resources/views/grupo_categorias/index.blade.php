@php
    $title = $title ?? 'Grupos de Categorias';
@endphp
@extends('default.layout')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-catalog-config.css') }}?v={{ filemtime(public_path('css/fer-catalog-config.css')) }}">
@endpush
@section('content')
<div class="fer-catalog-config fer-catalog-config--list fer-catalog-config--grupo_categorias">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Grupos de Categorias Financeiras</h4>
            <a href="{{ route('grupo-categorias.create') }}" class="btn btn-primary">
                Novo Grupo
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Fechar aviso"><span aria-hidden="true">&times;</span></button>
            </div>
        @endif

        <!-- Tabela Principal de Grupos -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Grupo</th>
                            <th>Descrição</th>
                            <th>Categorias Vinculadas</th>
                            <th class="text-end">Ações</th>
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
                                    <small class="text-muted d-block text-truncate" style="max-width: 400px;">
                                        {{ $grupo->categorias->pluck('nome')->implode(', ') }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('grupo-categorias.edit', $grupo->id) }}" class="btn btn-sm btn-outline-warning">
                                        Editar
                                    </a>
                                    <form action="{{ route('grupo-categorias.destroy', $grupo->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Deseja realmente excluir este grupo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Excluir
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

        <!-- Bloco de Categorias Sem Grupo (No mesmo padrão limpo do sistema) -->
        @if(isset($categoriasSemGrupo) && $categoriasSemGrupo->count() > 0)
            <div class="card shadow-sm border-0 border-top border-warning border-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-0 text-dark fw-bold fs-6">Categorias Sem Grupo Vinculado</h5>
                        <small class="text-muted">Estes lançamentos irão automaticamente para o resumo sob a categoria <strong>"OUTROS / SEM GRUPO"</strong>.</small>
                    </div>
                    <span class="badge bg-warning text-dark">{{ $categoriasSemGrupo->count() }} Pendente(s)</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Nome da Categoria (Sem Grupo)</th>
                                <th class="text-end" style="width: 150px;">Ação</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($categoriasSemGrupo as $catSemGrupo)
                                <tr>
                                    <td class="align-middle text-secondary">{{ $catSemGrupo->nome }}</td>
                                    <td class="text-end align-middle">
                                        <a href="{{ route('grupo-categorias.create') }}" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 12px;">
                                            Vincular Grupo
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

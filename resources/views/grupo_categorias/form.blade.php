
@php
    $title = $title ?? 'Grupos de Categorias';
@endphp
@extends('default.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-folder-plus me-2"></i>
                        {{ isset($grupo) ? 'Editar Grupo de Categorias' : 'Novo Grupo de Categorias' }}
                    </h5>
                    <a href="{{ route('grupo-categorias.index') }}" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>

                <div class="card-body">
                    <form action="{{ isset($grupo) ? route('grupo-categorias.update', $grupo->id) : route('grupo-categorias.store') }}" method="POST">
                        @csrf
                        @if(isset($grupo))
                            @method('PUT')
                        @endif

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="nome" class="form-label fw-bold">Nome do Grupo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $grupo->nome ?? '') }}" placeholder="Ex: Frotas, TI, Administrativo" required>
                                @error('nome')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="descricao" class="form-label fw-bold">Descrição / Observações</label>
                                <input type="text" class="form-control" id="descricao" name="descricao" value="{{ old('descricao', $grupo->descricao ?? '') }}" placeholder="Finalidade deste agrupamento">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold mb-3 text-secondary">
                            <i class="fas fa-list-check me-1"></i> Selecione as Categorias de Conta Vinculadas:
                        </h6>

                        {{-- Busca rápida de categorias no frontend --}}
                        <div class="mb-3">
                            <input type="text" id="filtroCategoria" class="form-control form-control-sm" placeholder="🔍 Digite para filtrar categorias...">
                        </div>

                        <div class="row row-cols-1 row-cols-md-3 g-2 style-scroll" style="max-height: 350px; overflow-y: auto;">
                            @forelse($categorias as $cat)
                                @php
                                    $checked = isset($categoriasSelecionadas) && in_array($cat->id, $categoriasSelecionadas) ? 'checked' : '';
                                @endphp
                                <div class="col item-categoria">
                                    <div class="form-check card p-2 border rounded shadow-xs h-100">
                                        <input class="form-check-input ms-1 me-2" type="checkbox" name="categorias[]" value="{{ $cat->id }}" id="cat_{{ $cat->id }}" {{ $checked }}>
                                        <label class="form-check-label w-100 text-truncate" for="cat_{{ $cat->id }}" style="cursor: pointer;">
                                            <strong>{{ $cat->nome }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                Tipo: <span class="badge {{ $cat->tipo == 'pagar' ? 'bg-danger' : 'bg-success' }} me-1">{{ strtoupper($cat->tipo ?? 'N/A') }}</span>
                                                @if($cat->dre_grupo)
                                                    DRE: {{ $cat->dre_grupo }}
                                                @endif
                                            </small>
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted py-3">
                                    Nenhuma categoria de conta cadastrada.
                                </div>
                            @endforelse
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('grupo-categorias.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Salvar Grupo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // JS Simples para busca em tempo real nas categorias
    document.getElementById('filtroCategoria').addEventListener('keyup', function() {
        let termo = this.value.toLowerCase();
        let itens = document.querySelectorAll('.item-categoria');

        itens.forEach(function(item) {
            let texto = item.textContent.toLowerCase();
            if(texto.includes(termo)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>
@endsection
@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fa fa-exchange-alt"></i> De-Para de Resíduos (MTR / SINIR / IEMA)</h4>
        <a href="{{ route('mtr.depara.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> Novo Vínculo De-Para
        </a>
    </div>

    @if(session('sucesso'))
        <div class="alert alert-success">{{ session('sucesso') }}</div>
    @endif
    @if(session('erro'))
        <div class="alert alert-danger">{{ session('erro') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Órgão</th>
                            <th>Produto / Categoria Fersoft</th>
                            <th>Cód. IBAMA</th>
                            <th>Descrição Resíduo</th>
                            <th>Classe</th>
                            <th>Estado Físico</th>
                            <th>Unidade</th>
                            <th>Fator Conv.</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deparas as $d)
                            @php
                                // Tradução legível do Estado Físico
                                $mapFisico = [1 => 'Sólido', 2 => 'Líquido', 3 => 'Semissólido', 4 => 'Gasoso'];
                                $estadoFisicoTexto = $mapFisico[$d->estado_fisico] ?? $d->estado_fisico;

                                // Tradução legível da Unidade de Medida
                                $mapUnidade = [1 => 'Quilograma (kg)', 2 => 'Quilograma (Kg)', 3 => 'Tonelada (Ton)', 20 => 'Metro Cúbico (M³)', 21 => 'Litro (Lt)'];
                                $unidadeTexto = $mapUnidade[$d->unidade_medida] ?? $d->unidade_medida;

                                // Tradução legível da Classe de Risco
                                $mapClasse = [1 => 'Classe I (Perigoso)', 2 => 'Classe II A (Não Inerte)', 3 => 'Classe II B (Inerte)'];
                                $classeTexto = $mapClasse[$d->classe_residuo] ?? $d->classe_residuo;
                            @endphp
                            <tr>
                                <td><span class="badge bg-info text-dark">{{ $d->orgao }}</span></td>
                                <td>
                                    @if(isset($d->produto_nome) && $d->produto_nome)
                                        <strong>Produto:</strong> {{ $d->produto_nome }} <small class="text-muted">({{ $d->produto_ncm }})</small>
                                    @elseif(isset($d->categoria_nome) && $d->categoria_nome)
                                        <strong>Categoria:</strong> {{ $d->categoria_nome }}
                                    @else
                                        <span class="text-muted">Geral</span>
                                    @endif
                                </td>
                                <td><code>{{ $d->cod_ibama }}</code></td>
                                <td>{{ $d->descricao_residuo }}</td>
                                <td>{{ $classeTexto }}</td>
                                <td>{{ $estadoFisicoTexto }}</td>
                                <td>{{ $unidadeTexto }}</td>
                                <td>{{ number_format($d->fator_conversao, 4, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('mtr.depara.edit', $d->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="{{ route('mtr.depara.destroy', $d->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Deseja realmente remover este vínculo?')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Nenhum vínculo De-Para cadastrado até o momento.
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
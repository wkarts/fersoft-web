@extends('default.layout')
@section('content')
<div class="container-fluid px-3 px-lg-5 py-4" style="max-width: 1140px">
    <div class="mb-4">
        <div class="text-muted small mb-1">Configurações</div>
        <h1 class="h3 mb-0">Pagamentos eletrônicos</h1>
    </div>
    <div class="card card-custom mb-4">
        <div class="card-body">
            <form method="get" action="{{ route('configuracao-pagamentos.index') }}" class="row align-items-end">
                <div class="col-12 col-md-7">
                    <label for="pag-local">Estabelecimento</label>
                    <select class="form-control" id="pag-local" name="local">
                        @foreach($locais as $id => $nome)
                            <option value="{{ $id }}" @selected($id === $local)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto mt-3 mt-md-0"><button class="btn btn-light-primary">Abrir estabelecimento</button></div>
            </form>
        </div>
    </div>

    @if($codigo === null)
        <div class="card card-custom">
            <div class="card-header"><div class="card-title"><h2 class="h5 mb-0">Provedores · {{ $locais[$local] }}</h2></div></div>
            <div class="card-body">
                @if($provedores->isEmpty())
                    <p class="text-muted">Nenhum provedor cadastrado neste estabelecimento.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover"><thead><tr><th>Provedor</th><th>Ambiente em uso</th><th>Ações</th></tr></thead>
                            <tbody>
                            @foreach($provedores as $id => $provedor)
                                @php($ativa = $conexoes->get($id, collect())->firstWhere('selecionada', true))
                                <tr><th scope="row">{{ $catalogo[$id]['nome'] ?? $id }}</th>
                                    <td>{{ $ativa ? ($ambientes[$ativa->ambiente] ?? 'Bloqueado pelo ambiente fiscal') : 'Inativo' }}</td>
                                    <td>@isset($catalogo[$id])
                                        <a class="btn btn-sm btn-light-primary mr-2 my-1" href="{{ route('configuracao-pagamentos.index', ['local' => $local, 'provedor' => $id, 'ambiente' => isset($ambientes[$ativa?->ambiente]) ? $ativa->ambiente : 'sandbox']) }}">Configurar</a>
                                        @if($id === 'asaas')<a class="btn btn-sm btn-light my-1" href="{{ route('configuracao-pagamentos.acessos', ['local' => $local]) }}">Acessos por usuário</a>@endif
                                    @endisset</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if($disponiveis = array_diff_key($catalogo, $provedores->all()))
                    <form method="post" action="{{ route('configuracao-pagamentos.adicionar', ['local' => $local]) }}" class="row align-items-end mt-3">
                        @csrf
                        <input type="hidden" name="estabelecimento" value="{{ $site->empresa }}-{{ $local }}">
                        <div class="col-12 col-sm-6"><label for="pag-provedor">Novo provedor</label>
                            <select class="form-control" id="pag-provedor" name="provedor" required>
                                @foreach($disponiveis as $id => $item)<option value="{{ $id }}">{{ $item['nome'] }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-auto mt-3 mt-sm-0"><button class="btn btn-primary">Adicionar provedor</button></div>
                    </form>
                @endif
            </div>
        </div>
    @else
        <a class="d-inline-block mb-3" href="{{ route('configuracao-pagamentos.index', ['local' => $local]) }}">&larr; Voltar aos provedores</a>
        <div class="card card-custom">
            <div class="card-header"><div class="card-title"><h2 class="h5 mb-0">{{ $catalogo[$codigo]['nome'] }} · {{ $locais[$local] }}</h2></div></div>
            <div class="card-body">
                <nav class="nav nav-pills mb-4" aria-label="Ambiente do provedor">
                    @foreach($ambientes as $id => $nome)
                        <a class="nav-link {{ $id === $ambiente ? 'active' : '' }}" @if($id === $ambiente) aria-current="page" @endif
                           href="{{ route('configuracao-pagamentos.index', ['local' => $local, 'provedor' => $codigo, 'ambiente' => $id]) }}">{{ $nome }}</a>
                    @endforeach
                </nav>
                @include($catalogo[$codigo]['formulario'], ['conexao' => $conexoes->get($codigo, collect())->firstWhere('ambiente', $ambiente)])
            </div>
        </div>
    @endif
</div>
@endsection

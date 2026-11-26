@extends('default.layout')
@section('content')
<link rel="stylesheet" href="{{ asset('css/configuracao-pagamentos.css') }}">
<div class="pg-config">
    <header class="pg-cabecalho">
        <div>
            @if($codigo !== null)
                <a class="pg-voltar" href="{{ route('configuracao-pagamentos.index') }}">&larr; Pagamentos eletrônicos</a>
            @else
                <div class="pg-origem">Configurações</div>
            @endif
            <h1>{{ $codigo !== null ? $catalogo[$codigo]['nome'] : 'Pagamentos eletrônicos' }}</h1>
        </div>
        <span class="pg-local"><i class="la la-map-marker" aria-hidden="true"></i>{{ $nomeLocal }}</span>
    </header>

    <section class="card card-custom pg-painel">
    @if($codigo === null)
        <h2 class="pg-titulo">Provedores de pagamento</h2>
        @forelse($provedores as $id => $provedor)
            @php
                $ativa = $conexoes->get($id, collect())->firstWhere('selecionada', true);
                $emUso = $ativa && isset($ambientes[$ativa->ambiente]);
            @endphp
            <div class="pg-provedor">
                <div class="pg-identidade">
                    <span class="pg-icone"><i class="la la-credit-card" aria-hidden="true"></i></span>
                    <div><h3>{{ $catalogo[$id]['nome'] ?? $id }}</h3>
                        <span class="pg-estado {{ $emUso ? 'pg-em-uso' : '' }}">{{ $ativa ? ($ambientes[$ativa->ambiente] ?? 'Bloqueado pelo ambiente fiscal') : 'Inativo' }}</span>
                    </div>
                </div>
                <div class="pg-metodos">
                    @if($emUso)
                        <small>Formas habilitadas</small>
                        {{ implode(' · ', $ativa->metodosAtivos()) ?: 'Nenhuma' }}
                    @endif
                </div>
                @isset($catalogo[$id])
                    <div class="pg-acoes">
                        @if($id === 'asaas')<a class="btn btn-light-primary" href="{{ route('configuracao-pagamentos.acessos', ['local' => $local]) }}">Acessos por usuário</a>@endif
                        <a class="btn btn-primary" href="{{ route('configuracao-pagamentos.index', ['local' => $local, 'provedor' => $id, 'ambiente' => $emUso ? $ativa->ambiente : 'sandbox']) }}">Configurar</a>
                    </div>
                @endisset
            </div>
        @empty
            <p class="pg-vazio">Nenhum provedor cadastrado em {{ $nomeLocal }}.</p>
        @endforelse
        @if($disponiveis = array_diff_key($catalogo, $provedores->all()))
            <form method="post" action="{{ route('configuracao-pagamentos.adicionar', ['local' => $local]) }}" class="pg-adicionar">
                @csrf
                <input type="hidden" name="estabelecimento" value="{{ $site->empresa }}-{{ $local }}">
                <div><label for="pag-provedor">Novo provedor</label>
                    <select class="form-control" id="pag-provedor" name="provedor" required>
                        @foreach($disponiveis as $id => $item)<option value="{{ $id }}">{{ $item['nome'] }}</option>@endforeach
                    </select>
                </div>
                <button class="btn btn-primary">Adicionar provedor</button>
            </form>
        @endif
    @else
        <div class="pg-barra">
            <h2>Configuração da conta</h2>
            <nav class="pg-ambientes" aria-label="Ambiente do provedor">
                @foreach($ambientes as $id => $nome)
                    <a @if($id === $ambiente) aria-current="page" @endif href="{{ route('configuracao-pagamentos.index', ['local' => $local, 'provedor' => $codigo, 'ambiente' => $id]) }}">{{ $nome }}</a>
                @endforeach
            </nav>
        </div>
        @include($catalogo[$codigo]['formulario'], ['conexao' => $conexoes->get($codigo, collect())->firstWhere('ambiente', $ambiente)])
    @endif
    </section>
</div>
@endsection

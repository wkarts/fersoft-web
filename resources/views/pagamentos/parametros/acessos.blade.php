@extends('default.layout')
@section('content')
<link rel="stylesheet" href="{{ asset('css/configuracao-pagamentos.css') }}">
<div class="pg-config">
    <header class="pg-cabecalho">
        <div>
            <a class="pg-voltar" href="{{ route('configuracao-pagamentos.index') }}">&larr; Pagamentos eletrônicos</a>
            <h1>Acessos por usuário</h1>
        </div>
        <span class="pg-local"><i class="la la-map-marker" aria-hidden="true"></i>{{ $site->local ? 'Filial #'.$site->local : 'Matriz' }}</span>
    </header>
    <section class="card card-custom pg-painel">
        <h2 class="pg-titulo">Permissões Asaas</h2>
        <div class="pg-secao">
            <form method="get" action="{{ route('configuracao-pagamentos.acessos', ['local' => $site->local]) }}" class="row align-items-end">
                <div class="col-12 col-md-7"><label for="pag-usuario">Usuário</label>
                    <select name="usuario" id="pag-usuario" required class="form-control">
                        <option value="">Selecione</option>
                        @foreach($usuarios as $id => $nome)<option value="{{ $id }}" @selected($id == $usuario)>{{ $nome }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto mt-3 mt-md-0"><button class="btn btn-light-primary">Consultar acessos</button></div>
            </form>
            @if($usuario)
                <form method="post" action="{{ route('configuracao-pagamentos.acessos.salvar', ['local' => $site->local, 'usuario' => $usuario]) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="estabelecimento" value="{{ $site->empresa }}-{{ $site->local }}">
                    <fieldset class="border-top mt-4 pt-4"><legend class="h6 mb-3">Permissões de {{ $usuarios[$usuario] }}</legend>
                        <div class="row">
                            @foreach($opcoes as $chave => $nome)
                                <div class="col-12 col-md-6 mb-3"><div class="custom-control custom-checkbox pg-opcao">
                                    <input id="pag-acesso-{{ $chave }}" class="custom-control-input" type="checkbox" name="recursos[]" value="{{ $chave }}" @checked(in_array($chave, $ativos, true))>
                                    <label class="custom-control-label" for="pag-acesso-{{ $chave }}">{{ $nome }}</label>
                                </div></div>
                            @endforeach
                        </div>
                    </fieldset>
                    <button class="btn btn-primary mt-3">Salvar acessos</button>
                </form>
            @endif
        </div>
    </section>
</div>
@endsection

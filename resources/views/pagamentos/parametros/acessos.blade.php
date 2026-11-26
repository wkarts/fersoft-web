@extends('default.layout')
@section('content')
<div class="container-fluid px-3 px-lg-5 py-4" style="max-width: 1140px">
    <a href="{{ route('configuracao-pagamentos.index', ['local' => $site->local]) }}">&larr; Voltar aos provedores</a>
    <div class="card card-custom mt-3">
        <div class="card-header"><div class="card-title">
            <h1 class="h5 mb-0">Acessos por usuário · Asaas · {{ $site->local ? 'Filial #'.$site->local : 'Matriz' }}</h1>
        </div></div>
        <div class="card-body">
            <form method="get" action="{{ route('configuracao-pagamentos.acessos', ['local' => $site->local]) }}" class="row align-items-end mb-4">
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
                    <fieldset class="border-top pt-4"><legend class="h6 mb-3">Permissões de {{ $usuarios[$usuario] }}</legend>
                        <div class="row">
                            @foreach($opcoes as $chave => $nome)
                                <div class="col-12 col-md-6 mb-3"><div class="custom-control custom-checkbox">
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
    </div>
</div>
@endsection

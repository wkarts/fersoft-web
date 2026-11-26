@extends('default.layout')
@section('content')
@php
    $rota = 'parametros-pagamentos.'.($site->local ? 'filial' : 'emitente').'.';
    $destino = $site->local ? ['filial' => $site->local] : [];
@endphp
<div class="card card-body">
    <a href="{{ ($site->local ? route('filial.edit', $site->local) : url('/configNF')).'#pagamentos-eletronicos' }}">Voltar ao cadastro</a>
    <h3 class="mt-3">Acessos Asaas · {{ $site->local ? 'Filial #'.$site->local : 'Matriz' }}</h3>
    <form method="get" action="{{ route($rota.'acessos', $destino) }}">
        <label>Usuário <select name="usuario" required class="form-control">
            <option value="">Selecione</option>
            @foreach($usuarios as $id => $nome)<option value="{{ $id }}" @selected($id == $usuario)>{{ $nome }}</option>@endforeach
        </select></label>
        <button class="btn btn-light-primary">Consultar acessos</button>
    </form>
    @if($usuario)
        <form method="post" action="{{ route($rota.'acessos.salvar', $destino + ['usuario' => $usuario]) }}">
            @csrf @method('PUT')
            <input type="hidden" name="estabelecimento" value="{{ $site->empresa }}-{{ $site->local }}">
            <fieldset><legend>Permissões de {{ $usuarios[$usuario] }}</legend>
                @foreach($opcoes as $chave => $nome)
                    <label class="d-block"><input type="checkbox" name="recursos[]" value="{{ $chave }}" @checked(in_array($chave, $ativos, true))> {{ $nome }}</label>
                @endforeach
            </fieldset>
            <button class="btn btn-primary">Salvar acessos</button>
        </form>
    @endif
</div>
@endsection

@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-people.css') }}?v={{ filemtime(public_path('css/ui-people.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-people.css') }}?v={{ filemtime(public_path('css/ui-people.css')) }}">
@endpush
@section('content')
<div class="ui-people ui-people-contacts">
    <header class="ui-people-heading">
        <div><h1>Contatos de {{ $funcionario->nome }}</h1><p>Lista de Contatos · Total de registros: {{ count($funcionario->contatos) }}</p></div>
    </header>

    <section class="ui-people-panel" aria-labelledby="ui-people-contact-form-title">
        <div class="ui-people-panel-head"><h2 id="ui-people-contact-form-title">Lista de contatos de {{ $funcionario->nome }}</h2></div>
        <form method="post" action="/funcionarios/saveContato" class="ui-people-contact-form">
            <input type="hidden" value="{{ $funcionario->id }}" name="funcionario_id">
            <input type="hidden" name="id" value="{{{ isset($contato->id) ? $contato->id : 0 }}}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="ui-people-contact-fields">
                <div class="form-group validated">
                    <label for="nome">Nome</label>
                    <input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" value="{{{ isset($contato->id) ? $contato->nome : old('nome') }}}" name="nome">
                    @if($errors->has('nome'))<div class="invalid-feedback">{{ $errors->first('nome') }}</div>@endif
                </div>
                <div class="form-group validated">
                    <label for="telefone">Telefone</label>
                    <input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif" value="{{{ isset($contato->id) ? $contato->telefone : old('telefone') }}}" name="telefone">
                    @if($errors->has('telefone'))<div class="invalid-feedback">{{ $errors->first('telefone') }}</div>@endif
                </div>
            </div>
            <div class="ui-people-form-actions">
                <button type="reset" class="btn ui-people-secondary"><i class="la la-close" aria-hidden="true"></i> Limpar</button>
                <button type="submit" class="btn ui-people-primary"><i class="la la-check" aria-hidden="true"></i> Salvar</button>
            </div>
        </form>
    </section>

    <section class="ui-people-panel" aria-labelledby="ui-people-contacts-title">
        <div class="ui-people-panel-head"><h2 id="ui-people-contacts-title">Lista de Contatos</h2><span class="ui-people-count">{{ count($funcionario->contatos) }}</span></div>
        <div class="ui-people-grid">
            @foreach($funcionario->contatos as $c)
                <article class="ui-people-person ui-people-contact-card">
                    <div class="ui-people-person-head"><h3>{{ $c->id }} - {{ substr($c->nome, 0, 30) }}</h3></div>
                    <dl class="ui-people-details"><div><dt>Telefone</dt><dd>{{ $c->telefone }}</dd></div></dl>
                    <div class="ui-people-person-actions">
                        <a href="/funcionarios/editContato/{{ $c->id }}" class="btn ui-people-secondary">Editar</a>
                        <a onclick="if(!confirm('Deseja excluir este registro?')) { return false; }" href="/funcionarios/deleteContato/{{$c->id}}" class="btn ui-people-danger">Excluir</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection

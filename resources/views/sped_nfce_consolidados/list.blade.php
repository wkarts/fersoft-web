@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-fiscal.css') }}?v={{ filemtime(public_path('css/ui-fiscal.css')) }}">
@endpush
@section('content')
    <div class="fiscal-page fiscal-page--sped_nfce_consolidados-list">
        <div class="fiscal-page__content card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0 fiscal-page__title">{{ $title ?? 'SPED NFC-e Consolidado' }}</h3>
                <a href="{{ $newItemUrl }}" class="btn btn-success">Novo Registro</a>
            </div>

            @if(session('mensagem_sucesso'))
                <div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>
            @endif
            @if(session('mensagem_erro'))
                <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-light">
                    <tr>
                        @foreach($headers as $h)
                            <th>{!! $h !!}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $r)
                        <tr>
                            @foreach($fields as $f)
                                <td>
                                    @if(is_string($f))
                                        {{ data_get($r, $f) }}
                                    @elseif(is_callable($f))
                                        {!! $f($r) !!}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($headers) }}" class="text-center">Nenhum registro</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection

@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush


@section('content')
    <div class="fer-extra fer-extra--people fer-extra--traccar container">
        <h3>{{ $title }}</h3>
        @if(isset($error))
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($devices as $device)
                <tr>
                    <td>{{ $device['id'] }}</td>
                    <td>{{ $device['name'] }}</td>
                    <td>
                        <form method="POST" action="{{ route('traccar.devices.delete', $device['id']) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Deletar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

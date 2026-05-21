@extends('layouts.app')
@section('content')
<div class="container">
  <h4>Conferência de Balança (ADP)</h4>
  @include('components.balanca-leitor-widget', ['balancas' => $balancas, 'modo' => 'teste'])
</div>
<script src="{{ asset('js/adp-runtime-client.js') }}"></script>
<script src="{{ asset('js/balanca-leitor.js') }}"></script>
@endsection

@extends('layouts.app')
@section('content')
<div class="container">
  <h4>Conferência de Balança (ADP)</h4>
  @include('components.balanca-leitor-widget', ['balancas' => $balancas, 'modo' => 'teste'])
  <pre class="bg-light p-2" id="balanca-log" style="min-height:120px"></pre>
</div>
<script src="{{ asset('js/balanca-leitor.js') }}"></script>
@endsection

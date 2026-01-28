@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			
			<h3>{{$item->titulo}}</h3>
			<h5>Prioridade: {{ strtoupper($item->prioridade) }}</h5>

			<br>

			{!! $item->texto !!}
		</div>
	</div>
</div>

@endsection	

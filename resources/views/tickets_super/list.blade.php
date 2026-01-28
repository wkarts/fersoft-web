@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/ticketsSuper/filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-3 col-md-6 col-sm-6">
						<label class="col-form-label">Empresa</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="empresa" class="form-control" value="{{{isset($empresa) ? $empresa : ''}}}" />
								
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-3 col-sm-3">
						<label class="col-form-label">Estado</label>
						<div class="">
							<select name="estado" class="custom-select">
								<option @isset($estado) @if($estado == '') selected @endif @endisset value="">TODOS</option>
								<option @isset($estado) @if($estado == 'aberto') selected @endif @endisset value="aberto">ABERTO</option>
								<option @isset($estado) @if($estado == 'respondida') selected @endif @endisset value="respondida">RESPONDIDO</option>
								<option @isset($estado) @if($estado == 'finalizado') selected @endif @endisset value="finalizado">FINALIZADO</option>
							</select>
						</div>
					</div>

					<div class="form-group col-lg-3 col-md-3 col-sm-3">
						<label class="col-form-label">Departamento</label>
						<div class="">
							<select name="departamento" class="custom-select">
								<option @isset($departamento) @if($departamento == '') selected @endif @endisset value="">TODOS</option>
								@foreach(App\Models\Ticket::departamentos() as $d)
								<option @isset($departamento) @if($departamento == $d) selected @endif @endisset value="{{$d}}">{{$d}}</option>
								@endforeach
							</select>
						</div>
					</div>
					
					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>

			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				@foreach($tickets as $t)

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-body">
							<h3 class="card-">
								<strong>TCK-<span class="text-info">{{$t->id}}</span></strong>
							</h3>
							<h3 class="card-">
								{{$t->empresa->nome}}
							</h3>
							<h5>{{\Carbon\Carbon::parse($t->created_at)->format('d/m/Y H:i')}}</h5>

							<h4>Estado: 
								@if($t->estado == 'aberto')
								<strong class="text-warning">ABERTO</strong>
								@elseif($t->estado == 'respondida')
								<strong class="text-primary">RESPONDIDA</strong>
								@else
								<strong class="text-success">FINALIZADO</strong>
								@endif
							</h4>
							<p>Assunto: <strong>{{$t->assunto}}</strong></p>
							<div class="card-toolbar">

								<a href="/ticketsSuper/view/{{$t->id}}" class="btn btn-icon btn-circle btn-sm btn-light-primary mr-1"><i class="la la-folder"></i></a>

							</div>
						</div>
					</div>

				</div>

				@endforeach

			</div>
		</div>
	</div>
</div>

@endsection
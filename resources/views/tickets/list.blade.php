@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">

				<a href="/tickets/new" class="btn btn-lg btn-warning">
					<i class="fa fa-bell"></i>Novo Ticket
				</a>
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>

			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				@foreach($tickets as $t)

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-body">
							<h3 class="">
								<strong>TCK-<span class="text-info">{{$t->id}}</span></strong>
							</h3>

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

								<a href="/tickets/view/{{$t->id}}" class="btn btn-icon btn-circle btn-sm btn-light-primary mr-1"><i class="la la-folder"></i></a>

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
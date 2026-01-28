@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12"><br>
				<!--begin::Portlet-->
				<div class="card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Ticket <strong class="text-info ml-1">TCK-{{$ticket->id}}</strong> <a class="btn btn-danger ml-3" href="/ticketsSuper/finalizar/{{$ticket->id}}">
							<i class="la la-close"></i>
							<span class="">Fechar Ticket</span>
						</a></h3>

						<h4 class="mt-6">Estado:
							@if($ticket->estado == 'aberto')
							<strong class="text-warning">ABERTO</strong>
							@elseif($ticket->estado == 'respondida')
							<strong class="text-primary">RESPONDIDA</strong>
							@else
							<strong class="text-success">FINALIZADO</strong>
							@endif

						</h4>
					</div>
				</div>
				<div class="card card-custom gutter-b example example-compact">
					<div class="card card-body">
						<div class="row">
							<div class="col-lg-6">
								<h4>Assunto: {{$ticket->assunto}}</h4>
							</div>
							<div class="col-lg-6 text-right">
								<h4>Departamento: {{$ticket->departamento}}</h4>
							</div>
						</div>
					</div>
				</div>

				@if($ticket->estado == 'finalizado')
				<div class="row" style="background: #fff; height: 120px; margin-top: -25px">
					<div class="container">
						<div class="alert alert-custom alert-light-danger show" style="margin-top: 10px;">

								<div class="alert-icon"><i class="la la-exclamation-triangle"></i></div>

								<h4 class="alert-text">Não é possível efetuar novas interações! <br>{{$ticket->mensagem_finalizar}}</h4>

						</div>
					</div>
				</div>
				@endif

				@foreach($ticket->mensagens as $m)
				<div class="card card-custom gutter-b example example-compact">
					<div class="card card-body @if($m->mensagemSuper()) bg-light-success @endif">
						<div class="row">
							<div class="col-lg-6">
								<i class="la la-user"></i>
								{{$m->usuario->nome}}
								@if($m->mensagemSuper())
								- <strong class="text-primary">suporte</strong>
								@else
								- <strong class="text-primary">cliente</strong>
								@endif
							</div>
							<div class="col-lg-6 text-right">
								{{\Carbon\Carbon::parse($m->created_at)->format('d/m/Y (H:i)')}}
							</div>
						</div>
						<hr>

						{!! $m->mensagem !!}
						@if($m->imagem != "")
						<img style="width: 100%; height: auto;" src="/ticket_img/{{$m->imagem}}">
						@endif
					</div>
				</div>
				@endforeach

				@if($ticket->estado != 'finalizado')
				<form method="post" action="/ticketsSuper/novaMensagem" enctype="multipart/form-data">
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<input type="hidden" name="ticket_id" value="{{$ticket->id}}">
										<div class="form-group validated col-sm-12 col-lg-12">
											<label class="col-form-label">Nova Mensagem</label>
											<div class="">

												<div class="row">
													<div class="col-12">
														<textarea name="mensagem" id="mensagem" style="width: 100%;height:300px;">{{old('mensagem')}}</textarea>
													</div>
												</div>

												@if($errors->has('mensagem'))
												<div class="invalid-feedback">
													{{ $errors->first('mensagem') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<div class="form-group row">
										<div class="col-lg-6">
											<div class="uppy" id="kt_uppy_5">
												<div class="uppy-wrapper"><div class="uppy-Root uppy-FileInput-container"><input class="uppy-FileInput-input uppy-input-control" accept=".jpg,.png,.jpeg" style="" type="file" name="file" multiple="" id="kt_uppy_5_input_control"><label class="uppy-input-label btn btn-light-primary btn-sm btn-bold" for="kt_uppy_5_input_control">Upload de Imagem</label></div></div>
												<div class="uppy-list"></div>
												<div class="uppy-status"><div class="uppy-Root uppy-StatusBar is-waiting" aria-hidden="true" dir="ltr"><div class="uppy-StatusBar-progress
													" style="width: 0%;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div><div class="uppy-StatusBar-actions"></div>
												</div>
											</div>
											<div class="uppy-informer uppy-informer-min"><div class="uppy uppy-Informer" aria-hidden="true"><p role="alert"> </p></div></div>
										</div>

										<span class="form-text text-muted">Extensões permitidas .jpg, .png</span>
										<label class="text-success" id="filename"></label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xl-2">

					</div>
					<div class="col-lg-3 col-sm-6 col-md-4">
						<a style="width: 100%" class="btn btn-danger" href="/ticketsSuper/finalizar/{{$ticket->id}}">
							<i class="la la-close"></i>
							<span class="">Fechar Ticket</span>
						</a>
					</div>
					<div class="col-lg-3 col-sm-6 col-md-4">
						<button style="width: 100%" type="submit" class="btn btn-success">
							<i class="la la-check"></i>
							<span class="">Salvar Mensagem</span>
						</button>
					</div>
				</div>
				<br>

			</form>
			@else
			<h2 class="text-danger text-center">Não é possível efetuar novas interações!</h2>
			@endif
		</div>
	</div>
</div>
</div>

@endsection
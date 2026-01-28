@extends('default.layout')
@section('content')

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

	<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
		<div class="card card-custom gutter-b example example-compact">
			<div class="col-lg-12">
				<!--begin::Portlet-->
				<div class="card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Finalizar <strong class="text-info ml-1">TCK-{{$ticket->id}}</strong> </h3>
					</div>
				</div>
				<form method="post" action="/tickets/finalizar">
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<input type="hidden" name="ticket_id" value="{{$ticket->id}}">
										<div class="form-group validated col-sm-12 col-lg-12">
											<label class="col-form-label">Mensagem</label>
											<div class="">

												<input required type="" class="form-control" name="mensagem">

												@if($errors->has('mensagem'))
												<div class="invalid-feedback">
													{{ $errors->first('mensagem') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									
								</div>
							</div>
						</div>
					</div>

					<div class="row">

						<div class="col-lg-3 col-sm-6 col-md-4">
							<button style="width: 100%" type="submit" class="btn btn-success">
								<i class="la la-check"></i>
								<span class="">Finalizar</span>
							</button>
						</div>
					</div>
					<br>

				</form>
			</div>
		</div>
	</div>
</div>

@endsection
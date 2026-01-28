@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{ isset($item) ? route('motoristas.update', [$item->id]) : route('motoristas.store') }}" enctype="multipart/form-data">
					@csrf
					@isset($item)
					@method('put')
					@endif

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{ isset($item) ? 'Editar' : 'Novo' }} Motorista</h3>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">Nome</label>
											<input type="text" class="form-control" name="nome" value="{{ isset($item) ? $item->nome : old('nome') }}">
										</div>

										<div class="form-group validated col-12 col-lg-3">
											<label class="col-form-label">CPF</label>
											<input type="text" class="form-control cpf" name="cpf" value="{{ isset($item) ? $item->cpf : old('cpf') }}" id="cpf">
										</div>
									</div>


								</div>
							</div>
						</div>
					</div>
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="{{ route('motoristas.index') }}">
									<i class="la la-close"></i>
									<span class="">Cancelar</span>
								</a>
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<button style="width: 100%" type="submit" class="btn btn-success">
									<i class="la la-check"></i>
									<span class="">Salvar</span>
								</button>
							</div>

						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection
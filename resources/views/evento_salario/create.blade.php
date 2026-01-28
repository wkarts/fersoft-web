@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ isset($item) ? route('eventosFuncionario.update', [$item->id]) : route('eventosFuncionario.store') }}}">
					@isset($item)
					@method('put')
					@endif
					<input type="hidden" name="id" value="{{{ isset($categoria) ? $categoria->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{isset($item) ? 'Editar' : 'Novo'}} Evento</h3>
						</div>
					</div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($item) ? $item->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-12 col-lg-2">
											<label class="col-form-label">Tipo</label>
											<div class="">
												<select class="form-control @if($errors->has('tipo')) is-invalid @endif" name="tipo">
													<option value="semanal" @isset($item) @if($item->tipo == 'semanal') selected @endif	 @endif>Semanal</option>
													<option value="mensal" @isset($item) @if($item->tipo == 'mensal') selected @endif	 @endif>Mensal</option>
													<option value="anual" @isset($item) @if($item->tipo == 'anual') selected @endif	 @endif>Anual</option>
												</select>
												@if($errors->has('tipo'))
												<div class="invalid-feedback">
													{{ $errors->first('tipo') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-12 col-lg-2">
											<label class="col-form-label">Método</label>
											<div class="">
												<select class="form-control @if($errors->has('metodo')) is-invalid @endif" name="metodo">
													<option value="informado" @isset($item) @if($item->metodo == 'informado') selected @endif @endif>Informado</option>
													<option value="fixo" @isset($item) @if($item->metodo == 'fixo') selected @endif @endif>Fixo</option>
												</select>
												@if($errors->has('metodo'))
												<div class="invalid-feedback">
													{{ $errors->first('metodo') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-12 col-lg-2">
											<label class="col-form-label">Condição</label>
											<div class="">
												<select class="form-control @if($errors->has('condicao')) is-invalid @endif" name="condicao">
													<option value="soma" @isset($item) @if($item->condicao == 'soma') selected @endif @endif>Soma</option>
													<option value="diminui" @isset($item) @if($item->condicao == 'diminui') selected @endif @endif>Diminui</option>
												</select>
												@if($errors->has('condicao'))
												<div class="invalid-feedback">
													{{ $errors->first('condicao') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-12 col-lg-2">
											<label class="col-form-label">Tipo valor</label>
											<div class="">
												<select class="form-control @if($errors->has('tipo_valor')) is-invalid @endif" name="tipo_valor">
													<option value="valor_fixo" @isset($item) @if($item->tipo_valor == 'valor_fixo') selected @endif @endif>Valor fixo</option>
													<option value="percentual" @isset($item) @if($item->tipo_valor == 'percentual') selected @endif @endif>Percentual</option>
												</select>
												@if($errors->has('tipo_valor'))
												<div class="invalid-feedback">
													{{ $errors->first('tipo_valor') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-12 col-lg-2">
											<label class="col-form-label">Ativo</label>
											<div class="">
												<select class="form-control @if($errors->has('ativo')) is-invalid @endif" name="ativo">
													<option value="1" @isset($item) @if($item->ativo == 1) selected @endif @endif>Sim</option>
													<option value="0" @isset($item) @if($item->ativo == 0) selected @endif @endif>Não</option>
												</select>
												@if($errors->has('ativo'))
												<div class="invalid-feedback">
													{{ $errors->first('ativo') }}
												</div>
												@endif
											</div>
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
								<a style="width: 100%" class="btn btn-danger" href="/eventosFuncionario">
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
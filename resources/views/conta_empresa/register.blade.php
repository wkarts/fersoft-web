@extends('default.layout', ['title' => isset($item) ? 'Editar Conta' : 'Nova Conta'])
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{ isset($item) ? route('contas-empresa.update', [$item->id]) : route('contas-empresa.store') }}">
					@csrf
					@isset($item)
					@method('put')
					@endif

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{isset($item) ? 'Editar' : 'Nova'}} Conta</h3>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-md-3 col-12">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($item) ? $item->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-md-3 col-12">
											<label class="col-form-label">Banco</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('banco')) is-invalid @endif" name="banco" value="{{{ isset($item) ? $item->banco : old('banco') }}}">
												@if($errors->has('banco'))
												<div class="invalid-feedback">
													{{ $errors->first('banco') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-md-2 col-12">
											<label class="col-form-label">Agência</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('agencia')) is-invalid @endif" name="agencia" value="{{{ isset($item) ? $item->agencia : old('agencia') }}}">
												@if($errors->has('agencia'))
												<div class="invalid-feedback">
													{{ $errors->first('agencia') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-md-2 col-12">
											<label class="col-form-label">Conta</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('conta')) is-invalid @endif" name="conta" value="{{{ isset($item) ? $item->conta : old('conta') }}}">
												@if($errors->has('conta'))
												<div class="invalid-feedback">
													{{ $errors->first('conta') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-md-4 col-12">
											<label class="col-form-label">Plano de conta</label>
											<div class="">
												<select required name="plano_conta_id" class="custom-select select2-custom">
													<option value="">selecione</option>
													@foreach($planos as $p)
													<option @isset($item) @if($item->plano_conta_id == $p->id) selected @endif @endif value="{{ $p->id }}">{{ $p->descricao }}</option>
													@endforeach
												</select>
												@if($errors->has('plano_conta_id'))
												<div class="invalid-feedback">
													{{ $errors->first('plano_conta_id') }}
												</div>
												@endif
											</div>
										</div>

										@if(!isset($item))
										<div class="form-group validated col-md-2 col-12">
											<label class="col-form-label">Saldo inicial</label>
											<div class="">
												<input required type="tel" class="form-control @if($errors->has('saldo_inicial')) is-invalid @endif money" name="saldo_inicial" value="{{{ isset($item) ? moeda($item->saldo_inicial) : old('saldo_inicial') }}}">
												@if($errors->has('saldo_inicial'))
												<div class="invalid-feedback">
													{{ $errors->first('saldo_inicial') }}
												</div>
												@endif
											</div>
										</div>
										@endif

										<div class="form-group validated col-md-2 col-12">
											<label class="col-form-label">Status da conta</label>
											<div class="">
												<select name="status" class="custom-select">
													<option value="1">Ativa</option>
													<option value="0">Desativada</option>
													
												</select>
											</div>
										</div>

										@if(is_adm() && isset($item))
										<div class="form-group validated col-md-2 col-12">
											<label class="col-form-label text-danger">Saldo atual</label>
											<div class="">
												<input required type="tel" class="form-control @if($errors->has('saldo')) is-invalid @endif money" name="saldo" value="{{{ isset($item) ? moeda($item->saldo) : old('saldo') }}}">
												@if($errors->has('saldo'))
												<div class="invalid-feedback">
													{{ $errors->first('saldo') }}
												</div>
												@endif
											</div>
										</div>
										@endif

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
								<a style="width: 100%" class="btn btn-danger" href="{{ route('contas-empresa.index') }}">
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
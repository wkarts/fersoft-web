@extends('default.layout')
@section('content')

<style type="text/css">
	.img-template img{
		width: 300px;
		border: 1px solid #999;
		border-radius: 10px;
	}

	.img-template-active img{
		width: 300px;
		border: 3px solid green;
		border-radius: 10px;
	}

	.template:hover{
		cursor: pointer;
	}

	#btn_token:hover{
		cursor: pointer;
	}
</style>
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/nuvemshop/saveCategoria" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{{ isset($categoria) ? $categoria->id : 0 }}}">
					<input type="hidden" name="categoria_id" value="{{{ isset($categoria) ? $categoria->parent : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($categoria) ? "Editar": "Cadastrar" }}} Categoria</h3>
						</div>
					</div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<div class="row">
										<div class="form-group validated col-sm-4 col-lg-4 col-12">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{isset($categoria) ? $categoria->name->pt : '' }}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-4 col-12">
											<label class="col-form-label">Atribuir a categoria (opcional)</label>
											<div class="">
												<select name="categoria_id" class="form-control">
													<option value="">--</option>
													@foreach($categorias as $c)
													<option @isset($categoria) @if($c->id == $categoria->parent) selected @endif @endif value="{{$c->id}}">{{$c->name->pt}}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-12">
											<label class="col-form-label">Descrição</label>
											<div class="">

												<textarea class="form-control" name="descricao" id="descricao" style="width:100%;height:200px;">{{isset($categoria) ? $categoria->description->pt : old('descricao')}}</textarea>

												@if($errors->has('descricao'))
												<div class="invalid-feedback">
													{{ $errors->first('descricao') }}
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
								<a style="width: 100%" class="btn btn-danger" href="/nuvemshop/categorias">
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

@section('javascript')
<script type="text/javascript">

</script>
@endsection
@endsection
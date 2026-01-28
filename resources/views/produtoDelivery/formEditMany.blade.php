@extends('default.layout')
@section('css')
<style type="text/css">
	.img-thumb{
		height: 70px;
		width: 100px;
		border-radius: 10px;
	}

</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="">

			<div class="col-12">

			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>
			<h4>Editar produtos</h4>

			<p class="text-danger">*filtro todos os produtos do sistema</p>

			<form class="row" method="post" action="/deliveryProduto/confirmManyPost" enctype="multipart/form-data">
				@csrf
				@isset($produtos)
				<table class="table">
					<thead>
						<tr>
							<th>Produto</th>
							<th>Delivery</th>
							<th>Categoria de delivery</th>
							<th>Imagem de delivery</th>
							<th>Valor de delivery</th>
						</tr>
					</thead>
					<tbody>
						@foreach($produtos as $key => $item)
						<tr>
							<input type="hidden" value="{{ $item->id }}" name="produto_id[]">
							<td>
								<input type="" disabled class="form-control" value="{{ $item->nome }}">
							</td>
							<td>
								@if($item->delivery)
								<i class="fa fa-check text-success"></i>
								@else
								<i class="fa fa-close text-danger"></i>
								@endif
							</td>
							<td>
								<select class="form-control" name="categoria_id[]" required>
									@foreach($categorias as $c)
									<option @if($item->delivery) @if($item->delivery->categoria_id == $c->id) selected @endif @endif value="{{$c->id}}">
										{{$c->nome}}
									</option>
									@endforeach
									
								</select>
							</td>

							<td>
								<div class="image-input image-input-outline" id="kt_image_{{$key+1}}">
									<div class="image-input-wrapper" @if($item->delivery) style="background-image: url({{$item->delivery->img}})" @else style="background-image: url(/imgs/no_image.png)" @endif></div>
									<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
										<i class="fa fa-pencil icon-sm text-muted"></i>
										<input type="file" name="file[]" accept=".png, .jpg, .jpeg">
										<input type="hidden" name="profile_avatar_remove">
									</label>
									<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
										<i class="fa fa-close icon-xs text-muted"></i>
									</span>
								</div>
							</td>

							<td>
								@if($item->delivery && $item->delivery->categoria->tipo_pizza)

								@foreach($item->delivery->pizza as $t)
								<label>{{$t->tamanho->nome}}</label>
								<input required value="{{ moeda($t->valor) }}" type="tel" class="form-control money" name="valor_pizza[]">
								@endforeach
								@else
								<input required @if($item->delivery) value="{{ moeda($item->delivery->valor) }}" @endif type="tel" class="form-control money" name="valor[]">
								@endif
							</td>
							
						</tr>
						@endforeach
					</tbody>
				</table>
				@endisset

				<button type="submit" class="btn btn-success spinner-white spinner-right">
					<i class="la la-check"></i> Salvar
				</button>

			</form>
			
		</div>
	</div>
</div>
@endsection
@section('javascript')
<script type="text/javascript">
	$('.btn-success').click(() => {
		$('.btn-success').addClass('spinner')
	})
</script>
@endsection
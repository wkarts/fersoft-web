@extends('default.layout')
@section('css')
<style type="text/css">
	.img-carousel{
		height: 140px;
		border-radius: 10px;
	}
</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<form method="post" action="/carrosselDelivery/save" enctype="multipart/form-data">
				@csrf
				<div class="row align-items-center">
					
					<div class="col-lg-2">

						<div class="image-input image-input-outline" id="kt_image_1">
							<div class="image-input-wrapper" style="background-image: url(/imgs/default.png)"></div>
							<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
								<i class="fa fa-pencil icon-sm text-muted"></i>
								<input type="file" name="file" accept=".png, .jpg, .jpeg">
								<input type="hidden" name="profile_avatar_remove">
							</label>
							<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
								<i class="fa fa-close icon-xs text-muted"></i>
							</span>
						</div>

						<span class="form-text text-muted">.png, .jpg, .jpeg</span>
						@if($errors->has('file'))
						<div class="invalid-feedback">
							{{ $errors->first('file') }}
						</div>
						@endif
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button class="btn btn-light-success px-6 font-weight-bold">Salvar</button>
					</div>
				</div>
			</form>
		</div>
		<hr>
		<h4>Imagens cadastradas</h4>
		<h5>As imagens serão exibidas respeitando a ordem abaixo</h5>
		<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

			<table class="datatable-table" style="max-width: 100%; overflow: scroll">
				<thead class="datatable-head">
					<tr class="datatable-row" style="left: 0px;">
						<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Imagem</span></th>
						<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status</span></th>
						<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Ações</span></th>
					</tr>
				</thead>
				<tbody id="body" class="datatable-body">
					@foreach($data as $item)
					<tr class="datatable-row">
						<td class="datatable-cell">
							<span class="" style="width: 250px; height: 150px;">
								<img class="img-carousel" src="{{ $item->img }}">
							</span>
						</td>
						<td class="datatable-cell">
							<span class="" style="width: 100px;">

								<div class="switch switch-outline switch-success">
									<label class="">
										<input onclick="alterarStatus({{$item->id}})" @if($item->status) checked @endif value="true" name="status" class="red-text" type="checkbox">
										<span class="lever"></span>
									</label>
								</div>
							</span>
						</td>

						<td class="datatable-cell">
							<span class="" style="width: 150px;">
								<a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover esta imagem?", "warning").then((sim) => {if(sim){ location.href="/carrosselDelivery/delete/{{ $item->id }}" }else{return false} })' href="#!">
									<i class="la la-trash"></i>	
								</a>
								@if($item->status)
								@if(!$loop->first)
								<a class="btn btn-success btn-sm" href="/carrosselDelivery/up/{{$item->id}}">
									<i class="la la-arrow-up"></i>	
								</a>
								@endif

								@if(!$loop->last)
								<a class="btn btn-dark btn-sm" href="/carrosselDelivery/down/{{$item->id}}">
									<i class="la la-arrow-down"></i>	
								</a>
								@endif
								@endif

							</span>
						</td>
						
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	function alterarStatus(id){
		$.get(path+'carrosselDelivery/alteraStatus/'+id)
		.done((success) => {
			console.log(success)
		})
		.fail((err) => {
			console.log(err)
		})
	}
</script>
@endsection
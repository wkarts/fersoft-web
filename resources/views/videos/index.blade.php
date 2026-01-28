@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">

				<button onclick="novoVideo()" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo Vídeo
				</button>
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>

			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<table class="table">
					<thead>
						<tr>
							<th>URL do vídeo</th>
							<th>URL do sistema</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						@foreach($data as $item)
						<tr>
							<td>{{ $item->url_video }}</td>
							<td>{{ $item->url_sistema }}</td>
							<td>
								<button onclick="editVideo('{{ $item }}')" title="Editar" class="btn btn-sm btn-warning"><i class="la la-pencil"></i>
								</button>
								<a title="Remover" class="btn btn-sm btn-danger" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/videos/delete/{{ $item->id }}" }else{return false} })' href="#!">
									<i class="la la-trash"></i>	
								</a>
							</td>
						</tr>
						@endforeach
					</tbody>
				</table>

			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-video" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form class="modal-content" action="/videos/store" method="post">
			<div class="modal-header">
				<h5 class="modal-title">Novo Vídeo</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>

			@csrf
			<input type="hidden" id="id" value="" name="id">

			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-12">
						<label class="col-form-label" id="">URL Vídeo</label><br>
						<input required id="url_video" class="form-control" type="text" name="url_video">
					</div>

					<div class="form-group validated col-12">
						<label class="col-form-label" id="">URL Sistema</label><br>
						<input required id="url_sistema" class="form-control" type="text" name="url_sistema">
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="submit" id="btn-send-cliente" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>

		</form>
	</div>
</div>

@endsection
@section('javascript')
<script type="text/javascript">
	function novoVideo(){
		$('#id').val('')
		$('#modal-video').modal('show')
		$('.modal-title').text('Novo Vídeo')
	}

	function editVideo(item){
		item = JSON.parse(item)
		$('#id').val(item.id)
		$('#url_video').val(item.url_video)
		$('#url_sistema').val(item.url_sistema)
		$('.modal-title').text('Editar Vídeo')
		$('#modal-video').modal('show')
	}
</script>
@endsection
@extends('default.layout')
@section('css')
<style type="text/css">
	.btn-sm{
		padding: 3px;
	}
	.btn-sm i{
		margin-left: 4px;
	}
</style>
@endsection
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">Plano de Contas</h4>

			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Registros: <strong class="text-success">{{ sizeof($data) }}</strong></label>
			

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					@if(sizeof($data) > 0)
					@foreach($data as $item)
					<form action="{{ route('plano-contas.destroy', $item->id) }}" method="post" id="form-delete-{{$item->id}}">
						@method('delete')
						@csrf
						@if($item->grauItem() == 1)

						<h2>{{ $item->descricao }} <button type="button" class="btn btn-sm btn-success" onclick="modalForm('{{$item->id}}')"><i class="la la-plus"></i></button></h2>
						@elseif($item->grauItem() == 3)
						<h4 style="margin-left: 20px">
							{{ $item->descricao }}
							<button type="button" class="btn btn-sm btn-warning" onclick="modalEdit('{{$item->id}}', '{{$item->descricao}}')"><i class="la la-edit"></i></button>
							<button type="button" class="btn btn-sm btn-success" onclick="modalForm('{{$item->id}}')"><i class="la la-plus"></i></button>
							
						</h4>
						@elseif($item->grauItem() == 5)
						<h6 style="margin-left: 50px">{{ $item->descricao }}
							<button type="button" class="btn btn-sm btn-warning" onclick="modalEdit('{{$item->id}}', '{{$item->descricao}}')"><i class="la la-edit"></i></button>
							<button type="button" class="btn btn-sm btn-success" onclick="modalForm('{{$item->id}}')"><i class="la la-plus"></i></button>
							<button class="btn btn-sm btn-danger btn-delete"><i class="la la-trash"></i></button>

						</h6>
						@elseif($item->grauItem() == 8)
						<p style="margin-left: 70px">{{ $item->descricao }}
							<button type="button" class="btn btn-sm btn-warning" onclick="modalEdit('{{$item->id}}', '{{$item->descricao}}')"><i class="la la-edit"></i></button>
							<button class="btn btn-sm btn-danger btn-delete"><i class="la la-trash"></i></button>
						</p>
						@endif

					</form>

					@endforeach
					@else
					<a class="btn btn-success" href="{{ route('plano-contas.issue') }}">
						<i class="la la-file"></i>
						Iniciar plano de contas
					</a>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-form" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form class="modal-content" method="post" action="{{ route('plano-contas.store') }}">
			@csrf
			<div class="modal-header">
				<h5 class="modal-title"></h5>
			</div>
			<div class="modal-body">
				<div class="row">
					<input type="hidden" id="plano_conta_id" name="plano_conta_id">
					<input type="hidden" id="edit_id" name="edit_id">
					<div class="form-group validated col-12">
						<label class="col-form-label" id="">Descrição</label>
						<input required type="text" id="descricao" name="descricao" class="form-control">
					</div>

				</div>

			</div>
			<div class="modal-footer">
				<button type="submit" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="submit" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</form>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	function modalForm(id){
		$('#modal-form').modal('show')
		$('#plano_conta_id').val(id)
		$('#edit_id').val(null)
		$('#descricao').val('')
	}

	function modalEdit(id, descricao){
		$('#modal-form').modal('show')
		$('#plano_conta_id').val(null)
		$('#edit_id').val(id)
		$('#descricao').val(descricao)
	}

</script>
@endsection

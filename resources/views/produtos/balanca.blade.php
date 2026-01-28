@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<form class="card-body" method="post" action="/produtos/exportacaoBalanca">
		@csrf
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-sm-12 col-md-6 col-xl-3">
				<label>Modelo balança</label>
				<select class="form-control custom-select" name="modelo">
					@foreach(\App\Models\Produto::modelosBalanca() as $b)
					<option value="{{$b}}">{{$b}}</option>
					@endforeach
				</select>
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>

			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<table class="table">
					<thead>
						<tr>
							<th>
								<input type="checkbox" id="todos">
							</th>
							<th>Produto</th>
							<th>Referência</th>
							<th>Valor de venda</th>
						</tr>
					</thead>
					<tbody>
						@foreach($data as $item)
						<tr>
							<td>
								<input type="checkbox" class="check_prod" name="produto_id[]" value="{{$item->id}}">
							</td>
							<td>
								{{$item->nome}}
							</td>
							<td>
								{{$item->referencia_balanca}}
							</td>
							<td>
								{{ moeda($item->valor_venda) }}
							</td>
						</tr>
						@endforeach
					</tbody>
				</table>

			</div>
			<button class="btn btn-success float-right">Gerar arquivo</button>
		</div>
	</form>
</div>

@endsection
@section('javascript')
<script type="text/javascript">
	$('#todos').click(() => {
		if($('#todos').is(":checked")){
			$('.check_prod').prop('checked',true);
		}else{
			$('.check_prod').prop('checked',false);
		}
	})
</script>
@endsection
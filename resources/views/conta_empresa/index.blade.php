@extends('default.layout', ['title' => 'Contas da Empresa'])
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-12">

				<a href="{{ route('contas-empresa.create') }}" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Nova Conta
				</a>
				
			</div>
		</div>
		<br>

		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<br>
			<h4>Lista de Contas</h4>

			<div class="row">
				<div class="table-responsive">
					<table class="table">
						<thead>
							<tr class="bg-light-dark">
								<th>Nome</th>
								<th>Plano de conta</th>
								<th>Banco</th>
								<th>Agência</th>
								<th>Conta</th>
								<th>Status</th>
								<th>Saldo</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody class="striped">
							@foreach($data as $item)
							<tr>
								<td>{{ $item->nome }}</td>
								<td>{{ $item->plano->descricao }}</td>
								<td>{{ $item->banco }}</td>
								<td>{{ $item->agencia }}</td>
								<td>{{ $item->conta }}</td>
								<td>
									@if($item->status)
									<i class="la la-check text-success"></i>
									@else
									<i class="la la-close text-danger"></i>
									@endif
								</td>
								<td>{{ moeda($item->saldo) }}</td>
								<td>
									<form action="{{ route('contas-empresa.destroy', $item->id) }}" method="post" id="form-{{$item->id}}" style="width: 150px">
										@method('delete')
										@csrf
										<a href="{{ route('contas-empresa.edit', $item->id) }}" class="btn btn-sm btn-warning">
											<i class="la la-edit"></i>
										</a>

										<button class="btn btn-sm btn-danger btn-delete">
											<i class="la la-trash"></i>
										</button>

										<a title="Movimentações" href="{{ route('contas-empresa.show', $item->id) }}" class="btn btn-sm btn-dark">
											<i class="la la-list"></i>
										</a>

									</form>
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>

		</div>
	</div>
</div>

@endsection
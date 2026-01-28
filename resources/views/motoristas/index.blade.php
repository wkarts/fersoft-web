@extends('default.layout', ['title' => 'Motoristas'])
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">

				<a href="{{ route('motoristas.create') }}" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo motorista
				</a>
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>

			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
				<div class="table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th>Nome</th>
								<th>CPF</th>
							</tr>
						</thead>
						<tbody>
							@foreach($data as $item)
							<tr>
								<td>{{ $item->nome }}</td>
								<td>{{ $item->cpf }}</td>
								<td>
									
									<form action="{{ route('motoristas.destroy', $item->id) }}" method="post" id="form-{{$item->id}}">
											@method('delete')
											@csrf
											<a href="{{ route('motoristas.edit', [$item->id]) }}" class="btn btn-sm btn-warning">
												<i class="la la-edit"></i>
											</a>


											<button class="btn btn-sm btn-danger btn-delete">
												<i class="la la-trash"></i>
											</button>

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
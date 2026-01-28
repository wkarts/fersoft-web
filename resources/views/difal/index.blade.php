@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">


			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
				<div class="row">

					<a href="{{ route('difal.create') }}" class="btn btn-success">
						<i class="la la-plus"></i>
						Adicionar
					</a>

				</div>
			</div>

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="table mt-3">
						<thead>
							<tr>
								<th>UF</th>
								<th>CFOP</th>
								<th>% ICMS UF Destino</th>
								<th>% ICMS Interno</th>
								<th>% ICMS Interestadual UF</th>
								<th>% Fundo Combate a Pobreza</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody>
							@foreach($data as $i)
							<tr>
								<td>{{ $i->uf }}</td>
								<td>{{ $i->cfop }}</td>
								<td>{{ $i->pICMSUFDest }}</td>
								<td>{{ $i->pICMSInter }}</td>
								<td>{{ $i->pICMSInterPart }}</td>
								<td>{{ $i->pFCPUFDest }}</td>
								<td>
									<form action="{{ route('difal.destroy', $i->id) }}" method="post" id="form-{{$i->id}}">
										@method('delete')
										@csrf
										<a title="Editar" class="btn btn-sm btn-warning" href="{{ route('difal.edit', [$i->id]) }}">
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

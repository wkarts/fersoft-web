@extends('default.layout')
@section('css')
<style type="text/css">
	.title{
		color: #fff;
	}
</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="">
			<div class="col-12">
				
				<div class="col-12 mt-4">
					<div class="card">
						<div class="card-header">
							<h5 class="title">Logs de consulta</h5>
						</div>
						<div class="card-body">

							<div class="table-responsive">
								<table class="table">
									<thead>
										<tr>
											<th>Data</th>
											<th>Status</th>
											<th>Descrição</th>
										</tr>
									</thead>
									<tbody>
										@foreach($data as $item)
										<tr>
											<td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/y H:i') }}</td>
											<td>
												@if($item->sucesso)
												<i class="la la-check text-success"></i>
												@else
												<i class="la la-close text-danger"></i>
												@endif
											</td>
											<td>{{ $item->resultado }}</td>
											
										</tr>
										@endforeach
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="d-flex justify-content-between align-items-center flex-wrap">
						<div class="d-flex flex-wrap py-2 mr-3">
							{{$data->links()}}
						</div>
					</div>
				</div>
				
			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">

</script>
@endsection



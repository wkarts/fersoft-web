@extends('default.layout')
@section('content')
<style type="text/css">
	.btn-file {
		position: relative;
		overflow: hidden;
	}

	.btn-file input[type=file] {
		position: absolute;
		top: 0;
		right: 0;
		min-width: 100%;
		min-height: 100%;
		font-size: 100px;
		text-align: right;
		filter: alpha(opacity=0);
		opacity: 0;
		outline: none;
		background: white;
		cursor: inherit;
		display: block;
	}
</style>

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<!--begin::Portlet-->

				<div class="card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Upload de documentos</h3>
					</div>
				</div>

				<form method="post" enctype="multipart/form-data" action="/clientes/upload-store/{{ $item->id }}">
					@csrf
					<div class="row">
						<div class="col-xl-12">

							<input type="hidden" name="_token" value="{{ csrf_token() }}">
							<div class="row">
								<div class="form-group validated col-lg-2 col-12">
									<label class="col-form-label">Arquivo</label>
									<div class="">
										<span class="btn btn-primary btn-file">
											Procurar arquivo<input required accept=".xls, .xlsx, .docx, .pdf, .png, .jpg, .jpeg" name="file" type="file">
										</span>
										<label class="text-info" id="filename"></label>
									</div>

								</div>

								<div class="form-group validated col-lg-7 col-12">
									<label class="col-form-label">Descrição</label>
									<div class="">
										<input type="text" name="descricao" class="form-control">
									</div>

								</div>

								<div class="form-group validated col-lg-3 col-12">
									<br>
									<button class="btn btn-success w-100 mt-5">Salvar arquivo</button>
								</div>
							</div>

						</div>
					</div>
				</form>

			</div>

			<div class="row">
				<div class="table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th>Documento</th>
								<th>Descrição</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody>
							@foreach($item->uploads as $doc)
							<tr>
								<td>{{ $doc->file_name }}</td>
								<td>{{ $doc->descricao }}</td>
								<td>

									<form action="{{ route('clientes.destroy-upload', $doc->id) }}" method="post" id="form-{{$doc->id}}">
										@method('delete')
										@csrf
										<a href="/clientes/download-documento/{{$doc->id}}" class="btn btn-sm btn-dark">
											<i class="la la-download"></i>
										</a>

										<button class="btn btn-sm btn-danger btn-delete">
											<i class="la la-trash"></i>
										</button>

									</form>
								</td>
							</tr>
							@endforeach
						</tbody>
						<caption>Lista de documentos carregados</caption>
					</table>

				</div>

			</div>
			<br>
		</div>
	</div>

	@endsection
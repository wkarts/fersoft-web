@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<br>
			<h4>Seus Catálogos iFood</h4>

			<div class="row">

				@foreach($data as $item)
				<!-- inicio grid -->
				<div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
					<!--begin::Card-->
					<div class="card card-custom gutter-b card-stretch">
						<!--begin::Body-->
						<div class="card-body pt-4">
							<div class="d-flex align-items-end mb-7">
								<!--begin::Pic-->
								<div class="d-flex align-items-center">

									<div class="d-flex flex-column">
										<a class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0">
											{{ $item->context[0] }}
										</a>

									</div>
								</div>
							</div>
							<!--end::User-->
							<p class="text-muted font-weight-bold">status: 
								<strong class="text-danger">{{ $item->status }}</strong>
							</p>
							<p class="text-muted font-weight-bold">ID: 
								<strong class="text-danger">{{ $item->catalogId }}</strong>
							</p>
							<p class="text-muted font-weight-bold">última modificação: 
								<strong class="text-danger">{{ \Carbon\Carbon::parse($item->modifiedAt)->format('d/m/Y H:i') }}</strong>
							</p>
							<p class="text-muted font-weight-bold">Grupo ID: 
								<strong class="text-danger">{{ $item->groupId }}</strong>
							</p>
							
							@if($config->catalogId != $item->catalogId)
							<a href="/ifood/setCatalogo/{{$item->catalogId}}" class="btn btn-success w-100">
								<i class="la la-check"></i>
								Definir catálogo
							</a>
							@endif
						</div>
					</div>
				</div>
				@endforeach
			</div>
			
		</div>
	</div>
</div>
@endsection	
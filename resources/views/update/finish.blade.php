@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-admin-modules.css') }}?v={{ filemtime(public_path('css/ui-admin-modules.css')) }}">
@endpush
@section('content')
<div class="document-fiscal-page admin-page admin-page--update">
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				
				@foreach($logMessage as $log)
				<p>{!! $log !!}</p>
				@endforeach
				
			</div>

			<a href="/appUpdate" class="btn btn-info mb-4 ml-4">
				<i class="la la-arrow-alt-circle-left"></i>
				voltar
			</a>
		</div>
	</div>
</div>

</div>
@endsection

@section('javascript')
<script type="text/javascript">
	
</script>
@endsection
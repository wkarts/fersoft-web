@extends('default.layout', ['title' => 'Sintegra'])
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <br>
        <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

            <h6 class="mb-0">Gerar Arquivo Sintegra</h6>
            <form method="post" action="{{ route('sintegra.store') }}" class="mt-3">
                @csrf
                <div class="row">
                    <div class="col-lg-2 col-6">
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="col-lg-2 col-6">
                        <input type="date" name="end_date" class="form-control">
                    </div>
                    <div class="col-md-3">

                        <button class="btn btn-success" type="submit"> <i class="bx bx-file"></i>Gerar</button>
                    </div>
                </div>
            </form>
            <hr />
        </div>

    </div>
</div>
</div>
@endsection

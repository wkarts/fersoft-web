@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h3>Recepção e Transporte de MTR</h3>
            <div class="text-muted">Consulta e recebimento de manifestos SINIR / IEMA.</div>
        </div>
        <a href="{{ route('mtr.emissao.index') }}" class="btn btn-light">Voltar à emissão</a>
    </div>

    @if(session('sucesso'))<div class="alert alert-success">{{ session('sucesso') }}</div>@endif
    @if(session('erro'))<div class="alert alert-danger">{{ session('erro') }}</div>@endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-custom gutter-b">
                <div class="card-header"><h4 class="card-title">Receber por número / código</h4></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('mtr.recepcao.barra') }}">
                        @csrf
                        <div class="form-group">
                            <label>Número do MTR</label>
                            <input name="codigo_barras" class="form-control" required>
                        </div>
                        <button class="btn btn-primary">Receber manifesto</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-custom gutter-b">
                <div class="card-header"><h4 class="card-title">MTR Provisório</h4></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('mtr.recepcao.provisorio') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Nº provisório</label><input name="numero_provisorio" class="form-control" required></div>
                            <div class="col-md-6 form-group"><label>CNPJ gerador</label><input name="cnpj_gerador" class="form-control" required></div>
                        </div>
                        <button class="btn btn-primary">Receber provisório</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-custom gutter-b">
        <div class="card-header"><h4 class="card-title">Manifestos como Transportador</h4></div>
        <div class="card-body">
            <form method="GET" action="{{ route('mtr.recepcao.index') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-3 form-group"><label>Inicial</label><input type="date" name="dt_inicio_transp" class="form-control" value="{{ request('dt_inicio_transp',$dtInicio) }}"></div>
                    <div class="col-md-3 form-group"><label>Final</label><input type="date" name="dt_fim_transp" class="form-control" value="{{ request('dt_fim_transp',$dtFim) }}"></div>
                    <div class="col-md-4 form-group"><label>MTR</label><input name="mtr_transp" class="form-control" value="{{ request('mtr_transp') }}"></div>
                    <div class="col-md-2 d-flex align-items-end form-group"><button class="btn btn-primary btn-block">Consultar</button></div>
                </div>
            </form>
            <pre class="bg-light p-3" style="max-height:340px;overflow:auto">{{ json_encode($mtrsTransportador, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>

    <div class="card card-custom">
        <div class="card-header"><h4 class="card-title">Manifestos como Destinador</h4></div>
        <div class="card-body">
            <form method="GET" action="{{ route('mtr.recepcao.index') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-3 form-group"><label>Inicial</label><input type="date" name="dt_inicio_dest" class="form-control" value="{{ request('dt_inicio_dest',$dtInicio) }}"></div>
                    <div class="col-md-3 form-group"><label>Final</label><input type="date" name="dt_fim_dest" class="form-control" value="{{ request('dt_fim_dest',$dtFim) }}"></div>
                    <div class="col-md-4 form-group"><label>MTR</label><input name="mtr_dest" class="form-control" value="{{ request('mtr_dest') }}"></div>
                    <div class="col-md-2 d-flex align-items-end form-group"><button class="btn btn-primary btn-block">Consultar</button></div>
                </div>
            </form>
            <pre class="bg-light p-3" style="max-height:340px;overflow:auto">{{ json_encode($mtrsDestinador, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
</div>
@endsection

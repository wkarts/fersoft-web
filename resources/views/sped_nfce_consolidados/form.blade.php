@extends('default.layout')
@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-body">

            <h3 class="mb-4">{{ $title ?? 'Novo SPED NFC-e Consolidado' }}</h3>

            @if(session('mensagem_sucesso'))
                <div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>
            @endif
            @if(session('mensagem_erro'))
                <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
            @endif

            <form method="post" action="{{ $actionSave }}" enctype="multipart/form-data" class="@if(env('ANIMACAO')) animate__animated @endif animate__fadeIn">
                @csrf

                <div class="form-row">
                    <div class="form-group col-lg-6 col-md-8 col-sm-12">
                        <label>Arquivo SPED (.txt)</label>
                        <input type="file" name="arquivo_sped" class="form-control" accept=".txt" required>
                        <small class="form-text text-muted">EFD ICMS-IPI completo. Consolidaremos apenas a NFC-e.</small>
                    </div>

                    <div class="form-group col-lg-3 col-md-4 col-sm-12">
                        <label>Filial</label>
                        <input type="number" name="filial_id" class="form-control" value="{{ $filiais ?? '' }}" placeholder="Opcional (<=0 vira null)">
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">Consolidar</button>
                    <a href="{{ $actionCancel }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>

            <hr>

            <p class="text-muted mt-3">
                • O arquivo consolidado será salvo em <code>public/sped_nfce_consolidado/cnpj_XXXXXXXXXXXXXX/</code> e ficará disponível para download. <br>
                • Apenas **NFC-e (mod 65)** são agregadas por dia/CFOP/CST/Alíquota; demais blocos permanecem fora (arquivo “interno”).<br>
                • Contadores dos blocos e de registros são recalculados para evitar erros de importação em sistemas contábeis internos.
            </p>

        </div>
    </div>
@endsection

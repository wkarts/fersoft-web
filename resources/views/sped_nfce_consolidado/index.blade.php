@extends('default.layout')
@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">{{ $title ?? 'SPED NFC-e Consolidado' }}</h3>
                <button class="btn btn-success" data-toggle="modal" data-target="#modalUploadSped">
                    Novo Consolidado
                </button>
            </div>

            @if(session('mensagem_sucesso'))
                <div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>
            @endif
            @if(session('mensagem_erro'))
                <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-light">
                    <tr>
                        @foreach($headers as $h)
                            <th>{!! $h !!}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $r)
                        <tr>
                            @foreach($fields as $f)
                                <td>
                                    @if(is_string($f))
                                        {{ data_get($r, $f) }}
                                    @elseif(is_callable($f))
                                        {!! $f($r) !!}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($headers) }}" class="text-center">Nenhum registro</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $records->links() }}
            </div>

            <hr>
            <small class="text-muted">
                • Os arquivos são salvos em <code>public/sped_nfce_consolidado/</code> sem subpastas. <br>
                • O nome é <strong>CNPJ_sped_nfce_consolidado_AAAAMMDD_HHMMSS_TOKEN_*.txt</strong>, evitando duplicidade. <br>
                • Apenas NFC-e (mod 65) são agregadas por dia/CFOP/CST/Alíquota; contadores são recalculados (X990, 9900, 9990, 9999).
            </small>

        </div>
    </div>

    {{-- MODAL DE UPLOAD --}}
    <div class="modal fade" id="modalUploadSped" tabindex="-1" role="dialog" aria-labelledby="modalUploadSpedLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="post" action="{{ $actionSave }}" enctype="multipart/form-data" class="@if(env('ANIMACAO')) animate__animated @endif animate__fadeIn w-100">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalUploadSpedLabel">Novo SPED NFC-e Consolidado</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <div class="form-row">
                            <div class="form-group col-lg-8 col-md-8 col-sm-12">
                                <label>Arquivo SPED (.txt)</label>
                                <input type="file" name="arquivo_sped" class="form-control" accept=".txt" required>
                                <small class="form-text text-muted">
                                    EFD ICMS-IPI completo. Consolidaremos apenas a NFC-e (uso interno/contábil).
                                </small>
                            </div>

                            <div class="form-group col-lg-4 col-md-4 col-sm-12">
                                <label>Filial (opcional)</label>
                                <input type="number" name="filial_id" class="form-control" value="{{ $filiais ?? '' }}" placeholder="≤ 0 vira null">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Consolidar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

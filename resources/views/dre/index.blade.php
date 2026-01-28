@extends('default.layout')
@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Nova DRE</h3>
                <a href="/dre/list" class="btn btn-outline-primary">
                    <i class="la la-list"></i> Lista
                </a>
            </div>
            <form method="POST" action="/dre/save">
                @csrf
                <div class="card-body">
                    <div class="form-row">
                        {{-- Data de Início --}}
                        <div class="form-group col-12 col-md-6 col-lg-2">
                            <label for="kt_datepicker_3">Data de Início</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="kt_datepicker_3"
                                    name="data_inicio"
                                    class="form-control @if($errors->has('data_inicio')) is-invalid @endif"
                                    readonly
                                >
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="la la-calendar"></i></span>
                                </div>
                                @if($errors->has('data_inicio'))
                                    <div class="invalid-feedback">
                                        {{ $errors->first('data_inicio') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Data de Término --}}
                        <div class="form-group col-12 col-md-6 col-lg-2">
                            <label for="kt_datepicker_3_end">Data de Término</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="kt_datepicker_3"
                                    name="data_fim"
                                    class="form-control @if($errors->has('data_fim')) is-invalid @endif"
                                    readonly
                                >
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="la la-calendar"></i></span>
                                </div>
                                @if($errors->has('data_fim'))
                                    <div class="invalid-feedback">
                                        {{ $errors->first('data_fim') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Filial (se aplicável) --}}
                        @if(empresaComFilial())
                            <div class="form-group col-12 col-md-6 col-lg-4">
                                {!! __view_locais_select() !!}
                            </div>
                        @endif

                        {{-- % Imposto --}}
                        @if($tributacao->regime != 1)
                            <div class="form-group col-12 col-md-6 col-lg-4">
                                <label for="perc_imposto">% Imposto</label>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        id="perc_imposto"
                                        name="perc_imposto"
                                        class="form-control @if($errors->has('perc_imposto')) is-invalid @endif"
                                        value="0"
                                    >
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="la la-percent"></i></span>
                                    </div>
                                    @if($errors->has('perc_imposto'))
                                        <div class="invalid-feedback">
                                            {{ $errors->first('perc_imposto') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Observação --}}
                        <div class="form-group col-12">
                            <label for="observacao">Observação</label>
                            <input
                                type="text"
                                id="observacao"
                                name="observacao"
                                class="form-control @if($errors->has('observacao')) is-invalid @endif"
                            >
                            @if($errors->has('observacao'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('observacao') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white text-right">
                    <a href="/dre/list" class="btn btn-outline-danger mr-2">
                        <i class="la la-close"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="la la-check"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

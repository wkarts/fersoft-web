@extends('default.layout')
@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b example example-compact">
            <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-lg-12">
                    <br>
                    <form method="post" action="{{ isset($data->id) ? "{$actionUpdate}/{$data->id}" : $actionSave }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $data->id ?? '' }}">
                        <div class="row align-items-center">
                            <div class="col-lg-12 col-md-12 col-sm-12">
                                <div class="card card-custom gutter-b example example-compact">
                                    <div class="card-header text-center">
                                        <h3 class="card-title">{{ $title }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xl-12">
                                <div class="kt-section kt-section--first">
                                    <div class="kt-section__body">
                                        <!-- Veículo -->
                                        <div class="row">
                                            <div class="form-group validated col-sm-10 col-lg-4">
                                                <label class="col-form-label">Veículo</label>
                                                <select id="veiculo_id" class="custom-select @if($errors->has('veiculo_id')) is-invalid @endif" name="veiculo_id">
                                                    <option value="">Selecione um Veículo</option>
                                                    @foreach($veiculos as $veiculo)
                                                        <option value="{{ $veiculo->id }}" {{ old('veiculo_id', $data->veiculo_id ?? '') == $veiculo->id ? 'selected' : '' }}>
                                                            {{ $veiculo->marca }} {{ $veiculo->modelo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($errors->has('veiculo_id'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('veiculo_id') }}
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Motorista -->
                                            <div class="form-group validated col-sm-10 col-lg-4">
                                                <label class="col-form-label">Motorista</label>
                                                <select id="motorista_id" class="custom-select @if($errors->has('motorista_id')) is-invalid @endif" name="motorista_id">
                                                    <option value="">Selecione um Motorista</option>
                                                    @foreach($motoristas as $motorista)
                                                        <option value="{{ $motorista->id }}" {{ old('motorista_id', $data->motorista_id ?? '') == $motorista->id ? 'selected' : '' }}>
                                                            {{ $motorista->nome }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($errors->has('motorista_id'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('motorista_id') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Tipo de Movimentação -->
                                        <div class="row">
                                            <div class="form-group validated col-sm-10 col-lg-4">
                                                <label class="col-form-label">Tipo de Movimentação</label>
                                                <select id="tipo_movimentacao_id" class="custom-select @if($errors->has('tipo_movimentacao_id')) is-invalid @endif" name="tipo_movimentacao_id">
                                                    <option value="">Selecione um Tipo</option>
                                                    @foreach($tiposMovimentacao as $tipo)
                                                        <option value="{{ $tipo->id }}" {{ old('tipo_movimentacao_id', $data->tipo_movimentacao_id ?? '') == $tipo->id ? 'selected' : '' }}>
                                                            {{ $tipo->nome }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($errors->has('tipo_movimentacao_id'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('tipo_movimentacao_id') }}
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Data de Movimentação -->
                                            <div class="form-group validated col-sm-10 col-lg-4">
                                                <label class="col-form-label">Data de Movimentação</label>
                                                <input id="data_movimentacao" type="date" class="form-control @if($errors->has('data_movimentacao')) is-invalid @endif" name="data_movimentacao" value="{{ old('data_movimentacao', $data->data_movimentacao ?? '') }}">
                                                @if($errors->has('data_movimentacao'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('data_movimentacao') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- KM Saída, KM Chegada, Custo -->
                                        <div class="row">
                                            <div class="form-group validated col-sm-12 col-lg-2">
                                                <label class="col-form-label">KM Saída</label>
                                                <input id="km_saida" type="number" class="form-control @if($errors->has('km_saida')) is-invalid @endif" name="km_saida" value="{{ old('km_saida', $data->km_saida ?? '') }}">
                                                @if($errors->has('km_saida'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('km_saida') }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="form-group validated col-sm-12 col-lg-2">
                                                <label class="col-form-label">KM Chegada</label>
                                                <input id="km_chegada" type="number" class="form-control @if($errors->has('km_chegada')) is-invalid @endif" name="km_chegada" value="{{ old('km_chegada', $data->km_chegada ?? '') }}">
                                                @if($errors->has('km_chegada'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('km_chegada') }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="form-group validated col-sm-12 col-lg-2">
                                                <label class="col-form-label">Custo</label>
                                                <input id="custo" type="number" step="0.01" class="form-control @if($errors->has('custo')) is-invalid @endif" name="custo" value="{{ old('custo', $data->custo ?? '') }}">
                                                @if($errors->has('custo'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('custo') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Observações -->
                                        <div class="row">
                                            <div class="form-group validated col-sm-12 col-lg-6">
                                                <label class="col-form-label">Observações</label>
                                                <textarea id="observacoes" class="form-control @if($errors->has('observacoes')) is-invalid @endif" name="observacoes">{{ old('observacoes', $data->observacoes ?? '') }}</textarea>
                                                @if($errors->has('observacoes'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('observacoes') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-md-4">
                                    <a style="width: 100%" class="btn btn-danger" href="{{ $actionCancel }}">
                                        <i class="la la-close"></i>
                                        <span class="">Cancelar</span>
                                    </a>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-md-4">
                                    <button style="width: 100%" type="submit" class="btn btn-success">
                                        <i class="la la-check"></i>
                                        <span class="">Salvar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

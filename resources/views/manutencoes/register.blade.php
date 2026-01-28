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
                                        <div class="row">
                                            <div class="form-group validated col-sm-12 col-lg-4">
                                                <label class="col-form-label">Veículo</label>
                                                <select class="custom-select @if($errors->has('veiculo_id')) is-invalid @endif" name="veiculo_id">
                                                    <option value="">Selecione um Veículo</option>
                                                    @foreach($veiculos as $veiculo)
                                                        <option value="{{ $veiculo->id }}" {{ old('veiculo_id', $data->veiculo_id ?? '') == $veiculo->id ? 'selected' : '' }}>
                                                            {{ $veiculo->placa }} - {{ $veiculo->marca }} {{ $veiculo->modelo }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($errors->has('veiculo_id'))
                                                    <div class="invalid-feedback">{{ $errors->first('veiculo_id') }}</div>
                                                @endif
                                            </div>

                                            <div class="form-group validated col-sm-12 col-lg-4">
                                                <label class="col-form-label">Responsável</label>
                                                <select class="custom-select @if($errors->has('responsavel_id')) is-invalid @endif" name="responsavel_id">
                                                    <option value="">Selecione um Responsável</option>
                                                    @foreach($funcionarios as $funcionario)
                                                        <option value="{{ $funcionario->id }}" {{ old('responsavel_id', $data->responsavel_id ?? '') == $funcionario->id ? 'selected' : '' }}>
                                                            {{ $funcionario->nome }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($errors->has('responsavel_id'))
                                                    <div class="invalid-feedback">{{ $errors->first('responsavel_id') }}</div>
                                                @endif
                                            </div>

                                            <div class="form-group validated col-sm-12 col-lg-4">
                                                <label class="col-form-label">Fornecedor</label>
                                                <select class="custom-select @if($errors->has('fornecedor_id')) is-invalid @endif" name="fornecedor_id">
                                                    <option value="">Selecione um Fornecedor</option>
                                                    @foreach($fornecedores as $fornecedor)
                                                        <option value="{{ $fornecedor->id }}" {{ old('fornecedor_id', $data->fornecedor_id ?? '') == $fornecedor->id ? 'selected' : '' }}>
                                                            {{ $fornecedor->razao_social ?? $fornecedor->nome_fantasia ?? $fornecedor->cpf_cnpj }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($errors->has('fornecedor_id'))
                                                    <div class="invalid-feedback">{{ $errors->first('fornecedor_id') }}</div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group validated col-sm-12 col-lg-4">
                                                <label class="col-form-label">Descrição</label>
                                                <input type="text" class="form-control @if($errors->has('descricao')) is-invalid @endif" name="descricao" value="{{ old('descricao', $data->descricao ?? '') }}">
                                                @if($errors->has('descricao'))
                                                    <div class="invalid-feedback">{{ $errors->first('descricao') }}</div>
                                                @endif
                                            </div>

                                            <div class="form-group validated col-sm-12 col-lg-4">
                                                <label class="col-form-label">Tipo</label>
                                                <input type="text" class="form-control @if($errors->has('tipo')) is-invalid @endif" name="tipo" value="{{ old('tipo', $data->tipo ?? '') }}">
                                                @if($errors->has('tipo'))
                                                    <div class="invalid-feedback">{{ $errors->first('tipo') }}</div>
                                                @endif
                                            </div>

                                            <div class="form-group validated col-sm-12 col-lg-4">
                                                <label class="col-form-label">Data da Manutenção</label>
                                                <input type="date" class="form-control @if($errors->has('data_manutencao')) is-invalid @endif" name="data_manutencao" value="{{ old('data_manutencao', isset($data->data_manutencao) ? optional($data->data_manutencao)->format('Y-m-d') : '') }}">
                                                @if($errors->has('data_manutencao'))
                                                    <div class="invalid-feedback">{{ $errors->first('data_manutencao') }}</div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group validated col-sm-12 col-lg-3">
                                                <label class="col-form-label">Quilometragem Atual</label>
                                                <input type="number" step="0.01" class="form-control @if($errors->has('quilometragem_atual')) is-invalid @endif" name="quilometragem_atual" value="{{ old('quilometragem_atual', $data->quilometragem_atual ?? '') }}">
                                                @if($errors->has('quilometragem_atual'))
                                                    <div class="invalid-feedback">{{ $errors->first('quilometragem_atual') }}</div>
                                                @endif
                                            </div>

                                            <div class="form-group validated col-sm-12 col-lg-3">
                                                <label class="col-form-label">Próxima Manutenção (KM)</label>
                                                <input type="number" step="0.01" class="form-control @if($errors->has('proxima_manutencao_km')) is-invalid @endif" name="proxima_manutencao_km" value="{{ old('proxima_manutencao_km', $data->proxima_manutencao_km ?? '') }}">
                                                @if($errors->has('proxima_manutencao_km'))
                                                    <div class="invalid-feedback">{{ $errors->first('proxima_manutencao_km') }}</div>
                                                @endif
                                            </div>

                                            <div class="form-group validated col-sm-12 col-lg-3">
                                                <label class="col-form-label">Próxima Manutenção (Data)</label>
                                                <input type="date" class="form-control @if($errors->has('proxima_manutencao_data')) is-invalid @endif" name="proxima_manutencao_data" value="{{ old('proxima_manutencao_data', isset($data->proxima_manutencao_data) ? optional($data->proxima_manutencao_data)->format('Y-m-d') : '') }}">
                                                @if($errors->has('proxima_manutencao_data'))
                                                    <div class="invalid-feedback">{{ $errors->first('proxima_manutencao_data') }}</div>
                                                @endif
                                            </div>

                                            <div class="form-group validated col-sm-12 col-lg-3">
                                                <label class="col-form-label">Custo</label>
                                                <input type="number" step="0.01" class="form-control @if($errors->has('custo')) is-invalid @endif" name="custo" value="{{ old('custo', $data->custo ?? '') }}">
                                                @if($errors->has('custo'))
                                                    <div class="invalid-feedback">{{ $errors->first('custo') }}</div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group validated col-sm-12 col-lg-4">
                                                <label class="col-form-label">Prioridade</label>
                                                <select class="custom-select @if($errors->has('prioridade')) is-invalid @endif" name="prioridade">
                                                    @foreach(['Baixa', 'Média', 'Alta'] as $prioridade)
                                                        <option value="{{ $prioridade }}" {{ old('prioridade', $data->prioridade ?? 'Média') == $prioridade ? 'selected' : '' }}>{{ $prioridade }}</option>
                                                    @endforeach
                                                </select>
                                                @if($errors->has('prioridade'))
                                                    <div class="invalid-feedback">{{ $errors->first('prioridade') }}</div>
                                                @endif
                                            </div>

                                            <div class="form-group validated col-sm-12 col-lg-4">
                                                <label class="col-form-label">Status</label>
                                                <select class="custom-select @if($errors->has('status')) is-invalid @endif" name="status">
                                                    @foreach(['Planejada', 'Em andamento', 'Concluída'] as $status)
                                                        <option value="{{ $status }}" {{ old('status', $data->status ?? 'Planejada') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                                    @endforeach
                                                </select>
                                                @if($errors->has('status'))
                                                    <div class="invalid-feedback">{{ $errors->first('status') }}</div>
                                                @endif
                                            </div>

                                            @php
                                                $checklistText = old('checklist_items');
                                                if($checklistText === null && isset($data) && is_array($data->checklist)) {
                                                    $checklistText = implode("\n", $data->checklist);
                                                }
                                            @endphp
                                            <div class="form-group validated col-sm-12 col-lg-4">
                                                <label class="col-form-label">Checklist (uma linha por item)</label>
                                                <textarea class="form-control" name="checklist_items" rows="4">{{ $checklistText }}</textarea>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group validated col-sm-12">
                                                <label class="col-form-label">Observações</label>
                                                <textarea class="form-control @if($errors->has('observacoes')) is-invalid @endif" name="observacoes" rows="3">{{ old('observacoes', $data->observacoes ?? '') }}</textarea>
                                                @if($errors->has('observacoes'))
                                                    <div class="invalid-feedback">{{ $errors->first('observacoes') }}</div>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

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

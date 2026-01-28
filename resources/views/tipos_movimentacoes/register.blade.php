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
                                            <div class="form-group validated col-sm-10 col-lg-4">
                                                <label class="col-form-label">Nome</label>
                                                <input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{ old('nome', $data->nome ?? '') }}">
                                                @if($errors->has('nome'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('nome') }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="form-group validated col-sm-12 col-lg-2">
                                                <label class="col-form-label">Ativo</label>
                                                <select class="custom-select @if($errors->has('ativo')) is-invalid @endif" name="ativo">
                                                    <option value="1" {{ old('ativo', $data->ativo ?? '') == 1 ? 'selected' : '' }}>Sim</option>
                                                    <option value="0" {{ old('ativo', $data->ativo ?? '') == 0 ? 'selected' : '' }}>Não</option>
                                                </select>
                                                @if($errors->has('ativo'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('ativo') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group validated col-sm-12 col-lg-6">
                                                <label class="col-form-label">Descrição</label>
                                                <textarea id="descricao" class="form-control @if($errors->has('descricao')) is-invalid @endif" name="descricao">{{ old('descricao', $data->descricao ?? '') }}</textarea>
                                                @if($errors->has('descricao'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('descricao') }}
                                                    </div>
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

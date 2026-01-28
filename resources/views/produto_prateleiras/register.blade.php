@extends('default.layout')

@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-body">

            <h3>{{ $title }}</h3>

            {{-- Mensagens de erro --}}
            @if(session('mensagem_erro'))
                <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
            @endif

            <form action="{{ isset($data) ? url("{$actionUpdate}/{$data->id}") : url($actionSave) }}" method="POST">
                @csrf

                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="identificacao">Identificação <span class="text-danger">*</span></label>
                        <input type="text" name="identificacao" id="identificacao"
                               value="{{ old('identificacao', $data->identificacao ?? '') }}"
                               class="form-control" required maxlength="50">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="descricao">Descrição</label>
                        <input type="text" name="descricao" id="descricao"
                               value="{{ old('descricao', $data->descricao ?? '') }}"
                               class="form-control" maxlength="100">
                    </div>

                    <div class="form-group col-md-2">
                        <label for="posicao">Posição</label>
                        <input type="text" name="posicao" id="posicao"
                               value="{{ old('posicao', $data->posicao ?? '') }}"
                               class="form-control" maxlength="10">
                    </div>

                    <div class="form-group col-md-2">
                        <label for="localizacao">Localização</label>
                        <input type="text" name="localizacao" id="localizacao"
                               value="{{ old('localizacao', $data->localizacao ?? '') }}"
                               class="form-control" maxlength="100">
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-12">
                        <label for="observacao">Observação</label>
                        <textarea name="observacao" id="observacao" class="form-control" rows="3">{{ old('observacao', $data->observacao ?? '') }}</textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-primary">
                            {{ isset($data) ? 'Atualizar' : 'Salvar' }}
                        </button>
                        <a href="{{ url($actionCancel) }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>

        </div>
    </div>
@endsection

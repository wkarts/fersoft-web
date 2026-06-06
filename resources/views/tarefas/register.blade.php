@extends('default.layout')
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm mt-3">
        <div class="card-header">
            <h4 class="mb-0">{{ $title }}</h4>
        </div>
        
        <div class="card-body">
            <form action="{{ $data ? $actionUpdate . '/' . $data->id : $actionSave }}" method="POST">
                @csrf
                
                @if($data)
                    <input type="hidden" name="id" value="{{ $data->id }}">
                @endif

                <input type="hidden" name="user_id" value="{{ session('user_logged')['id'] }}">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="titulo" class="form-label">Título da Tarefa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titulo" name="titulo" value="{{ old('titulo', $data->titulo ?? '') }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="funcionario_id" class="form-label">Atribuir ao Funcionário <span class="text-danger">*</span></label>
                        <select class="form-select" id="funcionario_id" name="funcionario_id" required>
                            <option value="">Selecione um funcionário</option>
                            @foreach($funcionarios as $f)
                                <option value="{{ $f->id }}" {{ old('funcionario_id', $data->funcionario_id ?? '') == $f->id ? 'selected' : '' }}>
                                    {{ $f->id }} - {{ $f->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="data" class="form-label">Data de Início <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="data" name="data" value="{{ old('data', isset($data) ? \Carbon\Carbon::parse($data->data)->format('Y-m-d') : '') }}" required>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="hora_estimada" class="form-label">Hora de Início (Opcional)</label>
                        <input type="time" class="form-control" id="hora_estimada" name="hora_estimada" value="{{ old('hora_estimada', $data->hora_estimada ?? '') }}">
                    </div>

                    <div class="col-md-3 mb-3 d-flex align-items-center">
                        <div class="form-check form-switch mt-4">
                            <input type="hidden" name="is_recorrente" value="0">
                            <input class="form-check-input" type="checkbox" id="is_recorrente" name="is_recorrente" value="1" {{ old('is_recorrente', $data->is_recorrente ?? 0) ? 'checked' : '' }} onchange="toggleRecorrencia()">
                            <label class="form-check-label" style="cursor: pointer;" for="is_recorrente"><strong>Tarefa Recorrente?</strong></label>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3" id="div_frequencia" style="display: {{ old('is_recorrente', $data->is_recorrente ?? 0) ? 'block' : 'none' }};">
                        <label for="frequencia" class="form-label">Frequência</label>
                        <select class="form-select" id="frequencia" name="frequencia">
                            <option value="">Selecione...</option>
                            <option value="diario" {{ old('frequencia', $data->frequencia ?? '') == 'diario' ? 'selected' : '' }}>Diária (Todo dia)</option>
                            <option value="semanal" {{ old('frequencia', $data->frequencia ?? '') == 'semanal' ? 'selected' : '' }}>Semanal (Toda semana)</option>
                            <option value="mensal" {{ old('frequencia', $data->frequencia ?? '') == 'mensal' ? 'selected' : '' }}>Mensal (Todo mês)</option>
                        </select>
                    </div>
                </div>

                <div class="row bg-light pt-3 pb-1 mb-3 border rounded mx-0">
                    <div class="col-md-4 mb-3">
                        <label for="data_limite" class="form-label text-danger"><strong>Data Limite (Prazo)</strong></label>
                        <input type="date" class="form-control" id="data_limite" name="data_limite" value="{{ old('data_limite', isset($data->data_limite) && $data->data_limite ? \Carbon\Carbon::parse($data->data_limite)->format('Y-m-d') : '') }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="hora_limite" class="form-label text-danger"><strong>Hora Limite</strong></label>
                        <input type="time" class="form-control" id="hora_limite" name="hora_limite" value="{{ old('hora_limite', $data->hora_limite ?? '') }}">
                    </div>

                    <div class="col-md-4 mb-3">
                      <label for="prioridade" class="form-label">Prioridade <span class="text-danger">*</span></label>
                      <select class="form-select" id="prioridade" name="prioridade" required>
                          <option value="Baixa" {{ old('prioridade', $data->prioridade ?? '') == 'Baixa' ? 'selected' : '' }}>🔵 Baixa</option>
                          <option value="Normal" {{ old('prioridade', $data->prioridade ?? 'Normal') == 'Normal' ? 'selected' : '' }}>🟢 Normal</option>
                          <option value="Alta" {{ old('prioridade', $data->prioridade ?? '') == 'Alta' ? 'selected' : '' }}>🟡 Alta</option>
                          <option value="Urgente" {{ old('prioridade', $data->prioridade ?? '') == 'Urgente' ? 'selected' : '' }}>🔴 Urgente</option>
                      </select>
                  </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="descricao" class="form-label">Descrição / Observações</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4">{{ old('descricao', $data->descricao ?? '') }}</textarea>
                    </div>
                </div>
				
              @if($data)
                <div class="row mt-2">
                    <div class="col-md-12 mb-3">
                        <label for="justificativa_atraso" class="form-label text-danger">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Justificação de Atraso (Preencher apenas se o prazo expirou)</strong>
                        </label>
                        <textarea class="form-control border-danger" id="justificativa_atraso" name="justificativa_atraso" rows="2" placeholder="Se a tarefa está atrasada, explique o motivo aqui...">{{ old('justificativa_atraso', $data->justificativa_atraso ?? '') }}</textarea>
                    </div>
                </div>
                @endif
                <hr>

                <div class="d-flex justify-content-end">
                    <a href="{{ $actionCancel }}" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar Tarefa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleRecorrencia() {
        var isChecked = document.getElementById('is_recorrente').checked;
        var divFrequencia = document.getElementById('div_frequencia');
        var selectFrequencia = document.getElementById('frequencia');

        if(isChecked) {
            divFrequencia.style.display = 'block';
            selectFrequencia.setAttribute('required', 'required');
        } else {
            divFrequencia.style.display = 'none';
            selectFrequencia.removeAttribute('required');
            selectFrequencia.value = ''; 
        }
    }
</script>
@endsection
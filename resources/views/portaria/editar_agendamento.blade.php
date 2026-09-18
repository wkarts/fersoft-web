@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title"><i class="fas fa-edit"></i> Editar Agendamento</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-warning">
            <h5 class="mb-0">Ajustar Dados da Visita</h5>
        </div>
        <div class="card-body">
            <form action="{{ url('/portaria/agendamento/'.$agendamento->id.'/atualizar') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">CPF do Visitante</label>
                        <input type="text" name="cpf" class="form-control" value="{{ $agendamento->visitante->cpf }}" required>
                    </div>
                    <div class="col-md-5 form-group">
                        <label class="font-weight-bold">Nome do Visitante</label>
                        <input type="text" name="nome" class="form-control" value="{{ $agendamento->visitante->nome }}" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold">Placa do Veículo (Opcional)</label>
                        <input type="text" name="placa_veiculo" class="form-control" value="{{ $agendamento->placa_veiculo_prevista }}">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Quem ele vem visitar?</label>
                        <select name="funcionario_id" class="form-control" required>
                            @foreach($funcionarios as $func)
                                <option value="{{ $func->id }}" {{ $agendamento->funcionario_id == $func->id ? 'selected' : '' }}>
                                    {{ $func->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Data e Hora Prevista</label>
                        <input type="datetime-local" name="data_hora_prevista" class="form-control" value="{{ \Carbon\Carbon::parse($agendamento->data_hora_prevista)->format('Y-m-d\TH:i') }}" required>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-right">
                        <a href="{{ url('/portaria') }}" class="btn btn-secondary mr-2">Cancelar</a>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar Alterações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
<div class="col-md-6 form-group">
    <label class="font-weight-bold">Jornada de Trabalho / Escala de Ponto</label>
    <select name="ponto_escala_id" class="form-control select2">
        <option value="">Padrão CLT 44h (Sem escala vinculada)</option>
        @foreach($escalas as $escala)
            <option value="{{ $escala->id }}" {{ (isset($funcionario) && $funcionario->ponto_escala_id == $escala->id) ? 'selected' : '' }}>
                {{ $escala->nome }} ({{ strtoupper($escala->tipo) }})
            </option>
        @endforeach
    </select>
    <small class="text-muted">Define as horas diárias previstas, folgas, DSR e tolerâncias calculadas pelo sistema.</small>
</div>

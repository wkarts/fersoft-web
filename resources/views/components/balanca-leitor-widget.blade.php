<div class="card border-primary mb-3" data-balanca-widget="1" data-modo="{{ $modo ?? 'teste' }}">
  <div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h5 class="mb-1">Leitor de Balança ADP</h5>
        <small class="text-muted">Modelo/integrador/protocolo/porta exibidos após detalhes</small>
      </div>
      <div><strong class="text-primary" data-peso-resumo>0 kg</strong></div>
    </div>
    <select class="form-control mt-2" data-balanca-select>
      <option value="">Selecione...</option>
      @foreach(($balancas ?? []) as $balanca)
      <option value="{{ $balanca->id }}" @selected(($selectedBalancaId ?? null) == $balanca->id)>{{ $balanca->descricao }}</option>
      @endforeach
    </select>
    <div class="bg-dark text-white rounded p-3 mt-3 text-center position-relative">
      <span class="badge badge-info position-absolute" style="top:8px;right:8px" data-status>OFFLINE</span>
      <div style="font-size: 2rem; font-weight:700" data-peso>0</div>
      <small class="d-block text-right">kg</small>
    </div>
    <div class="mt-2">
      <button class="btn btn-success btn-sm" data-action="open">Conectar</button>
      <button class="btn btn-secondary btn-sm" data-action="close">Desconectar</button>
      <button class="btn btn-primary btn-sm" data-action="read">Capturar peso</button>
      <button class="btn btn-info btn-sm" data-action="evidence">Capturar evidência</button>
    </div>
    <input type="hidden" id="{{ $inputBalancaId ?? 'balanca_config_id' }}" name="balanca_config_id">
    <input type="hidden" id="{{ $inputPesoOrigemId ?? 'peso_origem' }}" name="peso_origem" value="balanca">
    <input type="hidden" id="{{ $inputEvidenceId ?? 'balanca_evidence_json' }}" name="balanca_evidence_json">
  </div>
</div>

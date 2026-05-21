<div class="card border-primary mb-3" data-balanca-widget="1" data-modo="{{ $modo ?? 'teste' }}">
  <div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h5 class="mb-1">Leitor de Balança ADP</h5>
        <small class="text-muted">Leitura de peso e captura de evidências por câmeras vinculadas à balança</small>
      </div>
      <div><strong class="text-primary" data-peso-resumo>0 kg</strong></div>
    </div>

    <select class="form-control mt-2" data-balanca-select>
      <option value="">Selecione...</option>
      @foreach(($balancas ?? []) as $balanca)
      <option value="{{ $balanca->id }}"
              @selected(($selectedBalancaId ?? null) == $balanca->id)
              data-integrador="{{ $balanca->integrador ?? 'adp' }}"
              data-integrador-config-id="{{ $balanca->integrador_config_id ?? '' }}"
              data-adp-scale-uuid="{{ $balanca->adp_scale_uuid ?? '' }}"
              data-adp-camera-uuids='@json(json_decode((string) ($balanca->adp_camera_uuids ?? '[]'), true) ?: [])'
              data-modelo="{{ $balanca->modelo ?? '' }}"
              data-port="{{ $balanca->port ?? $balanca->porta_serial ?? '' }}"
              data-backend="{{ $balanca->backend_server_address ?? '' }}">
        {{ $balanca->descricao }}
      </option>
      @endforeach
    </select>

    <div class="bg-dark text-white rounded p-3 mt-3 text-center position-relative">
      <span class="badge badge-info position-absolute" style="top:8px;right:8px" data-status>OFFLINE</span>
      <div style="font-size: 2rem; font-weight:700" data-peso>0</div>
      <small class="d-block text-right">kg</small>
    </div>

    <div class="row mt-3" data-camera-area style="display:none;">
      <div class="col-12">
        <strong>Câmeras da balança</strong>
        <div class="row mt-2" data-camera-preview-list></div>
      </div>
    </div>

    <div class="mt-2">
      <button class="btn btn-success btn-sm" data-action="open">Conectar</button>
      <button class="btn btn-secondary btn-sm" data-action="close">Desconectar</button>
      <button class="btn btn-primary btn-sm" data-action="read">Capturar peso</button>
      <button class="btn btn-info btn-sm" data-action="evidence">Capturar evidência</button>
    </div>

    <pre class="mt-2 p-2 bg-light border" style="max-height:120px;overflow:auto;font-size:11px;" id="balanca-log">Aguardando ação...</pre>

    <input type="hidden" id="{{ $inputBalancaId ?? 'balanca_config_id' }}" name="balanca_config_id">
    <input type="hidden" id="{{ $inputPesoOrigemId ?? 'peso_origem' }}" name="peso_origem" value="balanca">
    <input type="hidden" id="{{ $inputEvidenceId ?? 'balanca_evidence_json' }}" name="balanca_evidence_json">
  </div>
</div>

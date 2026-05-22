@php
    $isEditingBalanca = isset($balanca) && $balanca;
    $selectedIntegradorConfigId = $isEditingBalanca
        ? (string) ($balanca->integrador_config_id ?? '')
        : (string) old('integrador_config_id', '');
    $selectedBackendAddress = $isEditingBalanca
        ? (string) ($balanca->backend_server_address ?? '')
        : (string) old('backend_server_address', '');
    $serverAdpConfigs = isset($adpConfigs) ? $adpConfigs : collect();
    $selectedAdpConfig = null;
    if ($selectedIntegradorConfigId !== '' && $serverAdpConfigs && method_exists($serverAdpConfigs, 'firstWhere')) {
        $selectedAdpConfig = $serverAdpConfigs->firstWhere('id', (int) $selectedIntegradorConfigId);
    }
@endphp
<div class="card mt-2 adp-cadastro-guided"
     data-default-integrador-config-id="{{ $selectedIntegradorConfigId }}"
     data-server-integrador-config-id="{{ $selectedIntegradorConfigId }}"
     data-balanca-id="{{ $balanca->id ?? '' }}"
     data-default-scale-uuid="{{ old('adp_scale_uuid', $balanca->adp_scale_uuid ?? '') }}"
     data-default-camera-uuids="{{ old('adp_camera_uuids', $balanca->adp_camera_uuids ?? '') }}">
  <div class="card-header py-1" style="font-size:14px;">Integração ADP (fluxo guiado)</div>
  <div class="card-body p-2" style="font-size:13px;">
    <div class="border rounded p-2 mb-2 bg-light">
      <small class="text-muted d-block mb-2">Informe a URL/porta da API ADP que será usada para localizar balanças e câmeras.</small>
      <div class="form-row">
        <div class="col-md-3">
          <label class="mb-1">Descrição ADP</label>
          <input class="form-control form-control-sm" data-adp="cfg-descricao" value="{{ $selectedAdpConfig->descricao ?? '' }}" placeholder="Ex.: ADP Matriz">
        </div>
        <div class="col-md-4">
          <label class="mb-1">Base URL ADP</label>
          <input class="form-control form-control-sm" data-adp="cfg-base-url" value="{{ $selectedAdpConfig->base_url ?? '' }}" placeholder="http://127.0.0.1:4789">
        </div>
        <div class="col-md-2">
          <label class="mb-1">Token Type</label>
          <select class="form-control form-control-sm" data-adp="cfg-token-type">
            <option value="x_adp_api_token" {{ ($selectedAdpConfig->global_token_type ?? 'x_adp_api_token') === 'x_adp_api_token' ? 'selected' : '' }}>x_adp_api_token</option>
            <option value="bearer" {{ ($selectedAdpConfig->global_token_type ?? '') === 'bearer' ? 'selected' : '' }}>bearer</option>
            <option value="query" {{ ($selectedAdpConfig->global_token_type ?? '') === 'query' ? 'selected' : '' }}>query</option>
            <option value="none" {{ ($selectedAdpConfig->global_token_type ?? '') === 'none' ? 'selected' : '' }}>none</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="mb-1">Token Global (opcional em edição)</label>
          <input class="form-control form-control-sm" data-adp="cfg-global-token" placeholder="Deixe vazio para manter o atual">
        </div>
      </div>
      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-dark" data-adp="btn-salvar-config" data-adp-action="salvar-config">Salvar/Atualizar configuração ADP</button>
        <button type="button" class="btn btn-sm btn-outline-secondary ml-1" data-adp="btn-nova-config" data-adp-action="nova-config">Nova configuração</button>
        <button type="button" class="btn btn-sm btn-outline-danger ml-1" data-adp="btn-excluir-config" data-adp-action="excluir-config">Excluir/Inativar</button>
      </div>
    </div>

    <div class="form-row">
      <div class="col-md-4">
        <label>Configuração Global ADP</label>
        <input type="hidden" name="adp_integrador_config_id_hidden" data-adp="integrador-config-id-hidden" value="{{ $selectedIntegradorConfigId }}">
        <input type="hidden" name="backend_server_address" data-adp="backend-server-address" value="{{ $selectedBackendAddress }}">
        <select class="form-control form-control-sm" name="integrador_config_id" data-adp="integrador-config-id" data-current-value="{{ $selectedIntegradorConfigId }}" required>
          <option value="">Selecione...</option>
          @foreach($serverAdpConfigs as $cfg)
              @php
                  $cfgId = (string) ($cfg->id ?? '');
                  $isSelectedCfg = $cfgId !== '' && $cfgId === $selectedIntegradorConfigId;
              @endphp
              <option value="{{ $cfgId }}"
                      data-token-masked="{{ $cfg->global_token_masked ?? '' }}"
                      data-base-url="{{ $cfg->base_url ?? '' }}"
                      data-descricao="{{ $cfg->descricao ?? '' }}"
                      data-token-type="{{ $cfg->global_token_type ?? 'x_adp_api_token' }}"
                      data-token-header="{{ $cfg->global_token_header ?? 'X-ADP-API-TOKEN' }}"
                      data-timeout-ms="{{ $cfg->timeout_ms ?? 5000 }}"
                      {{ $isSelectedCfg ? 'selected' : '' }}>
                  #{{ $cfgId }} - {{ $cfg->descricao ?: ($cfg->base_url ?: 'Configuração ADP') }}
              </option>
          @endforeach
        </select>
        <small class="text-muted" data-adp="config-token-mask"></small>
      </div>
      <div class="col-md-8 d-flex align-items-end">
        <button type="button" class="btn btn-outline-primary btn-sm mr-2" data-adp="btn-testar" data-adp-action="testar-conexao">Testar conexão</button>
        <button type="button" class="btn btn-primary btn-sm mr-2" data-adp="btn-buscar" data-adp-action="buscar-dispositivos">Buscar dispositivos</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-adp="btn-sync" data-adp-action="sincronizar-dispositivos">Sincronizar dispositivos</button>
      </div>
    </div>

    <hr>

    <div class="form-row">
      <div class="col-md-6">
        <label>Balanças encontradas</label>
        <select class="form-control form-control-sm" data-adp="scale-select"></select>
      </div>
      <div class="col-md-6">
        <label>Câmeras encontradas (multi)</label>
        <select class="form-control form-control-sm" data-adp="camera-select" multiple size="4"></select>
      </div>
    </div>

    <input type="hidden" name="ativo" value="1">
    <input type="hidden" name="tipo" value="{{ old('tipo', $balanca->tipo ?? 'plataforma') }}">

    <div class="form-row mt-2">
      <div class="col-md-3">
        <label>UUID Balança ADP</label>
        <input class="form-control form-control-sm" name="adp_scale_uuid" data-adp="scale-uuid" value="{{ old('adp_scale_uuid', $balanca->adp_scale_uuid ?? '') }}">
      </div>
      <div class="col-md-3">
        <label>Integrador</label>
        <input class="form-control form-control-sm" name="integrador" data-adp="integrador" value="{{ old('integrador', $balanca->integrador ?? 'adp') }}">
      </div>
      <div class="col-md-3">
        <label>Porta serial</label>
        <input class="form-control form-control-sm" name="porta_serial" data-adp="porta-serial" value="{{ old('porta_serial', $balanca->porta_serial ?? '') }}">
      </div>
      <div class="col-md-3">
        <label>Baud rate</label>
        <input class="form-control form-control-sm" name="baud_rate" data-adp="baud-rate" value="{{ old('baud_rate', $balanca->baud_rate ?? '') }}">
      </div>
    </div>

    <div class="form-row mt-2">
      <div class="col-md-6">
        <label>UUIDs das câmeras ADP</label>
        <input class="form-control form-control-sm" name="adp_camera_uuids" data-adp="camera-uuids" value="{{ old('adp_camera_uuids', $balanca->adp_camera_uuids ?? '') }}">
      </div>
      <div class="col-md-3">
        <label>Quantidade de câmeras</label>
        <input class="form-control form-control-sm" type="number" min="0" name="quantidade_cameras" data-adp="quantidade-cameras" value="{{ old('quantidade_cameras', $balanca->quantidade_cameras ?? 0) }}">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" data-adp="usa-cameras" name="usa_cameras" {{ old('usa_cameras', $balanca->usa_cameras ?? false) ? 'checked' : '' }}>
          <label class="form-check-label" for="usa_cameras">Usa câmeras</label>
        </div>
      </div>
    </div>

    <div class="mt-3">
      <pre data-adp="log" class="mt-2 p-2" style="max-height: 140px; overflow:auto; background:#f8f9fa; border:1px solid #e9ecef; font-size:11px;">Aguardando ação...</pre>
    </div>
  </div>
</div>

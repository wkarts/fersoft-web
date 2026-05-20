<div class="card mt-2 adp-cadastro-guided"
     data-default-integrador-config-id="{{ old('integrador_config_id', $balanca->integrador_config_id ?? '') }}"
     data-default-scale-uuid="{{ old('adp_scale_uuid', $balanca->adp_scale_uuid ?? '') }}"
     data-default-camera-uuids="{{ old('adp_camera_uuids', $balanca->adp_camera_uuids ?? '') }}">
  <div class="card-header py-1" style="font-size:14px;">Integração ADP (fluxo guiado)</div>
  <div class="card-body p-2" style="font-size:13px;">
    <div class="border rounded p-2 mb-2 bg-light">
      <small class="text-muted d-block mb-2">Use a URL/porta da API ADP acessível a partir do servidor Laravel. Atenção: 127.0.0.1/localhost só funciona se o ADP estiver instalado no mesmo servidor do Laravel. Em homologação/nuvem, use IP LAN/VPN/túnel público acessível pelo servidor.</small>
      <div class="form-row">
        <div class="col-md-3">
          <label class="mb-1">Descrição ADP</label>
          <input class="form-control form-control-sm" data-adp="cfg-descricao" placeholder="Ex.: ADP Matriz">
        </div>
        <div class="col-md-4">
          <label class="mb-1">Base URL ADP</label>
          <input class="form-control form-control-sm" data-adp="cfg-base-url" placeholder="http://127.0.0.1:4789">
        </div>
        <div class="col-md-2">
          <label class="mb-1">Token Type</label>
          <select class="form-control form-control-sm" data-adp="cfg-token-type">
            <option value="x_adp_api_token">x_adp_api_token</option>
            <option value="bearer">bearer</option>
            <option value="query">query</option>
            <option value="none">none</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="mb-1">Token Global (opcional em edição)</label>
          <input class="form-control form-control-sm" data-adp="cfg-global-token" placeholder="Deixe vazio para manter o atual">
        </div>
      </div>
      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-dark" data-adp="btn-salvar-config">Salvar configuração ADP</button>
      </div>
    </div>

    <div class="form-row">
      <div class="col-md-4">
        <label>Configuração Global ADP</label>
        <select class="form-control form-control-sm" name="integrador_config_id" data-adp="integrador-config-id">
          <option value="">Selecione...</option>
        </select>
        <small class="text-muted" data-adp="config-token-mask"></small>
      </div>
      <div class="col-md-8 d-flex align-items-end">
        <button type="button" class="btn btn-outline-primary btn-sm mr-2" data-adp="btn-testar">Testar conexão</button>
        <button type="button" class="btn btn-primary btn-sm mr-2" data-adp="btn-buscar">Buscar dispositivos</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-adp="btn-sync">Sincronizar local</button>
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
        <label>UUIDs das câmeras ADP (csv)</label>
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
      <small class="text-muted">A view chama o Laravel, e o Laravel chama o ADP. Nenhum token é exposto no frontend.</small>
      <pre data-adp="log" class="mt-2 p-2" style="max-height: 140px; overflow:auto; background:#f8f9fa; border:1px solid #e9ecef; font-size:11px;">Aguardando ação...</pre>
    </div>
  </div>
</div>

<script src="{{ asset('js/adp-device-discovery.js') }}"></script>

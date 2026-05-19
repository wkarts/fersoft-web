<div class="card mt-3" id="adp-cadastro-guided"
     data-default-integrador-config-id="{{ old('integrador_config_id', $balanca->integrador_config_id ?? '') }}"
     data-default-scale-uuid="{{ old('adp_scale_uuid', $balanca->adp_scale_uuid ?? '') }}"
     data-default-camera-uuids="{{ old('adp_camera_uuids', $balanca->adp_camera_uuids ?? '') }}">
  <div class="card-header py-2">Integração ADP (fluxo guiado)</div>
  <div class="card-body">
    <div class="border rounded p-2 mb-3 bg-light">
      <div class="form-row">
        <div class="col-md-3">
          <label class="mb-1">Descrição ADP</label>
          <input class="form-control form-control-sm" id="adp_cfg_descricao" placeholder="Ex.: ADP Matriz">
        </div>
        <div class="col-md-4">
          <label class="mb-1">Base URL ADP</label>
          <input class="form-control form-control-sm" id="adp_cfg_base_url" placeholder="http://127.0.0.1:4789">
        </div>
        <div class="col-md-2">
          <label class="mb-1">Token Type</label>
          <select class="form-control form-control-sm" id="adp_cfg_token_type">
            <option value="x_adp_api_token">x_adp_api_token</option>
            <option value="bearer">bearer</option>
            <option value="query">query</option>
            <option value="none">none</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="mb-1">Token Global (opcional em edição)</label>
          <input class="form-control form-control-sm" id="adp_cfg_global_token" placeholder="Deixe vazio para manter o atual">
        </div>
      </div>
      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-dark" id="btn-adp-salvar-config">Salvar configuração ADP</button>
      </div>
      <small class="text-muted">Cada empresa/tenant pode ter múltiplas configurações ADP e escolher qual usar em cada balança.</small>
    </div>

    <div class="form-row">
      <div class="col-md-4">
        <label>Configuração Global ADP</label>
        <select class="form-control" name="integrador_config_id" id="integrador_config_id">
          <option value="">Selecione...</option>
        </select>
        <small class="text-muted" id="adp-config-token-mask"></small>
      </div>
      <div class="col-md-8 d-flex align-items-end">
        <button type="button" class="btn btn-outline-primary mr-2" id="btn-adp-testar">Testar conexão</button>
        <button type="button" class="btn btn-primary mr-2" id="btn-adp-buscar">Buscar dispositivos</button>
        <button type="button" class="btn btn-outline-secondary" id="btn-adp-sync">Sincronizar local</button>
      </div>
    </div>

    <hr>

    <div class="form-row">
      <div class="col-md-6">
        <label>Balanças encontradas</label>
        <select class="form-control" id="adp_scale_select"></select>
      </div>
      <div class="col-md-6">
        <label>Câmeras encontradas (multi)</label>
        <select class="form-control" id="adp_camera_select" multiple size="4"></select>
      </div>
    </div>

    <div class="form-row mt-2">
      <div class="col-md-3">
        <label>UUID Balança ADP</label>
        <input class="form-control" name="adp_scale_uuid" id="adp_scale_uuid" value="{{ old('adp_scale_uuid', $balanca->adp_scale_uuid ?? '') }}">
      </div>
      <div class="col-md-3">
        <label>Integrador</label>
        <input class="form-control" name="integrador" id="integrador" value="{{ old('integrador', $balanca->integrador ?? 'adp') }}">
      </div>
      <div class="col-md-3">
        <label>Porta serial</label>
        <input class="form-control" name="porta_serial" id="porta_serial" value="{{ old('porta_serial', $balanca->porta_serial ?? '') }}">
      </div>
      <div class="col-md-3">
        <label>Baud rate</label>
        <input class="form-control" name="baud_rate" id="baud_rate" value="{{ old('baud_rate', $balanca->baud_rate ?? '') }}">
      </div>
    </div>

    <div class="form-row mt-2">
      <div class="col-md-6">
        <label>UUIDs das câmeras ADP (csv)</label>
        <input class="form-control" name="adp_camera_uuids" id="adp_camera_uuids" value="{{ old('adp_camera_uuids', $balanca->adp_camera_uuids ?? '') }}">
      </div>
      <div class="col-md-3">
        <label>Quantidade de câmeras</label>
        <input class="form-control" type="number" min="0" name="quantidade_cameras" id="quantidade_cameras" value="{{ old('quantidade_cameras', $balanca->quantidade_cameras ?? 0) }}">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" id="usa_cameras" name="usa_cameras" {{ old('usa_cameras', $balanca->usa_cameras ?? false) ? 'checked' : '' }}>
          <label class="form-check-label" for="usa_cameras">Usa câmeras</label>
        </div>
      </div>
    </div>

    <div class="mt-3">
      <small class="text-muted">A view chama o Laravel, e o Laravel chama o ADP. Nenhum token é exposto no frontend.</small>
      <pre id="adp-discovery-log" class="mt-2 p-2" style="max-height: 180px; overflow:auto; background:#f8f9fa; border:1px solid #e9ecef;">Aguardando ação...</pre>
    </div>
  </div>
</div>

<script src="{{ asset('js/adp-device-discovery.js') }}"></script>

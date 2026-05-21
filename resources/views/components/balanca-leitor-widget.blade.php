
<style>
.adp-balanca-camera-thumbs{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start;}
.adp-camera-thumb-btn{display:flex;align-items:center;gap:8px;width:220px;max-width:100%;border:1px solid #d7dce6;background:#fff;border-radius:8px;padding:6px;text-align:left;cursor:pointer;transition:.15s ease;}
.adp-camera-thumb-btn:hover{border-color:#3699ff;box-shadow:0 3px 10px rgba(54,153,255,.15);}
.adp-camera-thumb-img{width:72px!important;height:48px!important;max-width:72px!important;max-height:48px!important;object-fit:cover;border-radius:6px;background:#111827;display:block;}
.adp-camera-thumb-title{font-size:12px;font-weight:700;color:#2f3542;line-height:1.2;display:block;}
.adp-camera-thumb-meta{font-size:10px;color:#6c757d;line-height:1.2;display:block;word-break:break-all;}
.adp-camera-empty{font-size:12px;border-radius:8px;padding:8px 10px;margin:0;}
.adp-balanca-image-modal{display:none;position:fixed;z-index:99999;inset:0;background:rgba(12,18,32,.86);align-items:center;justify-content:center;padding:24px;}
.adp-balanca-image-modal.show{display:flex;}
.adp-balanca-image-dialog{max-width:min(1100px,96vw);max-height:94vh;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.35);}
.adp-balanca-image-header{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#f4f7fb;border-bottom:1px solid #e5e9f2;}
.adp-balanca-image-header button{border:0;background:#dc3545;color:#fff;border-radius:6px;width:32px;height:32px;font-size:20px;line-height:1;}
.adp-balanca-image-body{padding:10px;text-align:center;background:#0b1220;}
.adp-balanca-image-body img{max-width:94vw;max-height:82vh;object-fit:contain;}
</style>

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
        <div class="adp-balanca-camera-thumbs mt-2" data-camera-preview-list></div>
      </div>
    </div>

    <div class="mt-2">
      <button class="btn btn-success btn-sm" data-action="open">Conectar</button>
      <button class="btn btn-secondary btn-sm" data-action="close">Desconectar</button>
      <button class="btn btn-primary btn-sm" data-action="read">Capturar peso</button>
      <button class="btn btn-info btn-sm" data-action="evidence">Capturar evidência</button>
    </div>

    <pre class="mt-2 p-2 bg-light border" style="max-height:120px;overflow:auto;font-size:11px;" data-balanca-log>Aguardando ação...</pre>

    <input type="hidden" id="{{ $inputBalancaId ?? 'balanca_config_id' }}" name="balanca_config_id">
    <input type="hidden" id="{{ $inputPesoOrigemId ?? 'peso_origem' }}" name="peso_origem" value="balanca">
    <input type="hidden" id="{{ $inputEvidenceId ?? 'balanca_evidence_json' }}" name="balanca_evidence_json">
  </div>
</div>

<div class="adp-balanca-image-modal" data-balanca-image-modal>
  <div class="adp-balanca-image-dialog">
    <div class="adp-balanca-image-header">
      <strong data-balanca-image-title>Imagem da câmera</strong>
      <button type="button" data-balanca-image-close aria-label="Fechar">&times;</button>
    </div>
    <div class="adp-balanca-image-body">
      <img data-balanca-image-img alt="Imagem da câmera ADP">
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header">Integração ADP</div>
  <div class="card-body">
    <div class="form-row">
      <div class="col"><label>UUID Balança ADP</label><input class="form-control" name="adp_scale_uuid" value="{{ old('adp_scale_uuid', $balanca->adp_scale_uuid ?? '') }}"></div>
      <div class="col"><label>Integrador</label><input class="form-control" name="integrador" value="{{ old('integrador', $balanca->integrador ?? 'adp') }}"></div>
      <div class="col"><label>Porta serial</label><input class="form-control" name="porta_serial" value="{{ old('porta_serial', $balanca->porta_serial ?? '') }}"></div>
      <div class="col"><label>Baud</label><input class="form-control" name="baud_rate" value="{{ old('baud_rate', $balanca->baud_rate ?? '') }}"></div>
    </div>
    <small class="text-muted">Tokens são aplicados apenas no backend.</small>
  </div>
</div>

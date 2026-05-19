<div class="card mt-3">
  <div class="card-header">Integração ADP</div>
  <div class="card-body">
    <div class="form-row mb-3">
      <div class="col-md-8">
        <label>Token Global da API (mascarado)</label>
        <input id="adp_global_token_masked" class="form-control" readonly placeholder="Carregando...">
        <small class="text-muted">Token Global da API utilizado para autenticar chamadas internas e integrações externas. Caso seja regenerado, sistemas que usam o token anterior precisarão ser atualizados.</small>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button type="button" id="btnRegenerarTokenGlobalAdp" class="btn btn-warning btn-block">Gerar Novo Token Global</button>
      </div>
    </div>
    <div class="form-row">
      <div class="col"><label>UUID Balança ADP</label><input class="form-control" name="adp_scale_uuid" value="{{ old('adp_scale_uuid', $balanca->adp_scale_uuid ?? '') }}"></div>
      <div class="col"><label>Integrador</label><input class="form-control" name="integrador" value="{{ old('integrador', $balanca->integrador ?? 'adp') }}"></div>
      <div class="col"><label>Porta serial</label><input class="form-control" name="porta_serial" value="{{ old('porta_serial', $balanca->porta_serial ?? '') }}"></div>
      <div class="col"><label>Baud</label><input class="form-control" name="baud_rate" value="{{ old('baud_rate', $balanca->baud_rate ?? '') }}"></div>
    </div>
    <small class="text-muted">Tokens são aplicados apenas no backend.</small>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var configIdInput = document.querySelector('[name="integrador_config_id"]');
  if (!configIdInput || !configIdInput.value) return;

  var configId = configIdInput.value;
  var tokenInput = document.getElementById('adp_global_token_masked');
  var btn = document.getElementById('btnRegenerarTokenGlobalAdp');

  function loadTokenInfo() {
    fetch('/adp/discovery/token-info?integrador_config_id=' + encodeURIComponent(configId))
      .then(function(r){ return r.json(); })
      .then(function(data){
        tokenInput.value = data.token_masked || 'Não configurado';
      })
      .catch(function(){ tokenInput.value = 'Erro ao carregar'; });
  }

  btn.addEventListener('click', function(){
    if (!confirm('Tem certeza que deseja gerar um novo Token Global da API? O token atual deixará de funcionar e integrações externas precisarão ser atualizadas.')) return;
    fetch('/adp/discovery/regenerate-token?integrador_config_id=' + encodeURIComponent(configId), {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
    })
      .then(function(r){ return r.json(); })
      .then(function(data){
        alert(data.message || 'Token regenerado.');
        loadTokenInfo();
      })
      .catch(function(){ alert('Falha ao regenerar token global.'); });
  });

  loadTokenInfo();
});
</script>

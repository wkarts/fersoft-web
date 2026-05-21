@extends('default.layout')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .adp-page { --adp-primary:#3699ff; --adp-dark:#111827; --adp-soft:#f5f8fa; }
    .adp-hero { border-radius:14px; background:linear-gradient(135deg,#111827 0%,#1f3b73 55%,#3699ff 100%); color:#fff; padding:24px; box-shadow:0 12px 30px rgba(17,24,39,.18); }
    .adp-hero h3 { color:#fff; margin-bottom:4px; font-weight:700; }
    .adp-hero small { color:rgba(255,255,255,.78); }
    .adp-stat-card { border:1px solid #edf0f5; border-radius:14px; background:#fff; padding:18px 20px; min-height:86px; height:100%; box-shadow:0 6px 18px rgba(15,23,42,.04); display:flex; align-items:center; gap:14px; }
    .adp-stat-card .value { font-size:30px; font-weight:800; color:#111827; line-height:1; min-width:46px; text-align:left; }
    .adp-stat-card .label { color:#7e8299; font-size:13px; margin-top:0; line-height:1.25; white-space:normal; }
    .adp-stat-card .label strong { display:block; color:#1f2937; font-size:14px; }
    .adp-section { border:1px solid #e9eef7; border-radius:14px; background:#fff; box-shadow:0 6px 18px rgba(15,23,42,.04); }
    .adp-section-header { padding:14px 18px; border-bottom:1px solid #eef2f7; display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .adp-section-title { margin:0; font-size:16px; font-weight:700; color:#1f2937; }
    .adp-download-card { border:1px solid #e8edf6; border-radius:12px; padding:14px; background:#fbfdff; height:100%; }
    .adp-download-card strong { display:block; color:#111827; }
    .adp-download-card small { display:block; min-height:34px; color:#7e8299; }
    .adp-config-row { cursor:pointer; }
    .adp-config-row:hover { background:#f7fbff; }
    .adp-pill { border-radius:999px; padding:5px 9px; font-size:11px; font-weight:700; }
    .adp-pill-ok { background:#e8fff3; color:#0b8f55; }
    .adp-pill-off { background:#fff4de; color:#b26a00; }
    .adp-log { max-height:190px; overflow:auto; background:#0b1220; color:#d1e7ff; border-radius:10px; font-size:11px; padding:12px; }
</style>

<div class="container-fluid adp-page">
    <div class="adp-hero mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h3>A.D.P. - All Driver Platform</h3>
                <small>Centralize a conexão do ERP com balanças, câmeras e futuros dispositivos da empresa.</small>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="{{ url('/balancas') }}" class="btn btn-light btn-sm mr-1">Balanças</a>
                <a href="{{ url('/adp/cameras') }}" class="btn btn-light btn-sm mr-1">Câmeras</a>
                <a href="{{ url('/balancas/balanca') }}" class="btn btn-outline-light btn-sm">Teste de Balança</a>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="adp-stat-card">
                <div class="value">{{ $configsTotal ?? (($configs ?? collect())->count()) }}</div>
                <div class="label"><strong>A.D.P.</strong>cadastrados</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="adp-stat-card">
                <div class="value">{{ $balancasCount ?? 0 }}</div>
                <div class="label"><strong>Balanças</strong>ADP ativas</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="adp-stat-card">
                <div class="value">{{ $camerasCount ?? 0 }}</div>
                <div class="label"><strong>Câmeras</strong>ADP ativas</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="adp-stat-card">
                <div class="value">{{ ($devices ?? collect())->count() }}</div>
                <div class="label"><strong>Dispositivos</strong>sincronizados</div>
            </div>
        </div>
    </div>

    <div class="adp-section mb-4">
        <div class="adp-section-header">
            <h5 class="adp-section-title">Downloads do aplicativo ADP Desktop</h5>
            <small class="text-muted">Instaladores disponíveis para implantação do A.D.P.</small>
        </div>
        <div class="p-3">
            <div class="row">
                @php
                    $downloadItems = [
                        ['key' => 'windows_x86', 'title' => 'Windows x86 / 32 bits', 'desc' => 'Instalador para computadores antigos ou Windows 32 bits.'],
                        ['key' => 'windows_x64', 'title' => 'Windows x64 / 64 bits', 'desc' => 'Instalador recomendado para a maioria dos computadores Windows.'],
                        ['key' => 'macos', 'title' => 'macOS', 'desc' => 'Pacote para computadores Apple compatíveis.'],
                        ['key' => 'linux_debian', 'title' => 'Linux Debian Desktop', 'desc' => 'Pacote para Debian/Ubuntu e derivados.'],
                    ];
                @endphp
                @foreach($downloadItems as $item)
                    @php($url = $downloads[$item['key']] ?? '#')
                    <div class="col-md-3 mb-3">
                        <div class="adp-download-card">
                            <strong>{{ $item['title'] }}</strong>
                            <small>{{ $item['desc'] }}</small>
                            @if($url && $url !== '#')
                                <a href="{{ $url }}" class="btn btn-primary btn-sm btn-block" target="_blank" rel="noopener">Download</a>
                            @else
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-block" disabled>Não configurado</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="adp-section h-100">
                <div class="adp-section-header">
                    <h5 class="adp-section-title">A.D.P. cadastrados</h5>
                    <button type="button" class="btn btn-success btn-sm" data-adp-external-new>Nova configuração</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Descrição</th>
                            <th>Base URL</th>
                            <th>Token</th>
                            <th>Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($configs as $config)
                            <tr class="adp-config-row"
                                data-config-id="{{ $config->id }}"
                                data-config-descricao="{{ $config->descricao }}"
                                data-config-base-url="{{ rtrim($config->base_url, '/') }}"
                                data-config-token-type="{{ $config->global_token_type ?: 'x_adp_api_token' }}">
                                <td>#{{ $config->id }}</td>
                                <td>
                                    <strong>{{ $config->descricao }}</strong><br>
                                    <small class="text-muted">Timeout: {{ (int) ($config->timeout_ms ?? 5000) }}ms</small>
                                </td>
                                <td><small>{{ rtrim($config->base_url, '/') }}</small></td>
                                <td><small>{{ method_exists($config, 'tokenMascarado') ? ($config->tokenMascarado() ?: 'Sem token') : 'Protegido' }}</small></td>
                                <td>
                                    @if($config->ativo)
                                        <span class="adp-pill adp-pill-ok">Ativo</span>
                                    @else
                                        <span class="adp-pill adp-pill-off">Inativo</span>
                                    @endif
                                </td>
                                <td class="text-right" style="white-space:nowrap">
                                    <button type="button" class="btn btn-outline-primary btn-xs" data-adp-config-edit="{{ $config->id }}">Editar</button>
                                    <button type="button" class="btn btn-outline-dark btn-xs" data-adp-config-test="{{ $config->id }}">Testar</button>
                                    <button type="button" class="btn btn-outline-warning btn-xs" data-adp-config-delete="{{ $config->id }}">Inativar</button>
                                    <button type="button" class="btn btn-outline-danger btn-xs" data-adp-config-force-delete="{{ $config->id }}">Excluir definitivo</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma configuração ADP cadastrada.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="adp-section h-100 adp-cadastro-guided"
                 data-default-integrador-config-id=""
                 data-default-scale-uuid=""
                 data-default-camera-uuids="[]">
                <div class="adp-section-header">
                    <h5 class="adp-section-title">Cadastro e discovery</h5>
                    <small class="text-muted">URL, token e dispositivos</small>
                </div>
                <div class="p-3">
                    <div class="form-group mb-2">
                        <label>Configuração Global ADP</label>
                        <select class="form-control form-control-sm" data-adp="integrador-config-id">
                            <option value="">Selecione...</option>
                        </select>
                        <small class="text-muted" data-adp="config-token-mask"></small>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6">
                            <label>Descrição</label>
                            <input class="form-control form-control-sm" data-adp="cfg-descricao" placeholder="Ex.: ADP Matriz">
                        </div>
                        <div class="col-md-6">
                            <label>Base URL</label>
                            <input class="form-control form-control-sm" data-adp="cfg-base-url" placeholder="http://127.0.0.1:4789">
                        </div>
                    </div>
                    <div class="form-row mt-2">
                        <div class="col-md-5">
                            <label>Token Type</label>
                            <select class="form-control form-control-sm" data-adp="cfg-token-type">
                                <option value="x_adp_api_token">X-ADP-API-TOKEN</option>
                                <option value="bearer">Bearer</option>
                                <option value="query">Query string</option>
                                <option value="none">Sem token</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label>Token Global</label>
                            <input class="form-control form-control-sm" data-adp="cfg-global-token" placeholder="Deixe vazio para manter o atual">
                        </div>
                    </div>
                    <div class="mt-3 d-flex flex-wrap">
                        <button type="button" class="btn btn-dark btn-sm mr-2 mb-2" data-adp="btn-salvar-config">Salvar/Atualizar</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm mr-2 mb-2" data-adp="btn-nova-config">Nova</button>
                        <button type="button" class="btn btn-outline-danger btn-sm mr-2 mb-2" data-adp="btn-excluir-config">Excluir/Inativar</button>
                    </div>
                    <hr>
                    <div class="d-flex flex-wrap mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2" data-adp="btn-testar">Testar conexão</button>
                        <button type="button" class="btn btn-primary btn-sm mr-2 mb-2" data-adp="btn-buscar">Buscar dispositivos</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-2" data-adp="btn-sync">Sincronizar dispositivos</button>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6">
                            <label>Balanças encontradas</label>
                            <select class="form-control form-control-sm" data-adp="scale-select"></select>
                        </div>
                        <div class="col-md-6">
                            <label>Câmeras encontradas</label>
                            <select class="form-control form-control-sm" data-adp="camera-select" multiple size="4"></select>
                        </div>
                    </div>

                    <input type="hidden" data-adp="scale-uuid">
                    <input type="hidden" data-adp="integrador">
                    <input type="hidden" data-adp="porta-serial">
                    <input type="hidden" data-adp="baud-rate">
                    <input type="hidden" data-adp="camera-uuids">
                    <input type="hidden" data-adp="quantidade-cameras">
                    <input type="checkbox" data-adp="usa-cameras" style="display:none;">

                    <pre data-adp="log" class="adp-log mt-3">Aguardando ação...</pre>
                </div>
            </div>
        </div>
    </div>

    <div class="adp-section mb-4">
        <div class="adp-section-header">
            <h5 class="adp-section-title">Últimos dispositivos sincronizados</h5>
            <small class="text-muted">Balanças, câmeras e futuros drivers do ADP.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                <tr>
                    <th>Tipo</th>
                    <th>Nome</th>
                    <th>UUID</th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>Última atualização</th>
                </tr>
                </thead>
                <tbody>
                @forelse($devices as $device)
                    <tr>
                        <td><span class="badge badge-{{ $device->device_type === 'camera' ? 'primary' : 'success' }}">{{ $device->device_type }}</span></td>
                        <td>{{ $device->name ?: 'Dispositivo ADP' }}</td>
                        <td><small>{{ $device->device_uuid }}</small></td>
                        <td>{{ $device->driver ?: '-' }}</td>
                        <td>{{ $device->status ?: '-' }}</td>
                        <td>{{ optional($device->last_seen_at)->format('d/m/Y H:i:s') ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Nenhum dispositivo sincronizado ainda.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function scope() { return document.querySelector('.adp-cadastro-guided'); }
        function selectConfig(id) {
            const root = scope();
            if (!root) return;
            const select = root.querySelector('[data-adp="integrador-config-id"]');
            if (!select) return;
            select.value = String(id || '');
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        document.querySelectorAll('[data-adp-config-edit]').forEach(function (btn) {
            btn.addEventListener('click', function () { selectConfig(btn.dataset.adpConfigEdit); });
        });
        document.querySelectorAll('[data-adp-config-test]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                selectConfig(btn.dataset.adpConfigTest);
                setTimeout(function () {
                    const root = scope();
                    const test = root ? root.querySelector('[data-adp="btn-testar"]') : null;
                    if (test) test.click();
                }, 120);
            });
        });
        document.querySelectorAll('[data-adp-config-delete]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                selectConfig(btn.dataset.adpConfigDelete);
                setTimeout(function () {
                    const root = scope();
                    const del = root ? root.querySelector('[data-adp="btn-excluir-config"]') : null;
                    if (del) del.click();
                }, 120);
            });
        });
        document.querySelectorAll('[data-adp-config-force-delete]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = btn.dataset.adpConfigForceDelete;
                if (!confirm('Excluir definitivamente esta configuração ADP? Vínculos antigos serão desvinculados e a ação não poderá ser desfeita.')) {
                    return;
                }
                fetch('/adp/discovery/configs/delete/' + encodeURIComponent(id) + '?force=1', {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                }).then(function (r) { return r.json(); })
                  .then(function (json) {
                      if (!json.success) throw new Error(json.message || 'Falha ao excluir configuração ADP.');
                      window.location.reload();
                  })
                  .catch(function (e) { alert(e.message || e); });
            });
        });
        const newBtn = document.querySelector('[data-adp-external-new]');
        if (newBtn) {
            newBtn.addEventListener('click', function () {
                const root = scope();
                const btn = root ? root.querySelector('[data-adp="btn-nova-config"]') : null;
                if (btn) btn.click();
            });
        }
    });
</script>
<script src="{{ asset('js/adp-device-discovery.js') }}"></script>
@endsection

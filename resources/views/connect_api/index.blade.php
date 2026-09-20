@extends('default.layout')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .connect-card { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; }
    .connect-stat { min-height: 90px; }
    .connect-status { display:inline-flex; align-items:center; gap:7px; font-weight:600; }
    .connect-status::before { content:''; width:10px; height:10px; border-radius:50%; background:#9ca3af; }
    .connect-status.open::before { background:#22c55e; }
    .connect-status.connecting::before, .connect-status.awaiting_pairing::before { background:#f59e0b; }
    .connect-status.close::before, .connect-status.not_found::before, .connect-status.error::before { background:#ef4444; }
    .connect-actions .btn { margin:2px; }
    .connect-muted { color:#6b7280; font-size:.82rem; }
    .connect-instance-name { font-family:monospace; font-size:.82rem; }
    .connect-secret { letter-spacing:2px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Connect|API</h3>
            <div class="text-muted">Comunicação WhatsApp da instalação FERSOFT WEB</div>
        </div>
        @if($isSuper)
            <button id="btnOpenProvision" class="btn btn-primary" type="button">
                <i class="fa fa-plus"></i> Provisionar empresa
            </button>
        @endif
    </div>

    @php
        $total = $records->count();
        $connected = $records->where('connection_status', 'open')->count();
        $waiting = $records->whereIn('connection_status', ['awaiting_pairing','awaiting_provisioning','provisioning','connecting'])->count();
        $errors = $records->whereIn('connection_status', ['error','not_found','close'])->count();
    @endphp

    <div class="row mb-4">
        @foreach([
            ['Total', $total],
            ['Conectadas', $connected],
            ['Aguardando', $waiting],
            ['Offline/erro', $errors],
        ] as $stat)
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="connect-card connect-stat p-3">
                    <div class="connect-muted">{{ $stat[0] }}</div>
                    <div class="h2 mb-0">{{ $stat[1] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="connect-card">
        <div class="p-3 border-bottom">
            <input id="connectSearch" class="form-control" placeholder="Buscar empresa, CNPJ, instância ou telefone">
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="connectTable">
                <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Instância</th>
                    <th>WhatsApp</th>
                    <th>Status</th>
                    <th>Último evento</th>
                    <th style="min-width:340px">Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse($records as $inst)
                    <tr data-id="{{ $inst->id }}">
                        <td>
                            <strong>{{ optional($inst->empresa)->nome_fantasia ?: optional($inst->empresa)->nome ?: 'Empresa #' . $inst->empresa_id }}</strong>
                            <div class="connect-muted">ID: {{ $inst->empresa_id }}</div>
                        </td>
                        <td class="connect-instance-name">{{ $inst->instance_name }}</td>
                        <td>{{ $inst->connected_number ?: '—' }}</td>
                        <td>
                            <span class="connect-status {{ $inst->connection_status }}">
                                {{ $inst->connection_status ?: 'desconhecido' }}
                            </span>
                            @if($inst->is_blocked)
                                <span class="badge badge-danger ml-2">Bloqueada</span>
                            @endif
                            @if($inst->last_error_message)
                                <div class="connect-muted text-danger mt-1">{{ $inst->last_error_message }}</div>
                            @endif
                        </td>
                        <td class="connect-muted">
                            <div>{{ optional($inst->last_event_at)->format('d/m/Y H:i:s') ?: '—' }}</div>
                            @if($inst->webhook_configured_at)
                                <div class="mt-1"><span class="badge badge-success">Webhook configurado</span></div>
                            @else
                                <div class="mt-1"><span class="badge badge-warning">Webhook pendente</span></div>
                            @endif
                            @if($inst->webhook_last_received_at)
                                <div class="mt-1">Último webhook: {{ optional($inst->webhook_last_received_at)->format('d/m/Y H:i:s') }}</div>
                            @endif
                        </td>
                        <td class="connect-actions">
                            @if(!$inst->provisioned_at)
                                @if($isSuper)
                                    <button
                                        class="btn btn-sm btn-primary js-provision-existing"
                                        data-empresa="{{ $inst->empresa_id }}"
                                        data-nome="{{ optional($inst->empresa)->nome_fantasia ?: optional($inst->empresa)->nome ?: 'Empresa #' . $inst->empresa_id }}"
                                    >Provisionar</button>
                                @endif
                            @else
                                <button class="btn btn-sm btn-light-primary js-status">Status</button>
                                <button class="btn btn-sm btn-light-success js-qr">QR Code</button>
                                <button class="btn btn-sm btn-light-success js-pair">Código</button>
                                <button class="btn btn-sm btn-light-warning js-test">Testar</button>
                                <button class="btn btn-sm btn-light-secondary js-restart">Reiniciar</button>
                                @if($isSuper)
                                    <button class="btn btn-sm btn-outline-secondary js-sync-webhook">Sincronizar webhook</button>
                                    @if(in_array($inst->connection_status, ['not_found','error']))
                                        <button
                                            class="btn btn-sm btn-outline-primary js-reprovision"
                                            data-empresa="{{ $inst->empresa_id }}"
                                            data-nome="{{ optional($inst->empresa)->nome_fantasia ?: optional($inst->empresa)->nome ?: 'Empresa #' . $inst->empresa_id }}"
                                        >Reprovisionar</button>
                                    @endif
                                    <button class="btn btn-sm btn-light-danger js-block">{{ $inst->is_blocked ? 'Desbloquear' : 'Bloquear' }}</button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">Nenhuma instância Connect|API cadastrada nesta instalação.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($isSuper)
<div class="modal fade" id="modalProvision" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Provisionar Connect|API</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <input type="hidden" id="connectProvisionMode" value="provision">
                <input type="hidden" id="connectProvisionInstanceId" value="">

                <div class="form-group">
                    <label>Empresa</label>
                    <select id="connectCompany" class="form-control"></select>
                </div>

                <div class="form-group mb-2">
                    <label>Número do WhatsApp</label>
                    <input
                        type="tel"
                        id="connectProvisionNumber"
                        class="form-control"
                        placeholder="5575999999999"
                        autocomplete="tel"
                    >
                    <small class="text-muted">
                        Informe DDI + DDD + número. Ex.: 5575999999999.
                    </small>
                </div>

                <small class="text-muted d-block mt-3">
                    A instância será criada na Connect|API e o pareamento poderá ser concluído por código ou QR Code.
                </small>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button id="btnProvision" class="btn btn-primary">Provisionar</button>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="modalConnect" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="connectModalTitle">Conectar WhatsApp</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body" id="connectModalBody"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const headers = {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'};

    function rowId(button) {
        return button.closest('tr').dataset.id;
    }

    function show(title, html) {
        document.getElementById('connectModalTitle').textContent = title;
        document.getElementById('connectModalBody').innerHTML = html;
        $('#modalConnect').modal('show');
    }

    async function request(url, options = {}) {
        const response = await fetch(url, Object.assign({headers}, options));
        const data = await response.json();
        if (!response.ok || data.success === false) {
            throw new Error(data.error || data.message || 'Falha na operação Connect|API.');
        }
        return data;
    }

    document.getElementById('connectSearch').addEventListener('input', function () {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#connectTable tbody tr').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
        });
    });

    document.querySelectorAll('.js-status').forEach(btn => btn.onclick = async () => {
        try { await request('/connect-api/instances/' + rowId(btn) + '/status'); location.reload(); }
        catch (e) { show('Erro', e.message); }
    });

    document.querySelectorAll('.js-qr').forEach(btn => btn.onclick = async () => {
        try {
            const data = await request('/connect-api/instances/' + rowId(btn) + '/qr');
            const payload = data.data || {};
            const base64 = payload.base64 || payload.qrcode || payload.qr || '';
            show('QR Code', base64
                ? '<div class="text-center"><img style="max-width:320px;width:100%" src="' + (base64.startsWith('data:') ? base64 : 'data:image/png;base64,' + base64) + '"><p class="mt-3 text-muted">Escaneie no WhatsApp para conectar.</p></div>'
                : '<pre class="small">' + JSON.stringify(payload, null, 2) + '</pre>');
        } catch (e) { show('Erro', e.message); }
    });

    document.querySelectorAll('.js-pair').forEach(btn => btn.onclick = () => {
        const id = rowId(btn);
        show('Código de pareamento',
            '<label>Número do WhatsApp</label><input id="pairNumber" class="form-control" placeholder="5575999999999">' +
            '<button id="generatePair" class="btn btn-primary mt-3">Gerar código</button><div id="pairResult" class="mt-3"></div>');
        document.getElementById('generatePair').onclick = async () => {
            try {
                const data = await request('/connect-api/instances/' + id + '/pairing-code', {
                    method:'POST',
                    headers:Object.assign({}, headers, {'Content-Type':'application/json'}),
                    body:JSON.stringify({number:document.getElementById('pairNumber').value})
                });
                const payload = data.data || {};
                document.getElementById('pairResult').innerHTML =
                    '<div class="alert alert-success text-center"><div>Código de pareamento</div><strong class="h3 connect-secret">' +
                    (payload.pairingCode || payload.code || 'gerado') + '</strong></div>';
            } catch (e) { document.getElementById('pairResult').innerHTML='<div class="alert alert-danger">'+e.message+'</div>'; }
        };
    });

    document.querySelectorAll('.js-test').forEach(btn => btn.onclick = () => {
        const id = rowId(btn);
        show('Testar mensagem',
            '<label>Destino</label><input id="testNumber" class="form-control" placeholder="5575999999999">' +
            '<label class="mt-3">Mensagem</label><textarea id="testMessage" class="form-control">Teste de integração Connect|API - FERSOFT WEB.</textarea>' +
            '<button id="sendTest" class="btn btn-primary mt-3">Enviar</button><div id="testResult" class="mt-3"></div>');
        document.getElementById('sendTest').onclick = async () => {
            try {
                await request('/connect-api/instances/' + id + '/test', {
                    method:'POST',
                    headers:Object.assign({}, headers, {'Content-Type':'application/json'}),
                    body:JSON.stringify({number:document.getElementById('testNumber').value,message:document.getElementById('testMessage').value})
                });
                document.getElementById('testResult').innerHTML='<div class="alert alert-success">Mensagem enviada.</div>';
            } catch (e) { document.getElementById('testResult').innerHTML='<div class="alert alert-danger">'+e.message+'</div>'; }
        };
    });

    document.querySelectorAll('.js-restart').forEach(btn => btn.onclick = async () => {
        try { await request('/connect-api/instances/' + rowId(btn) + '/restart', {method:'POST'}); show('Connect|API', 'Reinicialização solicitada.'); }
        catch(e){ show('Erro', e.message); }
    });

    document.querySelectorAll('.js-sync-webhook').forEach(btn => btn.onclick = async () => {
        try {
            const data = await request('/connect-api/instances/' + rowId(btn) + '/sync-webhook', {method:'POST'});
            show('Connect|API', data.message || 'Webhook sincronizado.');
            setTimeout(() => location.reload(), 800);
        } catch(e) {
            show('Erro', e.message);
        }
    });

    document.querySelectorAll('.js-reprovision').forEach(btn => btn.onclick = () => {
        prepareProvisionModal({
            mode: 'reprovision',
            empresaId: btn.dataset.empresa,
            empresaName: btn.dataset.nome,
            instanceId: rowId(btn)
        });
    });

    document.querySelectorAll('.js-block').forEach(btn => btn.onclick = async () => {
        const blocked = btn.innerText.trim().toLowerCase().startsWith('des');
        try { await request('/connect-api/instances/' + rowId(btn) + '/' + (blocked ? 'unblock' : 'block'), {method:'POST'}); location.reload(); }
        catch(e){ show('Erro', e.message); }
    });

    document.querySelectorAll('.js-provision-existing').forEach(btn => btn.onclick = () => {
        prepareProvisionModal({
            mode: 'provision',
            empresaId: btn.dataset.empresa,
            empresaName: btn.dataset.nome
        });
    });

    @if($isSuper)
    $('#connectCompany').select2({
        dropdownParent: $('#modalProvision'),
        minimumInputLength: 1,
        ajax: {
            url: '/connect-api/companies',
            dataType: 'json',
            delay: 250,
            data: params => ({term: params.term}),
            processResults: data => ({results: data.map(item => ({
                id:item.id,
                text:(item.nome_fantasia || item.nome) + ' - ' + (item.cnpj || '')
            }))})
        }
    });

    function prepareProvisionModal({mode, empresaId = null, empresaName = '', instanceId = ''}) {
        document.getElementById('connectProvisionMode').value = mode || 'provision';
        document.getElementById('connectProvisionInstanceId').value = instanceId || '';
        document.getElementById('connectProvisionNumber').value = '';

        const company = $('#connectCompany');
        company.prop('disabled', false);

        if (empresaId) {
            const option = new Option(empresaName || ('Empresa #' + empresaId), empresaId, true, true);
            company.empty().append(option).trigger('change');
            company.prop('disabled', true);
        } else {
            company.val(null).trigger('change');
        }

        $('#modalProvision .modal-title').text(
            mode === 'reprovision' ? 'Reprovisionar Connect|API' : 'Provisionar Connect|API'
        );
        document.getElementById('btnProvision').textContent =
            mode === 'reprovision' ? 'Reprovisionar' : 'Provisionar';

        $('#modalProvision').modal('show');
    }

    document.getElementById('btnOpenProvision').onclick = () => {
        prepareProvisionModal({mode:'provision'});
    };

    document.getElementById('btnProvision').onclick = async () => {
        const empresa = $('#connectCompany').val();
        const number = document.getElementById('connectProvisionNumber').value.trim();
        const mode = document.getElementById('connectProvisionMode').value;
        const instanceId = document.getElementById('connectProvisionInstanceId').value;

        if (!empresa) {
            show('Erro', 'Selecione a empresa.');
            return;
        }

        if (!number) {
            show('Erro', 'Informe o número do WhatsApp para provisionar a instância.');
            return;
        }

        const url = mode === 'reprovision'
            ? '/connect-api/instances/' + instanceId + '/reprovision'
            : '/connect-api/provision/' + empresa;

        const button = document.getElementById('btnProvision');
        button.disabled = true;

        try {
            const data = await request(url, {
                method:'POST',
                headers:Object.assign({}, headers, {'Content-Type':'application/json'}),
                body:JSON.stringify({number})
            });

            $('#modalProvision').modal('hide');
            show('Connect|API', data.message || 'Instância provisionada com sucesso.');
            setTimeout(() => location.reload(), 900);
        } catch(e) {
            show('Erro', e.message);
        } finally {
            button.disabled = false;
        }
    };
    @endif
})();
</script>
@endsection

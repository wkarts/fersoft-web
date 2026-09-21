@extends('default.layout')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    $statusLabels = [
        'open' => 'Conectada',
        'connecting' => 'Conectando',
        'close' => 'Desconectada',
        'closed' => 'Desconectada',
        'awaiting_pairing' => 'Aguardando pareamento',
        'awaiting_provisioning' => 'Aguardando provisionamento',
        'provisioning' => 'Provisionando',
        'not_found' => 'Não encontrada na Connect|API',
        'error' => 'Erro',
        'blocked' => 'Bloqueada',
        'unknown' => 'Desconhecido',
    ];

    $total = $companies->count();
    $connected = $instancesByCompany->filter(fn ($inst) => $inst && $inst->connection_status === 'open')->count();
    $waiting = $companies->filter(function ($empresa) use ($instancesByCompany) {
        $inst = $instancesByCompany->get($empresa->id);
        return !$inst
            || !$inst->provisioned_at
            || in_array($inst->connection_status, ['awaiting_pairing', 'awaiting_provisioning', 'provisioning', 'connecting'], true);
    })->count();
    $errors = $instancesByCompany->filter(
        fn ($inst) => $inst && in_array($inst->connection_status, ['error', 'not_found', 'close', 'closed'], true)
    )->count();
@endphp

<style>
    .connect-card { border:1px solid #e3e8ef; border-radius:10px; background:#fff; }
    .connect-stat { min-height:88px; }
    .connect-muted { color:#6b7280; font-size:.82rem; }
    .connect-instance-name { font-family:monospace; font-size:.82rem; word-break:break-all; }
    .connect-status { display:inline-flex; align-items:center; gap:7px; font-weight:600; }
    .connect-status::before { content:''; width:10px; height:10px; border-radius:50%; background:#9ca3af; flex:none; }
    .connect-status.open::before { background:#22c55e; }
    .connect-status.connecting::before,
    .connect-status.awaiting_pairing::before,
    .connect-status.awaiting_provisioning::before,
    .connect-status.provisioning::before { background:#f59e0b; }
    .connect-status.error::before,
    .connect-status.not_found::before,
    .connect-status.close::before,
    .connect-status.closed::before { background:#ef4444; }
    .connect-status.blocked::before { background:#7f1d1d; }
    .connect-actions { display:flex; flex-wrap:wrap; gap:5px; min-width:330px; }
    .connect-actions .btn { margin:0; }
    .connect-table td { vertical-align:middle; }
    .connect-company-sub { display:flex; flex-wrap:wrap; gap:8px; }
    .connect-modal[hidden] { display:none !important; }
    .connect-modal {
        position:fixed;
        inset:0;
        z-index:120000;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:24px;
    }
    .connect-modal-backdrop {
        position:absolute;
        inset:0;
        background:rgba(17,24,39,.58);
    }
    .connect-modal-dialog {
        position:relative;
        width:min(560px, calc(100vw - 32px));
        max-height:calc(100vh - 48px);
        overflow:auto;
        background:#fff;
        border-radius:12px;
        box-shadow:0 24px 70px rgba(0,0,0,.28);
    }
    .connect-modal-header,
    .connect-modal-footer { padding:16px 18px; display:flex; align-items:center; gap:10px; }
    .connect-modal-header { border-bottom:1px solid #e5e7eb; justify-content:space-between; }
    .connect-modal-footer { border-top:1px solid #e5e7eb; justify-content:flex-end; }
    .connect-modal-body { padding:18px; }
    .connect-modal-close {
        border:0; background:transparent; font-size:24px; line-height:1; color:#6b7280; cursor:pointer;
    }
    .connect-code {
        font-family:monospace;
        font-size:1.6rem;
        letter-spacing:.12rem;
        font-weight:700;
        text-align:center;
        padding:15px;
        border-radius:8px;
        background:#f3f4f6;
    }
    .connect-qr { display:block; max-width:330px; width:100%; margin:0 auto; }
    .connect-inline-error { color:#b91c1c; font-size:.86rem; margin-top:8px; }
    .connect-company-inactive { opacity:.72; }
    @media (max-width: 900px) {
        .connect-actions { min-width:250px; }
        .connect-modal { padding:12px; }
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div class="mb-2">
            <h3 class="mb-1">Connect|API</h3>
            <div class="text-muted">
                Gerenciamento das instâncias WhatsApp desta instalação FERSOFT WEB
                @if($isSuper)
                    <span class="badge badge-primary ml-2">Master</span>
                @endif
            </div>
        </div>

        @if($isSuper)
            <button id="btnOpenProvision" type="button" class="btn btn-primary mb-2">
                <i class="fa fa-plus"></i> Provisionar empresa
            </button>
        @endif
    </div>

    <div class="row mb-4">
        @foreach([
            ['Empresas', $total],
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
            <input
                id="connectSearch"
                class="form-control"
                placeholder="Buscar empresa, CNPJ, ID, instância ou telefone"
                autocomplete="off"
            >
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 connect-table" id="connectTable">
                <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Instância</th>
                    <th>WhatsApp</th>
                    <th>Status</th>
                    <th>Último evento</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse($companies as $empresa)
                    @php
                        $inst = $instancesByCompany->get($empresa->id);
                        $companyName = $empresa->nome_fantasia ?: $empresa->nome ?: 'Empresa #' . $empresa->id;
                        $status = $inst ? ($inst->connection_status ?: 'unknown') : 'not_provisioned';
                        $statusLabel = $inst
                            ? ($statusLabels[$status] ?? $status)
                            : 'Não provisionada';
                        $companyPhone = preg_replace('/\D+/', '', (string) $empresa->telefone);
                    @endphp
                    <tr
                        class="{{ (int) $empresa->status === 0 ? 'connect-company-inactive' : '' }}"
                        data-search="{{ strtolower($companyName . ' ' . $empresa->cnpj . ' ' . $empresa->id . ' ' . ($inst->instance_name ?? '') . ' ' . $empresa->telefone) }}"
                    >
                        <td>
                            <strong>{{ $companyName }}</strong>
                            <div class="connect-company-sub connect-muted mt-1">
                                <span>ID: {{ $empresa->id }}</span>
                                @if($empresa->cnpj)
                                    <span>{{ $empresa->cnpj }}</span>
                                @endif
                                @if((int) $empresa->status === 0)
                                    <span class="badge badge-secondary">Inativa</span>
                                @endif
                            </div>
                        </td>

                        <td class="connect-instance-name">
                            {{ $inst ? $inst->instance_name : '—' }}
                        </td>

                        <td>
                            @if($inst && $inst->connected_number)
                                {{ $inst->connected_number }}
                            @elseif($companyPhone)
                                <span class="connect-muted">{{ $companyPhone }}</span>
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @if($inst)
                                <span class="connect-status {{ $status }}">{{ $statusLabel }}</span>
                                @if($inst->is_blocked)
                                    <span class="badge badge-danger ml-2">Bloqueada</span>
                                @endif
                                @if($inst->provisioned_at)
                                    <div class="connect-muted mt-1">
                                        Webhook:
                                        {{ $inst->webhook_configured_at ? 'configurado' : 'pendente' }}
                                    </div>
                                @endif
                                @if($inst->last_error_message)
                                    <div class="connect-inline-error">{{ $inst->last_error_message }}</div>
                                @endif
                            @else
                                <span class="connect-status">Não provisionada</span>
                            @endif
                        </td>

                        <td class="connect-muted">
                            @if($inst && $inst->last_event_at)
                                {{ optional($inst->last_event_at)->format('d/m/Y H:i:s') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            <div class="connect-actions">
                                @if(!$inst || !$inst->provisioned_at)
                                    @if($isSuper)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary js-open-provision"
                                            data-company-id="{{ $empresa->id }}"
                                            data-company-name="{{ $companyName }}"
                                            data-company-phone="{{ $companyPhone }}"
                                            data-instance-id="{{ $inst ? $inst->id : '' }}"
                                        >Provisionar</button>
                                    @else
                                        <span class="connect-muted">Aguardando provisionamento</span>
                                    @endif
                                @else
                                    <button type="button" class="btn btn-sm btn-light-primary js-status" data-id="{{ $inst->id }}">Status</button>
                                    <button type="button" class="btn btn-sm btn-light-success js-qr" data-id="{{ $inst->id }}">QR Code</button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light-success js-pair"
                                        data-id="{{ $inst->id }}"
                                        data-phone="{{ $companyPhone ?: $inst->connected_number }}"
                                    >Código</button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light-warning js-test"
                                        data-id="{{ $inst->id }}"
                                        data-phone="{{ $companyPhone ?: $inst->connected_number }}"
                                    >Testar</button>
                                    <button type="button" class="btn btn-sm btn-light-secondary js-restart" data-id="{{ $inst->id }}">Reiniciar</button>
                                    <button type="button" class="btn btn-sm btn-light-secondary js-disconnect" data-id="{{ $inst->id }}">Desconectar</button>

                                    @if($isSuper)
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-sync-webhook" data-id="{{ $inst->id }}">Webhook</button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary js-reprovision"
                                            data-id="{{ $inst->id }}"
                                            data-company-id="{{ $empresa->id }}"
                                            data-company-name="{{ $companyName }}"
                                            data-company-phone="{{ $companyPhone }}"
                                        >Reprovisionar</button>
                                        <button
                                            type="button"
                                            class="btn btn-sm {{ $inst->is_blocked ? 'btn-outline-success' : 'btn-outline-danger' }} js-block"
                                            data-id="{{ $inst->id }}"
                                            data-blocked="{{ $inst->is_blocked ? '1' : '0' }}"
                                        >{{ $inst->is_blocked ? 'Desbloquear' : 'Bloquear' }}</button>
                                        <button type="button" class="btn btn-sm btn-danger js-delete" data-id="{{ $inst->id }}" data-company-name="{{ $companyName }}">Excluir</button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            Nenhuma empresa disponível para gerenciamento.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($isSuper)
<div id="connectProvisionModal" class="connect-modal" hidden aria-hidden="true">
    <div class="connect-modal-backdrop" data-close-modal="connectProvisionModal"></div>
    <div class="connect-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="connectProvisionTitle">
        <div class="connect-modal-header">
            <h5 id="connectProvisionTitle" class="mb-0">Provisionar Connect|API</h5>
            <button type="button" class="connect-modal-close" data-close-modal="connectProvisionModal">&times;</button>
        </div>

        <div class="connect-modal-body">
            <input type="hidden" id="connectProvisionMode" value="provision">
            <input type="hidden" id="connectProvisionInstanceId" value="">

            <div class="form-group">
                <label for="connectProvisionCompany">Empresa</label>
                <select id="connectProvisionCompany" class="form-control">
                    <option value="">Selecione a empresa</option>
                    @foreach($companies as $empresa)
                        @php
                            $optionName = $empresa->nome_fantasia ?: $empresa->nome ?: 'Empresa #' . $empresa->id;
                            $optionPhone = preg_replace('/\D+/', '', (string) $empresa->telefone);
                        @endphp
                        <option
                            value="{{ $empresa->id }}"
                            data-phone="{{ $optionPhone }}"
                        >
                            ID {{ $empresa->id }} - {{ $optionName }}{{ $empresa->cnpj ? ' - ' . $empresa->cnpj : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mb-1">
                <label for="connectProvisionNumber">Número do WhatsApp</label>
                <input
                    type="tel"
                    id="connectProvisionNumber"
                    class="form-control"
                    placeholder="5575999999999"
                    autocomplete="tel"
                >
                <small class="text-muted">Informe DDI + DDD + número. Ex.: 5575999999999.</small>
            </div>

            <div id="connectProvisionError" class="connect-inline-error" hidden></div>
        </div>

        <div class="connect-modal-footer">
            <button type="button" class="btn btn-light" data-close-modal="connectProvisionModal">Cancelar</button>
            <button type="button" id="btnProvisionSubmit" class="btn btn-primary">Provisionar</button>
        </div>
    </div>
</div>
@endif

<div id="connectActionModal" class="connect-modal" hidden aria-hidden="true">
    <div class="connect-modal-backdrop" data-close-modal="connectActionModal"></div>
    <div class="connect-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="connectActionTitle">
        <div class="connect-modal-header">
            <h5 id="connectActionTitle" class="mb-0">Connect|API</h5>
            <button type="button" class="connect-modal-close" data-close-modal="connectActionModal">&times;</button>
        </div>
        <div id="connectActionBody" class="connect-modal-body"></div>
        <div class="connect-modal-footer">
            <button type="button" id="connectActionCancel" class="btn btn-light">Cancelar</button>
            <button type="button" id="connectActionConfirm" class="btn btn-primary">OK</button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const csrfElement = document.querySelector('meta[name="csrf-token"]');
    const csrf = csrfElement ? csrfElement.getAttribute('content') : '';
    const jsonHeaders = {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    };

    const byId = (id) => document.getElementById(id);

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openModal(id) {
        const modal = byId(id);
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        const modal = byId(id);
        if (!modal) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');

        if (!document.querySelector('.connect-modal:not([hidden])')) {
            document.body.style.overflow = '';
        }
    }

    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal(button.dataset.closeModal));
    });

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                ...jsonHeaders,
                ...(options.headers || {})
            }
        });

        const raw = await response.text();
        let data = {};

        if (raw) {
            try {
                data = JSON.parse(raw);
            } catch (_) {
                data = { error: raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 500) };
            }
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.error || data.message || ('Falha HTTP ' + response.status + ' na Connect|API.'));
        }

        return data;
    }

    function setButtonBusy(button, busy, label = null) {
        if (!button) return;
        if (busy) {
            button.dataset.originalText = button.textContent;
            button.disabled = true;
            button.textContent = label || 'Aguarde...';
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText || button.textContent;
        }
    }

    function showMessage(title, html, options = {}) {
        const modal = byId('connectActionModal');
        const body = byId('connectActionBody');
        const confirm = byId('connectActionConfirm');
        const cancel = byId('connectActionCancel');

        byId('connectActionTitle').textContent = title || 'Connect|API';
        body.innerHTML = html || '';
        confirm.textContent = options.confirmText || 'Fechar';
        cancel.hidden = options.showCancel !== true;

        confirm.onclick = async () => {
            if (typeof options.onConfirm === 'function') {
                setButtonBusy(confirm, true);
                try {
                    const shouldClose = await options.onConfirm();
                    if (shouldClose !== false) closeModal('connectActionModal');
                } finally {
                    setButtonBusy(confirm, false);
                }
                return;
            }

            closeModal('connectActionModal');
            if (typeof options.onClose === 'function') options.onClose();
        };

        cancel.onclick = () => closeModal('connectActionModal');
        openModal('connectActionModal');

        return modal;
    }

    function showError(message) {
        showMessage('Erro', '<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>');
    }

    const search = byId('connectSearch');
    if (search) {
        search.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            document.querySelectorAll('#connectTable tbody tr[data-search]').forEach((row) => {
                row.style.display = !term || (row.dataset.search || '').includes(term) ? '' : 'none';
            });
        });
    }

    @if($isSuper)
    const provisionModal = byId('connectProvisionModal');
    const companySelect = byId('connectProvisionCompany');
    const provisionNumber = byId('connectProvisionNumber');
    const provisionMode = byId('connectProvisionMode');
    const provisionInstanceId = byId('connectProvisionInstanceId');
    const provisionError = byId('connectProvisionError');
    const provisionSubmit = byId('btnProvisionSubmit');

    function selectedCompanyPhone() {
        const option = companySelect.options[companySelect.selectedIndex];
        return option ? (option.dataset.phone || '') : '';
    }

    function prefillProvisionPhone(force = false) {
        const phone = selectedCompanyPhone();
        if (phone && (force || !provisionNumber.value.trim())) {
            provisionNumber.value = phone;
        }
    }

    companySelect.addEventListener('change', () => prefillProvisionPhone(false));

    function prepareProvisionModal({
        mode = 'provision',
        companyId = '',
        companyName = '',
        phone = '',
        instanceId = ''
    } = {}) {
        provisionMode.value = mode;
        provisionInstanceId.value = instanceId || '';
        provisionError.hidden = true;
        provisionError.textContent = '';

        companySelect.disabled = Boolean(companyId);
        companySelect.value = companyId ? String(companyId) : '';

        if (companyId && companySelect.value === '') {
            const option = document.createElement('option');
            option.value = String(companyId);
            option.textContent = companyName || ('Empresa #' + companyId);
            option.dataset.phone = phone || '';
            option.selected = true;
            companySelect.appendChild(option);
        }

        provisionNumber.value = phone || selectedCompanyPhone() || '';

        byId('connectProvisionTitle').textContent =
            mode === 'reprovision' ? 'Reprovisionar Connect|API' : 'Provisionar Connect|API';
        provisionSubmit.textContent =
            mode === 'reprovision' ? 'Reprovisionar' : 'Provisionar';

        openModal('connectProvisionModal');
        setTimeout(() => provisionNumber.focus(), 50);
    }

    const topProvision = byId('btnOpenProvision');
    if (topProvision) {
        topProvision.addEventListener('click', () => prepareProvisionModal());
    }

    document.querySelectorAll('.js-open-provision').forEach((button) => {
        button.addEventListener('click', () => prepareProvisionModal({
            mode: 'provision',
            companyId: button.dataset.companyId,
            companyName: button.dataset.companyName,
            phone: button.dataset.companyPhone,
            instanceId: button.dataset.instanceId
        }));
    });

    document.querySelectorAll('.js-reprovision').forEach((button) => {
        button.addEventListener('click', () => prepareProvisionModal({
            mode: 'reprovision',
            companyId: button.dataset.companyId,
            companyName: button.dataset.companyName,
            phone: button.dataset.companyPhone,
            instanceId: button.dataset.id
        }));
    });

    provisionSubmit.addEventListener('click', async () => {
        const companyId = companySelect.value;
        const number = provisionNumber.value.trim();
        const mode = provisionMode.value;
        const instanceId = provisionInstanceId.value;

        provisionError.hidden = true;
        provisionError.textContent = '';

        if (!companyId) {
            provisionError.textContent = 'Selecione a empresa.';
            provisionError.hidden = false;
            return;
        }

        if (!number) {
            provisionError.textContent = 'Informe o número do WhatsApp.';
            provisionError.hidden = false;
            return;
        }

        const url = mode === 'reprovision'
            ? '/connect-api/instances/' + encodeURIComponent(instanceId) + '/reprovision'
            : '/connect-api/provision/' + encodeURIComponent(companyId);

        setButtonBusy(provisionSubmit, true, mode === 'reprovision' ? 'Reprovisionando...' : 'Provisionando...');

        try {
            const data = await request(url, {
                method: 'POST',
                body: JSON.stringify({ number })
            });

            closeModal('connectProvisionModal');
            showMessage(
                'Connect|API',
                '<div class="alert alert-success mb-0">' + escapeHtml(data.message || 'Operação concluída.') + '</div>',
                { onClose: () => window.location.reload() }
            );
        } catch (error) {
            provisionError.textContent = error.message;
            provisionError.hidden = false;
        } finally {
            setButtonBusy(provisionSubmit, false);
        }
    });
    @endif

    document.querySelectorAll('.js-status').forEach((button) => {
        button.addEventListener('click', async () => {
            setButtonBusy(button, true, 'Consultando...');
            try {
                const data = await request('/connect-api/instances/' + button.dataset.id + '/status', { method:'GET' });
                const state = data.instance ? data.instance.connection_status : 'desconhecido';
                showMessage('Status Connect|API', '<strong>' + escapeHtml(state) + '</strong>', {
                    onClose: () => window.location.reload()
                });
            } catch (error) {
                showError(error.message);
            } finally {
                setButtonBusy(button, false);
            }
        });
    });

    document.querySelectorAll('.js-qr').forEach((button) => {
        button.addEventListener('click', async () => {
            setButtonBusy(button, true, 'Gerando...');
            try {
                const data = await request('/connect-api/instances/' + button.dataset.id + '/qr', { method:'GET' });
                const payload = data.data || {};
                const qr = payload.base64 || (payload.qrcode && payload.qrcode.base64) || payload.qr || '';

                if (!qr) {
                    throw new Error(data.error || 'A Connect|API não retornou um QR Code.');
                }

                const src = String(qr).startsWith('data:')
                    ? String(qr)
                    : 'data:image/png;base64,' + String(qr);

                showMessage(
                    'QR Code',
                    '<img class="connect-qr" src="' + escapeHtml(src) + '" alt="QR Code WhatsApp"><p class="text-center text-muted mt-3 mb-0">Escaneie no WhatsApp para conectar.</p>'
                );
            } catch (error) {
                showError(error.message);
            } finally {
                setButtonBusy(button, false);
            }
        });
    });

    document.querySelectorAll('.js-pair').forEach((button) => {
        button.addEventListener('click', () => {
            const defaultPhone = button.dataset.phone || '';
            const inputId = 'connectPairNumber';

            showMessage(
                'Código de pareamento',
                '<label for="' + inputId + '">Número do WhatsApp</label>' +
                '<input id="' + inputId + '" class="form-control" value="' + escapeHtml(defaultPhone) + '" placeholder="5575999999999">',
                {
                    showCancel: true,
                    confirmText: 'Gerar código',
                    onConfirm: async () => {
                        const input = byId(inputId);
                        const number = input ? input.value.trim() : '';

                        if (!number) {
                            throw new Error('Informe o número do WhatsApp.');
                        }

                        const data = await request('/connect-api/instances/' + button.dataset.id + '/pairing-code', {
                            method:'POST',
                            body:JSON.stringify({ number })
                        });

                        const payload = data.data || {};
                        const code = payload.pairingCode
                            || (payload.qrcode && payload.qrcode.pairingCode)
                            || payload.code
                            || '';

                        if (!code) {
                            throw new Error('A Connect|API não retornou o código de pareamento.');
                        }

                        byId('connectActionBody').innerHTML =
                            '<div class="connect-code">' + escapeHtml(code) + '</div>' +
                            '<p class="text-center text-muted mt-3 mb-0">Informe este código no WhatsApp.</p>';
                        byId('connectActionConfirm').textContent = 'Fechar';
                        byId('connectActionCancel').hidden = true;
                        byId('connectActionConfirm').onclick = () => closeModal('connectActionModal');

                        return false;
                    }
                }
            );
        });
    });

    document.querySelectorAll('.js-test').forEach((button) => {
        button.addEventListener('click', () => {
            const inputId = 'connectTestNumber';
            const messageId = 'connectTestMessage';

            showMessage(
                'Testar mensagem',
                '<label for="' + inputId + '">Destino</label>' +
                '<input id="' + inputId + '" class="form-control" value="' + escapeHtml(button.dataset.phone || '') + '" placeholder="5575999999999">' +
                '<label class="mt-3" for="' + messageId + '">Mensagem</label>' +
                '<textarea id="' + messageId + '" class="form-control">Teste de integração Connect|API - FERSOFT WEB.</textarea>',
                {
                    showCancel: true,
                    confirmText: 'Enviar',
                    onConfirm: async () => {
                        const number = byId(inputId).value.trim();
                        const message = byId(messageId).value.trim();

                        if (!number) throw new Error('Informe o número de destino.');

                        await request('/connect-api/instances/' + button.dataset.id + '/test', {
                            method:'POST',
                            body:JSON.stringify({ number, message })
                        });

                        showMessage('Connect|API', '<div class="alert alert-success mb-0">Mensagem enviada com sucesso.</div>');
                        return false;
                    }
                }
            );
        });
    });

    document.querySelectorAll('.js-restart').forEach((button) => {
        button.addEventListener('click', async () => {
            setButtonBusy(button, true, 'Reiniciando...');
            try {
                await request('/connect-api/instances/' + button.dataset.id + '/restart', {
                    method:'POST',
                    body:'{}'
                });
                showMessage('Connect|API', '<div class="alert alert-success mb-0">Reinicialização solicitada.</div>');
            } catch (error) {
                showError(error.message);
            } finally {
                setButtonBusy(button, false);
            }
        });
    });

    document.querySelectorAll('.js-disconnect').forEach((button) => {
        button.addEventListener('click', async () => {
            setButtonBusy(button, true, 'Desconectando...');
            try {
                await request('/connect-api/instances/' + button.dataset.id + '/disconnect', {
                    method:'POST',
                    body:'{}'
                });
                showMessage('Connect|API', '<div class="alert alert-success mb-0">Instância desconectada.</div>', {
                    onClose: () => window.location.reload()
                });
            } catch (error) {
                showError(error.message);
            } finally {
                setButtonBusy(button, false);
            }
        });
    });

    document.querySelectorAll('.js-sync-webhook').forEach((button) => {
        button.addEventListener('click', async () => {
            setButtonBusy(button, true, 'Sincronizando...');
            try {
                const data = await request('/connect-api/instances/' + button.dataset.id + '/sync-webhook', {
                    method:'POST',
                    body:'{}'
                });
                showMessage('Connect|API', '<div class="alert alert-success mb-0">' + escapeHtml(data.message || 'Webhook sincronizado.') + '</div>', {
                    onClose: () => window.location.reload()
                });
            } catch (error) {
                showError(error.message);
            } finally {
                setButtonBusy(button, false);
            }
        });
    });

    document.querySelectorAll('.js-block').forEach((button) => {
        button.addEventListener('click', async () => {
            const blocked = button.dataset.blocked === '1';
            const action = blocked ? 'unblock' : 'block';

            setButtonBusy(button, true, blocked ? 'Desbloqueando...' : 'Bloqueando...');
            try {
                const data = await request('/connect-api/instances/' + button.dataset.id + '/' + action, {
                    method:'POST',
                    body:'{}'
                });
                showMessage('Connect|API', '<div class="alert alert-success mb-0">' + escapeHtml(data.message || 'Operação concluída.') + '</div>', {
                    onClose: () => window.location.reload()
                });
            } catch (error) {
                showError(error.message);
            } finally {
                setButtonBusy(button, false);
            }
        });
    });

    document.querySelectorAll('.js-delete').forEach((button) => {
        button.addEventListener('click', () => {
            showMessage(
                'Excluir instância',
                '<p>Excluir a instância de <strong>' + escapeHtml(button.dataset.companyName) + '</strong> local e remotamente?</p>' +
                '<div class="alert alert-warning mb-0">O WhatsApp precisará ser provisionado e pareado novamente.</div>',
                {
                    showCancel:true,
                    confirmText:'Excluir',
                    onConfirm: async () => {
                        await request('/connect-api/instances/' + button.dataset.id, {
                            method:'DELETE'
                        });

                        window.location.reload();
                        return false;
                    }
                }
            );
        });
    });
})();
</script>
@endsection

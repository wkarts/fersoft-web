(function (window, document) {
    'use strict';

    var pending = null;
    var csrfToken = null;

    function getCsrfToken() {
        if (csrfToken) {
            return csrfToken;
        }

        var meta = document.querySelector('meta[name="csrf-token"]');
        csrfToken = meta ? meta.getAttribute('content') : '';
        return csrfToken;
    }

    function getPageContext() {
        return window.SecurityOperationPage || null;
    }

    function normalizePath(value) {
        value = (value || '').toString();
        if (!value) {
            return '';
        }

        try {
            value = new URL(value, window.location.origin).pathname;
        } catch (e) {
            value = value.split('?')[0].split('#')[0];
        }

        value = '/' + value.replace(/^\/+|\/+$/g, '');
        return value === '/' ? '/' : value;
    }

    function contextBasePath() {
        var context = getPageContext();
        if (!context || !context.enabled || !context.base_path) {
            return '';
        }
        return normalizePath(context.base_path);
    }

    function contextResource() {
        var context = getPageContext();
        return context && context.enabled ? (context.resource || '') : '';
    }

    function isContextUrl(url) {
        var base = contextBasePath();
        var path = normalizePath(url);

        if (!base || !path) {
            return false;
        }

        return path === base || path.indexOf(base + '/') === 0;
    }

    function inferActionAndRecord(url, form) {
        var path = normalizePath(url);
        var base = contextBasePath();
        var result = { action: '', record_id: '' };

        if (!base || !path || path.indexOf(base) !== 0) {
            return result;
        }

        var suffix = path.substring(base.length).replace(/^\//, '');
        var parts = suffix.split('/').filter(Boolean);

        if (!parts.length) {
            return result;
        }

        var first = parts[0].toLowerCase();
        var second = parts.length > 1 ? parts[1] : '';

        if (first === 'save' || first === 'store') {
            result.action = form && getFormRecordId(form) ? 'edit' : 'create';
            result.record_id = form ? getFormRecordId(form) : '';
            return result;
        }

        if (first === 'update') {
            result.action = 'edit';
            result.record_id = second || (form ? getFormRecordId(form) : '');
            return result;
        }

        if (first === 'edit' || first === 'editar') {
            result.action = 'edit';
            result.record_id = second || '';
            return result;
        }

        if (first === 'delete' || first === 'destroy' || first === 'excluir') {
            result.action = 'delete';
            result.record_id = second || (form ? getFormRecordId(form) : '');
            return result;
        }

        if (first === 'restore' || first === 'restaurar') {
            result.action = 'restore';
            result.record_id = second || (form ? getFormRecordId(form) : '');
            return result;
        }

        return result;
    }

    function getFormRecordId(form) {
        if (!form) {
            return '';
        }

        var candidates = [
            'input[name="id"]',
            'input[name="record_id"]',
            'input[name="registro_id"]',
            'input[name="codigo"]'
        ];

        for (var i = 0; i < candidates.length; i++) {
            var input = form.querySelector(candidates[i]);
            if (input && input.value) {
                return input.value;
            }
        }

        var context = getPageContext();
        return context && context.record_id ? context.record_id : '';
    }

    function hasJqueryModal() {
        return typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn !== 'undefined' && typeof window.jQuery.fn.modal === 'function';
    }

    function showModal() {
        var modal = document.getElementById('securityOperationModal');
        if (!modal) {
            return;
        }

        if (hasJqueryModal()) {
            window.jQuery(modal).modal('show');
            return;
        }

        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }

    function hideModal() {
        var modal = document.getElementById('securityOperationModal');
        if (!modal) {
            return;
        }

        if (hasJqueryModal()) {
            window.jQuery(modal).modal('hide');
            return;
        }

        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = value || '';
        }
    }

    function setValue(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.value = value || '';
        }
    }

    function getValue(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }

    function showGroup(id, visible) {
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        el.style.display = visible ? '' : 'none';
    }

    function showError(message) {
        var el = document.getElementById('securityOperationError');
        if (!el) {
            return;
        }
        el.textContent = message || 'Não foi possível autorizar esta operação.';
        el.classList.remove('d-none');
    }

    function clearError() {
        var el = document.getElementById('securityOperationError');
        if (!el) {
            return;
        }
        el.textContent = '';
        el.classList.add('d-none');
    }

    function setLoading(loading) {
        var btn = document.getElementById('securityOperationAuthorizeBtn');
        var spinner = document.getElementById('securityOperationSpinner');
        var text = document.querySelector('.security-operation-btn-text');

        if (btn) {
            btn.disabled = !!loading;
        }
        if (spinner) {
            spinner.classList.toggle('d-none', !loading);
        }
        if (text) {
            text.textContent = loading ? 'Autorizando...' : 'Autorizar';
        }
    }

    function normalizeProtectionType(type) {
        return (type || 'none').toString();
    }

    function configureFields(protectionType) {
        protectionType = normalizeProtectionType(protectionType);

        var needsAuthorizer = ['authorizer_token', 'authorizer_otp', 'authorizer_token_or_otp'].indexOf(protectionType) >= 0;
        var needsToken = protectionType === 'authorizer_token' || protectionType === 'authorizer_token_or_otp';
        var needsOtp = protectionType === 'authorizer_otp' || protectionType === 'authorizer_token_or_otp';
        var needsLegacyPassword = protectionType === 'password_legacy';

        showGroup('securityAuthorizerLoginGroup', needsAuthorizer);
        showGroup('securityAuthorizerTokenGroup', needsToken);
        showGroup('securityAuthorizerOtpGroup', needsOtp);
        showGroup('securityLegacyPasswordGroup', needsLegacyPassword);
    }

    function parseJsonResponse(response) {
        return response.text().then(function (text) {
            var data = {};
            try {
                data = text ? JSON.parse(text) : {};
            } catch (e) {
                data = { message: text };
            }

            if (!response.ok) {
                var message = extractErrorMessage(data) || 'Erro ao processar a solicitação de segurança.';
                throw new Error(message);
            }

            return data;
        });
    }

    function extractErrorMessage(data) {
        if (!data) {
            return '';
        }

        if (data.message) {
            return data.message;
        }

        if (data.errors) {
            var keys = Object.keys(data.errors);
            if (keys.length && data.errors[keys[0]] && data.errors[keys[0]][0]) {
                return data.errors[keys[0]][0];
            }
        }

        return '';
    }

    function postJson(url, payload) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify(payload || {})
        }).then(parseJsonResponse);
    }

    function resolveOperation(options) {
        var context = getPageContext();
        var endpoint = context && context.endpoints && context.endpoints.resolve ? context.endpoints.resolve : '/seguranca/operacao/resolve';

        return postJson(endpoint, {
            resource: options.resource,
            security_crud_resource_id: options.security_crud_resource_id,
            action: options.action,
            record_id: options.record_id || null
        });
    }

    function authorizeOperation(options) {
        var context = getPageContext();
        var endpoint = context && context.endpoints && context.endpoints.authorize ? context.endpoints.authorize : '/seguranca/operacao/autorizar';

        return postJson(endpoint, {
            resource: options.resource,
            security_crud_resource_id: options.security_crud_resource_id,
            action: options.action,
            record_id: options.record_id || null,
            authorizer_login: getValue('securityAuthorizerLogin'),
            authorizer_token: getValue('securityAuthorizerToken'),
            authorizer_otp: getValue('securityAuthorizerOtp'),
            security_legacy_password: getValue('securityLegacyPassword')
        });
    }

    function openAuthorizationModal(options, resolveData) {
        clearError();
        setLoading(false);

        var protectionType = normalizeProtectionType(resolveData.protection_type);
        var message = resolveData.message || 'Esta operação exige liberação por usuário autorizado.';

        setText('securityOperationMessage', message);
        setValue('securityOperationResource', options.resource || '');
        setValue('securityOperationAction', options.action || '');
        setValue('securityOperationRecordId', options.record_id || '');
        setValue('securityOperationProtectionType', protectionType);

        setValue('securityAuthorizerLogin', '');
        setValue('securityAuthorizerToken', '');
        setValue('securityAuthorizerOtp', '');
        setValue('securityLegacyPassword', '');

        configureFields(protectionType);
        showModal();
    }

    function appendHiddenInput(form, name, value) {
        if (!form || !name) {
            return;
        }

        var input = form.querySelector('input[name="' + name + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            form.appendChild(input);
        }
        input.value = value || '';
    }

    function appendTokenToUrl(url, token) {
        if (!token) {
            return url;
        }

        try {
            var parsed = new URL(url, window.location.origin);
            parsed.searchParams.set('security_operation_authorization_token', token);
            parsed.searchParams.set('crud_authorization_token', token);
            return parsed.pathname + parsed.search + parsed.hash;
        } catch (e) {
            var separator = url.indexOf('?') >= 0 ? '&' : '?';
            var encoded = encodeURIComponent(token);
            return url + separator + 'security_operation_authorization_token=' + encoded + '&crud_authorization_token=' + encoded;
        }
    }

    function submitFormWithToken(form, token) {
        appendHiddenInput(form, 'security_operation_authorization_token', token);
        appendHiddenInput(form, 'crud_authorization_token', token);
        form.setAttribute('data-security-authorized', '1');
        form.submit();
    }

    function executePending(token) {
        if (!pending) {
            return;
        }

        if (pending.form) {
            submitFormWithToken(pending.form, token);
            pending = null;
            return;
        }

        if (pending.href) {
            window.location.href = appendTokenToUrl(pending.href, token);
            pending = null;
            return;
        }

        if (typeof pending.onAuthorized === 'function') {
            pending.onAuthorized(token);
            pending = null;
        }
    }

    function requestAuthorization(options) {
        options = options || {};

        if (!options.resource && !options.security_crud_resource_id) {
            return Promise.resolve({ required: false, authorization_token: null });
        }

        return resolveOperation(options).then(function (data) {
            if (!data.required) {
                return { required: false, authorization_token: null, resolve: data };
            }

            return new Promise(function (resolve, reject) {
                pending = {
                    options: options,
                    resolve: resolve,
                    reject: reject,
                    form: options.form || null,
                    onAuthorized: options.onAuthorized || null,
                    href: options.href || null
                };
                openAuthorizationModal(options, data);
            });
        });
    }

    function formOptions(form) {
        return {
            resource: form.getAttribute('data-security-resource') || '',
            security_crud_resource_id: form.getAttribute('data-security-resource-id') || '',
            action: form.getAttribute('data-security-action') || 'view',
            record_id: form.getAttribute('data-security-record-id') || getFormRecordId(form),
            form: form,
            href: ''
        };
    }

    function autoFormOptions(form) {
        if (!form || !form.action || !isContextUrl(form.action)) {
            return null;
        }

        var inferred = inferActionAndRecord(form.action, form);
        if (!inferred.action) {
            return null;
        }

        var method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'GET' && inferred.action !== 'delete' && inferred.action !== 'restore') {
            return null;
        }

        return {
            resource: contextResource(),
            security_crud_resource_id: '',
            action: inferred.action,
            record_id: inferred.record_id || getFormRecordId(form),
            form: form,
            href: ''
        };
    }

    function buttonOptions(button) {
        var formSelector = button.getAttribute('data-security-form') || '';
        var form = formSelector ? document.querySelector(formSelector) : button.closest('form');

        return {
            resource: button.getAttribute('data-security-resource') || (form ? form.getAttribute('data-security-resource') : ''),
            security_crud_resource_id: button.getAttribute('data-security-resource-id') || (form ? form.getAttribute('data-security-resource-id') : ''),
            action: button.getAttribute('data-security-action') || (form ? form.getAttribute('data-security-action') : 'view'),
            record_id: button.getAttribute('data-security-record-id') || (form ? form.getAttribute('data-security-record-id') : ''),
            form: form,
            href: button.getAttribute('data-security-href') || button.getAttribute('href') || ''
        };
    }

    function autoElementOptions(element) {
        if (!element) {
            return null;
        }

        var href = element.getAttribute('href') || element.getAttribute('data-href') || element.getAttribute('data-url') || '';
        var formSelector = element.getAttribute('form') ? '#' + element.getAttribute('form') : '';
        var form = formSelector ? document.querySelector(formSelector) : element.closest('form');

        if (href && isContextUrl(href)) {
            var inferredHref = inferActionAndRecord(href, null);
            if (inferredHref.action) {
                return {
                    resource: contextResource(),
                    security_crud_resource_id: '',
                    action: inferredHref.action,
                    record_id: inferredHref.record_id,
                    form: null,
                    href: href
                };
            }
        }

        if (form) {
            return autoFormOptions(form);
        }

        return null;
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.matches) {
            return;
        }

        if (form.getAttribute('data-security-authorized') === '1') {
            return;
        }

        var options = null;
        if (form.matches('form[data-security-protected="1"]')) {
            options = formOptions(form);
        } else {
            options = autoFormOptions(form);
        }

        if (!options) {
            return;
        }

        event.preventDefault();
        requestAuthorization(options).then(function (result) {
            if (!result.required) {
                form.setAttribute('data-security-authorized', '1');
                form.submit();
            }
        }).catch(function (error) {
            if (window.toastr) {
                window.toastr.error(error.message || 'Falha na validação de segurança.');
            } else {
                alert(error.message || 'Falha na validação de segurança.');
            }
        });
    }, true);

    document.addEventListener('click', function (event) {
        var element = event.target && event.target.closest ? event.target.closest('[data-security-trigger="1"], a[href], button, input[type="submit"]') : null;
        if (!element) {
            return;
        }

        if (element.getAttribute('data-security-authorized') === '1') {
            return;
        }

        var options = null;
        if (element.getAttribute('data-security-trigger') === '1') {
            options = buttonOptions(element);
        } else {
            options = autoElementOptions(element);
        }

        if (!options) {
            return;
        }

        event.preventDefault();
        requestAuthorization(options).then(function (result) {
            if (!result.required) {
                if (options.href) {
                    window.location.href = options.href;
                    return;
                }
                if (options.form) {
                    options.form.setAttribute('data-security-authorized', '1');
                    options.form.submit();
                }
            }
        }).catch(function (error) {
            if (window.toastr) {
                window.toastr.error(error.message || 'Falha na validação de segurança.');
            } else {
                alert(error.message || 'Falha na validação de segurança.');
            }
        });
    }, true);

    document.addEventListener('click', function (event) {
        var btn = event.target && event.target.closest ? event.target.closest('#securityOperationAuthorizeBtn') : null;
        if (!btn || !pending) {
            return;
        }

        clearError();
        setLoading(true);

        authorizeOperation(pending.options).then(function (data) {
            var token = data.authorization_token || '';
            hideModal();
            if (pending.resolve) {
                pending.resolve({ required: true, authorization_token: token, authorize: data });
            }
            executePending(token);
        }).catch(function (error) {
            showError(error.message || 'Não foi possível autorizar esta operação.');
        }).finally(function () {
            setLoading(false);
        });
    });

    window.SecurityOperation = {
        requestAuthorization: requestAuthorization,
        resolve: resolveOperation,
        authorize: authorizeOperation,
        inferActionAndRecord: inferActionAndRecord
    };
})(window, document);

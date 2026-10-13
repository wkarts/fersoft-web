/* Ajustes opcionais da logo. Não substitui o editor nem os scripts de autenticação. */
(function () {
    'use strict';
    function initialize() {
        var form = document.getElementById('appearance-editor-form');
        var frame = document.getElementById('appearance-preview-frame');
        if (!form || !frame || !form.querySelector('[data-logo-controls]')) return;

        function refresh() {
            var doc;
            try { doc = frame.contentDocument; } catch (_) { return; }
            if (!doc || !doc.body || !doc.body.classList.contains('login-access4')) return;
            var body = doc.body;
            ['desktop', 'mobile'].forEach(function (scope) {
                var prefix = 'data-logo-' + scope;
                ['width', 'height', 'align', 'position', 'inset', 'space_before', 'space_after', 'size', ''].forEach(function (key) {
                    var suffix = key.replace(/_/g, '-');
                    body.removeAttribute(prefix + (suffix ? '-' + suffix : ''));
                    if (suffix) body.style.removeProperty('--login-logo-' + scope + '-' + suffix);
                });
                var custom = false;
                var size = false;
                form.querySelectorAll('[data-logo-scope="' + scope + '"]').forEach(function (control) {
                    var key = control.dataset.logoField;
                    var value = control.value;
                    if (value === '' || value === 'default') return;
                    if (key === 'align' || key === 'position') {
                        var allowed = key === 'align' ? ['left', 'center', 'right'] : ['before', 'after'];
                        if (allowed.indexOf(value) === -1) return;
                    } else {
                        if (!/^\d+$/.test(value)) return;
                        var number = Number(value);
                        if (number < Number(control.min) || number > Number(control.max)) return;
                        value = String(number);
                        body.style.setProperty('--login-logo-' + scope + '-' + key.replace(/_/g, '-'), value + 'px');
                    }
                    body.setAttribute(prefix + '-' + key.replace(/_/g, '-'), value);
                    custom = true;
                    if (key === 'width' || key === 'height') size = true;
                });
                if (custom) body.setAttribute(prefix, 'custom');
                if (size) body.setAttribute(prefix + '-size', 'custom');
            });
        }

        form.querySelectorAll('[data-logo-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                var scope = button.dataset.logoReset;
                form.querySelectorAll('[data-logo-scope="' + scope + '"]').forEach(function (control) {
                    control.value = control.tagName === 'SELECT' ? 'default' : '';
                });
                refresh();
            });
        });
        frame.addEventListener('load', refresh);
        form.addEventListener('input', refresh);
        form.addEventListener('change', refresh);
        refresh();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, {once: true});
    else initialize();
}());

/* Navegação rápida do ERP, isolada em APP_UI_THEME=refined.
 * Nenhum evento Metronic/Bootstrap é interceptado ou substituído.
 */
(function () {
    'use strict';
    if (!document.body || !document.body.classList.contains('ui-theme-refined')) return;

    const panel = document.getElementById('ui-command-palette');
    const trigger = document.querySelector('[data-ui-command-open]');
    const field = document.getElementById('ui-command-search');
    if (!panel || !trigger || !field) return;

    const groups = Array.from(panel.querySelectorAll('[data-ui-command-group]'));
    const links = Array.from(panel.querySelectorAll('[data-ui-command-result]'));
    const empty = panel.querySelector('[data-ui-command-empty]');
    let previousFocus = null;
    let visibleLinks = [];

    const normalize = value => String(value || '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');

    function updateResults() {
        const term = normalize(field.value.trim());
        let count = 0;
        visibleLinks = [];

        for (const group of groups) {
            let visibleInGroup = 0;
            group.querySelectorAll('[data-ui-command-result]').forEach(link => {
                const match = normalize(link.getAttribute('data-search')).includes(term);
                link.hidden = !match;
                link.tabIndex = match ? 0 : -1;
                if (match) {
                    visibleLinks.push(link);
                    count++;
                    visibleInGroup++;
                }
            });
            group.hidden = visibleInGroup === 0;
        }
        if (empty) empty.hidden = count > 0;
    }

    function open() {
        if (!panel.hidden) return;
        if (document.querySelector('.modal.show, .swal-modal')) return;
        previousFocus = document.activeElement;
        panel.hidden = false;
        panel.setAttribute('aria-hidden', 'false');
        field.value = '';
        updateResults();
        document.body.classList.add('ui-command-open');
        field.focus();
    }

    function close() {
        if (panel.hidden) return;
        panel.hidden = true;
        panel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ui-command-open');
        if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
    }

    trigger.addEventListener('click', open);
    field.addEventListener('input', updateResults);
    panel.querySelectorAll('[data-ui-command-close]').forEach(button => {
        button.addEventListener('click', close);
    });

    field.addEventListener('keydown', event => {
        if (event.key !== 'ArrowDown' || visibleLinks.length === 0) return;
        event.preventDefault();
        visibleLinks[0].focus();
    });

    panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
        if (event.key === 'Tab') {
            const focusables = [field, ...visibleLinks, ...panel.querySelectorAll('.ui-command__close')];
            const current = focusables.indexOf(document.activeElement);
            if (current === -1) return;
            if (event.shiftKey && current === 0) {
                event.preventDefault();
                focusables[focusables.length - 1].focus();
            } else if (!event.shiftKey && current === focusables.length - 1) {
                event.preventDefault();
                focusables[0].focus();
            }
        }
    });

    document.addEventListener('keydown', event => {
        const active = document.activeElement;
        const editing = active && (active.matches('input,textarea,select') || active.isContentEditable);
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            panel.hidden ? open() : close();
        }
        if (!editing && !event.ctrlKey && !event.metaKey && event.key === '/' && panel.hidden) {
            event.preventDefault();
            open();
        }
        if (event.key === 'Escape' && !panel.hidden) close();
    });

    updateResults();
})();

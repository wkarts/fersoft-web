/* Pesquisa de empresas para o SUPERADMIN. Nenhuma dependência de plugin externa. */
(function () {
    'use strict';

    document.querySelectorAll('[data-ui-company-chooser]').forEach(chooser => {
        const input = chooser.querySelector('[data-ui-company-search]');
        const hidden = chooser.querySelector('[data-ui-company-id]');
        const results = chooser.querySelector('[data-ui-company-results]');
        const url = chooser.dataset.searchUrl;
        if (!input || !hidden || !results || !url) return;

        let requestController = null;
        let requestSequence = 0;
        let debounce = null;

        function clear() {
            results.textContent = '';
            results.hidden = true;
        }

        function selectCompany(company) {
            hidden.value = String(company.id);
            input.value = String(company.name) + ' (#' + company.id + ')';
            input.setCustomValidity('');
            clear();
        }

        function showCompanies(items) {
            clear();
            if (!Array.isArray(items) || items.length === 0) {
                const message = document.createElement('div');
                message.className = 'ui-studio__company-empty';
                message.textContent = 'Nenhuma empresa encontrada. Pesquise pelo nome ou pelo ID.';
                results.appendChild(message);
            } else {
                items.forEach(company => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'ui-studio__company-option';
                    button.setAttribute('role', 'option');
                    button.textContent = String(company.name || '') + ' (#' + String(company.id) + ')';
                    button.addEventListener('click', () => selectCompany(company));
                    results.appendChild(button);
                });
            }
            results.hidden = false;
        }

        async function findCompanies(query, serial) {
            if (requestController) requestController.abort();
            requestController = new AbortController();
            try {
                const response = await fetch(url + '?q=' + encodeURIComponent(query), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: requestController.signal,
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();
                if (serial !== requestSequence) return;
                showCompanies(data.items);
            } catch (error) {
                if (error && error.name === 'AbortError') return;
                if (serial !== requestSequence) return;
                clear();
                const message = document.createElement('div');
                message.className = 'ui-studio__company-empty';
                message.textContent = 'Não foi possível consultar as empresas. Tente novamente.';
                results.appendChild(message);
                results.hidden = false;
            }
        }

        input.addEventListener('input', () => {
            hidden.value = '';
            clear();
            clearTimeout(debounce);
            const term = input.value.trim();
            const serial = ++requestSequence;
            if (term.length < 2) {
                if (requestController) requestController.abort();
                return;
            }
            debounce = setTimeout(() => findCompanies(term, serial), 240);
        });

        input.addEventListener('keydown', event => {
            if (event.key === 'Escape') clear();
            if (event.key === 'ArrowDown') {
                const first = results.querySelector('[role="option"]');
                if (first) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });

        chooser.closest('form')?.addEventListener('submit', event => {
            if (!hidden.value) {
                event.preventDefault();
                input.setCustomValidity('Selecione uma empresa na lista de resultados.');
                input.reportValidity();
                input.focus();
            }
        });

        input.addEventListener('change', () => {
            if (hidden.value) input.setCustomValidity('');
        });

        document.addEventListener('pointerdown', event => {
            if (!chooser.contains(event.target)) clear();
        });
    });
})();

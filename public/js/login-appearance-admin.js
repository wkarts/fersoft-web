/* Administração progressiva: nenhuma dependência de jQuery ou CDN. */
(function () {
    'use strict';
    function initialize() {
        const page = document.getElementById('appearance-admin');
        if (!page) return;

        const tabs = Array.from(page.querySelectorAll('[data-appearance-tab]'));
        const panels = Array.from(page.querySelectorAll('[data-appearance-panel]'));
        const allowed = ['modelos', 'editor', 'conteudos', 'identidade'];

        function selectTab(tab, updateUrl) {
            if (!allowed.includes(tab)) tab = 'modelos';
            tabs.forEach(function (link) {
                const active = link.dataset.appearanceTab === tab;
                link.classList.toggle('is-active', active);
                link.setAttribute('aria-selected', active ? 'true' : 'false');
                link.tabIndex = active ? 0 : -1;
            });
            panels.forEach(function (panel) {
                panel.hidden = panel.dataset.appearancePanel !== tab;
            });
            if (updateUrl) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState(null, '', url.pathname + url.search + url.hash);
            }
        }

        const params = new URLSearchParams(window.location.search);
        selectTab(params.get('tab') || (params.has('edit') || params.has('preset') ? 'editor' : 'modelos'), false);
        tabs.forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                selectTab(link.dataset.appearanceTab, true);
            });
        });

        // Prévia apenas visual, não armazena dados nem injeta código HTML.
        const form = document.getElementById('appearance-editor-form');
        const frame = document.getElementById('appearance-preview-frame');
        if (!form || !frame) return;

        // Uma prévia de desktop precisa de viewport de desktop, mesmo quando
        // o editor está em uma coluna estreita. Escalar não muda o breakpoint.
        const screen = frame.parentElement;
        let previewDevice = 'desktop';
        const devices = {
            desktop: {width: 1200, height: 800, label: 'Computador'},
            mobile: {width: 390, height: 780, label: 'Celular'}
        };
        const toolbar = document.createElement('div');
        toolbar.setAttribute('role', 'group');
        toolbar.setAttribute('aria-label', 'Dispositivo da pré-visualização');
        toolbar.style.cssText = 'display:flex;gap:8px;margin:0 0 12px;flex-wrap:wrap';
        const deviceButtons = [];

        function resizePreview() {
            if (!screen || !screen.clientWidth) return;
            const size = devices[previewDevice];
            const available = Math.max(1, screen.clientWidth - 2);
            const scale = Math.min(available / size.width, 560 / size.height, 1);
            frame.style.width = size.width + 'px';
            frame.style.height = size.height + 'px';
            frame.style.transform = 'scale(' + scale + ')';
            frame.style.transformOrigin = 'left top';
            frame.style.marginLeft = Math.max(0, (available - size.width * scale) / 2) + 'px';
            frame.style.pointerEvents = 'auto';
            screen.style.height = Math.ceil(size.height * scale) + 2 + 'px';
            deviceButtons.forEach(function (button) {
                const selected = button.dataset.previewDevice === previewDevice;
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                button.classList.toggle('appearance-btn--primary', selected);
            });
        }

        Object.keys(devices).forEach(function (key) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'appearance-btn appearance-btn--outline';
            button.dataset.previewDevice = key;
            button.textContent = devices[key].label;
            button.addEventListener('click', function () {
                previewDevice = key;
                resizePreview();
            });
            toolbar.appendChild(button);
            deviceButtons.push(button);
        });
        if (screen && screen.parentElement) {
            screen.parentElement.insertBefore(toolbar, screen);
            if (typeof ResizeObserver === 'function') {
                new ResizeObserver(resizePreview).observe(screen);
            } else {
                window.addEventListener('resize', resizePreview);
            }
            resizePreview();
        }

        function value(name) {
            const element = form.elements.namedItem(name);
            return element && typeof element.value === 'string' ? element.value : '';
        }
        function checked(name) {
            const element = form.elements.namedItem(name);
            return !!(element && element.checked);
        }
        function refreshPreview() {
            let doc;
            try { doc = frame.contentDocument; } catch (_) { return; }
            if (!doc || !doc.body || !doc.body.classList.contains('login-access4')) return;

            const body = doc.body;
            const layout = value('layout');
            if (['split','centered','corporate','editorial','minimal','institutional','highlights','wide'].includes(layout)) {
                Array.from(body.classList).filter(function (c) {
                    return c.startsWith('login-layout-');
                }).forEach(function (c) { body.classList.remove(c); });
                body.classList.add('login-layout-' + layout);
            }

            ['accent_color', 'background_color'].forEach(function (field) {
                const color = value(field);
                if (!/^#[0-9a-f]{6}$/i.test(color)) return;
                body.style.setProperty(field === 'accent_color' ? '--login-accent' : '--login-background', color);
                const label = page.querySelector('[data-appearance-color-label="' + field + '"]');
                if (label) label.textContent = color.toUpperCase();
            });

            const hero = doc.querySelector('.login-hero-content');
            if (hero) {
                hero.hidden = !checked('show_hero');
                const title = hero.querySelector('h1');
                const tagline = hero.querySelector('p');
                if (title) title.textContent = value('headline').trim() || 'Tudo pronto para continuar.';
                if (tagline) tagline.textContent = value('tagline').trim() || 'Acesse seu ambiente de trabalho com segurança e praticidade.';
            }

            doc.querySelectorAll('.login-publication').forEach(function (item) { item.hidden = !checked('show_posts'); });
            doc.querySelectorAll('.login-phrases').forEach(function (item) { item.hidden = !checked('show_phrases'); });
            const register = doc.querySelector('a[href$="/cadastro"]');
            if (register) register.hidden = !checked('show_signup');
            const footer = doc.querySelector('.login-footer');
            if (footer) footer.hidden = !checked('show_support');
        }

        frame.addEventListener('load', function () { resizePreview(); refreshPreview(); });
        form.addEventListener('input', refreshPreview);
        form.addEventListener('change', refreshPreview);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, {once: true});
    } else {
        initialize();
    }
}());

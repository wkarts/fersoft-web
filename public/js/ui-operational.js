/* Native, progressive shell controller. Never intercepts business forms or Bootstrap modal events. */
(function () {
    'use strict';
    const body = document.body;
    if (!body || !body.classList.contains('ui-native-shell')) return;
    const sidebar = document.getElementById('ui-sidebar');
    const navigation = document.getElementById('ui-navigation');
    const workspace = document.getElementById('kt_wrapper');
    const backdrop = document.querySelector('.ui-menu-backdrop');
    if (!sidebar || !navigation || !workspace || !backdrop) return;
    const desktop = window.matchMedia('(min-width: 992px)');
    const key = 'ui:navigation:' + (body.dataset.uiIdentity || 'anonymous');
    const toggles = [];
    let previousFocus = null;
    let previousInert = false;
    let previousOverflow = '';
    const store = {
        read() { try { return localStorage.getItem(key) === 'collapsed'; } catch (_) { return false; } },
        write(value) { try { localStorage.setItem(key, value ? 'collapsed' : 'expanded'); } catch (_) { /* Storage is optional. */ } },
    };

    function setCollapsed(value) {
        const collapse = desktop.matches && body.dataset.uiLayout !== 'superior' && value;
        body.classList.toggle('ui-nav-collapsed', collapse);
        document.querySelectorAll('[data-ui-collapse]').forEach(button => {
            button.setAttribute('aria-expanded', String(!collapse));
            button.setAttribute('aria-label', collapse ? 'Expandir navegação' : 'Recolher navegação');
            button.textContent = collapse ? '»' : '«';
        });
    }
    function openMobile() {
        if (desktop.matches || body.classList.contains('ui-nav-mobile-open')) return;
        previousFocus = document.activeElement;
        previousInert = workspace.inert;
        previousOverflow = body.style.overflow;
        body.classList.add('ui-nav-mobile-open');
        backdrop.hidden = false;
        workspace.inert = true;
        body.style.overflow = 'hidden';
        sidebar.setAttribute('role', 'dialog');
        sidebar.setAttribute('aria-modal', 'true');
        sidebar.inert = false;
        document.querySelectorAll('[data-ui-menu-open]').forEach(button => button.setAttribute('aria-expanded', 'true'));
        sidebar.querySelector('[data-ui-menu-close]')?.focus();
    }
    function closeMobile(restoreFocus = true) {
        if (!body.classList.contains('ui-nav-mobile-open')) return;
        body.classList.remove('ui-nav-mobile-open');
        backdrop.hidden = true;
        workspace.inert = previousInert;
        body.style.overflow = previousOverflow;
        sidebar.removeAttribute('role');
        sidebar.removeAttribute('aria-modal');
        sidebar.inert = !desktop.matches;
        document.querySelectorAll('[data-ui-menu-open]').forEach(button => button.setAttribute('aria-expanded', 'false'));
        if (restoreFocus && previousFocus?.isConnected) previousFocus.focus();
    }
    function setGroup(entry, expanded) {
        entry.node.classList.toggle('ui-nav-expanded', expanded);
        entry.submenu.hidden = !expanded;
        entry.link.setAttribute('aria-expanded', String(expanded));
        entry.submenu.style.removeProperty('transform');
        if (expanded && desktop.matches && body.dataset.uiLayout === 'superior') {
            const rect = entry.submenu.getBoundingClientRect();
            let offset = Math.min(0, window.innerWidth - 12 - rect.right);
            if (rect.left + offset < 12) offset = 12 - rect.left;
            if (offset) entry.submenu.style.transform = 'translateX(' + offset + 'px)';
        }
    }
    navigation.querySelectorAll('.menu-item').forEach((node, index) => {
        const link = Array.from(node.children).find(el => el.matches('a.menu-toggle'));
        const submenu = Array.from(node.children).find(el => el.matches('.menu-submenu'));
        if (!link || !submenu) return;
        const name = link.querySelector('.menu-text')?.textContent.trim() || link.textContent.trim();
        link.title = name;
        link.setAttribute('role', 'button');
        link.setAttribute('href', '#ui-group-' + index);
        submenu.id = 'ui-group-' + index;
        link.setAttribute('aria-controls', submenu.id);
        const entry = { node, link, submenu };
        toggles.push(entry);
        setGroup(entry, node.classList.contains('menu-item-open') || !!submenu.querySelector('.menu-item-active'));
        link.addEventListener('click', event => {
            event.preventDefault();
            if (body.classList.contains('ui-nav-collapsed')) {
                setCollapsed(false);
                store.write(false);
            }
            const opening = submenu.hidden;
            if (desktop.matches && body.dataset.uiLayout === 'superior') {
                toggles.filter(other => other !== entry && !other.node.contains(node)).forEach(other => setGroup(other, false));
            }
            setGroup(entry, opening);
        });
        link.addEventListener('keydown', event => {
            if (event.key === ' ') { event.preventDefault(); link.click(); }
            if (event.key === 'ArrowRight') { event.preventDefault(); setGroup(entry, true); }
            if (event.key === 'ArrowLeft') { event.preventDefault(); setGroup(entry, false); }
        });
    });
    navigation.querySelectorAll('a:not(.menu-toggle)').forEach(link => {
        link.title = link.textContent.trim();
        if (link.closest('.menu-item-active')) link.setAttribute('aria-current', 'page');
    });
    document.querySelectorAll('[data-ui-collapse]').forEach(button => button.addEventListener('click', () => {
        const value = !body.classList.contains('ui-nav-collapsed');
        setCollapsed(value); store.write(value);
    }));
    document.querySelectorAll('[data-ui-menu-open]').forEach(button => button.addEventListener('click', openMobile));
    document.querySelectorAll('[data-ui-menu-close]').forEach(button => button.addEventListener('click', () => closeMobile()));
    sidebar.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (!desktop.matches) { event.preventDefault(); closeMobile(); }
            else toggles.forEach(entry => setGroup(entry, false));
        }
        if (event.key === 'Tab' && body.classList.contains('ui-nav-mobile-open')) {
            const items = Array.from(sidebar.querySelectorAll('a[href],button:not([disabled]),input,select,[tabindex="0"]'))
                .filter(el => el.getClientRects().length && !el.closest('[hidden]'));
            if (!items.length) return;
            if (event.shiftKey && document.activeElement === items[0]) { event.preventDefault(); items[items.length - 1].focus(); }
            else if (!event.shiftKey && document.activeElement === items[items.length - 1]) { event.preventDefault(); items[0].focus(); }
        }
    });
    document.addEventListener('pointerdown', event => {
        if (desktop.matches && body.dataset.uiLayout === 'superior' && !navigation.contains(event.target)) {
            toggles.forEach(entry => setGroup(entry, false));
        }
    });
    function changeViewport() {
        closeMobile(false);
        sidebar.inert = !desktop.matches;
        setCollapsed(store.read());
        // Upper navigation does not start with an open flyout obscuring the page.
        if (desktop.matches && body.dataset.uiLayout === 'superior') toggles.forEach(entry => setGroup(entry, false));
    }
    desktop.addEventListener('change', changeViewport);
    let resizeTick = 0;
    window.addEventListener('resize', () => {
        cancelAnimationFrame(resizeTick);
        resizeTick = requestAnimationFrame(() => toggles.filter(entry => !entry.submenu.hidden)
            .forEach(entry => setGroup(entry, true)));
    });
    changeViewport();
})();


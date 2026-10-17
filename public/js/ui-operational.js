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
    const masthead = workspace.querySelector('.ui-masthead');
    const sessionBar = workspace.querySelector('.ui-session');
    const key = 'ui:navigation:' + (body.dataset.uiIdentity || 'anonymous');
    const groupKey = key + ':groups';
    const scrollKey = key + ':scroll';
    const toggles = [];
    let previousFocus = null;
    let previousInert = false;
    let previousOverflow = '';
    const store = {
        read() { try { return localStorage.getItem(key) === 'collapsed'; } catch (_) { return false; } },
        write(value) { try { localStorage.setItem(key, value ? 'collapsed' : 'expanded'); } catch (_) { /* Storage is optional. */ } },
    };
    const lateral = body.dataset.uiLayout === 'lateral';
    function readExpandedGroups() {
        if (!lateral) return null;
        try {
            const saved = JSON.parse(sessionStorage.getItem(groupKey));
            return Array.isArray(saved) ? new Set(saved) : null;
        } catch (_) { return null; }
    }
    const rememberedGroups = readExpandedGroups();
    function rememberNavigation() {
        if (!lateral) return;
        try {
            // Flyouts on the icon rail are temporary; the expanded tree is not.
            if (!body.classList.contains('ui-nav-collapsed')) {
                sessionStorage.setItem(groupKey, JSON.stringify(toggles
                    .filter(entry => !entry.submenu.hidden)
                    .map(entry => entry.stateKey)));
            }
            sessionStorage.setItem(scrollKey, String(navigation.scrollTop));
        } catch (_) { /* Storage is optional. */ }
    }

    function isRootGroup(entry) {
        return entry.node.parentElement?.classList.contains('ui-nav-list');
    }
    function positionRailFlyout(entry) {
        if (!body.classList.contains('ui-nav-collapsed') || !isRootGroup(entry) || entry.submenu.hidden) return;
        const anchor = entry.link.getBoundingClientRect();
        const height = Math.min(entry.submenu.scrollHeight, window.innerHeight - 24);
        const top = Math.max(12, Math.min(anchor.top - 8, window.innerHeight - height - 12));
        entry.submenu.style.setProperty('--ui-rail-top', top + 'px');
    }

    // The operational header can grow when real company or account controls
    // wrap. Align the sidebar bands with the actual header and session rows.
    function alignSidebarBands() {
        if (masthead) body.style.setProperty('--ui-masthead-height', masthead.getBoundingClientRect().height + 'px');
        if (!desktop.matches || body.dataset.uiLayout !== 'lateral' || !masthead || !sessionBar) {
            body.style.removeProperty('--ui-sidebar-brand-height');
            body.style.removeProperty('--ui-sidebar-version-height');
            return;
        }
        body.style.setProperty('--ui-sidebar-brand-height', masthead.getBoundingClientRect().height + 'px');
        body.style.setProperty('--ui-sidebar-version-height', sessionBar.getBoundingClientRect().height + 'px');
    }
    if (window.ResizeObserver && masthead && sessionBar) {
        const bandObserver = new ResizeObserver(alignSidebarBands);
        bandObserver.observe(masthead);
        bandObserver.observe(sessionBar);
    }

    function setCollapsed(value) {
        const collapse = desktop.matches && body.dataset.uiLayout !== 'superior' && value;
        const wasCollapsed = body.classList.contains('ui-nav-collapsed');
        body.classList.toggle('ui-nav-collapsed', collapse);
        if (collapse && !wasCollapsed) toggles.filter(isRootGroup).forEach(entry => setGroup(entry, false));
        if (!collapse && wasCollapsed) toggles.filter(entry => isRootGroup(entry) &&
            (entry.node.classList.contains('menu-item-open') || entry.node.querySelector('.menu-item-active')))
            .forEach(entry => setGroup(entry, true));
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
        if (expanded && isRootGroup(entry) && body.classList.contains('ui-nav-collapsed')) positionRailFlyout(entry);
        else if (!expanded) entry.submenu.style.removeProperty('--ui-rail-top');
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
        if (node.parentElement?.classList.contains('ui-nav-list')) {
            submenu.dataset.uiGroupLabel = name;
            submenu.setAttribute('aria-label', name);
            submenu.setAttribute('role', 'group');
            link.setAttribute('aria-label', name);
            if (submenu.querySelector('.menu-item-active')) node.classList.add('ui-nav-current');
        }
        const entry = { node, link, submenu };
        const parentGroup = node.parentElement?.closest('.menu-item-submenu');
        entry.stateKey = (parentGroup?.dataset.uiGroupKey || '') + '/' + name;
        node.dataset.uiGroupKey = entry.stateKey;
        toggles.push(entry);
        setGroup(entry, (rememberedGroups?.has(entry.stateKey) ?? false)
            || node.classList.contains('menu-item-open') || !!submenu.querySelector('.menu-item-active'));
        link.addEventListener('click', event => {
            event.preventDefault();
            const opening = submenu.hidden;
            if (body.classList.contains('ui-nav-collapsed') && isRootGroup(entry)) {
                toggles.filter(other => other !== entry && isRootGroup(other))
                    .forEach(other => setGroup(other, false));
                setGroup(entry, opening);
                rememberNavigation();
                return;
            }
            if (desktop.matches && body.dataset.uiLayout === 'superior') {
                toggles.filter(other => other !== entry && !other.node.contains(node)).forEach(other => setGroup(other, false));
            }
            setGroup(entry, opening);
            rememberNavigation();
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
    // A normal Laravel navigation rebuilds the shell. Keep the selected group
    // and the sidebar's scroll position while the destination view loads.
    navigation.addEventListener('click', event => {
        if (event.target.closest('a[href]:not(.menu-toggle)')) rememberNavigation();
    });
    window.addEventListener('pagehide', rememberNavigation);
    document.querySelectorAll('[data-ui-collapse]').forEach(button => button.addEventListener('click', () => {
        const value = !body.classList.contains('ui-nav-collapsed');
        setCollapsed(value); store.write(value);
    }));
    document.querySelectorAll('[data-ui-menu-open]').forEach(button => button.addEventListener('click', openMobile));
    document.querySelectorAll('[data-ui-menu-close]').forEach(button => button.addEventListener('click', () => closeMobile()));
    sidebar.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (!desktop.matches) { event.preventDefault(); closeMobile(); }
            else {
                const openRail = body.classList.contains('ui-nav-collapsed')
                    ? toggles.find(entry => isRootGroup(entry) && !entry.submenu.hidden) : null;
                toggles.forEach(entry => setGroup(entry, false));
                if (openRail) { event.preventDefault(); openRail.link.focus(); }
            }
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
        if (body.classList.contains('ui-nav-collapsed') && !sidebar.contains(event.target)) {
            toggles.filter(isRootGroup).forEach(entry => setGroup(entry, false));
        }
        if (desktop.matches && body.dataset.uiLayout === 'superior' && !navigation.contains(event.target)) {
            toggles.forEach(entry => setGroup(entry, false));
        }
    });
    navigation.addEventListener('scroll', () => {
        if (!body.classList.contains('ui-nav-collapsed')) return;
        toggles.filter(entry => isRootGroup(entry) && !entry.submenu.hidden).forEach(entry => {
            const anchor = entry.link.getBoundingClientRect();
            if (anchor.bottom < 0 || anchor.top > window.innerHeight) setGroup(entry, false);
            else positionRailFlyout(entry);
        });
    });
    function changeViewport() {
        closeMobile(false);
        sidebar.inert = !desktop.matches;
        setCollapsed(store.read());
        // Upper navigation does not start with an open flyout obscuring the page.
        if (desktop.matches && body.dataset.uiLayout === 'superior') toggles.forEach(entry => setGroup(entry, false));
        alignSidebarBands();
    }
    desktop.addEventListener('change', changeViewport);
    let resizeTick = 0;
    window.addEventListener('resize', () => {
        cancelAnimationFrame(resizeTick);
        resizeTick = requestAnimationFrame(() => {
            alignSidebarBands();
            toggles.filter(entry => !entry.submenu.hidden).forEach(entry => setGroup(entry, true));
        });
    });
    changeViewport();
    if (lateral) {
        try {
            const scrollTop = Number(sessionStorage.getItem(scrollKey));
            if (Number.isFinite(scrollTop) && scrollTop > 0) {
                requestAnimationFrame(() => { navigation.scrollTop = scrollTop; });
            }
        } catch (_) { /* Storage is optional. */ }
    }
})();


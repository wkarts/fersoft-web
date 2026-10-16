/* Place the existing superior navigation below the header and anchor its real submenus. */
(function () {
    'use strict';
    const body = document.body;
    const sidebar = document.getElementById('ui-sidebar');
    const navigation = document.getElementById('ui-navigation');
    const workspace = document.getElementById('kt_wrapper');
    const masthead = workspace?.querySelector('.ui-masthead');
    if (!body?.classList.contains('ui-native-shell') || !sidebar || !navigation || !workspace || !masthead) return;
    const desktop = window.matchMedia('(min-width: 992px)');
    const superior = body.dataset.uiLayout === 'superior';
    if (!superior) return;

    function topEntries() {
        return Array.from(navigation.querySelectorAll('.ui-nav-list > .menu-item'))
            .map(item => ({ link: Array.from(item.children).find(child => child.matches('.menu-link')),
                submenu: Array.from(item.children).find(child => child.matches('.menu-submenu')) }))
            .filter(entry => entry.link && entry.submenu && !entry.submenu.hidden);
    }

    function positionFlyouts() {
        if (!desktop.matches) return;
        const visible = navigation.getBoundingClientRect();
        topEntries().forEach(({ link, submenu }) => {
            const rect = link.getBoundingClientRect();
            if (rect.right <= visible.left + 1 || rect.left >= visible.right - 1) {
                submenu.hidden = true;
                submenu.style.removeProperty('transform');
                submenu.style.removeProperty('--ui-flyout-top');
                submenu.style.removeProperty('--ui-flyout-left');
                link.setAttribute('aria-expanded', 'false');
                link.parentElement.classList.remove('ui-nav-expanded');
                return;
            }
            const width = Math.min(320, window.innerWidth - 24);
            const left = Math.max(12, Math.min(rect.left, window.innerWidth - width - 12));
            const barBottom = sidebar.getBoundingClientRect().bottom;
            submenu.style.setProperty('--ui-flyout-top', Math.max(0, barBottom + 4, rect.bottom + 5) + 'px');
            submenu.style.setProperty('--ui-flyout-left', left + 'px');
        });
    }

    function placeNavigation() {
        if (desktop.matches) {
            if (sidebar.previousElementSibling !== masthead) masthead.insertAdjacentElement('afterend', sidebar);
        } else if (sidebar.parentElement !== body.querySelector('.ui-app')) {
            workspace.parentElement.insertBefore(sidebar, workspace.parentElement.querySelector('.ui-menu-backdrop'));
            sidebar.querySelectorAll('.ui-nav-list > .menu-item > .menu-submenu').forEach(menu => {
                menu.style.removeProperty('--ui-flyout-top');
                menu.style.removeProperty('--ui-flyout-left');
            });
        }
        positionFlyouts();
    }

    desktop.addEventListener('change', placeNavigation);
    navigation.addEventListener('click', () => requestAnimationFrame(positionFlyouts));
    navigation.addEventListener('keydown', () => requestAnimationFrame(positionFlyouts));
    navigation.addEventListener('scroll', positionFlyouts, { passive: true });
    window.addEventListener('scroll', positionFlyouts, { passive: true });
    window.addEventListener('resize', () => requestAnimationFrame(positionFlyouts));
    placeNavigation();
})();

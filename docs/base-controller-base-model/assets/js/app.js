(function () {
  const body = document.body;
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar-nav');
  const backdrop = document.getElementById('sidebar-backdrop');
  const links = Array.from(document.querySelectorAll('.sidebar a'));
  const sections = links
    .map((link) => document.querySelector(link.getAttribute('href')))
    .filter(Boolean);

  function closeMenu() {
    body.classList.remove('menu-open');
    if (menuToggle) {
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.setAttribute('aria-label', 'Abrir menu de navegação');
    }
  }

  function toggleMenu() {
    if (!menuToggle || !sidebar) return;
    const open = !body.classList.contains('menu-open');
    body.classList.toggle('menu-open', open);
    menuToggle.setAttribute('aria-expanded', String(open));
    menuToggle.setAttribute('aria-label', open ? 'Fechar menu de navegação' : 'Abrir menu de navegação');
  }

  function setActive(id) {
    links.forEach((link) => {
      const active = link.getAttribute('href') === `#${id}`;
      link.classList.toggle('active', active);
    });
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          setActive(entry.target.id);
        }
      });
    },
    { rootMargin: '-35% 0px -55% 0px', threshold: 0.01 }
  );

  sections.forEach((section) => observer.observe(section));

  links.forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      const id = link.getAttribute('href')?.replace('#', '');
      const section = document.getElementById(id);
      if (!section) return;
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      history.replaceState(null, '', `#${id}`);
      setActive(id);
      if (window.matchMedia('(max-width: 980px)').matches) {
        closeMenu();
      }
    });
  });

  menuToggle?.addEventListener('click', toggleMenu);
  backdrop?.addEventListener('click', closeMenu);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMenu();
    }
  });

  window.addEventListener('resize', () => {
    if (!window.matchMedia('(max-width: 980px)').matches) {
      closeMenu();
    }
  });

  const initial = window.location.hash.replace('#', '');
  if (initial && document.getElementById(initial)) {
    setActive(initial);
  } else if (sections[0]) {
    setActive(sections[0].id);
  }
})();

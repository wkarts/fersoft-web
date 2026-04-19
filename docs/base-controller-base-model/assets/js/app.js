(function () {
  const links = Array.from(document.querySelectorAll('.sidebar a'));
  const sections = links
    .map((link) => document.querySelector(link.getAttribute('href')))
    .filter(Boolean);

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
    });
  });

  const initial = window.location.hash.replace('#', '');
  if (initial && document.getElementById(initial)) {
    setActive(initial);
  } else if (sections[0]) {
    setActive(sections[0].id);
  }
})();

document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-topnav-toggle]');
  const nav = document.querySelector('[data-topnav]');
  if (!toggle || !nav) return;

  const setOpen = (open) => {
    nav.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  };

  toggle.addEventListener('click', (event) => {
    event.stopPropagation();
    setOpen(!nav.classList.contains('is-open'));
  });

  document.addEventListener('click', (event) => {
    if (nav.classList.contains('is-open') && !nav.contains(event.target)) setOpen(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && nav.classList.contains('is-open')) {
      setOpen(false);
      toggle.focus();
    }
  });

  window.matchMedia('(min-width: 901px)').addEventListener('change', (event) => {
    if (event.matches) setOpen(false);
  });
});

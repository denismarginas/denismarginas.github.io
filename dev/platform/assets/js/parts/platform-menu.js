(() => {
  const STORAGE_KEY = 'platform.menu.open';

  function readOpen() {
    try {
      const value = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '[]');
      return Array.isArray(value) ? value : [];
    } catch (e) {
      return [];
    }
  }

  function writeOpen(keys) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(keys));
    } catch (e) {
    }
  }

  function setOpen(sub, open) {
    sub.classList.toggle('is-open', open);
    const toggle = sub.querySelector('[data-platform-menu-toggle]');
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  document.addEventListener('DOMContentLoaded', () => {
    const menu = document.querySelector('[data-platform-menu]');
    if (!menu) return;

    const subs = Array.from(menu.querySelectorAll('[data-platform-menu-sub]'));
    const stored = readOpen();

    subs.forEach((sub) => {
      const key = sub.dataset.platformMenuSub;
      if (sub.hasAttribute('data-platform-menu-active') || stored.includes(key)) setOpen(sub, true);

      const toggle = sub.querySelector('[data-platform-menu-toggle]');
      if (!toggle) return;
      toggle.addEventListener('click', () => {
        setOpen(sub, !sub.classList.contains('is-open'));
        writeOpen(subs.filter((s) => s.classList.contains('is-open')).map((s) => s.dataset.platformMenuSub));
      });
    });
  });
})();

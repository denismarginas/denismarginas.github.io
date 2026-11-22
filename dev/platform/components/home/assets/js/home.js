document.addEventListener('DOMContentLoaded', () => {
  const rootEl = document.querySelector('[data-home-root]');
  if (!rootEl) return;

  function resolveUrl(path) {
    const href = window.location.href;
    const qIdx = href.indexOf('?');
    const base = qIdx === -1 ? href : href.substring(0, qIdx);
    const lastSlash = base.lastIndexOf('/');
    const dir = lastSlash === -1 ? '' : base.substring(0, lastSlash + 1);
    return dir + path.replace(/^\//, '');
  }

  async function apiJson(path, options) {
    const res = await fetch(resolveUrl(path), Object.assign({ headers: { Accept: 'application/json' } }, options));
    let data = {};
    try {
      data = await res.json();
    } catch (e) {
      data = {};
    }
    if (!res.ok) throw new Error(data.message || `Request failed (${res.status})`);
    return data;
  }

  const actions = {
    'compile-html': async () => {
      await apiJson('api/compile-html.php', { method: 'POST' });
    },
  };

  rootEl.querySelectorAll('[data-platform-card-action]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const run = actions[btn.dataset.platformCardAction];
      if (!run) return;
      btn.disabled = true;
      btn.classList.remove('is-done', 'is-error');
      try {
        await run();
        btn.classList.add('is-done');
      } catch (e) {
        btn.classList.add('is-error');
        btn.title = e.message;
      } finally {
        btn.disabled = false;
        setTimeout(() => btn.classList.remove('is-done'), 2500);
      }
    });
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const containers = document.querySelectorAll('[data-platform-vars]');
  if (!containers.length) return;

  function resolveUrl(path) {
    const href = window.location.href;
    const qIdx = href.indexOf('?');
    const base = qIdx === -1 ? href : href.substring(0, qIdx);
    const lastSlash = base.lastIndexOf('/');
    const dir = lastSlash === -1 ? '' : base.substring(0, lastSlash + 1);
    return dir + path.replace(/^\//, '');
  }

  function readVars(container) {
    const vars = [];
    container.querySelectorAll('[data-vars-name]').forEach((el) => {
      const name = el.getAttribute('data-vars-name');
      const value = el.hasAttribute('data-vars-bool') ? (el.checked ? 'true' : 'false') : el.value;
      vars.push({ name, value });
    });
    return vars;
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

  containers.forEach((container) => {
    const cardType = container.getAttribute('data-vars-card-type');
    const saveBtn = container.querySelector('[data-vars-save]');
    const statusEl = container.querySelector('[data-vars-status]');
    if (!saveBtn || !cardType) return;

    saveBtn.addEventListener('click', async () => {
      statusEl.textContent = 'Saving...';
      saveBtn.disabled = true;
      try {
        const newVars = readVars(container);
        await apiJson('api/workflow.php', {
          method: 'POST',
          body: JSON.stringify({ section: cardType, variables: newVars }),
        });
        statusEl.textContent = 'Saved';
      } catch (e) {
        statusEl.textContent = 'Save failed: ' + e.message;
      } finally {
        saveBtn.disabled = false;
      }
    });
  });
});

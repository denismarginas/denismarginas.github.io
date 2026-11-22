function resolveApiUrl(path) {
  const href = window.location.href;
  const qIdx = href.indexOf('?');
  const base = qIdx === -1 ? href : href.substring(0, qIdx);
  const lastSlash = base.lastIndexOf('/');
  const dir = lastSlash === -1 ? '' : base.substring(0, lastSlash + 1);
  return dir + path.replace(/^\//, '');
}

function setStatus(msg) {
  statusElement.textContent = msg;
}

function tr(path, fallback, vars) {
  const s = t(path, fallback);
  return vars ? formatText(s, vars) : s;
}

function escapeHtml(v) {
  return String(v ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
}

function uid() {
  return Date.now().toString(36) + Math.random().toString(16).slice(2, 6);
}

async function loadEditPostStrings() {
  try {
    const res = await fetch(stringsUrl, { headers: { Accept: 'application/json' } });
    if (!res.ok) return;
    const data = await res.json();
    Object.assign(strings, data);
    epRoot.querySelectorAll('[data-ep-i18n]').forEach(el => {
      const path = el.dataset.epI18n;
      const fallback = el.textContent || '';
      el.textContent = t(path, fallback);
    });
    epRoot.querySelectorAll('[data-ep-placeholder]').forEach(el => {
      const path = el.dataset.epPlaceholder;
      const fallback = el.getAttribute('placeholder') || '';
      el.setAttribute('placeholder', t(path, fallback));
    });
  } catch (e) {
  }
}

async function apiFetch(url, options) {
  try {
    const res = await fetch(url, options);
    const data = await res.json();
    if (!data.ok) throw new Error(data.message || 'API error');
    return data;
  } catch (e) {
    setStatus(tr('editPosts.error', 'Error: {message}', { message: e.message }));
    throw e;
  }
}

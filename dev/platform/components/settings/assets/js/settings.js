document.addEventListener('DOMContentLoaded', () => {
  const rootEl = document.querySelector('[data-settings-root]');
  if (!rootEl) return;

  const fileListEl = rootEl.querySelector('[data-settings-file-list]');
  const searchInput = rootEl.querySelector('[data-settings-search]');
  const fileTitleEl = rootEl.querySelector('[data-settings-file-title]');
  const statusEl = rootEl.querySelector('[data-settings-status]');
  const saveBtn = rootEl.querySelector('[data-settings-save]');
  const editorEl = rootEl.querySelector('[data-settings-json-editor]');

  const params = new URLSearchParams(window.location.search);
  let pendingPath = params.get('path') || '';
  let files = [];
  let currentPath = '';
  let currentData = null;
  let editor = null;

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

  function escapeHtml(v) {
    return String(v ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
  }

  function renderFileList(filter) {
    fileListEl.innerHTML = '';
    const settingsFiles = files.filter((f) => f.startsWith('settings/'));
    const contentFiles = files.filter((f) => !f.startsWith('settings/'));

    const buildGroup = (title, list) => {
      if (!list.length) return;
      const group = document.createElement('div');
      group.className = 'platform-settings-file-group';
      const heading = document.createElement('div');
      heading.className = 'platform-settings-file-group-title';
      heading.textContent = title;
      group.appendChild(heading);
      list.forEach((name) => {
        const item = document.createElement('div');
        item.className = 'platform-settings-file-item' + (name === currentPath ? ' is-active' : '');
        item.textContent = name;
        item.title = name;
        item.addEventListener('click', () => { pendingPath = ''; loadFile(name); });
        group.appendChild(item);
      });
      fileListEl.appendChild(group);
    };

    if (filter) {
      const f = filter.toLowerCase();
      buildGroup('Matches', [...settingsFiles, ...contentFiles].filter((n) => n.toLowerCase().includes(f)));
      return;
    }
    buildGroup('Settings', settingsFiles);
    buildGroup('Content', contentFiles);
  }

  async function loadFiles() {
    try {
      const data = await apiJson('api/data-files.php');
      files = data.files || [];
      renderFileList(searchInput.value);
      const requested = params.get('file') || '';
      if (files.length && !currentPath) loadFile(files.includes(requested) ? requested : files[0]);
    } catch (e) {
      statusEl.textContent = 'Error loading files: ' + e.message;
    }
  }

  async function loadFile(name) {
    currentPath = name;
    renderFileList(searchInput.value);
    fileTitleEl.textContent = name;
    statusEl.textContent = 'Loading...';
    saveBtn.disabled = true;
    try {
      const data = await apiJson(`api/json-file.php?path=${encodeURIComponent(name)}`);
      currentData = data.content;
      editor = new JsonEditor(editorEl, currentData, {
        onChange: (val) => {
          currentData = val;
          saveBtn.disabled = false;
        },
      });
      statusEl.textContent = 'Loaded';
      const url = new URL(window.location.href);
      url.searchParams.set('file', name);
      if (pendingPath) url.searchParams.set('path', pendingPath);
      else url.searchParams.delete('path');
      window.history.replaceState(null, '', url);
      if (pendingPath) revealPath(pendingPath);
      pendingPath = '';
    } catch (e) {
      editorEl.innerHTML = '';
      statusEl.textContent = 'Error loading file: ' + e.message;
    }
  }

  function revealPath(path) {
    const rows = Array.from(editorEl.querySelectorAll('[data-je-path]'));
    const row = rows.find((node) => node.dataset.jePath === path);
    if (!row) return;
    row.scrollIntoView({ block: 'center' });
    row.classList.remove('is-flash');
    void row.offsetWidth;
    row.classList.add('is-flash');
    const field = row.querySelector(':scope > .platform-je-node input, :scope > .platform-je-node textarea, :scope > .platform-je-node select');
    if (field) field.focus({ preventScroll: true });
  }

  async function saveFile() {
    saveBtn.disabled = true;
    statusEl.textContent = 'Saving...';
    try {
      const data = await apiJson('api/json-file.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ path: currentPath, content: currentData }),
      });
      statusEl.textContent = data.ok ? 'Saved' : 'Save failed';
    } catch (e) {
      statusEl.textContent = 'Save failed: ' + e.message;
      saveBtn.disabled = false;
    }
  }

  saveBtn.addEventListener('click', saveFile);
  searchInput.addEventListener('input', () => renderFileList(searchInput.value));

  loadFiles();
});

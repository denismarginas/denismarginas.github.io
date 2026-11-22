document.addEventListener('DOMContentLoaded', () => {
  const rootEl = document.querySelector('[data-render-root]');
  if (!rootEl) return;

  const statusEl = rootEl.querySelector('[data-render-status]');
  const resultsEl = rootEl.querySelector('[data-render-scss-results]');
  const htmlStatusEl = rootEl.querySelector('[data-render-html-status]');
  const htmlResultsEl = rootEl.querySelector('[data-render-html-results]');
  const seoStatusEl = rootEl.querySelector('[data-render-seo-status]');
  const seoResultsEl = rootEl.querySelector('[data-render-seo-results]');
  const seoFilesEl = rootEl.querySelector('[data-render-seo-files]');

  let previewDefaults = {};
  try {
    previewDefaults = JSON.parse(rootEl.getAttribute('data-render-preview-defaults') || '{}');
  } catch (e) {
    previewDefaults = {};
  }

  const postTypeSelect = rootEl.querySelector('[data-render-post-type]');
  const postSelect = rootEl.querySelector('[data-render-post]');
  const previewFrame = rootEl.querySelector('[data-render-preview]');
  const openLink = rootEl.querySelector('[data-render-open]');

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

  async function runCompile() {
    statusEl.textContent = 'Compiling...';
    resultsEl.innerHTML = '';
    try {
      const data = await apiJson('api/compile-scss.php');
      statusEl.textContent = `SCSS: ${data.success_count} OK, ${data.error_count} errors (${data.total} files)`;
      (data.results || []).forEach((r) => {
        const item = document.createElement('div');
        item.className = 'platform-render-result-item';
        const file = document.createElement('span');
        file.className = 'platform-render-result-file';
        file.textContent = r.file || r.output || 'component';
        const state = document.createElement('span');
        state.className = r.success ? 'platform-render-result-ok' : 'platform-render-result-err';
        state.textContent = r.success ? `OK · ${r.bytes || 0} B` : (r.message || 'error');
        item.appendChild(file);
        item.appendChild(state);
        resultsEl.appendChild(item);
      });
    } catch (e) {
      statusEl.textContent = 'Compile failed: ' + e.message;
    }
  }

  function setPreview() {
    const postId = postSelect.value;
    if (!postId) return;
    const url = `preview/?_id=${encodeURIComponent(postId)}`;
    previewFrame.src = resolveUrl(url);
    openLink.href = resolveUrl(url);
  }

  async function runCompileHtml() {
    if (!htmlStatusEl || !htmlResultsEl) return;
    htmlStatusEl.textContent = 'Rendering...';
    htmlResultsEl.innerHTML = '';
    try {
      const data = await apiJson('api/compile-html.php', { method: 'POST' });
      if (!data.html_compile) {
        htmlStatusEl.textContent = 'html_compile flag is off (Render card params).';
        return;
      }
      const frontendInfo = Object.values(data.i18n || {}).map((x) => `${x.lang}: ${x.files} JSON files, ${Math.round(x.bytes / 1024)} KB`).join(' · ');
      htmlStatusEl.textContent = `${data.count} files rendered` + (data.static_languages && data.static_languages.length > 1 ? ` (${data.static_languages.join(', ')})` : '') + (frontendInfo ? ` · frontend ${frontendInfo}` : '') + (data.seo_files ? ` · sitemap ${data.seo_files.count} urls` : '');
      (data.results || []).forEach((r) => {
        const item = document.createElement('div');
        item.className = 'platform-render-result-item';
        const file = document.createElement('span');
        file.className = 'platform-render-result-file';
        file.textContent = r.post_type === 'seo' ? (r._id || r.post_id || '') : `${r.lang && data.languages && r.lang !== data.languages[0] ? r.lang + '/' : ''}${r.post_type}/${r._id || r.post_id || ''}`;
        const state = document.createElement('span');
        state.className = r.success ? 'platform-render-result-ok' : 'platform-render-result-err';
        state.textContent = r.success ? (r.strings !== undefined ? `OK · ${r.strings} strings` : (r.message || 'OK')) : (r.error || 'error');
        item.appendChild(file);
        item.appendChild(state);
        htmlResultsEl.appendChild(item);
      });
    } catch (e) {
      htmlStatusEl.textContent = 'Render failed: ' + e.message;
    }
  }

  function resultRow(container, label, ok, text) {
    const item = document.createElement('div');
    item.className = 'platform-render-result-item';
    const file = document.createElement('span');
    file.className = 'platform-render-result-file';
    file.textContent = label;
    const state = document.createElement('span');
    state.className = ok ? 'platform-render-result-ok' : 'platform-render-result-err';
    state.textContent = text;
    item.appendChild(file);
    item.appendChild(state);
    container.appendChild(item);
  }

  async function runSeoFiles() {
    if (!seoStatusEl || !seoResultsEl) return;
    seoStatusEl.textContent = 'Building...';
    seoResultsEl.innerHTML = '';
    if (seoFilesEl) seoFilesEl.innerHTML = '';
    try {
      const data = await apiJson('api/generate-seo-files.php', { method: 'POST' });
      const files = data.results || [];
      seoStatusEl.textContent = files.length
        ? `${data.count} urls · ${data.site_url || 'no site url'}`
        : 'Nothing to build (sitemap and robots are off).';
      files.forEach((r) => {
        resultRow(seoResultsEl, r.file, r.success, r.success ? `OK · ${r.bytes || 0} B${r.count !== undefined ? ' · ' + r.count + ' urls' : ''}` : 'error');
        if (seoFilesEl && r.content) {
          const title = document.createElement('h3');
          title.className = 'platform-vars-title';
          title.textContent = r.file;
          const pre = document.createElement('pre');
          pre.className = 'platform-render-seo-pre';
          pre.textContent = r.content;
          seoFilesEl.appendChild(title);
          seoFilesEl.appendChild(pre);
        }
      });
      (data.notes || []).forEach((n) => resultRow(seoResultsEl, n, false, 'note'));
    } catch (e) {
      seoStatusEl.textContent = 'Build failed: ' + e.message;
    }
  }

  async function loadTypes() {
    try {
      const data = await apiJson('api/posts.php?include_collections=1');
      const types = data.types || [];
      postTypeSelect.innerHTML = '';
      if (!types.length) {
        postTypeSelect.innerHTML = '<option value="">No post types</option>';
        postSelect.innerHTML = '';
        return;
      }
      let defaultType = previewDefaults.post_type || '';
      let selected = types.find((t) => t.post_type === defaultType) ? defaultType : types[0].post_type;
      types.forEach((t) => {
        const opt = document.createElement('option');
        opt.value = t.post_type;
        opt.textContent = `${t.title} (${t.count})`;
        if (t.post_type === selected) opt.selected = true;
        postTypeSelect.appendChild(opt);
      });
      loadPosts();
    } catch (e) {
      statusEl.textContent = 'Error loading post types: ' + e.message;
    }
  }

  async function loadPosts() {
    const postType = postTypeSelect.value;
    if (!postType) return;
    try {
      const data = await apiJson(`api/posts.php?post_type=${encodeURIComponent(postType)}&include_collections=1`);
      postSelect.innerHTML = '';
      let defaultPost = previewDefaults._id || '';
      (data.posts || []).forEach((p) => {
        const opt = document.createElement('option');
        opt.value = p._id;
        opt.textContent = p.title || p._id;
        if (p._id === defaultPost) opt.selected = true;
        postSelect.appendChild(opt);
      });
      if (defaultPost && !(data.posts || []).some((p) => p._id === defaultPost)) {
        postSelect.selectedIndex = 0;
      }
      setPreview();
    } catch (e) {
      statusEl.textContent = 'Error loading posts: ' + e.message;
    }
  }

  const compileBtn = rootEl.querySelector('[data-render-action="compile-scss"]');
  if (compileBtn) compileBtn.addEventListener('click', runCompile);

  const compileHtmlBtn = rootEl.querySelector('[data-render-action="compile-html"]');
  if (compileHtmlBtn) compileHtmlBtn.addEventListener('click', runCompileHtml);

  const seoBtn = rootEl.querySelector('[data-render-action="seo-files"]');
  if (seoBtn) seoBtn.addEventListener('click', runSeoFiles);

  if (postTypeSelect) postTypeSelect.addEventListener('change', loadPosts);
  if (postSelect) postSelect.addEventListener('change', setPreview);

  if (rootEl.dataset.renderTab === 'preview') loadTypes();
});

document.addEventListener('DOMContentLoaded', () => {
  const rootEl = document.querySelector('[data-generate-root]');
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
    const text = await res.text();
    let data = null;
    try {
      data = JSON.parse(text);
    } catch (e) {
      data = null;
    }
    if (!data) {
      const plain = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
      throw new Error(plain ? plain.slice(0, 300) : `Invalid response (${res.status})`);
    }
    if (!res.ok) throw new Error(data.message || `Request failed (${res.status})`);
    return data;
  }

  const statusEl = rootEl.querySelector('[data-generate-status]');
  const resultsEl = rootEl.querySelector('[data-generate-results]');
  const providerEl = rootEl.querySelector('[data-translate-provider]');
  const langSelect = rootEl.querySelector('[data-translate-lang]');
  const fileSelect = rootEl.querySelector('[data-translate-file]');
  const translateBtn = rootEl.querySelector('[data-generate-action="translate"]');
  const translateStopBtn = rootEl.querySelector('[data-generate-action="translate-stop"]');
  const overwriteEl = rootEl.querySelector('[data-translate-overwrite]');
  let translateState = null;
  let translateStop = false;

  function providerMessage(provider) {
    providerEl.innerHTML = '';
    const item = document.createElement('div');
    item.className = 'platform-generate-result-item' + (provider.available ? '' : ' is-error');
    item.textContent = provider.message || provider.provider;
    providerEl.appendChild(item);
  }

  async function loadTranslate() {
    if (!fileSelect) return;
    try {
      translateState = await apiJson('api/translate-data.php');
      providerMessage(translateState.provider || {});
      langSelect.innerHTML = '';
      (translateState.languages || []).forEach((lang) => {
        const opt = document.createElement('option');
        opt.value = lang.iso;
        opt.textContent = `${translateState.default} → ${lang.iso} (${lang.cached} cached)`;
        langSelect.appendChild(opt);
      });
      if ((translateState.languages || []).length > 1) {
        const all = document.createElement('option');
        all.value = '';
        all.textContent = 'All target languages';
        langSelect.prepend(all);
        langSelect.value = '';
      }
      fileSelect.innerHTML = '';
      const allFiles = document.createElement('option');
      allFiles.value = '';
      allFiles.textContent = `All data files (${translateState.files.length})`;
      fileSelect.appendChild(allFiles);
      translateState.files.forEach((file) => {
        const opt = document.createElement('option');
        opt.value = file;
        opt.textContent = file;
        fileSelect.appendChild(opt);
      });
      translateBtn.disabled = !(translateState.provider || {}).available || !(translateState.languages || []).length;
      statusEl.textContent = (translateState.languages || []).length ? 'Idle' : 'No target languages set in params (target_langs).';
    } catch (e) {
      statusEl.textContent = 'Error: ' + e.message;
    }
  }

  function resultRow(text, isError) {
    const item = document.createElement('div');
    item.className = 'platform-generate-result-item' + (isError ? ' is-error' : '');
    item.textContent = text;
    resultsEl.prepend(item);
    return item;
  }

  function setTranslateRunning(running) {
    translateBtn.disabled = running;
    translateStopBtn.style.display = running ? '' : 'none';
    langSelect.disabled = running;
    fileSelect.disabled = running;
    if (overwriteEl) overwriteEl.disabled = running;
  }

  async function runTranslate() {
    if (!translateState) return;
    const langs = langSelect.value ? [langSelect.value] : translateState.languages.map((l) => l.iso);
    const files = fileSelect.value ? [fileSelect.value] : translateState.files;
    const jobs = [];
    langs.forEach((lang) => files.forEach((file) => jobs.push({ lang, file })));
    const overwrite = !!(overwriteEl && overwriteEl.checked);
    if (overwrite && !window.confirm(`Overwrite: translate ${files.length} file(s) again and replace the existing ${langs.join(', ')} texts, including your hand edits? Your custom translations still apply.`)) return;
    const run = Date.now().toString(36) + Math.random().toString(36).slice(2, 8);

    translateStop = false;
    setTranslateRunning(true);
    resultsEl.innerHTML = '';
    const totals = { translated: 0, failed: 0, kept: 0, files: 0, errors: 0 };
    const startedAt = Date.now();

    for (let i = 0; i < jobs.length && !translateStop; i++) {
      const job = jobs[i];
      const row = resultRow(`${job.lang}/${job.file} · waiting...`, false);
      let fileTotals = { translated: 0, failed: 0 };
      try {
        while (!translateStop) {
          statusEl.textContent = `File ${i + 1}/${jobs.length} · ${job.lang}/${job.file} · ${totals.translated + fileTotals.translated} texts translated`;
          const r = await apiJson('api/translate-data.php', { method: 'POST', body: JSON.stringify({ lang: job.lang, file: job.file, budget: 20, overwrite, run }) });
          fileTotals.translated += r.translated;
          fileTotals.failed += r.failed;
          row.textContent = `${job.lang}/${job.file} · ${r.strings} texts · ${fileTotals.translated} translated` + (r.kept ? ` · ${r.kept} kept` : '') + (r.overridden ? ` · ${r.overridden} custom` : '') + (fileTotals.failed ? ` · ${fileTotals.failed} failed (kept English)` : '') + (r.done ? (r.written ? ' · saved' : ' · not saved') : ` · ${r.remaining} left...`);
          row.classList.toggle('is-error', r.done && !r.written);
          if (r.done) {
            totals.kept += r.kept;
            totals.files++;
            break;
          }
        }
      } catch (e) {
        totals.errors++;
        row.textContent = `${job.lang}/${job.file} · ${e.message}`;
        row.classList.add('is-error');
        if (/not running|not downloaded/i.test(e.message)) break;
      }
      totals.translated += fileTotals.translated;
      totals.failed += fileTotals.failed;
    }

    const minutes = Math.round((Date.now() - startedAt) / 6000) / 10;
    const summary = `${translateStop ? 'Stopped' : 'Done'}: ${totals.files}/${jobs.length} files · ${totals.translated} texts translated` + (totals.failed ? ` · ${totals.failed} failed` : '') + (totals.errors ? ` · ${totals.errors} errors` : '') + ` · ${minutes} min`;
    statusEl.textContent = summary;
    setTranslateRunning(false);
    await loadTranslate();
    statusEl.textContent = summary;
  }

  const rulesStatusEl = rootEl.querySelector('[data-translate-rules-status]');
  const keysEl = rootEl.querySelector('[data-translate-keys]');
  const keysFilterEl = rootEl.querySelector('[data-translate-keys-filter]');
  const keysEmptyEl = rootEl.querySelector('[data-translate-keys-empty]');
  const glossaryEl = rootEl.querySelector('[data-translate-glossary]');
  const skipValuesEl = rootEl.querySelector('[data-translate-skip-values]');
  const overridesEl = rootEl.querySelector('[data-translate-overrides]');
  let rulesKeys = [];

  function renderOverrides(rules, languages) {
    if (!overridesEl) return;
    overridesEl.innerHTML = '';
    languages.forEach((iso) => {
      const field = document.createElement('label');
      field.className = 'platform-translate-field';
      const title = document.createElement('span');
      title.innerHTML = `My translations for <strong>${iso.toUpperCase()}</strong> <small>(one per line: English text =&gt; your ${iso} text; used instead of the machine translation, for whole values and lines)</small>`;
      const area = document.createElement('textarea');
      area.className = 'platform-input';
      area.rows = 8;
      area.dataset.translateOverrideLang = iso;
      area.placeholder = 'web preview => previzualizare web';
      const pairs = (rules.overrides || {})[iso] || {};
      area.value = Object.keys(pairs).map((k) => `${k} => ${pairs[k]}`).join('\n');
      area.addEventListener('input', () => { rulesStatusEl.textContent = 'Unsaved changes'; });
      field.append(title, area);
      overridesEl.appendChild(field);
    });
  }

  function renderKeys() {
    if (!keysEl) return;
    const query = (keysFilterEl.value || '').trim().toLowerCase();
    const showEmpty = keysEmptyEl.checked;
    keysEl.innerHTML = '';
    rulesKeys.forEach((row) => {
      if (!showEmpty && !row.texts) return;
      if (query && !(row.key.toLowerCase().includes(query) || (row.example || '').toLowerCase().includes(query))) return;
      const line = document.createElement('label');
      line.className = 'platform-translate-key' + (row.translate ? '' : ' is-off');
      line.title = (row.files || []).join('\n');
      const box = document.createElement('input');
      box.type = 'checkbox';
      box.checked = row.translate;
      box.addEventListener('change', () => {
        row.translate = box.checked;
        line.classList.toggle('is-off', !row.translate);
        rulesStatusEl.textContent = 'Unsaved changes';
      });
      const key = document.createElement('code');
      key.textContent = row.key;
      const count = document.createElement('span');
      count.textContent = row.texts;
      const example = document.createElement('span');
      example.className = 'platform-translate-key-example';
      example.textContent = row.example || '—';
      line.append(box, key, count, example);
      keysEl.appendChild(line);
    });
  }

  async function loadRules() {
    if (!keysEl) return;
    try {
      const data = await apiJson('api/translate-data.php?view=rules');
      rulesKeys = data.keys || [];
      glossaryEl.value = (data.rules.glossary || []).join('\n');
      skipValuesEl.value = (data.rules.skip_values || []).join('\n');
      const targetLangs = (translateState && translateState.languages ? translateState.languages.map((l) => l.iso) : Object.keys(data.rules.overrides || {}));
      renderOverrides(data.rules, targetLangs.length ? targetLangs : ['ro']);
      renderKeys();
      const off = rulesKeys.filter((r) => !r.translate).length;
      rulesStatusEl.textContent = `${rulesKeys.length - off} keys translated · ${off} kept as they are`;
    } catch (e) {
      rulesStatusEl.textContent = 'Error: ' + e.message;
    }
  }

  async function saveRules() {
    const lines = (el) => el.value.split(/\r?\n/).map((v) => v.trim()).filter(Boolean);
    const rules = {
      skip_keys: rulesKeys.filter((r) => !r.translate).map((r) => r.key),
      glossary: lines(glossaryEl),
      skip_values: lines(skipValuesEl),
      overrides: {},
    };
    if (overridesEl) {
      overridesEl.querySelectorAll('[data-translate-override-lang]').forEach((area) => {
        const pairs = {};
        lines(area).forEach((line) => {
          const at = line.indexOf('=>');
          if (at > 0) {
            const from = line.slice(0, at).trim();
            const to = line.slice(at + 2).trim();
            if (from && to) pairs[from] = to;
          }
        });
        rules.overrides[area.dataset.translateOverrideLang] = pairs;
      });
    }
    rulesStatusEl.textContent = 'Saving...';
    try {
      await apiJson('api/translate-data.php', { method: 'POST', body: JSON.stringify({ action: 'rules', rules }) });
      await loadRules();
      rulesStatusEl.textContent = 'Saved · ' + rulesStatusEl.textContent;
    } catch (e) {
      rulesStatusEl.textContent = 'Error: ' + e.message;
    }
  }

  if (keysFilterEl) keysFilterEl.addEventListener('input', renderKeys);
  if (keysEmptyEl) keysEmptyEl.addEventListener('change', renderKeys);
  [glossaryEl, skipValuesEl].forEach((el) => el && el.addEventListener('input', () => { rulesStatusEl.textContent = 'Unsaved changes'; }));

  rootEl.querySelectorAll('[data-generate-action]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.generateAction;
      if (action === 'translate-rules-save') saveRules();
      if (action === 'translate') runTranslate();
      if (action === 'translate-stop') {
        translateStop = true;
        statusEl.textContent = 'Stopping after the current batch...';
      }
    });
  });

  const modesListEl = rootEl.querySelector('[data-translate-modes-list]');
  const modesStatusEl = rootEl.querySelector('[data-translate-modes-status]');

  function renderModes(modes) {
    modesListEl.innerHTML = '';
    const list = (modes && modes.list) || [];
    if (!list.length) {
      modesStatusEl.textContent = 'Only the default language is set in data_settings_languages.json.';
      return;
    }
    list.forEach((lang) => {
      const label = document.createElement('label');
      label.className = 'platform-translate-check';
      const name = document.createElement('span');
      name.textContent = `${lang.name} (${lang.iso})`;
      const select = document.createElement('select');
      select.className = 'platform-input platform-generate-select-sm';
      [['static', 'Static HTML pages (indexed)'], ['frontend', 'Frontend JS & JSON (not indexed)']].forEach(([value, text]) => {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = text;
        select.appendChild(opt);
      });
      select.value = lang.mode;
      select.addEventListener('change', () => saveModes({ [lang.iso]: select.value }));
      label.appendChild(name);
      label.appendChild(select);
      modesListEl.appendChild(label);
    });
    const frontend = list.filter((l) => l.mode === 'frontend').map((l) => l.iso);
    modesStatusEl.textContent = frontend.length ? `Frontend: ${frontend.join(', ')}` : 'All languages are static HTML';
  }

  async function loadModes() {
    if (!modesListEl) return;
    try {
      const data = await apiJson('api/translate-data.php?view=modes');
      renderModes(data.modes);
    } catch (e) {
      modesStatusEl.textContent = 'Error: ' + e.message;
    }
  }

  async function saveModes(modes) {
    modesStatusEl.textContent = 'Saving...';
    try {
      const data = await apiJson('api/translate-data.php', { method: 'POST', body: JSON.stringify({ action: 'modes', modes }) });
      renderModes(data.modes);
      modesStatusEl.textContent += ' · saved, run HTML Bulk Render';
    } catch (e) {
      modesStatusEl.textContent = 'Error: ' + e.message;
    }
  }

  if (rootEl.dataset.generateTab === 'translate') {
    loadModes();
    loadTranslate().then(loadRules);
  }

  const thumbsPanel = rootEl.querySelector('[data-generate-panel-thumbnails]');
  if (!thumbsPanel) return;

  const MISSING = '__missing__';
  const ALL = '__all__';
  const BIG = '__big__';
  const projectSelect = thumbsPanel.querySelector('[data-thumbs-project]');
  const thumbsStatus = thumbsPanel.querySelector('[data-thumbs-status]');
  const thumbsResults = thumbsPanel.querySelector('[data-thumbs-results]');
  const warningsEl = thumbsPanel.querySelector('[data-thumbs-warnings]');
  const previewBtn = thumbsPanel.querySelector('[data-thumbs-action="preview"]');
  const generateBtn = thumbsPanel.querySelector('[data-thumbs-action="generate"]');
  const stopBtn = thumbsPanel.querySelector('[data-thumbs-action="stop"]');

  let state = { projects: [], settings: { variants: ['default'], skip_existing: true } };
  let stopRequested = false;

  function projectLabel(p) {
    const mark = p.has_thumbnail ? ' ✓' : '';
    const kinds = [p.has_web ? 'web' : '', p.has_media ? 'media' : ''].filter(Boolean).join('+') || 'no images';
    return `${p.path}${mark} (${kinds})`;
  }

  function missingProjects() {
    return state.projects.filter((p) => !p.has_thumbnail);
  }

  function bigProjects() {
    const s = state.settings || {};
    if (!s.overwrite_if_bigger) return [];
    return state.projects.filter((p) => Object.values(p.generated || {}).some((kb) => kb !== false && kb > s.overwrite_bigger_than));
  }

  async function loadThumbs() {
    thumbsStatus.textContent = 'Loading projects...';
    try {
      const data = await apiJson('api/generate-thumbnails.php');
      state = data;
      warningsEl.innerHTML = '';
      (data.requirements || []).forEach((msg) => {
        const w = document.createElement('div');
        w.className = 'platform-generate-result-item is-error';
        w.textContent = msg;
        warningsEl.appendChild(w);
      });

      const selected = projectSelect.value;
      projectSelect.innerHTML = '';
      const add = (value, text) => {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = text;
        projectSelect.appendChild(opt);
      };
      add(MISSING, `All projects without a thumbnail (${missingProjects().length})`);
      if (data.settings.overwrite_if_bigger) {
        add(BIG, `Generated thumbnails over ${data.settings.overwrite_bigger_than} KB (${bigProjects().length})`);
      }
      add(ALL, `All render:true projects (${data.projects.length})`);
      data.projects.forEach((p) => add(p.path, projectLabel(p)));
      if (selected && [...projectSelect.options].some((o) => o.value === selected)) projectSelect.value = selected;

      thumbsStatus.textContent = `${data.projects.length} projects, ${missingProjects().length} without a thumbnail`;
      updateButtons();
    } catch (e) {
      thumbsStatus.textContent = 'Error loading projects: ' + e.message;
    }
  }

  function chosenVariants() {
    return ['default'];
  }

  function chosenProjects() {
    const v = projectSelect.value;
    if (v === MISSING) return missingProjects();
    if (v === BIG) return bigProjects();
    if (v === ALL) {
      const big = bigProjects();
      return state.projects.filter((p) => !(state.settings.skip_existing && p.has_thumbnail) || big.includes(p));
    }
    return state.projects.filter((p) => p.path === v);
  }

  function updateButtons() {
    const single = ![MISSING, ALL, BIG].includes(projectSelect.value);
    previewBtn.disabled = !single;
  }

  function addResult(r, imgSrc) {
    const item = document.createElement('figure');
    const ok = r.success && !r.skipped;
    item.className = 'platform-generate-thumb' + (r.success ? '' : (r.skipped ? ' is-skipped' : ' is-error'));
    if (imgSrc) {
      const img = document.createElement('img');
      img.src = imgSrc;
      img.alt = r.path;
      img.loading = 'lazy';
      item.appendChild(img);
    }
    const cap = document.createElement('figcaption');
    const title = document.createElement('strong');
    title.textContent = r.path;
    const info = document.createElement('span');
    info.textContent = ok
      ? (r.file || '') + (r.bytes ? ` · ${(r.bytes / 1024).toFixed(1)} KB` : '') + (r.replaced_kb ? ` (was ${r.replaced_kb} KB)` : '')
      : (r.message || 'error');
    cap.appendChild(title);
    cap.appendChild(info);
    if (r.sources) {
      const src = document.createElement('span');
      src.className = 'platform-generate-thumb-sources';
      src.textContent = Object.entries(r.sources).map(([k, f]) => `${k}: ${String(f).split('/').slice(-2).join('/')}`).join(' · ');
      cap.appendChild(src);
    }
    item.appendChild(cap);
    thumbsResults.prepend(item);
  }

  function setRunning(running) {
    stopBtn.style.display = running ? '' : 'none';
    generateBtn.disabled = running;
    previewBtn.disabled = running || [MISSING, ALL, BIG].includes(projectSelect.value);
    projectSelect.disabled = running;
  }

  async function runPreview() {
    const projects = chosenProjects();
    if (projects.length !== 1) return;
    setRunning(true);
    try {
      for (const variant of chosenVariants()) {
        thumbsStatus.textContent = `Preview ${projects[0].path}...`;
        const r = await apiJson('api/generate-thumbnails.php', {
          method: 'POST',
          body: JSON.stringify({ path: projects[0].path, variant, preview: true }),
        });
        addResult(r, r.data_uri || '');
      }
      thumbsStatus.textContent = 'Preview ready (nothing was saved)';
    } catch (e) {
      thumbsStatus.textContent = 'Preview failed: ' + e.message;
    } finally {
      setRunning(false);
    }
  }

  async function runGenerate() {
    const projects = chosenProjects();
    const variants = chosenVariants();
    const jobs = [];
    projects.forEach((p) => variants.forEach((variant) => jobs.push({ path: p.path, variant })));
    if (!jobs.length) {
      thumbsStatus.textContent = 'Nothing to generate.';
      return;
    }

    stopRequested = false;
    setRunning(true);
    let done = 0;
    let saved = 0;
    let skipped = 0;
    let errors = 0;
    for (const job of jobs) {
      if (stopRequested) break;
      thumbsStatus.textContent = `Generating ${done + 1}/${jobs.length} · ${job.path}`;
      try {
        const r = await apiJson('api/generate-thumbnails.php', { method: 'POST', body: JSON.stringify(job) });
        if (r.skipped) skipped++;
        else if (r.success) saved++;
        else errors++;
        const src = r.success && !r.skipped && r.file
          ? resolveUrl(`api/serve-file.php?path=${encodeURIComponent(r.file)}&v=${Date.now()}`)
          : '';
        addResult(r, src);
      } catch (e) {
        errors++;
        addResult({ path: job.path, variant: job.variant, success: false, message: e.message }, '');
      }
      done++;
    }
    thumbsStatus.textContent = `${stopRequested ? 'Stopped' : 'Done'}: ${saved} saved, ${skipped} skipped, ${errors} errors (${done}/${jobs.length})`;
    setRunning(false);
    loadThumbs();
  }

  projectSelect.addEventListener('change', updateButtons);
  previewBtn.addEventListener('click', runPreview);
  generateBtn.addEventListener('click', runGenerate);
  stopBtn.addEventListener('click', () => {
    stopRequested = true;
    thumbsStatus.textContent = 'Stopping after the current image...';
  });

  if (rootEl.dataset.generateTab === 'thumbnails') loadThumbs();
});

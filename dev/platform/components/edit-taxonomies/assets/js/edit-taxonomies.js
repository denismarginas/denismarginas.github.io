let etRoot, etTypesEl, etTypeListWrap, etListWrap, etList, etSearch, etEditor, etEditorBody, jsonEditorInstance;
let etTypeTitle, etRoutableWrap, etRoutableInput;
let taxonomyTypes = [];
let taxonomies = [];
let currentType = '';
let currentTerm = null;
let currentFile = '';
let typesApiUrl = '';
let jsonFileApiUrl = '';

document.addEventListener('DOMContentLoaded', async () => {
  etRoot = document.querySelector('[data-et-root]');
  if (!etRoot) return;

  etTypesEl = etRoot.querySelector('[data-et-types]');
  etListWrap = etRoot.querySelector('[data-et-term-list-wrap]');
  etList = etRoot.querySelector('[data-et-list]');
  etSearch = etRoot.querySelector('[data-et-search]');
  etEditor = etRoot.querySelector('[data-et-editor]');
  etEditorBody = etRoot.querySelector('[data-et-json-editor]');
  etTypeTitle = etRoot.querySelector('[data-et-title]');
  etRoutableWrap = etRoot.querySelector('[data-et-routable-wrap]');
  etRoutableInput = etRoot.querySelector('[data-et-routable]');

  typesApiUrl = resolveApiUrl('/api/types.php');
  jsonFileApiUrl = resolveApiUrl('/api/json-file.php');

  etRoot.querySelectorAll('[data-et-action]').forEach(btn => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.etAction;
      if (action === 'save') saveTerm();
      if (action === 'delete') deleteTerm();
      if (action === 'new') createNewTerm();
    });
  });

  if (etSearch) {
    etSearch.addEventListener('input', () => renderTermList());
  }

  if (etRoutableInput) {
    etRoutableInput.addEventListener('change', () => {
      updateTypeRoutable('taxonomy', currentType, etRoutableInput.checked);
    });
  }

  await loadTaxonomyTypes();
});

function resolveApiUrl(path) {
  const href = window.location.href;
  const qIdx = href.indexOf('?');
  const base = qIdx === -1 ? href : href.substring(0, qIdx);
  const lastSlash = base.lastIndexOf('/');
  const dir = lastSlash === -1 ? '' : base.substring(0, lastSlash + 1);
  return dir + path.replace(/^\//, '');
}

async function loadTaxonomyTypes() {
  try {
    const response = await fetch(typesApiUrl);
    const result = await response.json();
    if (result.ok) {
      taxonomyTypes = result.types.taxonomy || [];
    }
  } catch (e) {
    console.error('Error loading taxonomy types:', e);
  }
  renderTaxonomyTypes();
  if (taxonomyTypes.length) {
    const wanted = PlatformEntry.type();
    await selectTaxonomyType(taxonomyTypes.some(t => t.type === wanted) ? wanted : taxonomyTypes[0].type);
    openRequestedTerm();
  }
}

function renderTaxonomyTypes() {
  etTypesEl.innerHTML = '';
  if (!taxonomyTypes.length) {
    etTypesEl.innerHTML = '<div class="platform-list-item" style="color:var(--platform-color-text-secondary)">No taxonomy types found</div>';
    return;
  }
  taxonomyTypes.forEach(t => {
    const item = document.createElement('div');
    item.className = 'platform-list-item platform-ep-post-type-item' + (t.type === currentType ? ' is-active' : '');
    item.innerHTML = `<span>${escapeHtml(t.label || t.type)}</span><span class="platform-ep-post-type-count">${t.count}</span>`;
    item.addEventListener('click', () => selectTaxonomyType(t.type));
    etTypesEl.appendChild(item);
  });
}

async function selectTaxonomyType(type) {
  currentType = type;
  currentTerm = null;
  etEditor.style.display = 'none';

  const typeDef = taxonomyTypes.find(t => t.type === type);
  currentFile = typeDef?.file || '';
  if (etRoutableInput) etRoutableInput.checked = !!typeDef?.routable;
  if (etRoutableWrap) etRoutableWrap.style.display = 'flex';

  renderTaxonomyTypes();

  taxonomies = [];
  renderTermList();
  if (currentFile) {
    await loadTerms();
  }
}

async function loadTerms() {
  try {
    const response = await fetch(jsonFileApiUrl + '?path=' + encodeURIComponent(currentFile));
    const result = await response.json();
    if (result.ok) {
      taxonomies = Array.isArray(result.content) ? result.content : [];
    } else {
      taxonomies = [];
    }
    renderTermList();
    etListWrap.style.display = 'block';
  } catch (e) {
    taxonomies = [];
    renderTermList();
    console.error('Load terms error:', e);
  }
}

function renderTermList() {
  etList.innerHTML = '';
  const q = (etSearch?.value || '').toLowerCase();
  const filtered = taxonomies.filter(t => (t.name || t._id || '').toLowerCase().includes(q));
  if (!filtered.length) {
    etList.innerHTML = '<div class="platform-list-item" style="color:var(--platform-color-text-secondary)">No terms match your search.</div>';
    return;
  }
  filtered.forEach(term => {
    const item = document.createElement('div');
    item.className = 'platform-list-item' + (currentTerm && currentTerm._id === term._id ? ' is-active' : '');
    item.innerHTML = `<div>${escapeHtml(term.name || term._id)}</div>`;
    item.addEventListener('click', () => loadTerm(term._id));
    etList.appendChild(item);
  });
}

async function loadTerm(_id) {
  const found = taxonomies.find(t => t._id === _id);
  if (!found) return;

  currentTerm = JSON.parse(JSON.stringify(found));
  originalTermId = currentTerm._id;
  renderTermEditor();
  etEditor.style.display = '';
}

function renderTermEditor() {
  if (!currentTerm) return;
  if (etTypeTitle) etTypeTitle.textContent = termTitle(currentTerm);
  renderTermList();

  if (!etEditorBody) return;
  jsonEditorInstance = new JsonEditor(etEditorBody, currentTerm, {
    onChange: (val) => { currentTerm = val; },
  });
}

async function saveTerm() {
  if (!currentTerm || !currentFile) return;
  try {
    const lookupId = originalTermId || currentTerm._id;
    if (currentTerm._id !== lookupId && taxonomies.some(t => t._id === currentTerm._id)) {
      alert('A term with _id "' + currentTerm._id + '" already exists.');
      return;
    }
    const exists = taxonomies.some(t => t._id === lookupId);
    const updatedTerms = exists ? taxonomies.map(t => t._id === lookupId ? currentTerm : t) : taxonomies.concat([currentTerm]);
    const response = await fetch(jsonFileApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ path: currentFile, content: updatedTerms }),
    });
    const result = await response.json();
    if (result.ok) {
      taxonomies = updatedTerms;
      originalTermId = currentTerm._id;
      PlatformEntry.setId(currentTerm._id);
      if (etTypeTitle) etTypeTitle.textContent = termTitle(currentTerm);
      renderTermList();
      flashTermButton('save', 'Saved');
    } else {
      alert('Save failed: ' + (result.message || 'unknown error'));
    }
  } catch (e) {
    console.error('Save term error:', e);
  }
}

async function deleteTerm() {
  if (!currentTerm || !currentFile) return;
  if (!confirm('Delete this taxonomy permanently?')) return;

  const _id = originalTermId;
  if (!_id) {
    window.location.href = termListUrl();
    return;
  }
  try {
    const updatedList = taxonomies.filter(t => t._id !== _id);
    const response = await fetch(jsonFileApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ path: currentFile, content: updatedList }),
    });
    const result = await response.json();
    if (result.ok) {
      window.location.href = termListUrl();
    }
  } catch (e) {
    console.error('Delete term error:', e);
  }
}

function createNewTerm() {
  const id = 'new-' + (currentType || 'term') + '-' + Date.now().toString(36);
  const newTerm = {
    _id: id,
    data: {
      settings: { type: 'taxonomy', taxonomy_type: currentType, render: true },
      seo: { title: 'New term', description: '', slug: id, index: true },
    },
  };
  currentTerm = newTerm;
  originalTermId = '';
  renderTermEditor();
  etEditor.style.display = '';
}

async function updateTypeRoutable(kind, typeKey, routable) {
  try {
    const res = await fetch(jsonFileApiUrl + '?path=settings/data_settings_types.json');
    const result = await res.json();
    if (!result.ok) return;
    const registry = result.content || {};
    if (registry[kind] && registry[kind][typeKey]) {
      registry[kind][typeKey].routable = !!routable;
    }
    await fetch(jsonFileApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ path: 'settings/data_settings_types.json', content: registry }),
    });
  } catch (e) {
    console.error('updateTypeRoutable error:', e);
  }
}

function escapeHtml(text) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

let originalTermId = '';

function termListUrl() {
  return PlatformEntry.listUrl('taxonomy', currentType || PlatformEntry.type());
}

function termTitle(entry) {
  const data = entry && entry.data ? entry.data : {};
  return (data.seo && data.seo.title) || data.title || data.name || entry.title || entry.name || entry._id || '';
}

function openRequestedTerm() {
  if (PlatformEntry.isNew()) {
    createNewTerm();
    return;
  }
  const id = PlatformEntry.id();
  if (!taxonomies.some(item => item._id === id)) {
    PlatformEntry.showMissing(etRoot.querySelector('.platform-ep-layout'), 'No entry with _id "' + id + '" in ' + (currentFile || currentType) + '.', termListUrl());
    return;
  }
  loadTerm(id);
}

function flashTermButton(action, text) {
  const button = etRoot.querySelector('[data-et-action="' + action + '"] > span:not(.platform-svg)');
  if (!button) return;
  const original = button.dataset.label || button.textContent;
  button.dataset.label = original;
  button.textContent = text;
  window.setTimeout(() => { button.textContent = original; }, 1600);
}

let ecRoot, ecTypesEl, ecListWrap, ecList, ecSearch, ecEditor, ecEditorBody, jsonEditorInstance;
let ecTypeTitle, ecRoutableWrap, ecRoutableInput;
let collectionTypes = [];
let entries = [];
let currentType = '';
let currentEntry = null;
let currentFile = '';
let typesApiUrl = '';
let jsonFileApiUrl = '';

document.addEventListener('DOMContentLoaded', async () => {
  ecRoot = document.querySelector('[data-ec-root]');
  if (!ecRoot) return;

  ecTypesEl = ecRoot.querySelector('[data-ec-types]');
  ecListWrap = ecRoot.querySelector('[data-ec-entry-list-wrap]');
  ecList = ecRoot.querySelector('[data-ec-list]');
  ecSearch = ecRoot.querySelector('[data-ec-search]');
  ecEditor = ecRoot.querySelector('[data-ec-editor]');
  ecEditorBody = ecRoot.querySelector('[data-ec-json-editor]');
  ecTypeTitle = ecRoot.querySelector('[data-ec-title]');
  ecRoutableWrap = ecRoot.querySelector('[data-ec-routable-wrap]');
  ecRoutableInput = ecRoot.querySelector('[data-ec-routable]');

  typesApiUrl = resolveApiUrl('/api/types.php');
  jsonFileApiUrl = resolveApiUrl('/api/json-file.php');

  ecRoot.querySelectorAll('[data-ec-action]').forEach(btn => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.ecAction;
      if (action === 'save') saveEntry();
      if (action === 'delete') deleteEntry();
      if (action === 'new') createNewEntry();
    });
  });

  if (ecSearch) {
    ecSearch.addEventListener('input', () => renderEntryList());
  }

  if (ecRoutableInput) {
    ecRoutableInput.addEventListener('change', () => {
      updateTypeRoutable('collection', currentType, ecRoutableInput.checked);
    });
  }

  await loadCollectionTypes();
});

function resolveApiUrl(path) {
  const href = window.location.href;
  const qIdx = href.indexOf('?');
  const base = qIdx === -1 ? href : href.substring(0, qIdx);
  const lastSlash = base.lastIndexOf('/');
  const dir = lastSlash === -1 ? '' : base.substring(0, lastSlash + 1);
  return dir + path.replace(/^\//, '');
}

async function loadCollectionTypes() {
  try {
    const response = await fetch(typesApiUrl);
    const result = await response.json();
    if (result.ok) {
      collectionTypes = result.types.collection || [];
    }
  } catch (e) {
    console.error('Error loading collections:', e);
  }
  renderCollectionTypes();
  if (collectionTypes.length) {
    const wanted = PlatformEntry.type();
    await selectCollection(collectionTypes.some(t => t.type === wanted) ? wanted : collectionTypes[0].type);
    openRequestedEntry();
  }
}

function renderCollectionTypes() {
  ecTypesEl.innerHTML = '';
  if (!collectionTypes.length) {
    ecTypesEl.innerHTML = '<div class="platform-list-item" style="color:var(--platform-color-text-secondary)">No collections found</div>';
    return;
  }
  collectionTypes.forEach(t => {
    const item = document.createElement('div');
    item.className = 'platform-list-item platform-ep-post-type-item' + (t.type === currentType ? ' is-active' : '');
    item.innerHTML = `<span>${escapeHtml(t.label || t.type)}</span><span class="platform-ep-post-type-count">${t.count}</span>`;
    item.addEventListener('click', () => selectCollection(t.type));
    ecTypesEl.appendChild(item);
  });
}

async function selectCollection(type) {
  currentType = type;
  currentEntry = null;
  ecEditor.style.display = 'none';

  const typeDef = collectionTypes.find(t => t.type === type);
  currentFile = typeDef?.file || '';
  if (ecRoutableInput) ecRoutableInput.checked = !!typeDef?.routable;
  if (ecRoutableWrap) ecRoutableWrap.style.display = 'flex';

  renderCollectionTypes();

  entries = [];
  renderEntryList();
  if (currentFile) {
    await loadEntries();
  }
}

async function loadEntries() {
  try {
    const response = await fetch(jsonFileApiUrl + '?path=' + encodeURIComponent(currentFile));
    const result = await response.json();
    if (result.ok) {
      entries = Array.isArray(result.content) ? result.content : [];
    } else {
      entries = [];
    }
    renderEntryList();
    ecListWrap.style.display = 'block';
  } catch (e) {
    entries = [];
    renderEntryList();
    console.error('Load entries error:', e);
  }
}

function renderEntryList() {
  ecList.innerHTML = '';
  const q = (ecSearch?.value || '').toLowerCase();
  const filtered = entries.filter(i => (i.name || i._id || '').toLowerCase().includes(q));
  if (!filtered.length) {
    ecList.innerHTML = '<div class="platform-list-item" style="color:var(--platform-color-text-secondary)">No entries match your search.</div>';
    return;
  }
  filtered.forEach(item => {
    const li = document.createElement('div');
    li.className = 'platform-list-item' + (currentEntry && currentEntry._id === item._id ? ' is-active' : '');
    li.innerHTML = `<div>${escapeHtml(item.name || item._id)}</div>`;
    li.addEventListener('click', () => loadEntry(item._id));
    ecList.appendChild(li);
  });
}

function loadEntry(_id) {
  const found = entries.find(i => i._id === _id);
  if (!found) return;

  currentEntry = JSON.parse(JSON.stringify(found));
  originalEntryId = currentEntry._id;
  renderEntryEditor();
  ecEditor.style.display = '';
}

function renderEntryEditor() {
  if (!currentEntry) return;
  if (ecTypeTitle) ecTypeTitle.textContent = entryTitle(currentEntry);
  renderEntryList();

  if (!ecEditorBody) return;
  jsonEditorInstance = new JsonEditor(ecEditorBody, currentEntry, {
    onChange: (val) => { currentEntry = val; },
  });
}

async function saveEntry() {
  if (!currentEntry || !currentFile) return;
  try {
    const lookupId = originalEntryId || currentEntry._id;
    if (currentEntry._id !== lookupId && entries.some(i => i._id === currentEntry._id)) {
      alert('An entry with _id "' + currentEntry._id + '" already exists.');
      return;
    }
    const exists = entries.some(i => i._id === lookupId);
    const updatedEntries = exists ? entries.map(i => i._id === lookupId ? currentEntry : i) : entries.concat([currentEntry]);
    const response = await fetch(jsonFileApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ path: currentFile, content: updatedEntries }),
    });
    const result = await response.json();
    if (result.ok) {
      entries = updatedEntries;
      originalEntryId = currentEntry._id;
      PlatformEntry.setId(currentEntry._id);
      if (ecTypeTitle) ecTypeTitle.textContent = entryTitle(currentEntry);
      renderEntryList();
      flashEntryButton('save', 'Saved');
    } else {
      alert('Save failed: ' + (result.message || 'unknown error'));
    }
  } catch (e) {
    console.error('Save entry error:', e);
  }
}

async function deleteEntry() {
  if (!currentEntry || !currentFile) return;
  if (!confirm('Delete this entry permanently?')) return;

  const _id = originalEntryId;
  if (!_id) {
    window.location.href = entryListUrl();
    return;
  }
  try {
    const updatedList = entries.filter(i => i._id !== _id);
    const response = await fetch(jsonFileApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ path: currentFile, content: updatedList }),
    });
    const result = await response.json();
    if (result.ok) {
      window.location.href = entryListUrl();
    }
  } catch (e) {
    console.error('Delete entry error:', e);
  }
}

function createNewEntry() {
  const newId = (currentType || 'entry') + '-' + Date.now().toString(36);
  currentEntry = {
    _id: newId,
    settings: { collection_type: currentType, render: true },
    data: {
      seo: { title: 'New entry', slug: newId },
    },
  };
  originalEntryId = '';
  renderEntryEditor();
  ecEditor.style.display = '';
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

let originalEntryId = '';

function entryListUrl() {
  return PlatformEntry.listUrl('collection', currentType || PlatformEntry.type());
}

function entryTitle(entry) {
  if (!entry) return '';
  const typeDef = collectionTypes.find(t => t.type === currentType) || {};
  const custom = Array.isArray(typeDef.title_path) ? typeDef.title_path : (typeDef.title_path ? [typeDef.title_path] : []);
  const paths = custom.concat(['data.seo.title', 'data.title', 'data.name', 'title', 'name', 'text']);
  for (const path of paths) {
    const value = path.split('.').reduce((v, key) => (v && typeof v === 'object' ? v[key] : undefined), entry);
    if ((typeof value === 'string' || typeof value === 'number') && String(value).trim() !== '') {
      const text = String(value).replace(/\s+/g, ' ').trim();
      return text.length > 80 ? text.slice(0, 77).trim() + '…' : text;
    }
  }
  const id = String(entry._id || '').replace(/[-_]+/g, ' ').trim();
  return id.charAt(0).toUpperCase() + id.slice(1);
}

function openRequestedEntry() {
  if (PlatformEntry.isNew()) {
    createNewEntry();
    return;
  }
  const id = PlatformEntry.id();
  if (!entries.some(item => item._id === id)) {
    PlatformEntry.showMissing(ecRoot.querySelector('.platform-ep-layout'), 'No entry with _id "' + id + '" in ' + (currentFile || currentType) + '.', entryListUrl());
    return;
  }
  loadEntry(id);
}

function flashEntryButton(action, text) {
  const button = ecRoot.querySelector('[data-ec-action="' + action + '"] > span:not(.platform-svg)');
  if (!button) return;
  const original = button.dataset.label || button.textContent;
  button.dataset.label = original;
  button.textContent = text;
  window.setTimeout(() => { button.textContent = original; }, 1600);
}

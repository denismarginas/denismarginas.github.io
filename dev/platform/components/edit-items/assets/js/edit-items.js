let eiRoot, eiTypesEl, eiListWrap, eiList, eiSearch, eiEditor, eiEditorBody, jsonEditorInstance;
let eiTypeTitle, eiRoutableWrap, eiRoutableInput;
let itemTypes = [];
let items = [];
let currentType = '';
let currentItem = null;
let currentFile = '';
let typesApiUrl = '';
let jsonFileApiUrl = '';

document.addEventListener('DOMContentLoaded', async () => {
  eiRoot = document.querySelector('[data-ei-root]');
  if (!eiRoot) return;

  eiTypesEl = eiRoot.querySelector('[data-ei-types]');
  eiListWrap = eiRoot.querySelector('[data-ei-item-list-wrap]');
  eiList = eiRoot.querySelector('[data-ei-list]');
  eiSearch = eiRoot.querySelector('[data-ei-search]');
  eiEditor = eiRoot.querySelector('[data-ei-editor]');
  eiEditorBody = eiRoot.querySelector('[data-ei-json-editor]');
  eiTypeTitle = eiRoot.querySelector('[data-ei-title]');
  eiRoutableWrap = eiRoot.querySelector('[data-ei-routable-wrap]');
  eiRoutableInput = eiRoot.querySelector('[data-ei-routable]');

  typesApiUrl = resolveApiUrl('/api/types.php');
  jsonFileApiUrl = resolveApiUrl('/api/json-file.php');

  eiRoot.querySelectorAll('[data-ei-action]').forEach(btn => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.eiAction;
      if (action === 'save') saveItem();
      if (action === 'delete') deleteItem();
      if (action === 'new') createNewItem();
    });
  });

  if (eiSearch) {
    eiSearch.addEventListener('input', () => renderItemList());
  }

  if (eiRoutableInput) {
    eiRoutableInput.addEventListener('change', () => {
      updateTypeRoutable('item', currentType, eiRoutableInput.checked);
    });
  }

  await loadItemTypes();
});

function resolveApiUrl(path) {
  const href = window.location.href;
  const qIdx = href.indexOf('?');
  const base = qIdx === -1 ? href : href.substring(0, qIdx);
  const lastSlash = base.lastIndexOf('/');
  const dir = lastSlash === -1 ? '' : base.substring(0, lastSlash + 1);
  return dir + path.replace(/^\//, '');
}

async function loadItemTypes() {
  try {
    const response = await fetch(typesApiUrl);
    const result = await response.json();
    if (result.ok) {
      itemTypes = result.types.item || [];
    }
  } catch (e) {
    console.error('Error loading item types:', e);
  }
  renderItemTypes();
  if (itemTypes.length) {
    selectItemType(itemTypes[0].type);
  }
}

function renderItemTypes() {
  eiTypesEl.innerHTML = '';
  if (!itemTypes.length) {
    eiTypesEl.innerHTML = '<div class="platform-list-item" style="color:var(--platform-color-text-secondary)">No item types found</div>';
    return;
  }
  itemTypes.forEach(t => {
    const item = document.createElement('div');
    item.className = 'platform-list-item platform-ep-post-type-item' + (t.type === currentType ? ' is-active' : '');
    item.innerHTML = `<span>${escapeHtml(t.label || t.type)}</span><span class="platform-ep-post-type-count">${t.count}</span>`;
    item.addEventListener('click', () => selectItemType(t.type));
    eiTypesEl.appendChild(item);
  });
}

async function selectItemType(type) {
  currentType = type;
  currentItem = null;
  eiEditor.style.display = 'none';

  const typeDef = itemTypes.find(t => t.type === type);
  currentFile = typeDef?.file || '';
  if (eiRoutableInput) eiRoutableInput.checked = !!typeDef?.routable;
  if (eiRoutableWrap) eiRoutableWrap.style.display = 'flex';

  renderItemTypes();

  items = [];
  renderItemList();
  if (currentFile) {
    await loadItems();
  }
}

async function loadItems() {
  try {
    const response = await fetch(jsonFileApiUrl + '?path=' + encodeURIComponent(currentFile));
    const result = await response.json();
    if (result.ok) {
      items = Array.isArray(result.content) ? result.content : [];
    } else {
      items = [];
    }
    renderItemList();
    eiListWrap.style.display = 'block';
  } catch (e) {
    items = [];
    renderItemList();
    console.error('Load items error:', e);
  }
}

function renderItemList() {
  eiList.innerHTML = '';
  const q = (eiSearch?.value || '').toLowerCase();
  const filtered = items.filter(i => (i.name || i._id || '').toLowerCase().includes(q));
  if (!filtered.length) {
    eiList.innerHTML = '<div class="platform-list-item" style="color:var(--platform-color-text-secondary)">No items match your search.</div>';
    return;
  }
  filtered.forEach(item => {
    const li = document.createElement('div');
    li.className = 'platform-list-item' + (currentItem && currentItem._id === item._id ? ' is-active' : '');
    li.innerHTML = `<div>${escapeHtml(item.name || item._id)}</div>`;
    li.addEventListener('click', () => loadItem(item._id));
    eiList.appendChild(li);
  });
}

function loadItem(_id) {
  const found = items.find(i => i._id === _id);
  if (!found) return;

  currentItem = JSON.parse(JSON.stringify(found));
  renderItemEditor();
  eiEditor.style.display = '';
}

function renderItemEditor() {
  if (!currentItem) return;
  if (eiTypeTitle) eiTypeTitle.textContent = currentItem.name || currentItem._id;
  renderItemList();

  if (!eiEditorBody) return;
  jsonEditorInstance = new JsonEditor(eiEditorBody, currentItem, {
    onChange: (val) => { currentItem = val; },
  });
}

async function saveItem() {
  if (!currentItem || !currentFile) return;
  try {
    const updatedItems = items.map(i => i._id === currentItem._id ? currentItem : i);
    const response = await fetch(jsonFileApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ path: currentFile, content: updatedItems }),
    });
    const result = await response.json();
    if (result.ok) {
      const idx = items.findIndex(i => i._id === currentItem._id);
      if (idx !== -1) {
        items[idx] = currentItem;
      } else {
        items.push(currentItem);
      }
      renderItemList();
    }
  } catch (e) {
    console.error('Save item error:', e);
  }
}

async function deleteItem() {
  if (!currentItem || !currentFile) return;
  if (!confirm('Delete this item permanently?')) return;

  const _id = currentItem._id;
  try {
    const updatedList = items.filter(i => i._id !== _id);
    const response = await fetch(jsonFileApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ path: currentFile, content: updatedList }),
    });
    const result = await response.json();
    if (result.ok) {
      currentItem = null;
      eiEditor.style.display = 'none';
      items = updatedList;
      renderItemList();
    }
  } catch (e) {
    console.error('Delete item error:', e);
  }
}

function createNewItem() {
  const newId = 'new_item_' + Date.now();
  currentItem = {
    _id: newId,
    name: 'New Item',
    description: '',
    type: currentType,
  };
  renderItemEditor();
  eiEditor.style.display = '';
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

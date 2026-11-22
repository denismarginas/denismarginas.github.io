function renderContent() {
  contentListEl.innerHTML = '';
  const items = currentPost.content || [];

  if (!items.length) {
    contentListEl.innerHTML = '<div class="platform-ep-content-empty">' + escapeHtml(tr('editPosts.noContent', 'No content items. Click "Add Component" to add one.')) + '</div>';
    return;
  }

  items.forEach((item, i) => {
    const el = createContentItemEl(item, i, items, 0);
    contentListEl.appendChild(el);
  });
}

function createContentItemEl(item, index, parentArray, level) {
  const el = document.createElement('div');
  el.className = 'platform-ep-content-item' + (level > 0 ? ' platform-ep-content-item-nested' : '');
  el.dataset.contentIndex = index;

  const isTopLevel = level === 0;
  if (isTopLevel) el.draggable = true;

  const header = buildContentHeader(item.component || 'unknown', isTopLevel);

  const body = document.createElement('div');
  body.className = 'platform-ep-content-item-body';

  const dataFields = item.data || {};
  const dataKeys = Object.keys(dataFields);
  const children = item.children || [];

  dataKeys.forEach(k => {
    body.appendChild(createDataFieldRow(k, dataFields[k], item));
  });

  body.appendChild(buildAddFieldRow(item));

  if (children.length) {
    const childrenWrap = document.createElement('div');
    childrenWrap.className = 'platform-ep-children';
    children.forEach((child, ci) => {
      childrenWrap.appendChild(createContentItemEl(child, ci, children, level + 1));
    });
    body.appendChild(childrenWrap);
  }

  const addRow = buildAddChildRow(item);
  body.appendChild(addRow);

  if (!dataKeys.length && !children.length) {
    const empty = document.createElement('div');
    empty.style.cssText = 'color:var(--platform-color-text-secondary);font-size:11px;padding:0 0 8px 0';
    empty.textContent = tr('editPosts.noDataFields', 'No data fields');
    body.insertBefore(empty, addRow);
  }

  el.appendChild(header);
  el.appendChild(body);

  if (isTopLevel) wireContentDrag(el, index);

  header.querySelector('[data-ep-action="remove-content"]').addEventListener('click', () => {
    parentArray.splice(index, 1);
    markDirty();
    renderContent();
  });

  return el;
}

function createDataFieldRow(key, val, item) {
  const label = document.createElement('label');
  label.className = 'platform-form-label';
  label.textContent = key.replace(/_/g, ' ');

  const input = document.createElement('input');
  input.className = 'platform-ep-input';
  input.style.width = '100%';
  let v = val;
  if (Array.isArray(v)) v = v.join(', ');
  if (typeof v === 'object' && v !== null) v = JSON.stringify(v);
  input.value = v ?? '';
  input.dataset.dataKey = key;
  input.addEventListener('change', () => {
    if (!item.data) item.data = {};
    item.data[input.dataset.dataKey] = input.value;
    markDirty();
  });
  const row = document.createElement('div');
  row.className = 'platform-form-row';
  row.appendChild(label);
  row.appendChild(input);
  return row;
}

function makeFieldRow(key, val, dataObj, post, prefix) {
  const label = document.createElement('label');
  label.className = 'platform-form-label';
  label.textContent = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

  const isComplex = Array.isArray(val) ? val.some(v => typeof v === 'object' && v !== null) : (typeof val === 'object' && val !== null);
  const isSimpleArray = Array.isArray(val) && !isComplex;

  let input;
  let displayVal;

  if (isComplex) {
    input = document.createElement('textarea');
    input.className = 'platform-ep-input';
    input.style.width = '100%';
    input.style.fontFamily = 'monospace';
    input.style.fontSize = '11px';
    displayVal = JSON.stringify(val, null, 2);
  } else if (isSimpleArray) {
    input = document.createElement('input');
    input.className = 'platform-ep-input';
    input.style.width = '100%';
    displayVal = val.join(', ');
  } else {
    input = document.createElement('input');
    input.className = 'platform-ep-input';
    input.style.width = '100%';
    displayVal = val;
  }

  input.value = displayVal;
  input.dataset.fieldKey = key;
  if (prefix) input.dataset.fieldPrefix = prefix;

  input.addEventListener('change', () => {
    const newVal = input.value;
    const prefix2 = input.dataset.fieldPrefix;

    if (isComplex) {
      try { currentPost = setFieldVal(key, JSON.parse(newVal), dataObj, post, prefix2); }
      catch (e) { setStatus(tr('editPosts.invalidJson', 'Invalid JSON: {message}', { message: e.message })); return; }
    } else if (isSimpleArray) {
      currentPost = setFieldVal(key, newVal.split(',').map(s => s.trim()).filter(Boolean), dataObj, post, prefix2);
    } else {
      currentPost = setFieldVal(key, newVal, dataObj, post, prefix2);
    }
    markDirty();
  });

  const row = document.createElement('div');
  row.className = 'platform-form-row';
  row.appendChild(label);
  row.appendChild(input);
  return row;
}

function setFieldVal(key, val, dataObj, post, prefix) {
  const p = { ...post };
  if (prefix === 'seo') {
    if (!p.data) p.data = {};
    if (!p.data.seo) p.data.seo = {};
    p.data = { ...p.data, seo: { ...p.data.seo, [key]: val } };
  } else if (prefix) {
    if (!p.data) p.data = {};
    p.data = { ...p.data };
    if (!p.data[prefix]) p.data[prefix] = {};
    p.data[prefix] = { ...p.data[prefix], [key]: val };
  } else if (p.data) {
    p.data = { ...p.data, [key]: val };
  } else {
    p[key] = val;
  }
  return p;
}

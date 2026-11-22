JsonEditor.prototype._buildObjectNode = function (obj, path, depth) {
  const wrap = document.createElement('div');
  wrap.className = 'platform-je-object';

  const keys = Object.keys(obj);
  keys.forEach(k => {
    wrap.appendChild(this._buildKeyValueRow(k, obj, path, depth));
  });

  if (!this.readonly) {
    wrap.appendChild(this._buildAddKeyRow(obj, path, depth));
  }

  return wrap;
};

JsonEditor.prototype._buildAddKeyRow = function (obj, path) {
  const row = document.createElement('div');
  row.className = 'platform-je-row platform-je-add-row';

  const keyInput = document.createElement('input');
  keyInput.className = 'platform-je-key-input platform-je-add-key-input';
  keyInput.placeholder = 'new key';

  const typeSelect = document.createElement('select');
  typeSelect.className = 'platform-je-type-switch';
  ['string', 'number', 'boolean', 'null', 'object', 'array'].forEach(t => {
    const opt = document.createElement('option');
    opt.value = t;
    opt.textContent = t;
    typeSelect.appendChild(opt);
  });

  const addBtn = document.createElement('button');
  addBtn.className = 'platform-je-btn platform-je-btn-add';
  addBtn.innerHTML = PlatformSvg.render('add', { size: 14 });

  const confirmAdd = () => {
    const k = keyInput.value.trim();
    if (!k || k in obj) return;
    obj[k] = this._defaultValue(typeSelect.value);
    this._emitChange(path);
    this._rebuildValNode(this.container, this._value, [], 0);
  };

  addBtn.addEventListener('click', confirmAdd);
  keyInput.addEventListener('keydown', e => { if (e.key === 'Enter') confirmAdd(); });

  row.appendChild(keyInput);
  row.appendChild(typeSelect);
  row.appendChild(addBtn);

  return row;
};

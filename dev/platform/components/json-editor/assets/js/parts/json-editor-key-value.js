JsonEditor.prototype._buildKeyValueRow = function (key, obj, path, depth) {
  const row = document.createElement('div');
  row.className = 'platform-je-row';
  row.dataset.jePath = path.concat(key).join('.');

  const keyInput = document.createElement('input');
  keyInput.className = 'platform-je-key-input';
  keyInput.value = key;
  keyInput.spellcheck = false;

  if (!this.readonly) {
    keyInput.addEventListener('change', () => {
      const newKey = keyInput.value.trim();
      if (!newKey || newKey === key) return;
      if (newKey in obj) { keyInput.value = key; return; }
      obj[newKey] = obj[key];
      delete obj[key];
      this._emitChange(path);
    });
  } else {
    keyInput.disabled = true;
  }

  const colon = document.createElement('span');
  colon.className = 'platform-je-colon';
  colon.textContent = ': ';

  const valNode = this._buildNode(obj[key], path.concat(key), depth + 1);

  const typeSwitch = document.createElement('select');
  typeSwitch.className = 'platform-je-type-switch';
  ['string', 'number', 'boolean', 'null', 'object', 'array'].forEach(t => {
    const opt = document.createElement('option');
    opt.value = t;
    opt.textContent = t;
    if (t === this._typeOf(obj[key])) opt.selected = true;
    typeSwitch.appendChild(opt);
  });

  if (!this.readonly) {
    typeSwitch.addEventListener('change', () => {
      obj[key] = this._convertType(typeSwitch.value, obj[key]);
      this._emitChange(path);
      this._rebuildValNode(valNode, obj[key], path.concat(key), depth + 1);
    });
  } else {
    typeSwitch.disabled = true;
  }

  const actions = document.createElement('span');
  actions.className = 'platform-je-actions';

  if (!this.readonly) {
    const delBtn = document.createElement('button');
    delBtn.className = 'platform-je-btn platform-je-btn-del';
    delBtn.innerHTML = PlatformSvg.render('close', { size: 14 });
    delBtn.title = 'Delete key';
    delBtn.addEventListener('click', () => {
      delete obj[key];
      this._emitChange(path);
      row.remove();
    });
    actions.appendChild(delBtn);
  }

  row.appendChild(keyInput);
  row.appendChild(colon);
  row.appendChild(typeSwitch);
  row.appendChild(valNode);
  row.appendChild(actions);

  return row;
};

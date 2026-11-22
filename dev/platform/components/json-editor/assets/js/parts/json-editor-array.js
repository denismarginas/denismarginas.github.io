JsonEditor.prototype._buildArrayNode = function (arr, path, depth) {
  const wrap = document.createElement('div');
  wrap.className = 'platform-je-array';

  arr.forEach((item, i) => {
    wrap.appendChild(this._buildArrayItemRow(item, i, arr, path, depth));
  });

  if (!this.readonly) {
    wrap.appendChild(this._buildAddItemRow(arr, path, depth));
  }

  return wrap;
};

JsonEditor.prototype._buildArrayItemRow = function (item, index, arr, path, depth) {
  const row = document.createElement('div');
  row.className = 'platform-je-row';
  row.dataset.jePath = path.concat(index).join('.');

  const idxBadge = document.createElement('span');
  idxBadge.className = 'platform-je-idx';
  idxBadge.textContent = index;

  const valNode = this._buildNode(item, path.concat(index), depth + 1);

  const typeSwitch = document.createElement('select');
  typeSwitch.className = 'platform-je-type-switch';
  ['string', 'number', 'boolean', 'null', 'object', 'array'].forEach(t => {
    const opt = document.createElement('option');
    opt.value = t;
    opt.textContent = t;
    if (t === this._typeOf(item)) opt.selected = true;
    typeSwitch.appendChild(opt);
  });

  if (!this.readonly) {
    typeSwitch.addEventListener('change', () => {
      arr[index] = this._convertType(typeSwitch.value, arr[index]);
      this._emitChange(path);
      this._rebuildValNode(valNode, arr[index], path.concat(index), depth + 1);
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
    delBtn.title = 'Delete item';
    delBtn.addEventListener('click', () => {
      arr.splice(index, 1);
      this._emitChange(path);
      this._rebuildValNode(this.container, this._value, [], 0);
    });
    actions.appendChild(delBtn);
  }

  row.appendChild(idxBadge);
  row.appendChild(typeSwitch);
  row.appendChild(valNode);
  row.appendChild(actions);

  return row;
};

JsonEditor.prototype._buildAddItemRow = function (arr, path, depth) {
  const row = document.createElement('div');
  row.className = 'platform-je-row platform-je-add-row';

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
  addBtn.innerHTML = PlatformSvg.render('add', { size: 14 }) + '<span>Add</span>';

  addBtn.addEventListener('click', () => {
    arr.push(this._defaultValue(typeSelect.value));
    this._emitChange(path);
    this._rebuildValNode(this.container, this._value, [], 0);
  });

  row.appendChild(typeSelect);
  row.appendChild(addBtn);

  return row;
};

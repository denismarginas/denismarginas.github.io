JsonEditor.prototype._buildPrimitiveNode = function (val, path, type) {
  const node = document.createElement('div');
  node.className = 'platform-je-primitive';

  if (type === 'null') {
    const span = document.createElement('span');
    span.className = 'platform-je-null';
    span.textContent = 'null';
    node.appendChild(span);
    return node;
  }

  if (type === 'boolean') {
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.className = 'platform-checkbox';
    cb.checked = val;
    if (!this.readonly) {
      cb.addEventListener('change', () => {
        this._setPathValue(path, cb.checked);
        this._emitChange(path.slice(0, -1));
      });
    } else {
      cb.disabled = true;
    }
    node.appendChild(cb);
    const label = document.createElement('span');
    label.textContent = String(val);
    node.appendChild(label);
    return node;
  }

  const input = document.createElement('input');
  input.className = 'platform-je-value-input';
  input.type = type === 'number' ? 'number' : 'text';
  input.value = type === 'number' ? String(val) : val;

  if (!this.readonly) {
    input.addEventListener('change', () => {
      const p = path.slice(0, -1);
      const k = path[path.length - 1];
      const parent = this._getPathValue(p);
      if (parent !== undefined) {
        parent[k] = type === 'number' ? parseFloat(input.value) : input.value;
        this._emitChange(p);
      }
    });
  } else {
    input.disabled = true;
  }

  node.appendChild(input);
  return node;
};

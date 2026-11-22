JsonEditor.prototype._convertType = function (newType, oldVal) {
  switch (newType) {
    case 'string': return String(oldVal ?? '');
    case 'number': return Number(oldVal) || 0;
    case 'boolean': return Boolean(oldVal);
    case 'null': return null;
    case 'object': return typeof oldVal === 'object' && !Array.isArray(oldVal) && oldVal !== null ? oldVal : {};
    case 'array': return Array.isArray(oldVal) ? oldVal : [];
  }
};

JsonEditor.prototype._defaultValue = function (type) {
  switch (type) {
    case 'string': return '';
    case 'number': return 0;
    case 'boolean': return false;
    case 'null': return null;
    case 'object': return {};
    case 'array': return [];
  }
};

JsonEditor.prototype._getPathValue = function (path) {
  let cur = this._value;
  for (const seg of path) {
    if (cur === undefined || cur === null) return undefined;
    cur = cur[seg];
  }
  return cur;
};

JsonEditor.prototype._setPathValue = function (path, val) {
  let cur = this._value;
  for (let i = 0; i < path.length - 1; i++) {
    cur = cur[path[i]];
  }
  cur[path[path.length - 1]] = val;
};

JsonEditor.prototype._rebuildValNode = function (node, val, path, depth) {
  const parent = node.parentElement;
  const newNode = this._buildNode(val, path, depth);
  if (parent) parent.replaceChild(newNode, node);
};

JsonEditor.prototype._emitChange = function (path) {
  this.onChange(this._value, path);
};

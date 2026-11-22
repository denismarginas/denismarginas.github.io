class JsonEditor {
  constructor(container, value, options = {}) {
    this.container = container;
    this._value = value;
    this.onChange = options.onChange || (() => {});
    this.readonly = options.readonly || false;
    this.maxDepth = options.maxDepth || 20;
    this._pathCache = new Map();
    this.render();
  }

  get value() { return this._value; }
  set value(v) { this._value = v; this.render(); }

  render() {
    this.container.innerHTML = '';
    this.container.className = 'platform-je-root';
    this.container.appendChild(this._buildNode(this._value, [], 0));
  }

  _typeOf(val) {
    if (val === null) return 'null';
    if (Array.isArray(val)) return 'array';
    return typeof val;
  }

  _buildNode(val, path, depth) {
    const type = this._typeOf(val);
    const node = document.createElement('div');
    node.className = 'platform-je-node platform-je-type-' + type;

    if (depth >= this.maxDepth) {
      node.textContent = '[max depth]';
      return node;
    }

    if (type === 'object') {
      node.appendChild(this._buildObjectNode(val, path, depth));
    } else if (type === 'array') {
      node.appendChild(this._buildArrayNode(val, path, depth));
    } else {
      node.appendChild(this._buildPrimitiveNode(val, path, type));
    }

    return node;
  }
}

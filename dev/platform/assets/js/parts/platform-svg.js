(function () {
  const cache = {};
  let started = false;

  async function preload() {
    if (started) return;
    started = true;
    const names = [
      'add', 'back', 'check', 'chevron-bottom', 'chevron-left', 'chevron-right',
      'chevron-top', 'close', 'compile', 'duplicate', 'edit', 'error', 'home',
      'remove', 'render', 'save', 'settings', 'trash', 'update', 'view', 'warning', 'web'
    ];
    await Promise.all(names.map(async (n) => {
      try {
        const res = await fetch('assets/svg/' + n + '.svg');
        if (res.ok) cache[n] = await res.text();
      } catch (e) { }
    }));
  }

  function icon(name) {
    return cache[name] || '';
  }

  function render(name, opts) {
    const svg = icon(name);
    if (!svg) return '';
    const o = opts || {};
    const size = o.size ? ' style="--platform-svg-size:' + o.size + 'px"' : '';
    const cls = o.class ? ' ' + o.class : '';
    return '<span class="platform-svg' + cls + '"' + size + '>' + svg + '</span>';
  }

  window.PlatformSvg = { preload, icon, render };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', preload);
  } else {
    preload();
  }
})();

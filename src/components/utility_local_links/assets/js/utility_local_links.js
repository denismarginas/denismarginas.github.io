(function () {
  'use strict';

  var host = window.location.hostname;
  var isFile = window.location.protocol === 'file:';
  var isLocal = isFile
    || /^(localhost|127\.0\.0\.1|\[?::1\]?)$/.test(host)
    || /\.(local|localhost|test)$/.test(host);
  if (!isLocal) return;

  function fix(link) {
    var raw = link.getAttribute('href');
    if (!raw || link.hasAttribute('data-no-html')) return;
    raw = raw.trim();
    if (/^(#|\?|mailto:|tel:|javascript:|data:)/i.test(raw)) return;

    var url;
    try { url = new URL(raw, window.location.href); } catch (e) { return; }
    if (isFile ? url.protocol !== 'file:' : url.origin !== window.location.origin) return;

    var path = url.pathname;
    if (path === '' || path.slice(-1) === '/' || path.indexOf('/dev/platform/') !== -1) return;
    var last = path.slice(path.lastIndexOf('/') + 1);
    if (last === '' || last.indexOf('.') !== -1) return;

    var cut = raw.search(/[?#]/);
    var base = cut === -1 ? raw : raw.slice(0, cut);
    var rest = cut === -1 ? '' : raw.slice(cut);
    if (base === '' || base.slice(-1) === '/') return;

    link.setAttribute('href', base + '.html' + rest);
  }

  function scan(root) {
    if (!root || root.nodeType !== 1) return;
    if (root.matches && root.matches('a[href], area[href]')) fix(root);
    var links = root.querySelectorAll('a[href], area[href]');
    for (var i = 0; i < links.length; i++) fix(links[i]);
  }

  function init() {
    scan(document.body);
    if (!window.MutationObserver) return;
    new MutationObserver(function (records) {
      records.forEach(function (record) {
        if (record.type === 'attributes') fix(record.target);
        else for (var i = 0; i < record.addedNodes.length; i++) scan(record.addedNodes[i]);
      });
    }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['href'] });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

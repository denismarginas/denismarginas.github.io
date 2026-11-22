(function () {
  const IMG_EXTS = ['webp', 'jpg', 'jpeg', 'png', 'avif'];

  function isImageValue(val) {
    if (typeof val !== 'string' || !val) return false;
    const ext = val.split('.').pop().toLowerCase();
    return IMG_EXTS.includes(ext);
  }

  function resolveApiUrl(path) {
    const href = window.location.href;
    const qIdx = href.indexOf('?');
    const base = qIdx === -1 ? href : href.substring(0, qIdx);
    const lastSlash = base.lastIndexOf('/');
    const dir = lastSlash === -1 ? '' : base.substring(0, lastSlash + 1);
    return dir + path.replace(/^\//, '');
  }

  function getGlobalImgDir() {
    const el = document.querySelector('[data-ep-root]');
    return el?.dataset?.globalImgDir || 'projects';
  }

  function renderThumb(input) {
    const val = input.value.trim();
    if (!isImageValue(val)) return;
    const parent = input.parentElement;
    if (!parent || parent.querySelector('.platform-img-thumb')) return;

    const dir = getGlobalImgDir();
    const src = resolveApiUrl('/api/serve-file.php?type=img&path=' + encodeURIComponent(dir + '/' + val));

    const img = document.createElement('img');
    img.className = 'platform-img-thumb';
    img.style.marginTop = '4px';
    img.onerror = function () {
      this.outerHTML = '<span class="platform-img-not-found" style="color:var(--platform-danger);font-size:11px">IMG Not Found</span>';
    };
    img.src = src;
    parent.appendChild(img);
  }

  function scanInputs(root) {
    (root || document).querySelectorAll('.platform-ep-input').forEach(renderThumb);
  }

  const observer = new MutationObserver(function (mutations) {
    for (const m of mutations) {
      for (const node of m.addedNodes) {
        if (node.nodeType === 1) {
          if (node.matches && node.matches('.platform-ep-input')) {
            renderThumb(node);
          }
          if (node.querySelectorAll) {
            node.querySelectorAll('.platform-ep-input').forEach(renderThumb);
          }
        }
      }
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    scanInputs();
    observer.observe(document.body, { childList: true, subtree: true });
  });
})();

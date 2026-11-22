(function () {
  'use strict';

  var MAX_RATIO = 1.5;

  function viewportCols(width) {
    if (width > 1024) return 4;
    if (width > 768) return 3;
    if (width > 320) return 2;
    return 1;
  }

  function ratioOf(img) {
    var w = parseFloat(img.getAttribute('width')) || img.naturalWidth;
    var h = parseFloat(img.getAttribute('height')) || img.naturalHeight;
    return w && h ? h / w : 1;
  }

  function setup(root) {
    var tiles = Array.prototype.slice.call(root.querySelectorAll('.media-gallery-filterable-tile'));
    var chips = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-filter]'));
    var foot = root.querySelector('.media-gallery-filterable-foot');
    var status = root.querySelector('[data-gallery-status]');
    var more = root.querySelector('[data-gallery-more]');
    var all = root.querySelector('[data-gallery-all]');
    var group = root.getAttribute('data-group') || '';
    var pageSize = parseInt(root.getAttribute('data-page-size') || '0', 10) || 0;
    var statusText = root.getAttribute('data-status-text') || '{shown} / {total}';

    var grid = root.querySelector('.media-gallery-filterable-grid');
    var featured = root.getAttribute('data-featured') !== 'false';

    var filter = 'all';
    var limit = pageSize || Infinity;

    function layout() {
      if (!grid) return;
      var visible = tiles.filter(function (tile) { return !tile.hidden; });
      var cols = Math.max(1, Math.min(visible.length, viewportCols(window.innerWidth)));
      grid.style.setProperty('--gallery-cols', cols);
      grid.setAttribute('data-masonry', '');

      var gap = parseFloat(getComputedStyle(grid).columnGap) || 0;
      var colWidth = (grid.clientWidth - gap * (cols - 1)) / cols;
      tiles.forEach(function (tile, i) {
        if (tile.hidden) return;
        var img = tile.querySelector('img');
        var isFeatured = featured && filter === 'all' && cols >= 3 && tile === visible[0];
        tile.classList.toggle('is-featured', isFeatured);
        var width = isFeatured ? colWidth * 2 + gap : colWidth;
        var naturalWidth = img ? (parseFloat(img.getAttribute('width')) || img.naturalWidth || width) : width;
        var small = visible.length === 1 && naturalWidth < width;
        tile.classList.toggle('is-small', small);
        var shownWidth = small ? naturalWidth : width;
        var ratio = img ? ratioOf(img) : 1;
        if (tile.classList.contains('is-logo')) ratio = Math.min(Math.max(ratio, 0.75), 1.333);
        var height = Math.min(shownWidth * ratio, width * MAX_RATIO, window.innerHeight * 0.85);
        tile.style.gridRowEnd = 'span ' + Math.max(1, Math.round(height + gap));
      });
    }

    function update() {
      var matching = tiles.filter(function (tile) {
        return filter === 'all' || tile.getAttribute('data-gallery') === filter;
      });
      var shown = 0;
      tiles.forEach(function (tile) {
        var visible = matching.indexOf(tile) !== -1 && shown < limit;
        if (visible) shown++;
        tile.hidden = !visible;
        tile.setAttribute('data-popup-group', group + (visible ? '' : '-hidden'));
      });
      layout();

      if (status) status.textContent = statusText.replace('{shown}', shown).replace('{total}', matching.length);
      if (foot) foot.hidden = shown >= matching.length;
    }

    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        filter = chip.getAttribute('data-gallery-filter') || 'all';
        chips.forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
        limit = pageSize || Infinity;
        update();
      });
    });

    if (more) more.addEventListener('click', function () { limit += pageSize || Infinity; update(); });
    if (all) all.addEventListener('click', function () { limit = Infinity; update(); });

    update();

    if (grid && 'ResizeObserver' in window) {
      var lastWidth = 0;
      new ResizeObserver(function () {
        if (grid.clientWidth === lastWidth) return;
        lastWidth = grid.clientWidth;
        layout();
      }).observe(grid);
    } else {
      window.addEventListener('resize', layout);
    }
    tiles.forEach(function (tile) {
      var img = tile.querySelector('img');
      if (img && !img.getAttribute('width') && !img.complete) img.addEventListener('load', layout, { once: true });
    });
  }

  function init() {
    document.querySelectorAll('[data-media-gallery]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

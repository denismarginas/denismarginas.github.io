(function () {
  function fold(text) {
    var s = String(text || '').toLowerCase();
    if (s.normalize) s = s.normalize('NFD').replace(/[̀-ͯ]/g, '');
    return s.replace(/\s+/g, ' ').trim();
  }

  function init(form) {
    var root = form.closest('[data-search]') || form.parentNode;
    var list = document.getElementById(form.dataset.target);
    var indexEl = form.querySelector('.filters-index');
    if (!list || !indexEl) return;

    var index = {};
    try { index = JSON.parse(indexEl.textContent || '{}'); } catch (e) { return; }
    Object.keys(index).forEach(function (id) { index[id].keywords = fold(index[id].keywords); });

    var items = Array.prototype.slice.call(list.querySelectorAll('li[data-item-id]'));
    var selects = Array.prototype.slice.call(form.querySelectorAll('select[data-filter]'));
    var input = form.querySelector('.item-search');
    var showAll = false;
    var status = root.querySelector('[data-search-status]');
    var minChars = parseInt(form.dataset.minChars, 10) || 2;
    var timer = 0;

    var ui = window.filtersToolbar ? window.filtersToolbar.init(form, {
      list: list,
      input: input,
      selects: selects,
      views: {
        all: function (value) { showAll = value === 'yes'; apply(); }
      }
    }) : null;

    function query() {
      return input ? input.value.trim() : '';
    }

    function apply() {
      var q = query();
      var folded = fold(q);
      var words = folded.split(' ').filter(Boolean);
      var filtered = selects.some(function (select) { return select.value !== ''; });
      var active = folded.length >= minChars || showAll || filtered;
      if (folded.length < minChars) words = [];
      var shown = 0;

      items.forEach(function (item) {
        var entry = index[item.dataset.itemId];
        var visible = active && !!entry &&
          words.every(function (word) { return entry.keywords.indexOf(word) !== -1; }) &&
          selects.every(function (select) {
            return !select.value || (entry[select.dataset.filter] || []).indexOf(select.value) !== -1;
          });
        item.hidden = !visible;
        if (visible) shown++;
      });

      if (ui) ui.update(shown);
      list.setAttribute('data-results', shown);
      if (status) {
        status.textContent = !active
          ? (status.dataset.labelHint || '')
          : (shown === 0 ? (status.dataset.labelNone || '').replace('{query}', q) : '');
      }
      if (window.listingToolbarUrl) window.listingToolbarUrl.save(form);
    }

    selects.forEach(function (select) { select.addEventListener('change', apply); });
    if (input) {
      input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(apply, 120);
      });
    }
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      clearTimeout(timer);
      apply();
    });
    form.addEventListener('reset', function () { setTimeout(apply, 0); });

    if (window.listingToolbarUrl) window.listingToolbarUrl.restore(form);
    apply();
  }

  function start() {
    document.querySelectorAll('form.search-filters[data-target]').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

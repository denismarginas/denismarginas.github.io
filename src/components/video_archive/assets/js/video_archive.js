(function () {
  function init(form) {
    var list = document.getElementById(form.dataset.target);
    var indexEl = form.querySelector('.filters-index');
    if (!list || !indexEl) return;

    var index = {};
    try { index = JSON.parse(indexEl.textContent || '{}'); } catch (e) { return; }

    var items = Array.prototype.slice.call(list.querySelectorAll('li[data-item-id]'));
    var selects = Array.prototype.slice.call(form.querySelectorAll('select[data-filter]'));
    var input = form.querySelector('.item-search');
    var empty = list.parentNode ? list.parentNode.querySelector('.video-archive-empty') : null;

    var ui = window.filtersToolbar ? window.filtersToolbar.init(form, {
      list: list,
      input: input,
      selects: selects
    }) : null;

    function matches(entry, words) {
      var text = entry.keywords || '';
      if (!words.every(function (word) { return text.indexOf(word) !== -1; })) return false;
      return selects.every(function (select) {
        if (!select.value) return true;
        return (entry[select.dataset.filter] || []).indexOf(select.value) !== -1;
      });
    }

    function apply() {
      var words = (input ? input.value : '').toLowerCase().split(/\s+/).filter(Boolean);
      var shown = 0;

      items.forEach(function (item) {
        var entry = index[item.dataset.itemId];
        var visible = !entry || matches(entry, words);
        item.hidden = !visible;
        if (visible) shown++;
      });

      if (ui) ui.update(shown);
      if (empty) empty.hidden = shown !== 0;
      list.setAttribute('data-results', shown);
      if (window.listingToolbarUrl) window.listingToolbarUrl.save(form);
    }

    selects.forEach(function (select) { select.addEventListener('change', apply); });
    if (input) input.addEventListener('input', apply);
    form.addEventListener('submit', function (event) { event.preventDefault(); apply(); });
    form.addEventListener('reset', function () { setTimeout(apply, 0); });

    if (window.listingToolbarUrl) window.listingToolbarUrl.restore(form);
    apply();
  }

  function start() {
    document.querySelectorAll('form.video-archive-filters[data-target]').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

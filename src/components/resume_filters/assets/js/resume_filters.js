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
    var empty = form.querySelector('.resume-filters-empty');

    var ui = window.filtersToolbar ? window.filtersToolbar.init(form, { list: list, input: input, selects: selects }) : null;

    function apply() {
      var words = (input ? input.value : '').toLowerCase().split(/\s+/).filter(Boolean);
      var shown = 0;
      items.forEach(function (item) {
        var entry = index[item.dataset.itemId];
        var visible = !entry || (
          words.every(function (word) { return (entry.keywords || '').indexOf(word) !== -1; }) &&
          selects.every(function (select) { return !select.value || entry[select.dataset.filter] === select.value; })
        );
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
    document.querySelectorAll('form.resume-filters[data-target]').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

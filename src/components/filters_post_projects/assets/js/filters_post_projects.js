(function () {
  function yearOf(value) {
    var match = String(value || '').match(/\d{4}/);
    return match ? parseInt(match[0], 10) : null;
  }

  function matchesFilter(values, select) {
    var selected = window.filtersToolbar.values(select);
    if (!selected.length) return true;
    values = values || [];

    if (select.dataset.type === 'date') {
      var mode = select.dataset.mode || '=';
      return selected.some(function (value) {
        var wanted = parseInt(value, 10);
        return values.some(function (v) {
          var year = yearOf(v);
          if (year === null) return false;
          if (mode === '>=') return year >= wanted;
          if (mode === '<=') return year <= wanted;
          return year === wanted;
        });
      });
    }
    return selected.some(function (value) { return values.indexOf(value) !== -1; });
  }

  function matchesKeywords(entry, words) {
    var text = entry.keywords || '';
    return words.every(function (word) { return text.indexOf(word) !== -1; });
  }

  function init(form) {
    var list = document.getElementById(form.dataset.target);
    var indexEl = form.querySelector('.filters-index');
    if (!list || !indexEl) return;

    var index = {};
    try { index = JSON.parse(indexEl.textContent || '{}'); } catch (e) { return; }

    var items = Array.prototype.slice.call(list.querySelectorAll('li[data-post-id]'));
    var selects = Array.prototype.slice.call(form.querySelectorAll('select[data-filter]'));
    var input = form.querySelector('.post-search');

    var ui = window.filtersToolbar ? window.filtersToolbar.init(form, {
      list: list,
      input: input,
      selects: selects,
      views: {
        preview: function (value) { list.classList.toggle('show-preview', value === 'preview'); }
      }
    }) : null;

    function apply() {
      var words = (input ? input.value : '').toLowerCase().split(/\s+/).filter(Boolean);
      var shown = 0;

      items.forEach(function (item) {
        var entry = index[item.dataset.postId];
        var visible = !entry || (
          matchesKeywords(entry, words) &&
          selects.every(function (select) { return matchesFilter(entry[select.dataset.filter], select); })
        );
        item.hidden = !visible;
        if (visible) shown++;
      });

      list.setAttribute('data-results', shown);
      if (ui) ui.update(shown);
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
    document.querySelectorAll('form.filters-post-projects[data-target]').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

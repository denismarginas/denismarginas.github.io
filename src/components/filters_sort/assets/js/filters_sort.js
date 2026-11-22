(function () {
  function datasetKey(by) {
    return String(by).replace(/[^a-zA-Z0-9]+(.)/g, function (_, c) { return c.toUpperCase(); });
  }

  function toNumber(value) {
    if (typeof value === 'number') return value;
    var match = String(value == null ? '' : value).match(/-?\d+(?:[.,]\d+)?/);
    return match ? parseFloat(match[0].replace(',', '.')) : null;
  }

  function init(box) {
    var form = box.closest('form') || box.parentNode;
    var list = document.getElementById(box.dataset.target);
    var bySelect = box.querySelector('.sort-by');
    var orderSelect = box.querySelector('.sort-order');
    if (!list || !bySelect || !orderSelect) return;

    var index = {};
    var indexEl = form.querySelector('.filters-index');
    if (indexEl) {
      try { index = JSON.parse(indexEl.textContent || '{}'); } catch (e) { index = {}; }
    }

    var items = Array.prototype.slice.call(list.children).filter(function (li) {
      return li.dataset && (li.dataset.itemId || li.dataset.postId);
    });
    var original = new Map(items.map(function (li, i) { return [li, i]; }));

    function valueOf(li, by, type) {
      var entry = index[li.dataset.itemId || li.dataset.postId];
      var raw = entry && entry._sort && Object.prototype.hasOwnProperty.call(entry._sort, by)
        ? entry._sort[by]
        : li.dataset[datasetKey(by)];
      if (raw === undefined || raw === null || raw === '') return null;
      return type === 'number' || type === 'date' ? toNumber(raw) : String(raw);
    }

    function render(order) {
      order.forEach(function (li) { list.appendChild(li); });
    }

    function apply() {
      var option = bySelect.options[bySelect.selectedIndex];
      var by = bySelect.value;

      if (!by) {
        render(items.slice().sort(function (a, b) { return original.get(a) - original.get(b); }));
        return;
      }

      var type = option.dataset.type || 'text';
      var dir = orderSelect.value === 'desc' ? -1 : 1;

      render(items.slice().sort(function (a, b) {
        var va = valueOf(a, by, type);
        var vb = valueOf(b, by, type);
        if (va === null || vb === null) {
          if (va !== vb) return va === null ? 1 : -1;
        } else {
          var cmp = typeof va === 'number'
            ? va - vb
            : va.localeCompare(vb, undefined, { numeric: true, sensitivity: 'base' });
          if (cmp !== 0) return dir * cmp;
        }
        return original.get(a) - original.get(b);
      }));
    }

    function onFieldChange() {
      var option = bySelect.options[bySelect.selectedIndex];
      var isDefault = !bySelect.value;
      orderSelect.disabled = isDefault;
      if (!isDefault) {
        orderSelect.querySelector('option[value="asc"]').textContent = option.dataset.labelAsc || 'Asc';
        orderSelect.querySelector('option[value="desc"]').textContent = option.dataset.labelDesc || 'Desc';
        orderSelect.value = option.dataset.order === 'desc' ? 'desc' : 'asc';
      }
      apply();
    }

    bySelect.addEventListener('change', onFieldChange);
    orderSelect.addEventListener('change', apply);
    if (form.tagName === 'FORM') {
      form.addEventListener('reset', function () { setTimeout(onFieldChange, 0); });
    }

    var toggle = form.querySelector('.toggle-sort');
    var row = form.querySelector('.block-sort');
    if (toggle && row) {
      toggle.addEventListener('click', function () {
        var open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        row.hidden = !open;
      });
    }

    onFieldChange();

    var url = window.listingToolbarUrl;
    if (url) {
      var defaults = url.restoreSort(form, bySelect, orderSelect, onFieldChange, apply);
      var saveUrl = function () { url.saveSort(bySelect, orderSelect, defaults); };
      bySelect.addEventListener('change', saveUrl);
      orderSelect.addEventListener('change', saveUrl);
      if (form.tagName === 'FORM') {
        form.addEventListener('reset', function () { setTimeout(saveUrl, 0); });
      }
    }
  }

  function start() {
    document.querySelectorAll('.filters-sort[data-target]').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

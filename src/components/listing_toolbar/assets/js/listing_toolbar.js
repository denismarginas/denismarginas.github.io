(function () {
  function slug(value) {
    var s = String(value == null ? '' : value).toLowerCase();
    if (s.normalize) s = s.normalize('NFD').replace(/[̀-ͯ]/g, '');
    return s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }

  function params() {
    try { return new URL(window.location.href).searchParams; } catch (e) { return null; }
  }

  function token(select, value) {
    var s = slug(value);
    var same = Array.prototype.filter.call(select.options, function (o) { return o.value !== '' && slug(o.value) === s; });
    return same.length > 1 || s === '' ? String(value) : s;
  }

  function pick(select, wanted) {
    if (wanted == null || wanted === '') return false;
    var options = Array.prototype.filter.call(select.options, function (o) { return o.value !== ''; });
    var w = slug(wanted);
    var match = options.filter(function (o) { return o.value === wanted; })[0]
      || options.filter(function (o) { return slug(o.value) === w; })[0]
      || options.filter(function (o) { return slug(o.textContent) === w; })[0];
    if (!match) return false;
    select.value = match.value;
    return true;
  }

  function set(values) {
    if (!window.history || !history.replaceState || !window.URL) return;
    var url;
    try { url = new URL(window.location.href); } catch (e) { return; }
    Object.keys(values).forEach(function (key) {
      var v = values[key];
      if (v === null || v === undefined || v === '') url.searchParams.delete(key);
      else url.searchParams.set(key, v);
    });
    var next = url.toString().replace(/\?$/, '');
    if (next !== window.location.href) history.replaceState(history.state, '', next);
  }

  function open(form, buttonSelector, blockSelector) {
    var block = form.querySelector(blockSelector);
    var button = form.querySelector(buttonSelector);
    if (block) block.hidden = false;
    if (button) button.setAttribute('aria-expanded', 'true');
  }

  function filterSelects(form) {
    return Array.prototype.filter.call(form.querySelectorAll('select[data-filter]'), function (s) { return !!s.name; });
  }

  function searchInput(form) {
    return form.querySelector('input[type="search"]');
  }

  function restore(form) {
    var p = params();
    var defaults = {};
    var filtered = false;
    filterSelects(form).forEach(function (select) {
      defaults[select.name] = select.value;
      if (p && p.has(select.name) && pick(select, p.get(select.name)) && select.value !== defaults[select.name]) filtered = true;
    });
    form._toolbarDefaults = defaults;
    if (filtered) open(form, '.toggle-filters', '.block-filters');

    var input = searchInput(form);
    var q = p ? (p.get('q') || '').trim() : '';
    if (input && q) {
      input.value = q;
      var box = input.closest('.search-input');
      if (box && box.hidden) open(form, '.toggle-search', '.search-input');
    }
  }

  function save(form) {
    if (!form._toolbarDefaults) return;
    var values = {};
    filterSelects(form).forEach(function (select) {
      var v = select.value;
      values[select.name] = v === '' || v === form._toolbarDefaults[select.name] ? null : token(select, v);
    });
    var input = searchInput(form);
    if (input) values.q = input.value.trim();
    set(values);
  }

  function restoreSort(form, bySelect, orderSelect, onField, apply) {
    var defaults = { by: bySelect.value, order: orderSelect.value };
    var p = params();
    var changed = false;
    if (p && p.has('sort') && pick(bySelect, p.get('sort')) && bySelect.value !== defaults.by) {
      onField();
      changed = true;
    }
    var order = p ? p.get('order') : null;
    if ((order === 'asc' || order === 'desc') && !orderSelect.disabled && order !== orderSelect.value) {
      orderSelect.value = order;
      apply();
      changed = true;
    }
    if (changed) open(form, '.toggle-sort', '.block-sort');
    return defaults;
  }

  function saveSort(bySelect, orderSelect, defaults) {
    if (!defaults) return;
    var isDefault = bySelect.value === defaults.by && orderSelect.value === defaults.order;
    var option = bySelect.options[bySelect.selectedIndex];
    set({
      sort: isDefault ? null : (bySelect.value === '' ? 'default' : slug(option ? option.textContent : bySelect.value)),
      order: isDefault || bySelect.value === '' ? null : orderSelect.value
    });
  }

  window.listingToolbarUrl = {
    slug: slug,
    restore: restore,
    save: save,
    restoreSort: restoreSort,
    saveSort: saveSort
  };
})();

(() => {
  const SEARCH_DELAY = 150;
  const SAVE_DELAY = 400;

  document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-cl-root]');
    if (root) initListing(root);
  });

  function initListing(root) {
    const el = {
      count: root.querySelector('[data-cl-count]'),
      search: root.querySelector('[data-cl-search]'),
      summary: root.querySelector('[data-cl-summary]'),
      body: root.querySelector('[data-cl-body]'),
      range: root.querySelector('[data-cl-range]'),
      pagination: root.querySelector('[data-cl-pagination]'),
      sentinel: root.querySelector('[data-cl-sentinel]'),
      saved: root.querySelector('[data-cl-saved]'),
      routable: root.querySelector('[data-cl-routable]'),
      limit: root.querySelector('[data-cl-pref="limit_max"]'),
      showAll: root.querySelector('[data-cl-pref="show_all"]'),
      modes: Array.from(root.querySelectorAll('[data-cl-pref="mode"]')),
      sorts: Array.from(root.querySelectorAll('[data-cl-sort]')),
    };

    const strings = parseJson(root.dataset.strings, {});
    const icons = parseJson(root.dataset.icons, {});
    const tr = (key, fallback, vars) => {
      let text = strings[key] || fallback;
      Object.entries(vars || {}).forEach(([name, value]) => { text = text.split('{' + name + '}').join(String(value)); });
      return text;
    };

    const kind = root.dataset.kind;
    const type = root.dataset.type;
    const columnCount = root.querySelectorAll('thead th').length || 5;

    const state = {
      rows: [],
      filtered: [],
      query: '',
      sort: { key: '', dir: 1 },
      page: 1,
      shown: 0,
      prefs: normalizePrefs(parseJson(root.dataset.prefs, {})),
      file: '',
      loaded: false,
    };
    state.shown = state.prefs.limit_max;

    let observer = null;
    let searchTimer = null;
    let saveTimer = null;

    function normalizePrefs(prefs) {
      const limit = parseInt(prefs.limit_max, 10);
      return {
        show_all: prefs.show_all === true,
        limit_max: Number.isFinite(limit) ? Math.min(500, Math.max(1, limit)) : 20,
        mode: prefs.mode === 'scroll' ? 'scroll' : 'pagination',
      };
    }

    function readPrefsFromForm() {
      const checked = el.modes.find((input) => input.checked);
      return normalizePrefs({
        show_all: !!(el.showAll && el.showAll.checked),
        limit_max: el.limit ? el.limit.value : state.prefs.limit_max,
        mode: checked ? checked.value : state.prefs.mode,
      });
    }

    function syncPrefsForm() {
      if (el.showAll) el.showAll.checked = state.prefs.show_all;
      if (el.limit) {
        el.limit.value = String(state.prefs.limit_max);
        el.limit.disabled = state.prefs.show_all;
      }
      el.modes.forEach((input) => {
        input.checked = input.value === state.prefs.mode;
        input.disabled = state.prefs.show_all;
      });
    }

    function onPrefsChange() {
      state.prefs = readPrefsFromForm();
      state.page = 1;
      state.shown = state.prefs.limit_max;
      syncPrefsForm();
      render();
      window.clearTimeout(saveTimer);
      saveTimer = window.setTimeout(savePrefs, SAVE_DELAY);
    }

    async function savePrefs() {
      showSaved('');
      try {
        const response = await fetch(root.dataset.settingsApi, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ listing: state.prefs }),
        });
        const result = await response.json();
        if (!result.ok) throw new Error(result.message || 'save failed');
        state.prefs = normalizePrefs(result.listing || state.prefs);
        syncPrefsForm();
        showSaved(tr('saved', 'Saved'));
      } catch (error) {
        console.error('Listing preferences:', error);
        showSaved(tr('saveFailed', 'Could not save'), true);
      }
    }

    function showSaved(text, isError) {
      if (!el.saved) return;
      el.saved.textContent = text;
      el.saved.classList.toggle('is-error', !!isError);
      el.saved.classList.toggle('is-visible', text !== '');
    }

    async function load() {
      try {
        const url = root.dataset.listApi + '?kind=' + encodeURIComponent(kind) + '&type=' + encodeURIComponent(type);
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!result.ok) throw new Error(result.message || 'API error');
        state.rows = (result.rows || []).map((row, index) => Object.assign(row, { _order: index, _search: searchText(row) }));
        state.file = result.file || '';
        state.loaded = true;
        if (el.count) el.count.textContent = '(' + state.rows.length + ')';
        render();
      } catch (error) {
        console.error('Content listing:', error);
        el.body.innerHTML = messageRow(error.message);
      }
    }

    function searchText(row) {
      const terms = Object.values(row.taxonomies || {}).flat().join(' ');
      return [row._id, row.title, row.type, row.subtype, terms, row.publish].join(' ').toLowerCase();
    }

    function applyFilter() {
      const query = state.query.trim().toLowerCase();
      const rows = query === '' ? state.rows.slice() : state.rows.filter((row) => row._search.includes(query));
      const { key, dir } = state.sort;
      if (key !== '') rows.sort((a, b) => compare(a, b, key) * dir || a._order - b._order);
      state.filtered = rows;
    }

    function compare(a, b, key) {
      if (key === 'publish') {
        const x = a.publish_ts;
        const y = b.publish_ts;
        if (x === y) return 0;
        if (x === null || x === undefined) return 1;
        if (y === null || y === undefined) return -1;
        return x - y;
      }
      const x = String(key === 'title' ? (a.title || a._id) : key === 'type' ? a.type + ' ' + (a.subtype || '') : a[key] || '');
      const y = String(key === 'title' ? (b.title || b._id) : key === 'type' ? b.type + ' ' + (b.subtype || '') : b[key] || '');
      return x.localeCompare(y, undefined, { numeric: true, sensitivity: 'base' });
    }

    function setSort(key) {
      if (state.sort.key === key) {
        if (state.sort.dir === 1) state.sort.dir = -1;
        else state.sort = { key: '', dir: 1 };
      } else {
        state.sort = { key, dir: key === 'publish' ? -1 : 1 };
      }
      el.sorts.forEach((button) => {
        const active = button.dataset.clSort === state.sort.key;
        button.classList.toggle('is-sorted', active);
        button.classList.toggle('is-desc', active && state.sort.dir === -1);
        button.closest('th').setAttribute('aria-sort', active ? (state.sort.dir === 1 ? 'ascending' : 'descending') : 'none');
      });
      state.page = 1;
      state.shown = state.prefs.limit_max;
      render();
    }

    function render() {
      if (!state.loaded) return;
      applyFilter();

      const total = state.filtered.length;
      const { show_all: showAll, limit_max: limit, mode } = state.prefs;
      let from = 0;
      let to = total;

      if (!showAll && mode === 'pagination') {
        const pages = Math.max(1, Math.ceil(total / limit));
        state.page = Math.min(Math.max(1, state.page), pages);
        from = (state.page - 1) * limit;
        to = Math.min(total, from + limit);
      } else if (!showAll && mode === 'scroll') {
        to = Math.min(total, Math.max(limit, state.shown));
      }

      const visible = state.filtered.slice(from, to);
      if (!state.rows.length) el.body.innerHTML = messageRow(tr('empty', 'Nothing here yet.'));
      else if (!total) el.body.innerHTML = messageRow(tr('noMatch', 'No entries match your search.'));
      else el.body.innerHTML = visible.map(rowHtml).join('');

      if (el.summary) el.summary.textContent = state.query.trim() !== '' ? total + ' / ' + state.rows.length : '';
      if (el.range) {
        el.range.textContent = total === 0 ? ''
          : (from === 0 && to === total) ? tr('rangeAll', '{total} entries', { total })
            : tr('range', 'Showing {from}–{to} of {total}', { from: from + 1, to, total });
      }

      renderPagination(total, showAll || mode !== 'pagination');
      updateSentinel(!showAll && mode === 'scroll' && to < total);
    }

    function rowHtml(row) {
      const title = row.title || tr('untitled', '(no title)');
      const taxonomies = Object.entries(row.taxonomies || {});
      const chips = taxonomies.length
        ? taxonomies.map(([name, terms]) => terms.map((term) =>
          '<span class="platform-cl-chip" title="' + esc(name) + '"><span class="platform-cl-chip-key">' + esc(name) + '</span>' + esc(term) + '</span>').join('')).join('')
        : '<span class="platform-cl-none">—</span>';
      const editLabel = tr('edit', 'Edit');
      const viewLabel = tr('view', 'View');
      const actions = '<a class="platform-cl-action platform-cl-action-edit" href="' + esc(row.edit_url) + '" title="' + esc(editLabel + ' ' + title) + '">'
        + (icons.edit || '') + '<span>' + esc(editLabel) + '</span></a>'
        + (row.view_url
          ? '<a class="platform-cl-action" href="' + esc(row.view_url) + '" target="_blank" rel="noopener" title="' + esc(viewLabel + ' ' + title) + '">' + (icons.view || '') + '<span>' + esc(viewLabel) + '</span></a>'
          : '<span class="platform-cl-action is-disabled" aria-hidden="true">' + (icons.view || '') + '<span>' + esc(viewLabel) + '</span></span>');

      return '<tr class="' + (row.hidden ? 'is-hidden' : '') + '">'
        + '<td class="platform-cl-col-id"><code title="' + esc(row._id) + '">' + esc(row._id) + '</code></td>'
        + '<td class="platform-cl-col-title">'
        + '<a class="platform-cl-title" href="' + esc(row.edit_url) + '">' + esc(title) + '</a>'
        + (row.hidden ? ' <span class="platform-cl-badge">' + esc(tr('hidden', 'Hidden')) + '</span>' : '')
        + '</td>'
        + '<td class="platform-cl-col-type">' + esc(row.type) + (row.subtype ? '<span class="platform-cl-subtype">' + esc(row.subtype) + '</span>' : '') + '</td>'
        + '<td class="platform-cl-col-taxonomies"><div class="platform-cl-chips">' + chips + '</div></td>'
        + '<td class="platform-cl-col-publish">' + (row.publish ? esc(row.publish) : '<span class="platform-cl-none">—</span>') + '</td>'
        + '<td class="platform-cl-col-actions"><div class="platform-cl-actions">' + actions + '</div></td>'
        + '</tr>';
    }

    function messageRow(text) {
      return '<tr><td class="platform-cl-message" colspan="' + columnCount + '">' + esc(text) + '</td></tr>';
    }

    function renderPagination(total, hide) {
      const pages = Math.ceil(total / state.prefs.limit_max);
      if (hide || pages <= 1) {
        el.pagination.innerHTML = '';
        return;
      }
      const current = state.page;
      const button = (page, label, extra) => '<button class="platform-cl-page' + (extra || '') + '" type="button" data-cl-page="' + page + '"'
        + (page === current && !extra ? ' aria-current="page"' : '') + '>' + label + '</button>';

      let html = button(current - 1, '‹', ' platform-cl-page-step' + (current === 1 ? ' is-disabled' : ''))
        .replace('type="button"', 'type="button" aria-label="' + esc(tr('prev', 'Previous')) + '"' + (current === 1 ? ' disabled' : ''));
      pageNumbers(current, pages).forEach((page) => {
        html += page === null ? '<span class="platform-cl-ellipsis">…</span>' : button(page, String(page), page === current ? ' is-current' : '');
      });
      html += button(current + 1, '›', ' platform-cl-page-step' + (current === pages ? ' is-disabled' : ''))
        .replace('type="button"', 'type="button" aria-label="' + esc(tr('next', 'Next')) + '"' + (current === pages ? ' disabled' : ''));
      el.pagination.innerHTML = html;
    }

    function pageNumbers(current, pages) {
      const wanted = new Set([1, pages, current - 2, current - 1, current, current + 1, current + 2]);
      const list = Array.from(wanted).filter((p) => p >= 1 && p <= pages).sort((a, b) => a - b);
      const out = [];
      list.forEach((page, index) => {
        if (index > 0 && page - list[index - 1] > 1) out.push(null);
        out.push(page);
      });
      return out;
    }

    function updateSentinel(active) {
      if (!el.sentinel) return;
      el.sentinel.hidden = !active;
      el.sentinel.textContent = active ? tr('loadingMore', 'Scroll to load more') : '';
      if (!active) {
        if (observer) observer.disconnect();
        return;
      }
      if (!('IntersectionObserver' in window)) return;
      if (!observer) {
        observer = new IntersectionObserver((entries) => {
          if (!entries.some((entry) => entry.isIntersecting)) return;
          state.shown += state.prefs.limit_max;
          render();
        }, { rootMargin: '200px 0px' });
      }
      observer.disconnect();
      observer.observe(el.sentinel);
    }

    async function saveRoutable(routable) {
      const api = root.dataset.jsonApi;
      const path = 'settings/data_settings_types.json';
      try {
        const response = await fetch(api + '?path=' + encodeURIComponent(path));
        const result = await response.json();
        if (!result.ok) throw new Error(result.message || 'read failed');
        const registry = result.content || {};
        if (!registry[kind] || !registry[kind][type]) throw new Error('type not found');
        registry[kind][type].routable = routable;
        const save = await fetch(api, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ path, content: registry }),
        });
        const saved = await save.json();
        if (!saved.ok) throw new Error(saved.message || 'write failed');
        showSaved(tr('saved', 'Saved'));
        load();
      } catch (error) {
        console.error('Routable:', error);
        if (el.routable) el.routable.checked = !routable;
        showSaved(tr('saveFailed', 'Could not save'), true);
      }
    }

    if (el.showAll) el.showAll.addEventListener('change', onPrefsChange);
    if (el.limit) el.limit.addEventListener('change', onPrefsChange);
    el.modes.forEach((input) => input.addEventListener('change', onPrefsChange));
    el.sorts.forEach((button) => button.addEventListener('click', () => setSort(button.dataset.clSort)));
    if (el.routable) el.routable.addEventListener('change', () => saveRoutable(el.routable.checked));

    if (el.search) {
      el.search.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
          state.query = el.search.value;
          state.page = 1;
          state.shown = state.prefs.limit_max;
          render();
        }, SEARCH_DELAY);
      });
    }

    el.pagination.addEventListener('click', (event) => {
      const button = event.target.closest('[data-cl-page]');
      if (!button || button.disabled) return;
      state.page = parseInt(button.dataset.clPage, 10) || 1;
      render();
      root.querySelector('.platform-cl-table-card').scrollIntoView({ block: 'start', behavior: 'smooth' });
    });

    syncPrefsForm();
    load();
  }

  function parseJson(text, fallback) {
    try {
      const value = JSON.parse(text || '');
      return value && typeof value === 'object' ? value : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function esc(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  }
})();

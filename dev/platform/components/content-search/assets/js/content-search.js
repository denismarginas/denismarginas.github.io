(() => {
  const MAX_RESULTS = 60;
  const SEARCH_DELAY = 60;

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-cs-root]').forEach(initSearch);
  });

  function parseJson(value, fallback) {
    try {
      return value ? JSON.parse(value) : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function fold(value) {
    return String(value == null ? '' : value).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  }

  function highlight(text, terms) {
    const source = String(text == null ? '' : text);
    const lower = source.toLowerCase();
    if (lower.length !== source.length || !terms.length) return esc(source);
    const ranges = [];
    terms.forEach((term) => {
      if (!term) return;
      let from = 0;
      let index;
      while ((index = lower.indexOf(term, from)) !== -1) {
        ranges.push([index, index + term.length]);
        from = index + term.length;
      }
    });
    if (!ranges.length) return esc(source);
    ranges.sort((a, b) => a[0] - b[0]);
    const merged = [ranges[0]];
    ranges.slice(1).forEach((range) => {
      const last = merged[merged.length - 1];
      if (range[0] <= last[1]) last[1] = Math.max(last[1], range[1]);
      else merged.push(range);
    });
    let html = '';
    let cursor = 0;
    merged.forEach(([start, end]) => {
      html += esc(source.slice(cursor, start)) + '<mark>' + esc(source.slice(start, end)) + '</mark>';
      cursor = end;
    });
    return html + esc(source.slice(cursor));
  }

  function snippet(text, terms, size) {
    const lower = String(text || '').toLowerCase();
    let index = -1;
    terms.some((term) => (index = lower.indexOf(term)) !== -1);
    if (index === -1) return '';
    const start = Math.max(0, index - Math.floor(size / 3));
    const end = Math.min(text.length, start + size);
    return (start > 0 ? '…' : '') + text.slice(start, end).trim() + (end < text.length ? '…' : '');
  }

  function initSearch(root) {
    const el = {
      input: root.querySelector('[data-cs-input]'),
      results: root.querySelector('[data-cs-results]'),
      summary: root.querySelector('[data-cs-summary]'),
      kinds: Array.from(root.querySelectorAll('[data-cs-kind]')),
      counts: Array.from(root.querySelectorAll('[data-cs-count]')),
    };
    if (!el.input || !el.results) return;

    const strings = parseJson(root.dataset.strings, {});
    const tr = (key, fallback, vars) => {
      let text = strings[key] || fallback;
      Object.entries(vars || {}).forEach(([name, value]) => { text = text.split('{' + name + '}').join(String(value)); });
      return text;
    };

    const searchId = root.dataset.csId || 'search';
    const optionId = (index) => 'platform-cs-' + searchId + '-option-' + index;
    const state = { rows: null, error: '', kind: '', list: [], active: -1, terms: [], rawTerms: [] };
    let timer = null;

    function prepare(row) {
      const terms = Object.values(row.taxonomies || {}).flat().join(' ');
      row._id = String(row._id || '');
      row._f = {
        id: fold(row._id),
        title: fold(row.title),
        meta: fold([row.type_label, row.type, row.kind_label, row.subtype, terms, row.publish].join(' ')),
        desc: fold(row.description),
        text: fold(row.text),
      };
      return row;
    }

    function load() {
      fetch(root.dataset.api, { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then((result) => {
          if (!result.ok) throw new Error(result.message || 'API error');
          state.rows = (result.rows || []).map(prepare);
          const counts = { '': state.rows.length };
          state.rows.forEach((row) => { counts[row.kind] = (counts[row.kind] || 0) + 1; });
          el.counts.forEach((node) => { node.textContent = counts[node.dataset.csCount] || 0; });
          render();
        })
        .catch((error) => {
          console.error('Content search:', error);
          state.error = error.message || 'Error';
          render();
        });
    }

    function score(row, terms) {
      let total = 0;
      for (const term of terms) {
        const f = row._f;
        let s = 0;
        if (f.id === term || f.title === term) s = 100;
        else if (f.id.startsWith(term)) s = 60;
        else if (f.title.startsWith(term)) s = 55;
        else if (f.title.includes(' ' + term)) s = 40;
        else if (f.title.includes(term)) s = 30;
        else if (f.id.includes(term)) s = 25;
        else if (f.meta.includes(term)) s = 12;
        else if (f.desc.includes(term)) s = 6;
        else if (f.text.includes(term)) s = 2;
        if (!s) return 0;
        total += s;
      }
      return total;
    }

    function search() {
      const query = el.input.value.trim();
      state.rawTerms = query.toLowerCase().split(/\s+/).filter(Boolean);
      state.terms = state.rawTerms.map(fold);
      if (!state.rows || !state.terms.length) {
        state.list = [];
        return;
      }
      state.list = state.rows
        .filter((row) => state.kind === '' || row.kind === state.kind)
        .map((row) => ({ row, score: score(row, state.terms) }))
        .filter((item) => item.score > 0)
        .sort((a, b) => b.score - a.score || String(a.row.title).localeCompare(String(b.row.title), undefined, { numeric: true, sensitivity: 'base' }))
        .map((item) => item.row);
    }

    function itemHtml(row, index) {
      const terms = state.rawTerms;
      const tax = Object.values(row.taxonomies || {}).flat();
      const titleHit = row._f.title.includes(state.terms[0] || '\u0000') || row._f.id.includes(state.terms[0] || '\u0000');
      let detail = row.description || '';
      if (!titleHit && !state.terms.every((t) => row._f.desc.includes(t))) {
        const found = snippet(row.text || '', terms, 140);
        if (found) detail = found;
      }
      const meta = [
        '<span class="platform-cs-badge">' + esc(row.kind_label) + '</span>',
        '<span>' + esc(row.type_label) + (row.subtype ? ' · ' + esc(row.subtype) : '') + '</span>',
        '<code class="platform-cs-id">' + highlight(row._id, terms) + '</code>',
      ];
      if (tax.length) meta.push('<span>' + highlight(tax.join(', '), terms) + '</span>');
      if (row.hidden) meta.push('<span class="platform-cs-badge platform-cs-badge-hidden">' + esc(tr('hidden', 'Hidden')) + '</span>');

      return '<li class="platform-cs-item' + (index === state.active ? ' is-active' : '') + '" role="option" id="' + optionId(index) + '" aria-selected="' + (index === state.active) + '" data-cs-index="' + index + '">'
        + '<a class="platform-cs-link" href="' + esc(row.edit_url) + '" tabindex="-1">'
        + '<span class="platform-cs-title">' + highlight(row.title || row._id, terms) + '</span>'
        + '<span class="platform-cs-meta">' + meta.join('') + '</span>'
        + (detail ? '<span class="platform-cs-desc">' + highlight(detail, terms) + '</span>' : '')
        + '</a>'
        + (row.view_url ? '<a class="platform-cs-view" href="' + esc(row.view_url) + '" target="_blank" rel="noopener" tabindex="-1">' + esc(tr('view', 'View')) + '</a>' : '')
        + '</li>';
    }

    function render() {
      search();
      const query = el.input.value.trim();
      const open = query !== '';
      el.results.hidden = !open;
      el.input.setAttribute('aria-expanded', open ? 'true' : 'false');
      root.classList.toggle('is-searching', open);

      if (!open) {
        el.results.innerHTML = '';
        el.summary.textContent = '';
        state.active = -1;
        el.input.removeAttribute('aria-activedescendant');
        return;
      }
      if (state.error) {
        el.results.innerHTML = '<li class="platform-cs-message">' + esc(tr('error', 'Could not load content: {message}', { message: state.error })) + '</li>';
        el.summary.textContent = '';
        return;
      }
      if (!state.rows) {
        el.results.innerHTML = '<li class="platform-cs-message">' + esc(tr('loading', 'Loading content...')) + '</li>';
        el.summary.textContent = '';
        return;
      }

      const total = state.list.length;
      const shown = state.list.slice(0, MAX_RESULTS);
      if (state.active >= shown.length) state.active = shown.length - 1;
      if (state.active < 0 && shown.length) state.active = 0;

      el.results.innerHTML = total
        ? shown.map(itemHtml).join('')
        : '<li class="platform-cs-message">' + esc(tr('none', 'Nothing matches "{query}".', { query })) + '</li>';
      el.summary.textContent = total > shown.length
        ? tr('more', 'Showing {shown} of {count}', { shown: shown.length, count: total })
        : total === 1 ? tr('result', '1 result') : total ? tr('results', '{count} results', { count: total }) : '';
      syncActive(false);
    }

    function syncActive(scroll) {
      el.results.querySelectorAll('[data-cs-index]').forEach((node) => {
        const on = Number(node.dataset.csIndex) === state.active;
        node.classList.toggle('is-active', on);
        node.setAttribute('aria-selected', on ? 'true' : 'false');
        if (on && scroll) node.scrollIntoView({ block: 'nearest' });
      });
      if (state.active >= 0) el.input.setAttribute('aria-activedescendant', optionId(state.active));
      else el.input.removeAttribute('aria-activedescendant');
    }

    function open(index, newTab) {
      const row = state.list[index];
      if (!row) return;
      if (newTab) window.open(row.edit_url, '_blank');
      else window.location.href = row.edit_url;
    }

    el.input.addEventListener('input', () => {
      window.clearTimeout(timer);
      state.active = 0;
      timer = window.setTimeout(render, SEARCH_DELAY);
    });

    el.input.addEventListener('keydown', (event) => {
      const count = Math.min(state.list.length, MAX_RESULTS);
      if (event.key === 'ArrowDown' && count) {
        event.preventDefault();
        state.active = (state.active + 1) % count;
        syncActive(true);
      } else if (event.key === 'ArrowUp' && count) {
        event.preventDefault();
        state.active = (state.active - 1 + count) % count;
        syncActive(true);
      } else if (event.key === 'Enter') {
        event.preventDefault();
        window.clearTimeout(timer);
        render();
        open(Math.max(0, state.active), event.ctrlKey || event.metaKey);
      } else if (event.key === 'Escape') {
        if (el.input.value !== '') {
          event.preventDefault();
          el.input.value = '';
          render();
        } else {
          el.input.blur();
        }
      }
    });

    el.results.addEventListener('mousemove', (event) => {
      const item = event.target.closest('[data-cs-index]');
      if (!item) return;
      const index = Number(item.dataset.csIndex);
      if (index !== state.active) {
        state.active = index;
        syncActive(false);
      }
    });

    el.kinds.forEach((button) => {
      button.addEventListener('click', () => {
        state.kind = button.dataset.csKind || '';
        el.kinds.forEach((other) => {
          const on = other === button;
          other.classList.toggle('is-active', on);
          other.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        state.active = 0;
        render();
        el.input.focus();
      });
    });

    const shortcut = (root.dataset.csShortcut || '').toLowerCase();
    if (shortcut) {
      document.addEventListener('keydown', (event) => {
        const target = event.target;
        const typing = target && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName));
        const combo = (event.ctrlKey || event.metaKey) && !event.altKey && !event.shiftKey && event.key.toLowerCase() === shortcut;
        if (combo || (shortcut === 'k' && event.key === '/' && !typing)) {
          event.preventDefault();
          el.input.focus();
          el.input.select();
        }
      });
    }

    if (new URLSearchParams(window.location.search).get('focus') === searchId) {
      el.input.focus();
      root.scrollIntoView({ block: 'center' });
    }

    load();
  }
})();

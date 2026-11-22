(() => {
  const LIST_LIMIT = 40;

  document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-ds-root]');
    if (root) init(root);
  });

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function init(root) {
    let strings = {};
    let icons = {};
    try { strings = JSON.parse(root.dataset.strings || '{}'); } catch (e) { strings = {}; }
    try { icons = JSON.parse(root.dataset.icons || '{}'); } catch (e) { icons = {}; }
    const tr = (key, fallback, vars) => {
      let text = strings[key] || fallback;
      Object.entries(vars || {}).forEach(([name, value]) => { text = text.split('{' + name + '}').join(String(value)); });
      return text;
    };
    const cards = {};
    root.querySelectorAll('[data-ds-card]').forEach((card) => {
      cards[card.dataset.dsCard] = {
        el: card,
        body: card.querySelector('[data-ds-body]'),
        badge: card.querySelector('[data-ds-badge]'),
        time: card.querySelector('[data-ds-time]'),
        refresh: card.querySelector('[data-ds-refresh]'),
      };
    });

    const ago = (ts) => {
      if (!ts) return tr('never', 'never');
      const s = Math.max(0, Math.round(Date.now() / 1000 - ts));
      if (s < 60) return tr('justNow', 'just now');
      if (s < 3600) return tr('minutesAgo', '{n} min ago', { n: Math.round(s / 60) });
      if (s < 86400) return tr('hoursAgo', '{n} h ago', { n: Math.round(s / 3600) });
      return tr('daysAgo', '{n} days ago', { n: Math.round(s / 86400) });
    };
    const fmtTime = (ts) => new Date(ts * 1000).toLocaleString();

    const badge = (card, state, text) => {
      const icon = icons[state];
      card.badge.className = 'platform-ds-badge is-' + state + (icon ? ' has-icon' : '');
      card.badge.innerHTML = icon ? icon + '<span class="platform-ds-sr">' + esc(text) + '</span>' : esc(text);
      card.badge.title = text;
      card.badge.setAttribute('role', 'img');
      card.badge.setAttribute('aria-label', text);
    };

    const list = (title, rows, render, open) => {
      if (!rows.length) return '';
      const shown = rows.slice(0, LIST_LIMIT);
      return '<details class="platform-ds-details"' + (open ? ' open' : '') + '><summary><span>' + esc(title) + '</span><span class="platform-ds-count">' + rows.length + '</span></summary>'
        + '<ul class="platform-ds-list">' + shown.map(render).join('')
        + (rows.length > shown.length ? '<li class="platform-ds-more">' + esc(tr('andMore', '+ {n} more', { n: rows.length - shown.length })) + '</li>' : '')
        + '</ul></details>';
    };
    const entryRow = (row) => '<li><a href="' + esc(row.edit_url) + '"><span class="platform-ds-row-title">' + esc(row.title || row._id) + '</span><code>' + esc(row.file || row._id) + '</code></a></li>';
    const bar = (total, parts) => '<div class="platform-ds-bar">' + parts.filter(([n]) => n > 0).map(([n, state]) => '<span class="is-' + state + '" style="width:' + (total ? (n / total) * 100 : 0).toFixed(2) + '%"></span>').join('') + '</div>';
    const num = (n) => Number(n || 0).toLocaleString('en-US');
    const stat = (value, label, state, icon) => '<div class="platform-ds-stat' + (state && state !== 'ok' ? ' is-' + state : '') + (typeof value === 'number' ? '' : ' is-text') + '">'
      + (icon && icons[icon] ? '<span class="platform-ds-stat-icon">' + icons[icon] + '</span>' : '')
      + '<span class="platform-ds-stat-value">' + esc(value) + '</span><span class="platform-ds-stat-label">' + esc(label) + '</span></div>';

    const renderers = {
      translations(data) {
        const card = cards.translations;
        const langs = data.languages || [];
        if (!langs.length) {
          badge(card, 'muted', tr('noTargets', 'No target languages'));
          return '<p class="platform-ds-muted">' + esc(tr('noTargetsHint', 'Add target languages in the Translate params.')) + '</p>';
        }
        const missingTotal = langs.reduce((n, l) => n + l.todo + l.failed, 0);
        badge(card, missingTotal ? 'warn' : 'ok', missingTotal ? tr('missingTexts', '{n} missing', { n: missingTotal }) : tr('complete', 'Complete'));
        return langs.map((l) => {
          const state = l.todo === 0 && l.failed === 0 ? 'ok' : 'warn';
          return '<div class="platform-ds-lang">'
            + '<div class="platform-ds-lang-head"><span class="platform-ds-lang-name">' + esc(l.text) + '</span><code>' + esc(l.iso) + '</code>'
            + '<span class="platform-ds-tag">' + esc(l.mode) + '</span><span class="platform-ds-percent is-' + state + '">' + esc(l.percent) + '%</span></div>'
            + bar(l.total, [[l.done, 'ok'], [l.failed, 'error'], [l.todo, 'warn']])
            + '<p class="platform-ds-line">' + esc(tr('textsDone', '{done} / {total} texts translated', { done: num(l.done), total: num(l.total) }))
            + (l.todo ? ' · <strong>' + esc(tr('textsMissing', '{n} missing', { n: l.todo })) + '</strong>' : '')
            + (l.failed ? ' · <strong class="is-error">' + esc(tr('textsFailed', '{n} failed', { n: l.failed })) + '</strong>' : '') + '</p>'
            + list(tr('missingTranslations', 'Missing translations'), l.files || [], (f) => '<li><a href="?page=generate&amp;tab=translate"><span class="platform-ds-row-title">' + esc(f.file.replace(/^.*\//, '')) + '</span><code>'
              + esc((f.todo ? tr('nMissing', '{n} missing', { n: f.todo }) : '') + (f.todo && f.failed ? ' · ' : '') + (f.failed ? tr('nFailed', '{n} failed', { n: f.failed }) : '')) + '</code></a></li>', false)
            + '</div>';
        }).join('');
      },

      html(data) {
        const card = cards.html;
        const langs = data.languages || [];
        const missing = langs.reduce((n, l) => n + (l.missing || []).length, 0);
        const outdated = langs.reduce((n, l) => n + (l.outdated_count || 0), 0);
        const notBuilt = langs.filter((l) => l.mode === 'frontend' && (l.missing_files || []).length);
        if (!data.html_compile) badge(card, 'muted', tr('htmlOff', 'Writing HTML is off'));
        else if (missing) badge(card, 'error', tr('missingPages', '{n} missing', { n: missing }));
        else if (notBuilt.length) badge(card, 'warn', tr('filesMissingBadge', '{langs} files missing', { langs: notBuilt.map((l) => l.iso.toUpperCase()).join(', ') }));
        else if (outdated) badge(card, 'warn', tr('outdatedBadge', '{n} outdated', { n: outdated }));
        else badge(card, 'ok', tr('upToDate', 'Up to date'));

        let html = '<div class="platform-ds-stats">'
          + stat(ago(data.last_build), tr('lastRender', 'last page written'), '', 'clock')
          + stat(missing, tr('missingLabel', 'missing pages'), missing ? 'error' : '', 'pages')
          + stat(outdated, tr('outdatedLabel', 'outdated pages'), outdated ? 'warn' : '', 'outdated')
          + '</div>';
        langs.forEach((l) => {
          const unbuilt = l.mode === 'frontend' && (l.missing_files || []).length > 0;
          const state = (l.missing || []).length ? 'error' : unbuilt || l.stale ? 'warn' : 'ok';
          html += '<div class="platform-ds-lang">'
            + '<div class="platform-ds-lang-head"><span class="platform-ds-lang-name">' + esc(l.name) + '</span><code>' + esc(l.iso) + '</code><span class="platform-ds-tag">' + esc(l.mode) + '</span>'
            + '<span class="platform-ds-percent is-' + state + '">' + esc(l.built) + ' / ' + esc(l.expected) + '</span></div>'
            + (l.mode === 'frontend'
              ? bar(l.expected, [[l.built, 'ok'], [l.expected - l.built, 'warn']])
              : bar(l.expected, [[l.built - (l.outdated_count || 0), 'ok'], [l.outdated_count || 0, 'warn'], [(l.missing || []).length, 'error']]))
            + '<p class="platform-ds-line">' + (unbuilt ? '<strong>' + esc(tr('frontendFilesMissing', '{n} data files have no {name} copy yet. Run Translate.', { n: l.expected - l.built, name: l.name })) + '</strong>' : esc(l.mode === 'frontend'
              ? tr('frontendLine', 'Translated in the browser from data/{iso}/ · {n} files', { n: l.built, iso: l.iso })
              : (l.iso === (langs[0] || {}).iso ? tr('staticLineDefault', '{n} pages', { n: l.built }) : tr('staticLine', '{n} pages in /{iso}/', { n: l.built, iso: l.iso }))))
            + (l.mode === 'frontend' && l.stale ? ' · <strong>' + esc(tr('frontendStale', 'content changed since the last build')) + '</strong>' : '') + '</p>'
            + list(tr('missingList', 'Missing pages'), l.missing || [], entryRow, true)
            + list(tr('missingFilesList', 'Missing data files'), l.missing_files || [], entryRow, true)
            + list(tr('outdatedList', 'Outdated pages'), l.outdated || [], entryRow, false)
            + '</div>';
        });
        html += list(tr('orphansList', 'Extra files'), (data.orphans || []).map((f) => ({ _id: f, title: f, file: '', edit_url: '../../dist/' + f })), (r) => '<li><a href="' + esc(r.edit_url) + '" target="_blank" rel="noopener"><span class="platform-ds-row-title">' + esc(r.title) + '</span></a></li>', false);
        if (data.sitemap) html += '<p class="platform-ds-note">' + esc(tr('sitemapLine', 'sitemap.xml built {time}.', { time: ago(data.sitemap) })) + '</p>';
        return html;
      },

      recent(data) {
        const card = cards.recent;
        card.badge.className = 'platform-ds-badge';
        card.badge.innerHTML = '';
        const files = data.files || [];
        if (!files.length) return '<p class="platform-ds-muted">' + esc(tr('noFiles', 'No data files found.')) + '</p>';
        return '<ul class="platform-ds-recent">' + files.map((f) => '<li><a href="' + esc(f.url) + '" title="' + esc(f.file + ' · ' + fmtTime(f.mtime)) + '">'
          + '<span class="platform-ds-recent-copy"><span class="platform-ds-row-title">' + esc(f.title) + '</span><code>' + esc(f.file) + '</code></span>'
          + '<span class="platform-ds-recent-time">' + esc(ago(f.mtime)) + '</span></a></li>').join('') + '</ul>';
      },

      projects(data) {
        const card = cards.projects;
        if (!data.available) {
          badge(card, 'muted', '—');
          return '<p class="platform-ds-muted">' + esc(tr('noProjects', 'There is no "project" post type.')) + '</p>';
        }
        const noThumb = (data.no_thumbnail || []).filter((r) => !r.hidden);
        const noLogo = (data.no_logo || []).filter((r) => !r.hidden);
        const hidden = data.hidden || [];
        const issues = noThumb.length + noLogo.length;
        badge(card, issues ? 'warn' : 'ok', issues ? (issues === 1 ? tr('issue', '1 issue') : tr('issues', '{n} issues', { n: issues })) : tr('allGood', 'All good'));
        const tagHidden = () => '';
        return '<div class="platform-ds-stats">'
          + stat(data.total, tr('projectsTotal', 'projects'), '', 'projects')
          + stat(data.rendered, tr('projectsRendered', 'rendered'), '', 'rendered')
          + stat(hidden.length, tr('projectsHidden', 'not rendered'), '', 'hidden')
          + stat(noThumb.length, tr('projectsNoThumb', 'missing thumbnail'), noThumb.length ? 'warn' : '', 'thumbnail')
          + stat(noLogo.length, tr('projectsNoLogo', 'missing logo'), noLogo.length ? 'warn' : '', 'logo')
          + '</div>'
          + list(tr('noThumbList', 'Missing thumbnail'), noThumb, (r) => '<li><a href="' + esc(r.edit_url) + '"><span class="platform-ds-row-title">' + esc(r.title) + '</span>' + tagHidden(r) + '<code>' + esc(r._id) + '</code></a></li>', noThumb.length <= 5)
          + list(tr('noLogoList', 'Missing logo'), noLogo, (r) => '<li><a href="' + esc(r.edit_url) + '"><span class="platform-ds-row-title">' + esc(r.title) + '</span>' + tagHidden(r) + '<code>' + esc(r.reason === 'file' ? tr('logoFileMissing', 'image not found') : r.reason === 'svg' ? tr('logoSvgMissing', 'svg "{name}" not found', { name: r.value }) : tr('logoUnset', 'not set')) + '</code></a></li>', noLogo.length <= 5)
          + list(tr('hiddenList', 'Not rendered'), hidden, (r) => '<li><a href="' + esc(r.edit_url) + '"><span class="platform-ds-row-title">' + esc(r.title) + '</span><code>' + esc(r._id) + '</code></a></li>', false);
      },
    };

    async function load(section) {
      const card = cards[section];
      if (!card) return;
      card.el.setAttribute('aria-busy', 'true');
      card.el.classList.add('is-loading');
      try {
        const response = await fetch(root.dataset.api + '?section=' + encodeURIComponent(section), { headers: { Accept: 'application/json' }, cache: 'no-store' });
        const result = await response.json();
        if (!result.ok) throw new Error(result.message || 'API error');
        card.body.innerHTML = renderers[section](result[section] || {});
        card.time.textContent = tr('checkedAt', 'Checked {time}', { time: new Date(result.checked_at * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) });
        card.time.title = fmtTime(result.checked_at);
      } catch (error) {
        badge(card, 'error', tr('error', 'Error'));
        card.body.innerHTML = '<p class="platform-ds-muted">' + esc(error.message) + '</p>';
      } finally {
        card.el.setAttribute('aria-busy', 'false');
        card.el.classList.remove('is-loading');
      }
    }

    Object.entries(cards).forEach(([section, card]) => {
      if (card.refresh) card.refresh.addEventListener('click', () => load(section));
    });
    const all = root.querySelector('[data-ds-refresh-all]');
    if (all) all.addEventListener('click', () => Object.keys(cards).forEach(load));

    root.querySelectorAll('[data-ds-run]').forEach((button) => {
      button.addEventListener('click', async () => {
        const label = button.querySelector('span:not(.platform-svg)');
        const original = label.textContent;
        button.disabled = true;
        label.textContent = tr('rendering', 'Rendering...');
        try {
          const response = await fetch(root.dataset.renderApi, { method: 'POST', headers: { Accept: 'application/json' } });
          const result = await response.json();
          label.textContent = result.ok ? tr('rendered', '{n} pages written', { n: result.count || 0 }) : (result.message || tr('error', 'Error'));
        } catch (error) {
          label.textContent = tr('error', 'Error');
        } finally {
          load('html');
          load('translations');
          window.setTimeout(() => { label.textContent = original; button.disabled = false; }, 2500);
        }
      });
    });

    Object.keys(cards).forEach(load);
  }
})();

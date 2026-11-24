(function () {
    'use strict';

    function setup(nav) {
        var entries = [];
        nav.querySelectorAll('a.anchor-index-link[href^="#"]').forEach(function (link) {
            var target = document.getElementById(decodeURIComponent(link.getAttribute('href').slice(1)));
            if (target) entries.push({ link: link, target: target });
            else if (link.parentElement) link.parentElement.hidden = true;
        });
        var min = parseInt(nav.getAttribute('data-anchor-index-min') || '2', 10);
        if (entries.length < Math.max(1, min || 1)) {
            nav.hidden = true;
            return;
        }

        var list = nav.querySelector('.anchor-index-list');
        var current = null;

        function update() {
            var line = window.innerHeight * 0.35;
            var active = null;
            var atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4;

            entries.forEach(function (entry) {
                var top = entry.target.getBoundingClientRect().top;
                if (top <= line || (atBottom && top < window.innerHeight)) active = entry;
            });

            var link = active ? active.link : null;
            if (link === current) return;
            if (current) current.removeAttribute('aria-current');
            if (link) link.setAttribute('aria-current', 'true');
            current = link;

            if (link && list && list.scrollHeight > list.clientHeight) {
                var offset = link.offsetTop - list.offsetTop;
                if (offset < list.scrollTop || offset > list.scrollTop + list.clientHeight - link.offsetHeight) {
                    list.scrollTo({ top: offset - list.clientHeight / 2, behavior: 'smooth' });
                }
            }
        }

        var queued = false;
        function queue() {
            if (queued) return;
            queued = true;
            window.requestAnimationFrame(function () { queued = false; update(); });
        }

        window.addEventListener('scroll', queue, { passive: true });
        window.addEventListener('resize', queue);
        update();
    }

    function init() {
        document.querySelectorAll('[data-anchor-index]').forEach(setup);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();

(function() {
    'use strict';
})();
(function () {
  'use strict';

  var grids = [];
  var timer = 0;
  var paused = false;

  function setPaused(value) {
    if (paused === value) return;
    paused = value;
    grids.forEach(function (grid) { grid.classList.toggle('is-paused', value); });
  }

  function onScroll() {
    setPaused(true);
    window.clearTimeout(timer);
    timer = window.setTimeout(function () { setPaused(false); }, 200);
  }

  function init() {
    grids = Array.prototype.slice.call(document.querySelectorAll('.animation-grid-background'));
    if (!grids.length) return;
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function() {
    'use strict';
})();
(function() {
    'use strict';
})();

(function () {
  'use strict';

  var KEY = 'cookie_notice';
  var SETTINGS_HASH = '#cookie-settings';

  function noticeEl() {
    return document.querySelector('[data-cookie-notice]');
  }

  function currentVersion() {
    var notice = noticeEl();
    return notice ? notice.getAttribute('data-cookie-notice-version') || '1' : '1';
  }

  function readCookie() {
    var match = document.cookie.match(/(?:^|;\s*)cookie_notice=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
  }

  function readRaw() {
    try {
      return window.localStorage.getItem(KEY) || readCookie();
    } catch (e) {
      return readCookie();
    }
  }

  function readChoice() {
    var raw = readRaw();
    if (!raw) return null;
    var choice = null;
    if (raw.indexOf('accepted:') === 0) {
      choice = { v: raw.slice(9), analytics: true };
    } else {
      try { choice = JSON.parse(raw); } catch (e) { choice = null; }
    }
    if (!choice || String(choice.v) !== String(currentVersion())) return null;
    return { v: String(choice.v), analytics: choice.analytics === true };
  }

  function write(choice) {
    var value = JSON.stringify(choice);
    var notice = noticeEl();
    var days = notice ? parseInt(notice.getAttribute('data-cookie-notice-days'), 10) || 365 : 365;
    try { window.localStorage.setItem(KEY, value); } catch (e) {}
    document.cookie = KEY + '=' + encodeURIComponent(value) + '; expires=' + new Date(Date.now() + days * 864e5).toUTCString() + '; path=/; SameSite=Lax';
  }

  function clear() {
    try { window.localStorage.removeItem(KEY); } catch (e) {}
    document.cookie = KEY + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
  }

  function defaults() {
    var notice = noticeEl();
    try { return JSON.parse((notice && notice.getAttribute('data-cookie-notice-defaults')) || '{}') || {}; } catch (e) { return {}; }
  }

  function effective(category) {
    var choice = readChoice();
    return choice ? choice[category] === true : defaults()[category] === true;
  }

  function updateStatus() {
    var on = effective('analytics');
    document.querySelectorAll('[data-cookie-status]').forEach(function (el) {
      el.textContent = el.getAttribute(on ? 'data-on' : 'data-off') || '';
      el.classList.toggle('is-on', on);
    });
  }

  function set(values) {
    var choice = { v: currentVersion(), analytics: !!(values && values.analytics) };
    write(choice);
    updateStatus();
    document.dispatchEvent(new CustomEvent('cookie-consent:change', { detail: { analytics: choice.analytics } }));
    if (choice.analytics) document.dispatchEvent(new CustomEvent('cookie-notice:accept', { detail: choice }));
    return choice;
  }

  function show(notice) {
    if (!notice.hidden && notice.classList.contains('is-visible')) return;
    notice.hidden = false;
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () { notice.classList.add('is-visible'); });
    });
  }

  function hide(notice) {
    notice.classList.remove('is-visible');
    var done = false;
    var finish = function () {
      if (done) return;
      done = true;
      notice.hidden = true;
      togglePanel(notice, false);
    };
    notice.addEventListener('transitionend', finish, { once: true });
    window.setTimeout(finish, 600);
  }

  function togglePanel(notice, open) {
    var panel = notice.querySelector('[data-cookie-notice-panel]');
    var toggle = notice.querySelector('[data-cookie-notice-toggle]');
    if (!panel) return;
    if (open) syncSwitches(notice);
    panel.hidden = !open;
    notice.classList.toggle('is-expanded', open);
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function syncSwitches(notice) {
    notice.querySelectorAll('[data-cookie-notice-category]').forEach(function (input) {
      input.checked = effective(input.getAttribute('data-cookie-notice-category'));
    });
  }

  function open(withSettings) {
    var notice = noticeEl();
    if (!notice) return;
    show(notice);
    togglePanel(notice, !!withSettings);
    if (withSettings) {
      var first = notice.querySelector('[data-cookie-notice-category]');
      if (first) first.focus({ preventScroll: true });
    }
  }

  window.cookieNotice = {
    consent: function (category) {
      var choice = readChoice();
      return choice ? choice[category] === true : null;
    },
    accepted: function () { return readChoice() !== null; },
    effective: effective,
    set: set,
    open: open,
    reset: function () { clear(); updateStatus(); open(false); },
  };

  function init() {
    document.addEventListener('click', function (event) {
      var link = event.target.closest && event.target.closest('a[href$="' + SETTINGS_HASH + '"], [data-cookie-settings]');
      if (!link) return;
      event.preventDefault();
      open(true);
    });

    updateStatus();

    var notice = noticeEl();
    if (!notice) return;

    var toggle = notice.querySelector('[data-cookie-notice-toggle]');
    var accept = notice.querySelector('[data-cookie-notice-accept]');
    var save = notice.querySelector('[data-cookie-notice-save]');

    if (toggle) {
      toggle.addEventListener('click', function () {
        var panel = notice.querySelector('[data-cookie-notice-panel]');
        togglePanel(notice, !!(panel && panel.hidden));
      });
    }

    notice.querySelectorAll('[data-cookie-notice-accept], [data-cookie-notice-close]').forEach(function (button) {
      button.addEventListener('click', function () {
        set({ analytics: true });
        hide(notice);
      });
    });

    if (save) {
      save.addEventListener('click', function () {
        var values = {};
        notice.querySelectorAll('[data-cookie-notice-category]').forEach(function (input) {
          values[input.getAttribute('data-cookie-notice-category')] = input.checked;
        });
        set(values);
        hide(notice);
      });
    }

    notice.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      var panel = notice.querySelector('[data-cookie-notice-panel]');
      if (panel && !panel.hidden) {
        togglePanel(notice, false);
        if (toggle) toggle.focus();
      }
    });

    if (window.location.hash === SETTINGS_HASH) open(true);
    else if (readChoice() === null) show(notice);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
  function setup(root) {
    if (root.dataset.timelineReady !== undefined) return;
    var items = root.querySelectorAll('.experience-timeline-item');
    var line = root.querySelector('.experience-timeline-line');
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!('IntersectionObserver' in window) || reduce) {
      items.forEach(function (item) { item.classList.add('is-visible'); });
      root.style.setProperty('--tl-progress', 1);
      return;
    }

    root.setAttribute('data-timeline-ready', '');

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.15 });

    items.forEach(function (item) { observer.observe(item); });

    if (!line) return;
    var ticking = false;
    function update() {
      ticking = false;
      var rect = line.getBoundingClientRect();
      var anchor = window.innerHeight * 0.6;
      var progress = rect.height > 0 ? (anchor - rect.top) / rect.height : 0;
      root.style.setProperty('--tl-progress', Math.max(0, Math.min(1, progress)).toFixed(4));
    }
    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(update);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    update();
  }

  function start() {
    document.querySelectorAll('[data-experience-timeline]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

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

(function () {
  function labelOf(form, select) {
    var label = select.id ? form.querySelector('label[for="' + select.id + '"]') : null;
    return label ? label.textContent.replace(/[:\s]+$/, '') : select.name;
  }

  var STORE = 'ft-view:';

  function values(select) {
    return Array.prototype.filter.call(select.options, function (o) { return o.selected && o.value !== ''; })
      .map(function (o) { return o.value; });
  }

  function stored(name) {
    try { return window.localStorage.getItem(STORE + name); } catch (e) { return null; }
  }

  function store(name, value) {
    try {
      if (value === null) window.localStorage.removeItem(STORE + name);
      else window.localStorage.setItem(STORE + name, value);
    } catch (e) {}
  }

  function siteHeader(form) {
    var headers = document.querySelectorAll('header');
    for (var i = 0; i < headers.length; i++) {
      if (form.contains(headers[i])) continue;
      var position = getComputedStyle(headers[i]).position;
      if (position === 'fixed' || position === 'sticky') return headers[i];
    }
    return null;
  }

  function init(form, opts) {
    opts = opts || {};
    var list = opts.list || null;
    var input = opts.input || null;
    var selects = opts.selects || [];
    var amounts = Array.prototype.slice.call(form.querySelectorAll('.query-amount'));
    var badge = form.querySelector('.ft-badge');
    var chips = form.querySelector('.ft-chips');
    var clearBtn = form.querySelector('.ft-clear');
    var results = form.querySelector('.ft-results');
    var drawer = form.querySelector('.ft-drawer');
    var filtersBtn = form.querySelector('.toggle-filters');
    var drawerInput = form.querySelector('.ft-drawer-search-input');

    function syncDrawerInput() {
      if (drawerInput && input && document.activeElement !== drawerInput && drawerInput.value !== input.value) drawerInput.value = input.value;
    }

    if (drawerInput && input) {
      drawerInput.value = input.value;
      drawerInput.addEventListener('input', function () {
        input.value = drawerInput.value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
      drawerInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') event.preventDefault();
      });
      input.addEventListener('input', syncDrawerInput);
    }

    function addChip(text, onRemove) {
      var li = document.createElement('li');
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'ft-chip';
      button.setAttribute('aria-label', ((chips && chips.getAttribute('data-label-remove')) || 'Remove') + ' ' + text);
      button.innerHTML = '<span class="ft-chip-text"></span><span class="ft-chip-x" aria-hidden="true">&times;</span>';
      button.firstChild.textContent = text;
      button.addEventListener('click', onRemove);
      li.appendChild(button);
      chips.appendChild(li);
    }

    function update(shown) {
      var query = input ? input.value.trim() : '';
      syncDrawerInput();
      var active = selects.filter(function (select) { return values(select).length > 0; });
      var chosen = active.reduce(function (total, select) { return total + values(select).length; }, 0);

      if (typeof shown === 'number') amounts.forEach(function (el) { el.textContent = shown; });
      if (badge) {
        badge.textContent = chosen;
        badge.hidden = chosen === 0;
      }
      form.classList.toggle('has-active-filters', active.length > 0);

      if (chips) {
        chips.textContent = '';
        active.forEach(function (select) {
          Array.prototype.forEach.call(select.options, function (option) {
            if (!option.selected || option.value === '') return;
            addChip(labelOf(form, select) + ': ' + option.textContent, function () {
              if (select.multiple) option.selected = false;
              else select.value = '';
              select.dispatchEvent(new Event('change', { bubbles: true }));
            });
          });
        });
        if (query && input) {
          addChip('"' + query + '"', function () {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
          });
        }
      }
      if (clearBtn) clearBtn.hidden = active.length === 0 && !query;
      if (filtersBtn && drawer) filtersBtn.setAttribute('aria-expanded', drawer.open ? 'true' : 'false');
      if (window.selectBoxSearch) window.selectBoxSearch.refresh(form);
    }

    if (input) {
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && input.value) {
          event.preventDefault();
          input.value = '';
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });
    }

    if (drawer && filtersBtn) {
      var closeDrawer = function () {
        if (typeof drawer.close === 'function' && drawer.open) drawer.close();
        else drawer.removeAttribute('open');
        filtersBtn.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('ft-drawer-open');
      };
      filtersBtn.addEventListener('click', function () {
        if (typeof drawer.showModal === 'function') drawer.showModal();
        else drawer.setAttribute('open', '');
        filtersBtn.setAttribute('aria-expanded', 'true');
        document.documentElement.classList.add('ft-drawer-open');
      });
      drawer.addEventListener('close', function () {
        filtersBtn.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('ft-drawer-open');
        filtersBtn.focus();
      });
      drawer.querySelectorAll('.ft-drawer-close, .ft-apply').forEach(function (button) {
        button.addEventListener('click', closeDrawer);
      });
      drawer.addEventListener('click', function (event) {
        if (event.target === drawer) closeDrawer();
      });
    }

    var toolbar = form.querySelector('.ft-toolbar');
    var placeholder = document.createElement('div');
    placeholder.className = 'ft-sticky-placeholder';
    placeholder.setAttribute('aria-hidden', 'true');
    if (toolbar) toolbar.parentNode.insertBefore(placeholder, toolbar.nextSibling);
    var sticky = { on: false, stuck: false, frame: 0 };

    function setStuck(stuck, top) {
      if (stuck) {
        var rect = form.getBoundingClientRect();
        var viewport = document.documentElement.clientWidth;
        form.style.setProperty('--ft-stuck-top', top + 'px');
        form.style.setProperty('--ft-stuck-left', rect.left + 'px');
        form.style.setProperty('--ft-stuck-right', Math.max(0, viewport - rect.right) + 'px');
        form.style.setProperty('--ft-stuck-width', rect.width + 'px');
        if (!sticky.stuck) placeholder.style.height = toolbar.offsetHeight + 'px';
      }
      if (stuck !== sticky.stuck) {
        sticky.stuck = stuck;
        form.classList.toggle('is-stuck', stuck);
        toolbar.classList.toggle('is-stuck', stuck);
      }
    }

    function measure() {
      sticky.frame = 0;
      if (!toolbar || !sticky.on) { if (toolbar) setStuck(false); return; }
      var header = siteHeader(form);
      var top = (header ? Math.max(0, header.getBoundingClientRect().bottom) : 0) + 8;
      var anchor = (sticky.stuck ? placeholder : toolbar).getBoundingClientRect();
      var end = (list || form).getBoundingClientRect().bottom;
      setStuck(anchor.top < top && end > top + toolbar.offsetHeight + 24, top);
    }

    function queue() {
      if (!sticky.frame) sticky.frame = window.requestAnimationFrame(measure);
    }
    window.addEventListener('scroll', queue, { passive: true });
    window.addEventListener('resize', queue);

    var views = Object.assign({
      search: function (value) {
        var hide = value === 'hide';
        form.classList.toggle('ft-search-hidden', hide);
        if (hide && input && input.value) {
          input.value = '';
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
      },
      results: function (value) { if (results) results.hidden = value === 'hide'; },
      sticky: function (value) { sticky.on = value === 'yes'; }
    }, opts.views || {});
    var segments = Array.prototype.slice.call(form.querySelectorAll('.ft-segment[data-view]'));
    function syncViews() {
      segments.forEach(function (box) {
        var checked = box.querySelector('input[type="radio"]:checked');
        if (checked && views[box.dataset.view]) views[box.dataset.view](checked.value, list);
      });
      if (sticky.stuck) setStuck(false);
      queue();
    }
    segments.forEach(function (box) {
      var saved = stored(box.dataset.view);
      var radio = saved !== null ? box.querySelector('input[type="radio"][value="' + saved + '"]') : null;
      if (radio) radio.checked = true;
      box.addEventListener('change', function (event) {
        if (event.target && event.target.value) store(box.dataset.view, event.target.value);
        syncViews();
      });
    });
    form.addEventListener('reset', function () {
      segments.forEach(function (box) { store(box.dataset.view, null); });
      setTimeout(syncViews, 0);
    });
    syncViews();

    return { update: update, syncViews: syncViews };
  }

  window.filtersToolbar = { init: init, values: values };
})();

(function () {
  function fieldsOf(scope) {
    return Array.prototype.slice.call(scope.querySelectorAll('input[name], textarea[name], select[name]'))
      .filter(function (el) { return el.type !== 'hidden'; });
  }

  function validate(step) {
    var fields = fieldsOf(step);
    for (var i = 0; i < fields.length; i++) {
      if (!fields[i].checkValidity()) {
        fields[i].reportValidity();
        return false;
      }
    }
    return true;
  }

  function valueOf(el) {
    if (el.type === 'radio' || el.type === 'checkbox') return el.checked ? el.value : '';
    return (el.value || '').trim();
  }

  function init(form) {
    if (form.dataset.formReady === 'true') return;
    form.dataset.formReady = 'true';

    var steps = Array.prototype.slice.call(form.querySelectorAll('.form-step'));
    var status = form.querySelector('.form-status');
    var statusLink = form.querySelector('.form-status-link');
    if (!steps.length) return;

    var history = [steps[0].dataset.step];

    function stepEl(id) {
      for (var i = 0; i < steps.length; i++) if (steps[i].dataset.step === String(id)) return steps[i];
      return null;
    }

    function current() {
      return stepEl(history[history.length - 1]);
    }

    function show(id) {
      steps.forEach(function (s) { s.hidden = s.dataset.step !== String(id); });
      var target = stepEl(id);
      var first = target && target.querySelector('input:not([type="hidden"]), textarea, select');
      if (first && form.dataset.focus === 'true') first.focus({ preventScroll: true });
      form.dataset.focus = 'true';
      if (status) status.hidden = true;
      updateProgress(id);
    }

    var progressItems = Array.prototype.slice.call(form.querySelectorAll('.form-progress-item'));
    function updateProgress(id) {
      var active = -1;
      progressItems.forEach(function (item, i) {
        if ((item.dataset.steps || '').split(' ').indexOf(String(id)) !== -1) active = i;
      });
      if (active === -1) return;
      progressItems.forEach(function (item, i) {
        item.classList.toggle('is-active', i === active);
        item.classList.toggle('is-done', i < active);
        if (i === active) item.setAttribute('aria-current', 'step');
        else item.removeAttribute('aria-current');
      });
    }

    function resolveStep(value) {
      if (!value) return null;
      if (value.charAt(0) !== '@') return value;
      var checked = form.querySelector('input[name="' + value.slice(1) + '"]:checked');
      return checked ? checked.dataset.step || null : null;
    }

    form.addEventListener('click', function (event) {
      var next = event.target.closest('.form-next');
      var prev = event.target.closest('.form-prev');

      if (next) {
        var step = current();
        if (step && !validate(step)) return;
        var target = resolveStep(next.dataset.step);
        if (!target || !stepEl(target)) return;
        history.push(target);
        show(target);
      } else if (prev) {
        if (prev.dataset.step) {
          var index = history.lastIndexOf(prev.dataset.step);
          history = index >= 0 ? history.slice(0, index + 1) : [prev.dataset.step];
        } else if (history.length > 1) {
          history.pop();
        }
        show(history[history.length - 1]);
      }
    });

    form.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' || event.target.tagName === 'TEXTAREA') return;
      var step = current();
      var next = step && step.querySelector('.form-next');
      if (next) { event.preventDefault(); next.click(); }
    });

    function compose() {
      var finals = {};
      var isSteps = form.dataset.formType === 'steps';
      var scopes = isSteps ? history.map(stepEl).filter(Boolean) : [form];

      scopes.forEach(function (scope) {
        fieldsOf(scope).forEach(function (el) {
          var target = el.dataset.target;
          var value = valueOf(el);
          if (!target || !value) return;
          (finals[target] = finals[target] || []).push({ label: el.dataset.label || '', value: value });
        });
      });

      var params = [];
      var finalEls = isSteps
        ? Array.prototype.slice.call(form.querySelectorAll('.form-final'))
        : fieldsOf(form).filter(function (el) { return el.dataset.externalName; });

      finalEls.forEach(function (el) {
        var name = isSteps ? el.dataset.final : el.dataset.target;
        var parts = finals[name] || [];
        var lines = el.dataset.compose === 'lines';
        var value = parts.map(function (p) {
          return lines && p.label ? p.label + ': ' + p.value : p.value;
        }).join(lines ? '\n' : ', ');
        if (isSteps) el.value = value;
        if (el.dataset.externalName && value) params.push(encodeURIComponent(el.dataset.externalName) + '=' + encodeURIComponent(value));
      });

      return params;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var step = form.dataset.formType === 'steps' ? current() : form;
      if (step && !validate(step)) return;

      var params = compose();
      var base = form.dataset.externalUrl || '';
      if (!base) return;

      var url = base;
      if (form.dataset.externalType === 'google-forms') {
        url = base + (base.indexOf('?') === -1 ? '?' : '&') + 'usp=pp_url' + (params.length ? '&' + params.join('&') : '');
      }

      if (statusLink) statusLink.href = url;
      if (status) status.hidden = false;
      window.open(url, '_blank', 'noopener');
    });

    form.addEventListener('reset', function () {
      history = [steps[0].dataset.step];
      form.dataset.focus = 'false';
      show(history[0]);
    });
  }

  function initAll(scope) {
    (scope || document).querySelectorAll('form.form-item').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initAll(); });
  } else {
    initAll();
  }

  document.addEventListener('popup:open', function (event) {
    if (event.detail && event.detail.body) initAll(event.detail.body);
  });

  window.FormItem = { init: init, initAll: initAll };
})();

(function () {
  const SCROLL_THRESHOLD = 80;
  const LOGO_SELECTOR = ".logo";
  const LOGO_CLASS_TOP = "logo-shape-circle";
  const LOGO_CLASS_SCROLLED = "logo-shape-square";

  function initHeaderScroll() {
    const header = document.querySelector("header");

    if (!header) {
      console.warn("Header scroll script: <header> element was not found.");
      return;
    }

    const logo = header.querySelector(LOGO_SELECTOR);

    const updateScrolled = () => {
      const isScrolled = window.scrollY > SCROLL_THRESHOLD;
      header.setAttribute("data-scrolled", isScrolled ? "true" : "false");

      if (logo) {
        logo.classList.toggle(LOGO_CLASS_TOP, !isScrolled);
        logo.classList.toggle(LOGO_CLASS_SCROLLED, isScrolled);
      }
    };

    updateScrolled();
    window.addEventListener("scroll", updateScrolled, { passive: true });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeaderScroll, {
      once: true,
    });
  } else {
    initHeaderScroll();
  }
})();

(function () {
  const TOGGLE_SELECTOR = ".header-menu-navbar-toggle";
  const NAVBAR_SELECTOR = ".header-menu";

  function initHeaderMenu() {
    const toggle = document.querySelector(TOGGLE_SELECTOR);
    const navbar = document.querySelector(NAVBAR_SELECTOR);

    if (!toggle) {
      console.warn("Header menu script: toggle element was not found.");
      return;
    }

    toggle.addEventListener("click", () => {
      const expanded = toggle.getAttribute("aria-expanded") === "true";
      const nextState = expanded ? "false" : "true";

      toggle.setAttribute("aria-expanded", nextState);
      toggle.classList.toggle("active", !expanded);
      document.body.setAttribute("data-overlay", nextState);

      if (navbar) {
        navbar.setAttribute("display-navbar", nextState);
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeaderMenu, {
      once: true,
    });
  } else {
    initHeaderMenu();
  }
})();

(function () {
  var MOBILE = "(max-width: 1024px)";

  function init() {
    var menu = document.querySelector("header .header-menu");
    if (!menu) return;

    var items = Array.prototype.slice.call(menu.querySelectorAll("li.has-submenu"));
    if (!items.length) return;
    var mobile = window.matchMedia(MOBILE);

    function set(li, open) {
      li.classList.toggle("is-open", open);
      var button = li.querySelector(".header-submenu-toggle");
      if (button) button.setAttribute("aria-expanded", open ? "true" : "false");
    }

    items.forEach(function (li) {
      var button = li.querySelector(".header-submenu-toggle");
      if (!button) return;
      button.addEventListener("click", function (event) {
        event.preventDefault();
        var open = !li.classList.contains("is-open");
        if (!mobile.matches) items.forEach(function (other) { if (other !== li) set(other, false); });
        set(li, open);
      });
    });

    function openCurrent() {
      items.forEach(function (li) {
        set(li, mobile.matches && li.classList.contains("is-current-parent"));
      });
    }
    openCurrent();
    if (mobile.addEventListener) mobile.addEventListener("change", openCurrent);

    document.addEventListener("click", function (event) {
      if (mobile.matches) return;
      items.forEach(function (li) { if (!li.contains(event.target)) set(li, false); });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") return;
      items.forEach(function (li) {
        if (!li.classList.contains("is-open") || mobile.matches) return;
        var hadFocus = li.contains(document.activeElement);
        set(li, false);
        if (hadFocus) li.querySelector(".header-submenu-toggle").focus();
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
  } else {
    init();
  }
})();

(function () {
  const STORAGE_KEY = "dm_mode_value";
  const TOGGLE_SELECTOR = ".header-theme-mode-toggle";

  function readMode() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved === "dark" || saved === "light") {
        return saved;
      }
    } catch (e) {}

    const bodyMode =
      typeof document.body !== "undefined" && document.body
        ? document.body.getAttribute("mode")
        : null;
    return bodyMode === "dark" || bodyMode === "light" ? bodyMode : "light";
  }

  function saveMode(mode) {
    try {
      localStorage.setItem(STORAGE_KEY, mode);
    } catch (e) {}
  }

  function applyMode(mode) {
    if (document.body) {
      document.body.setAttribute("mode", mode);
    }
  }

  function syncIcons(toggle, mode) {
    const icons = toggle.querySelectorAll(".header-theme-mode-toggle-icon");
    icons.forEach((icon) => {
      icon.setAttribute("active", icon.getAttribute("theme-style") === mode ? "true" : "false");
    });
  }

  function initHeaderMode() {
    const toggle = document.querySelector(TOGGLE_SELECTOR);
    const mode = readMode();

    applyMode(mode);

    if (!toggle) return;

    const input = toggle.querySelector(".header-theme-mode-toggle-input");
    input.checked = mode === "dark";
    toggle.setAttribute("data-mode", mode);
    syncIcons(toggle, mode);

    toggle.addEventListener("mousedown", (event) => {
      if (event.detail > 1) event.preventDefault();
    });

    input.addEventListener("change", () => {
      const next = input.checked ? "dark" : "light";
      applyMode(next);
      toggle.setAttribute("data-mode", next);
      syncIcons(toggle, next);
      saveMode(next);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeaderMode, {
      once: true,
    });
  } else {
    initHeaderMode();
  }
})();

(function () {
  'use strict';

  function init() {
    var dropdowns = Array.prototype.slice.call(document.querySelectorAll('[data-language-dropdown]'));
    if (!dropdowns.length) return;

    document.addEventListener('click', function (event) {
      dropdowns.forEach(function (dropdown) {
        if (dropdown.open && !dropdown.contains(event.target)) dropdown.open = false;
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      dropdowns.forEach(function (dropdown) {
        if (!dropdown.open) return;
        dropdown.open = false;
        var toggle = dropdown.querySelector('summary');
        if (toggle) toggle.focus();
      });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

class Hero {
  static TEMPLATE_URL = 'components/hero/assets/html/template.html';

  static render({ title = '', description = '', background = '', layout = 'standard', className = 'hero' } = {}) {
    const block = document.createElement('div');
    block.className = className;
    block.dataset.layout = layout;

    const bg = document.createElement('div');
    bg.className = 'hero-img-bg';
    if (background) {
      bg.style.backgroundImage = `url("${background}")`;
    }
    block.appendChild(bg);

    if (title || description) {
      const textContent = document.createElement('div');
      textContent.className = 'container-sm';

      if (title) {
        const heading = document.createElement('h2');
        heading.textContent = title;
        textContent.appendChild(heading);
      }

      if (description) {
        const paragraph = document.createElement('p');
        paragraph.textContent = description;
        textContent.appendChild(paragraph);
      }

      block.appendChild(textContent);
    }

    return block;
  }
}

window.Hero = window.Hero || Hero;

(function () {
  function toNumber(value) {
    var match = String(value == null ? '' : value).match(/-?\d+(?:[.,]\d+)?/);
    return match ? parseFloat(match[0].replace(',', '.')) : null;
  }

  function matchesFilter(values, select) {
    var selected = window.filtersToolbar.values(select);
    if (!selected.length) return true;
    values = values || [];

    var type = select.dataset.type;
    if (type === 'number' || type === 'date') {
      var mode = select.dataset.mode || '=';
      return selected.some(function (value) {
        var wanted = toNumber(value);
        return values.some(function (v) {
          var n = toNumber(v);
          if (n === null || wanted === null) return false;
          if (mode === '>=') return n >= wanted;
          if (mode === '<=') return n <= wanted;
          return n === wanted;
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

    var items = Array.prototype.slice.call(list.querySelectorAll('li[data-item-id]'));
    var selects = Array.prototype.slice.call(form.querySelectorAll('select[data-filter]:not([data-type="display"])'));
    var displaySelect = form.querySelector('select[data-filter][data-type="display"]');
    var input = form.querySelector('.item-search');
    var empty = list.parentNode ? list.parentNode.querySelector('.hobby-pc-video-games-empty') : null;

    var ui = window.filtersToolbar ? window.filtersToolbar.init(form, {
      list: list,
      input: input,
      selects: Array.prototype.slice.call(form.querySelectorAll('select[data-filter]')),
      views: {
        details: function (value) { list.classList.toggle('show-details', value === 'yes'); }
      }
    }) : null;

    function apply() {
      var words = (input ? input.value : '').toLowerCase().split(/\s+/).filter(Boolean);
      var shown = 0;
      var showAll = displaySelect && displaySelect.value === 'all';
      list.setAttribute('data-display', showAll ? 'all' : 'default');

      items.forEach(function (item) {
        var entry = index[item.dataset.itemId];
        var displayed = showAll || item.dataset.display !== 'false';
        var visible = displayed && (!entry || (
          matchesKeywords(entry, words) &&
          selects.every(function (select) { return matchesFilter(entry[select.dataset.filter], select); })
        ));
        item.hidden = !visible;
        if (visible) shown++;
      });

      if (ui) ui.update(shown);
      if (empty) empty.hidden = shown !== 0;
      list.setAttribute('data-results', shown);
      if (window.listingToolbarUrl) window.listingToolbarUrl.save(form);
    }

    selects.forEach(function (select) { select.addEventListener('change', apply); });
    if (displaySelect) displaySelect.addEventListener('change', apply);
    if (input) input.addEventListener('input', apply);
    form.addEventListener('submit', function (event) { event.preventDefault(); apply(); });
    form.addEventListener('reset', function () { setTimeout(apply, 0); });

    if (window.listingToolbarUrl) window.listingToolbarUrl.restore(form);
    apply();
  }

  function start() {
    document.querySelectorAll('form.hobby-pc-video-games-filters[data-target]').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

(function () {
  'use strict';

  var c = window.dmI18n;
  if (!c || !c.fetch || !window.Promise || !window.TextEncoder) return;

  var root = document.documentElement;
  var WS = /[\t\n\v\f\r \u00a0\u1680\u2000-\u200a\u2028\u2029\u202f\u205f\u3000\ufeff]+/g;
  var LEAD = /^[\t\n\v\f\r \u00a0\u1680\u2000-\u200a\u2028\u2029\u202f\u205f\u3000\ufeff]*/;
  var TRAIL = /[\t\n\v\f\r \u00a0\u1680\u2000-\u200a\u2028\u2029\u202f\u205f\u3000\ufeff]*$/;

  var skipTags = {};
  (c.skip || []).forEach(function (tag) { skipTags[tag] = true; });
  var attrs = c.attrs || [];
  var prefixes = c.prefixes || [];
  var metas = c.meta || [];
  var inputs = c.inputs || [];

  var encoder = new TextEncoder();
  var keyCache = new Map();
  var textRecords = new WeakMap();
  var attrRecords = new WeakMap();
  var dict = null;
  var current = c.default;
  var seq = 0;
  var observer = null;

  function normalize(text) {
    return String(text).replace(WS, ' ').replace(/^ | $/g, '');
  }

  function fnv(bytes, hash) {
    for (var i = 0; i < bytes.length; i++) {
      hash ^= bytes[i];
      hash = Math.imul(hash, 16777619) >>> 0;
    }
    return hash >>> 0;
  }

  function pad(hash) {
    return ('0000000' + hash.toString(36)).slice(-7);
  }

  function hashKey(text) {
    var key = keyCache.get(text);
    if (key === undefined) {
      var bytes = encoder.encode(text);
      key = pad(fnv(bytes, 2166136261)) + pad(fnv(bytes, 84696351));
      keyCache.set(text, key);
    }
    return key;
  }

  function translate(source) {
    if (!dict) return source;
    var text = normalize(source);
    if (!text) return source;
    var found = dict instanceof Map ? dict.get(text) : dict[hashKey(text)];
    if (found === undefined) return source;
    return source.match(LEAD)[0] + found + source.match(TRAIL)[0];
  }

  function textNode(node) {
    var record = textRecords.get(node);
    if (record && node.nodeValue !== record.out) record = null;
    var source = record ? record.src : node.nodeValue;
    var out = translate(source);
    if (out !== node.nodeValue) node.nodeValue = out;
    if (out !== source) textRecords.set(node, { src: source, out: out });
    else if (record) textRecords.delete(node);
  }

  function attribute(el, name) {
    var value = el.getAttribute(name);
    if (value === null) return;
    var records = attrRecords.get(el);
    var record = records && records[name];
    if (record && value !== record.out) record = null;
    var source = record ? record.src : value;
    var out = translate(source);
    if (out !== value) el.setAttribute(name, out);
    if (out !== source) {
      if (!records) attrRecords.set(el, records = {});
      records[name] = { src: source, out: out };
    } else if (records) {
      delete records[name];
    }
  }

  function element(el) {
    var i;
    for (i = 0; i < attrs.length; i++) {
      if (el.hasAttribute(attrs[i])) attribute(el, attrs[i]);
    }
    if (prefixes.length) {
      var names = [];
      for (i = 0; i < el.attributes.length; i++) {
        var name = el.attributes[i].name;
        for (var p = 0; p < prefixes.length; p++) {
          if (name.indexOf(prefixes[p]) === 0) { names.push(name); break; }
        }
      }
      names.forEach(function (n) { attribute(el, n); });
    }
    var tag = el.localName;
    if (tag === 'meta') {
      var meta = (el.getAttribute('name') || el.getAttribute('property') || '').toLowerCase().trim();
      if (metas.indexOf(meta) !== -1) attribute(el, 'content');
    } else if (tag === 'input' && inputs.indexOf((el.getAttribute('type') || '').toLowerCase()) !== -1) {
      attribute(el, 'value');
    }
  }

  function skipped(el) {
    if (skipTags[el.localName]) return true;
    if ((el.getAttribute('translate') || '').trim().toLowerCase() === 'no') return true;
    if (el.hasAttribute('data-i18n-skip')) return true;
    return !!(el.classList && el.classList.contains('notranslate'));
  }

  function skippedUp(node) {
    for (var el = node; el && el.nodeType === 1; el = el.parentNode) {
      if (skipped(el)) return true;
    }
    return false;
  }

  function walk(start) {
    if (!start) return;
    if (start.nodeType === 3) {
      if (!skippedUp(start.parentNode)) textNode(start);
      return;
    }
    if (start.nodeType !== 1 || skippedUp(start)) return;

    var walker = document.createTreeWalker(start, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT, {
      acceptNode: function (node) {
        return node.nodeType === 1 && skipped(node) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
      }
    });
    var node = start;
    do {
      if (node.nodeType === 1) element(node);
      else textNode(node);
    } while ((node = walker.nextNode()));
  }

  function observe() {
    if (observer || !window.MutationObserver) return;
    observer = new MutationObserver(function (mutations) {
      if (!dict) return;
      for (var i = 0; i < mutations.length; i++) {
        var added = mutations[i].addedNodes;
        for (var j = 0; j < added.length; j++) walk(added[j]);
      }
    });
    observer.observe(root, { childList: true, subtree: true });
  }

  function syncSwitcher(lang) {
    var dropdowns = document.querySelectorAll('[data-language-dropdown]');
    Array.prototype.forEach.call(dropdowns, function (dropdown) {
      var link = dropdown.querySelector('a[data-i18n-lang="' + lang + '"]');
      if (!link) return;
      Array.prototype.forEach.call(dropdown.querySelectorAll('a[data-i18n-lang]'), function (a) {
        var active = a === link;
        a.classList.toggle('active', active);
        if (active) a.setAttribute('aria-current', 'true');
        else a.removeAttribute('aria-current');
      });
      var code = link.querySelector('.header-language-dropdown-code');
      var currentCode = dropdown.querySelector('.header-language-dropdown-current');
      if (code && currentCode) currentCode.textContent = code.textContent;
      var flag = link.querySelector('.header-language-flag');
      var currentFlag = dropdown.querySelector('summary .header-language-flag');
      if (flag && currentFlag) currentFlag.parentNode.replaceChild(flag.cloneNode(true), currentFlag);
      var summary = dropdown.querySelector('summary');
      if (summary && link.title) {
        var label = summary.getAttribute('aria-label') || '';
        summary.setAttribute('aria-label', label.indexOf(':') !== -1 ? label.replace(/:.*$/, ': ' + link.title) : link.title);
      }
      dropdown.open = false;
    });
  }

  function emit(name, lang) {
    var event;
    try {
      event = new CustomEvent(name, { detail: { lang: lang } });
    } catch (e) {
      event = document.createEvent('CustomEvent');
      event.initCustomEvent(name, false, false, { lang: lang });
    }
    document.dispatchEvent(event);
  }

  function apply(lang) {
    var token = ++seq;
    var target = c.frontend.indexOf(lang) !== -1 ? lang : c.default;
    emit('dm:i18n-start', target);

    var load = target === c.default
      ? Promise.resolve(null)
      : (target === c.lang && c.pending ? c.pending : c.fetch(target));

    return load.then(function (data) {
      if (token !== seq) return;
      dict = target === c.default ? null : (data || {});
      current = target;
      walk(root);
      root.setAttribute('lang', target);
      syncSwitcher(target);
      observe();
      finish(target);
    }, function () {
      if (token === seq) finish(current);
    });
  }

  function finish(lang) {
    root.classList.remove('i18n-loading');
    c.current = lang;
    c.done = true;
    emit('dm:i18n-done', lang);
  }

  function setUrl(lang) {
    try {
      var url = new URL(window.location.href);
      if (lang === c.default) url.searchParams.delete('lang');
      else url.searchParams.set('lang', lang);
      window.history.replaceState(window.history.state, '', url.toString());
    } catch (e) {}
  }

  document.addEventListener('click', function (event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    var link = event.target.closest ? event.target.closest('a[data-i18n-lang]') : null;
    if (!link) return;

    var lang = link.getAttribute('data-i18n-lang');
    if (c.store) c.store(lang);

    var inPlace = c.page_lang === c.default && (lang === c.default || c.frontend.indexOf(lang) !== -1);
    if (!inPlace) {
      if (window.dmPreloader) window.dmPreloader.show('navigate');
      return;
    }

    event.preventDefault();
    if (lang === current) {
      syncSwitcher(lang);
      return;
    }
    setUrl(lang);
    apply(lang);
  });

  window.dmI18n.apply = apply;

  function init() {
    if (c.page_lang === c.default && c.lang) apply(c.lang);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

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

  function chosen(select) {
    return Array.prototype.filter.call(select.options, function (o) { return o.selected && o.value !== ''; })
      .map(function (o) { return o.value; });
  }

  function find(select, wanted) {
    if (wanted == null || wanted === '') return null;
    var options = Array.prototype.filter.call(select.options, function (o) { return o.value !== ''; });
    var w = slug(wanted);
    return options.filter(function (o) { return o.value === wanted; })[0]
      || options.filter(function (o) { return slug(o.value) === w; })[0]
      || options.filter(function (o) { return slug(o.textContent) === w; })[0]
      || null;
  }

  function pick(select, wanted) {
    var match = find(select, wanted);
    if (!match) return false;
    select.value = match.value;
    return true;
  }

  function pickAll(select, wanted) {
    if (!select.multiple) return pick(select, wanted);
    var found = String(wanted || '').split(',').map(function (w) { return find(select, w.trim()); }).filter(Boolean);
    if (!found.length) return false;
    Array.prototype.forEach.call(select.options, function (o) { o.selected = found.indexOf(o) !== -1; });
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
    var next = url.toString().replace(/%2C/gi, ',').replace(/\?$/, '');
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
      defaults[select.name] = chosen(select).join(',');
      if (p && p.has(select.name) && pickAll(select, p.get(select.name)) && chosen(select).join(',') !== defaults[select.name]) filtered = true;
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
      var list = chosen(select);
      values[select.name] = list.length === 0 || list.join(',') === form._toolbarDefaults[select.name]
        ? null
        : list.map(function (v) { return token(select, v); }).join(',');
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

function addJavaScriptAttribute() {
  let duration = 0;

  const applyAttribute = () => {
    if (document.body) {
      document.body.setAttribute("java-script", "true");
    } else {
      requestAnimationFrame(applyAttribute);
    }
  };

  setTimeout(() => {
    applyAttribute();
  }, duration);
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", addJavaScriptAttribute);
} else {
  addJavaScriptAttribute();
}

(function () {
  function start() {
    document.querySelectorAll('[data-page-not-found-path]').forEach(function (el) {
      var value = el.querySelector('.page-not-found-path-value');
      if (!value || value.textContent.trim() !== '') return;
      var path = decodeURIComponent(window.location.pathname + window.location.search);
      if (!path || path === '/') return;
      value.textContent = path;
      el.hidden = false;
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

(function () {
    'use strict';

    var ESCAPE_KEY = 27;
    var FIT_MIN_WIDTH = 1280;
    var FIT_MIN_HEIGHT = 1500;
    var FIT_GROW = 1.1;
    var FIT_ICONS = {
        expand: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h18M3 12l4-4M3 12l4 4M21 12l-4-4M21 12l-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        shrink: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12h7M9 12l-4-4M9 12l-4 4M22 12h-7M15 12l4-4M15 12l4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    };

    function emit(name, detail) {
        document.dispatchEvent(new CustomEvent(name, { detail: detail }));
    }

    function createPopup() {
        var existing = document.getElementById('popup');
        if (existing) return existing;

        var tpl = document.querySelector('.popup-template-source');
        var markup = tpl ? tpl.innerHTML : (
            '<div class="popup-backdrop"></div>' +
            '<button type="button" class="popup-close" aria-label="Close">' +
              '<svg width="14" height="14" viewBox="0 0 14 14"><path d="M13 1L1 13M1 1L13 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
            '</button>' +
            '<div class="popup-content">' +
              '<div class="popup-body"></div>' +
            '</div>'
        );

        var popup = document.createElement('div');
        popup.id = 'popup';
        popup.className = 'popup';
        popup.hidden = true;
        popup.innerHTML = markup;
        document.body.appendChild(popup);
        bindPopupEvents(popup);
        return popup;
    }

    var POST_COLORS = ['--post-color-primary', '--post-color-secondary'];

    function applyColors(popup, source) {
        var styles = source ? window.getComputedStyle(source) : null;
        POST_COLORS.forEach(function (name) {
            var value = styles ? styles.getPropertyValue(name).trim() : '';
            if (value) popup.style.setProperty(name, value);
            else popup.style.removeProperty(name);
        });
    }

    function openPopup(html, options) {
        options = options || {};
        var popup = createPopup();
        if (!popup.hidden) closePopup(popup);
        applyColors(popup, options.source || null);
        destroyGallery(popup);
        setFit(popup, false);
        var body = popup.querySelector('.popup-body');
        body.className = 'popup-body' + (options.gallery ? ' has-gallery' : '');

        if (options.node) {
            body.innerHTML = '';
            body.appendChild(wrapPanel(moveIn(popup, options.node), options));
        } else {
            body.innerHTML = html;
        }

        show(popup);
        if (options.gallery) initGallery(popup, body, parseInt(options.index, 10) || 0);
        initVideos(body);
        updateFit(popup);

        popup.dataset.hash = options.hash || '';
        if (options.hash) setHash(options.hash);

        emit('popup:open', { popup: popup, body: body, source: options.source || null });
        return popup;
    }

    function closePopup(popup) {
        if (!popup || popup.hidden) return;
        var body = popup.querySelector('.popup-body');
        emit('popup:close', { popup: popup, body: body });

        destroyGallery(popup);
        setFit(popup, false);
        fitButton(popup).hidden = true;
        popup.hidden = true;
        moveBack(popup);
        if (body) {
            body.innerHTML = '';
            body.classList.remove('has-gallery');
        }

        if (popup.dataset.hash && currentHash() === popup.dataset.hash) setHash('');
        popup.dataset.hash = '';
    }

    function moveIn(popup, node) {
        var placeholder = document.createComment('popup-move');
        node.parentNode.insertBefore(placeholder, node);
        popup.movedNode = { node: node, placeholder: placeholder };
        return node;
    }

    function moveBack(popup) {
        var moved = popup.movedNode;
        if (!moved) return;
        if (moved.placeholder.parentNode) {
            moved.placeholder.parentNode.insertBefore(moved.node, moved.placeholder);
            moved.placeholder.parentNode.removeChild(moved.placeholder);
        }
        popup.movedNode = null;
    }

    function wrapPanel(node, options) {
        if (!options.panel) return node;
        var panel = document.createElement('div');
        panel.className = 'popup-panel';
        if (options.width) panel.style.setProperty('--popup-panel-width', options.width);
        panel.appendChild(node);
        return panel;
    }

    function currentHash() {
        return decodeURIComponent((window.location.hash || '').slice(1));
    }

    function setHash(hash) {
        if (!window.history || !window.history.replaceState) return;
        var url = window.location.pathname + window.location.search + (hash ? '#' + encodeURIComponent(hash) : '');
        window.history.replaceState(window.history.state, '', url);
    }

    function openFromHash() {
        var hash = currentHash();
        if (!hash) return;
        var popup = document.getElementById('popup');
        if (popup && !popup.hidden && popup.dataset.hash === hash) return;

        var triggers = document.querySelectorAll('[data-popup-hash]');
        for (var i = 0; i < triggers.length; i++) {
            if (triggers[i].getAttribute('data-popup-hash') === hash) {
                openTrigger(triggers[i]);
                return;
            }
        }
    }

    function linkHandledByBrowser(link, event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return true;

        var url;
        try { url = new URL(link.href, window.location.href); } catch (e) { return false; }
        var samePage = url.origin === window.location.origin
            && url.pathname === window.location.pathname
            && url.search === window.location.search;
        var id = url.hash ? decodeURIComponent(url.hash.slice(1)) : '';
        return samePage && id !== '' && document.getElementById(id) !== null;
    }

    function show(popup) { popup.hidden = false; }

    function initGallery(popup, body, index) {
        var gallery = body.querySelector('[data-popup-gallery]');
        var slides = gallery ? gallery.querySelectorAll('.popup-slide') : [];
        if (!slides.length || !window.UtilitySlider) return;

        var track = document.createElement('div');
        track.className = 'utility-slider-track';
        Array.prototype.forEach.call(slides, function (slide) {
            slide.classList.add('utility-slider-slide');
            track.appendChild(slide);
        });
        gallery.classList.add('utility-slider-viewport');
        gallery.appendChild(track);

        popup.classList.add('popup-has-gallery');
        window.UtilitySlider.init(popup, { viewport: gallery, start: index, arrows: true, counter: true, dots: false });
    }

    function initVideos(body) {
        var players = body.querySelectorAll('.video-container');
        if (!players.length) return;

        Array.prototype.forEach.call(document.querySelectorAll('video'), function (video) {
            if (!body.contains(video) && !video.paused) video.pause();
        });

        Array.prototype.forEach.call(players, function (player) {
            player.removeAttribute('data-video-initialized');
            player.classList.add('paused');
            player.classList.remove('full-screen', 'scrubbing');
            var thumbnail = player.querySelector('.thumbnail');
            if (thumbnail) thumbnail.style.display = '';
            var showPlay = player.querySelector('.show-play');
            if (showPlay) showPlay.style.display = 'flex';
            var showPause = player.querySelector('.show-pause');
            if (showPause) showPause.style.display = 'none';
            var controls = player.querySelector('.video-controls-container');
            if (controls) controls.style.display = '';
        });

        if (window.VideoControls && typeof window.VideoControls.initAll === 'function') window.VideoControls.initAll();
    }

    function fitButton(popup) {
        var btn = popup.querySelector(':scope > .popup-fit');
        if (btn) return btn;
        btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'popup-fit';
        btn.hidden = true;
        popup.insertBefore(btn, popup.querySelector('.popup-content'));
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            setFit(popup, !popup.classList.contains('popup-fit-width'));
        });
        return btn;
    }

    function setFit(popup, on) {
        popup.classList.toggle('popup-fit-width', on);
        var btn = fitButton(popup);
        var label = on ? 'Fit to screen' : 'Fit to width';
        btn.innerHTML = on ? FIT_ICONS.shrink : FIT_ICONS.expand;
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        btn.setAttribute('aria-label', label);
        btn.title = label;
        Array.prototype.forEach.call(popup.querySelectorAll('.popup-fit-target'), function (img) {
            img.style.transform = '';
            img.dataset.zoomLevel = 0;
            var box = img.closest('.popup-fit-scroll');
            if (box) box.scrollTop = 0;
        });
    }

    function currentImage(popup) {
        var body = popup.querySelector('.popup-body');
        var scope = body && popup.utilitySlider ? body.querySelector('.popup-slide.active') : body;
        if (!scope || scope.textContent.trim() !== '') return null;
        var imgs = scope.querySelectorAll('img');
        return imgs.length === 1 ? imgs[0] : null;
    }

    function canFit(popup, img) {
        var tall = img.naturalHeight > FIT_MIN_HEIGHT
            || (img.naturalWidth >= FIT_MIN_WIDTH && img.naturalHeight >= img.naturalWidth);
        if (!tall) return false;
        var content = popup.querySelector('.popup-content');
        var available = parseFloat(window.getComputedStyle(content).maxWidth) || content.clientWidth;
        return Math.min(available, img.naturalWidth) > img.offsetWidth * FIT_GROW;
    }

    function updateFit(popup) {
        if (popup.hidden) return;
        var btn = fitButton(popup);
        var img = currentImage(popup);
        if (img && !img.complete) {
            btn.hidden = true;
            img.addEventListener('load', function () { updateFit(popup); }, { once: true });
            return;
        }
        var ok = !!img && (img.classList.contains('popup-fit-target') || canFit(popup, img));
        if (ok) {
            img.classList.add('popup-fit-target');
            var wrap = img.parentElement;
            if (!wrap.classList.contains('popup-fit-scroll')) {
                wrap = document.createElement('div');
                wrap.className = 'popup-fit-scroll';
                img.parentNode.insertBefore(wrap, img);
                wrap.appendChild(img);
            }
            wrap.style.setProperty('--popup-fit-max', img.naturalWidth + 'px');
            var content = popup.querySelector('.popup-content');
            if (content && !popup.utilitySlider) content.style.setProperty('--popup-fit-max', img.naturalWidth + 'px');
        }
        btn.hidden = !ok;
    }

    function destroyGallery(popup) {
        if (popup.utilitySlider) popup.utilitySlider.destroy();
        popup.classList.remove('popup-has-gallery');
    }

    function bindPopupEvents(popup) {
        popup.querySelector('.popup-backdrop').addEventListener('click', function () { closePopup(popup); });
        popup.querySelector('.popup-close').addEventListener('click', function () { closePopup(popup); });

        document.addEventListener('keydown', function (e) {
            if (popup.hidden) return;
            if (e.keyCode === ESCAPE_KEY || e.key === 'Escape') closePopup(popup);
            if (!popup.utilitySlider) return;
            if (document.activeElement && document.activeElement.closest && document.activeElement.closest('.video-container')) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); popup.utilitySlider.prev(); }
            if (e.key === 'ArrowRight') { e.preventDefault(); popup.utilitySlider.next(); }
        });

        popup.addEventListener('click', function (event) {
            var img = event.target.closest('img');
            if (!img || !img.closest('.popup-body')) return;
            if (popup.classList.contains('popup-fit-width') && img.classList.contains('popup-fit-target')) return;
            toggleZoom(img, event);
        });

        popup.addEventListener('utility-slider:change', function () { updateFit(popup); });

        var resizeTick = false;
        window.addEventListener('resize', function () {
            if (popup.hidden || resizeTick) return;
            resizeTick = true;
            window.requestAnimationFrame(function () {
                resizeTick = false;
                updateFit(popup);
            });
        });
    }

    function toggleZoom(img, event) {
        var level = parseInt(img.dataset.zoomLevel || 0, 10) + 1;
        var rect = img.getBoundingClientRect();
        img.style.transformOrigin = ((event.clientX - rect.left) / rect.width) * 100 + '% ' + ((event.clientY - rect.top) / rect.height) * 100 + '%';

        if (level === 1) img.style.transform = 'scale(1.25)';
        else if (level === 2) img.style.transform = 'scale(1.5)';
        else if (level === 3) img.style.transform = 'scale(2)';
        else { img.style.transform = 'scale(1)'; level = 0; }
        img.dataset.zoomLevel = level;
    }

    function openTrigger(trigger) {
        var hash = trigger.getAttribute('data-popup-hash') || '';

        if (trigger.hasAttribute('data-popup-move')) {
            var selector = trigger.getAttribute('data-popup-move');
            var node = selector ? document.querySelector(selector) : trigger;
            if (!node) return null;
            return openPopup('', {
                node: node,
                source: trigger,
                hash: hash,
                panel: trigger.hasAttribute('data-popup-panel'),
                width: trigger.getAttribute('data-popup-width') || ''
            });
        }

        var template = trigger.querySelector('template.popup-template');
        if (template) {
            var galleryEl = template.content.querySelector('[data-popup-gallery]');
            var slides = template.content.querySelectorAll('[data-popup-gallery] .popup-slide');
            return openPopup(template.innerHTML, { gallery: !!galleryEl && slides.length > 1, source: trigger, hash: hash });
        }

        var group = trigger.getAttribute('data-popup-group');
        if (group) {
            var members = Array.prototype.slice.call(document.querySelectorAll('[data-popup-group="' + group + '"]'));
            var idx = members.indexOf(trigger);
            var slidesHtml = members.map(function (m) {
                var player = m.querySelector('.video-container');
                if (player) return '<div class="popup-slide popup-slide-video">' + player.outerHTML + '</div>';
                var img = m.tagName === 'IMG' ? m : m.querySelector('img');
                return '<div class="popup-slide">' + (img ? img.outerHTML : m.innerHTML) + '</div>';
            }).join('');
            return openPopup('<div data-popup-gallery>' + slidesHtml + '</div>', { gallery: members.length > 1, index: idx, source: trigger, hash: hash });
        }

        var targetSel = trigger.getAttribute('data-popup-target');
        var content = '';
        if (targetSel) {
            var t = document.querySelector(targetSel);
            content = t ? t.innerHTML : '';
        } else {
            var img = trigger.tagName === 'IMG' ? trigger : trigger.querySelector('img');
            content = img ? img.outerHTML : trigger.innerHTML;
        }
        return openPopup(content, { source: trigger, hash: hash });
    }

    function initTriggers() {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-popup="true"]');
            if (!trigger) return;

            var link = event.target.closest('a[href]');
            if (link && trigger.contains(link)) {
                if (linkHandledByBrowser(link, event)) return;
                event.preventDefault();
            }

            openTrigger(trigger);
        });

        openFromHash();
        window.addEventListener('hashchange', openFromHash);
    }

    document.addEventListener('DOMContentLoaded', initTriggers);

    window.Popup = {
        open: openPopup,
        close: function (popup) { closePopup(popup || document.getElementById('popup')); },
        openTrigger: openTrigger
    };
})();
(function () {
  var items = [];
  var ticking = false;

  function update() {
    ticking = false;
    var vh = window.innerHeight || document.documentElement.clientHeight;
    items.forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.bottom < -100 || r.top > vh + 100) return;
      var center = r.top + r.height / 2;
      var progress = (vh / 2 - center) / (vh / 2 + r.height / 2);
      progress = Math.max(-1, Math.min(1, progress));
      el.style.setProperty('--ph-progress', progress.toFixed(3));
    });
  }

  function onScroll() {
    if (!ticking) {
      ticking = true;
      window.requestAnimationFrame(update);
    }
  }

  function start() {
    items = Array.prototype.slice.call(document.querySelectorAll('[data-portrait-home-parallax]'));
    if (!items.length) return;

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.25 });
    items.forEach(function (el) { observer.observe(el); });

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    update();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

(function () {
  'use strict';

  var root = document.documentElement;
  var holds = { page: true };
  var el = document.querySelector('[data-preloader]');
  var wait = el ? el.getAttribute('data-preloader-wait') : 'load';
  var max = el ? parseInt(el.getAttribute('data-preloader-max'), 10) || 4000 : 4000;

  if (root.classList.contains('i18n-loading')) holds.i18n = true;

  function update() {
    var busy = Object.keys(holds).some(function (key) { return holds[key]; });
    root.classList.toggle('is-loading', busy);
    if (el) el.setAttribute('aria-busy', busy ? 'true' : 'false');
  }

  function hold(key) {
    holds[key || 'manual'] = true;
    update();
  }

  function release(key) {
    if (key) holds[key] = false;
    else holds = {};
    update();
  }

  window.dmPreloader = { show: hold, hide: release };

  document.addEventListener('dm:i18n-start', function () { hold('i18n'); });
  document.addEventListener('dm:i18n-done', function () { release('i18n'); });

  function pageReady() { release('page'); }

  if (wait === 'dom') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', pageReady);
    else pageReady();
  } else if (document.readyState === 'complete') {
    pageReady();
  } else {
    window.addEventListener('load', pageReady);
  }

  setTimeout(function () {
    holds.page = false;
    holds.i18n = false;
    update();
  }, max);

  window.addEventListener('pageshow', function (event) {
    if (event.persisted) release();
  });

  update();
})();

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
    var tiles = Array.prototype.slice.call(root.querySelectorAll('.project-gallery-tile'));
    var chips = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-filter]'));
    var foot = root.querySelector('.project-gallery-foot');
    var status = root.querySelector('[data-gallery-status]');
    var more = root.querySelector('[data-gallery-more]');
    var all = root.querySelector('[data-gallery-all]');
    var group = root.getAttribute('data-group') || '';
    var pageSize = parseInt(root.getAttribute('data-page-size') || '0', 10) || 0;
    var statusText = root.getAttribute('data-status-text') || '{shown} / {total}';

    var grid = root.querySelector('.project-gallery-grid');
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

      if (grid) grid.setAttribute('data-gallery-complete', shown >= matching.length ? 'true' : 'false');
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

    if (more) more.addEventListener('click', function () {
      more.classList.remove('is-loading');
      void more.offsetWidth;
      more.classList.add('is-loading');
      limit += pageSize || Infinity;
      update();
    });
    if (more) more.addEventListener('animationend', function () { more.classList.remove('is-loading'); });
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
    document.querySelectorAll('[data-project-gallery]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
  'use strict';

  function pad(n) { return (n < 10 ? '0' : '') + n; }

  function setup(viewer) {
    var stage = viewer.querySelector('[data-screens-stage]');
    var thumbs = Array.prototype.slice.call(viewer.querySelectorAll('[data-screens-index]'));
    var pairs = viewer.querySelectorAll('template[data-screens-pair]');
    var sources = viewer.querySelectorAll('.project-screens-source-item');
    var rail = viewer.querySelector('[data-screens-thumbs]');
    var counter = viewer.querySelector('[data-screens-counter]');
    var title = viewer.querySelector('[data-screens-title]');
    var sub = viewer.querySelector('[data-screens-sub]');
    if (!stage || !thumbs.length) return;

    var total = thumbs.length;

    function vertical() {
      return !!rail && rail.scrollHeight > rail.clientHeight + 2 && rail.scrollWidth <= rail.clientWidth + 2;
    }
    var active = 0;

    function select(index, focusThumb) {
      index = (index + total) % total;
      if (index === active && stage.childElementCount) return;
      active = index;

      var tpl = pairs[index];
      if (tpl) {
        stage.classList.remove('is-in');
        stage.innerHTML = tpl.innerHTML;
        void stage.offsetWidth;
        stage.classList.add('is-in');
      }

      thumbs.forEach(function (thumb, i) {
        if (i === index) thumb.setAttribute('aria-current', 'true');
        else thumb.removeAttribute('aria-current');
      });

      var thumb = thumbs[index];
      if (counter) counter.textContent = pad(index + 1) + ' / ' + pad(total);
      if (title) title.textContent = thumb.getAttribute('data-title') || '';
      if (sub) sub.textContent = thumb.getAttribute('data-sub') || '';

      if (rail) {
        var railBox = rail.getBoundingClientRect();
        var box = thumb.getBoundingClientRect();
        if (vertical()) {
          var top = rail.scrollTop + box.top - railBox.top - (rail.clientHeight - box.height) / 2;
          rail.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        } else if (rail.scrollWidth > rail.clientWidth) {
          var left = rail.scrollLeft + box.left - railBox.left - (rail.clientWidth - box.width) / 2;
          rail.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
        }
      }
      if (focusThumb) thumb.focus({ preventScroll: true });
    }

    function openGallery(index) {
      var item = sources[index] || sources[0];
      if (item && window.Popup && typeof window.Popup.openTrigger === 'function') window.Popup.openTrigger(item);
    }

    thumbs.forEach(function (thumb, i) {
      thumb.addEventListener('click', function () { select(i, false); });
    });

    viewer.querySelectorAll('[data-screens-prev]').forEach(function (btn) {
      btn.addEventListener('click', function () { select(active - 1, false); });
    });
    viewer.querySelectorAll('[data-screens-next]').forEach(function (btn) {
      btn.addEventListener('click', function () { select(active + 1, false); });
    });

    if (rail) {
      rail.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); select(active - 1, true); }
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); select(active + 1, true); }
      });

      var updateFade = function () {
        rail.classList.toggle('mask-fade-bottom', vertical() && rail.scrollHeight - rail.scrollTop - rail.clientHeight > 2);
      };
      rail.addEventListener('scroll', updateFade, { passive: true });
      if ('ResizeObserver' in window) new ResizeObserver(updateFade).observe(rail);
      else window.addEventListener('resize', updateFade);
      updateFade();
    }

    stage.addEventListener('click', function () { openGallery(active); });
    stage.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openGallery(active); }
    });

    var open = viewer.querySelector('[data-screens-open]');
    if (open) open.addEventListener('click', function () { openGallery(0); });

    var startX = null;
    stage.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(dx) > 50) select(active + (dx < 0 ? 1 : -1), false);
    });

    stage.classList.add('is-in');
  }

  function init() {
    document.querySelectorAll('[data-project-screens]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
  'use strict';

  function setup(root) {
    var screen = root.querySelector('[data-video-screen]');
    var items = Array.prototype.slice.call(root.querySelectorAll('[data-video-index]'));
    var title = root.querySelector('[data-video-title]');
    var meta = root.querySelector('[data-video-meta]');
    var detailsBtn = root.querySelector('[data-video-details-open]');
    var list = root.querySelector('[data-video-list]');
    if (!screen) return;

    function updateFade() {
      if (!list) return;
      list.classList.toggle('mask-fade-bottom', list.scrollHeight - list.scrollTop - list.clientHeight > 2);
    }
    if (list) {
      list.addEventListener('scroll', updateFade, { passive: true });
      if ('ResizeObserver' in window) new ResizeObserver(updateFade).observe(list);
      else window.addEventListener('resize', updateFade);
      updateFade();
    }

    var active = 0;

    function template(kind, index) {
      return root.querySelector('template[data-video-' + kind + '="' + index + '"]');
    }

    function select(index, autoplay) {
      if (index === active) return;
      var tpl = template('player', index);
      if (!tpl) return;
      active = index;

      screen.querySelectorAll('video').forEach(function (video) { video.pause(); });
      screen.innerHTML = tpl.innerHTML;
      if (window.VideoControls && typeof window.VideoControls.initAll === 'function') window.VideoControls.initAll();

      items.forEach(function (item, i) {
        if (i === index) item.setAttribute('aria-current', 'true');
        else item.removeAttribute('aria-current');
      });

      var item = items[index];
      if (title) title.textContent = item.getAttribute('data-title') || '';
      if (meta) meta.textContent = item.getAttribute('data-meta') || '';
      if (detailsBtn) detailsBtn.hidden = item.getAttribute('data-has-details') !== 'true';

      if (autoplay) {
        var video = screen.querySelector('video');
        var play = screen.querySelector('.show-play');
        if (play) play.click();
        else if (video) video.play().catch(function () {});
      }
    }

    items.forEach(function (item, i) {
      item.addEventListener('click', function () { select(i, false); });
    });

    if (detailsBtn) {
      detailsBtn.addEventListener('click', function () {
        var tpl = template('details', active);
        if (!tpl || !window.Popup || typeof window.Popup.open !== 'function') return;
        screen.querySelectorAll('video').forEach(function (video) { video.pause(); });
        window.Popup.open(tpl.innerHTML, { source: root });
      });
    }
  }

  function init() {
    document.querySelectorAll('[data-project-videos]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

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

(function () {
  'use strict';

  function pad(n) { return (n < 10 ? '0' : '') + n; }

  function setup(viewer) {
    var stage = viewer.querySelector('[data-screens-stage]');
    var thumbs = Array.prototype.slice.call(viewer.querySelectorAll('[data-screens-index]'));
    var pairs = viewer.querySelectorAll('template[data-screens-pair]');
    var sources = viewer.querySelectorAll('.screens-viewer-source-item');
    var rail = viewer.querySelector('[data-screens-thumbs]');
    var counter = viewer.querySelector('[data-screens-counter]');
    var title = viewer.querySelector('[data-screens-title]');
    var sub = viewer.querySelector('[data-screens-sub]');
    if (!stage || !thumbs.length) return;

    var total = thumbs.length;

    function vertical() {
      return !!rail && rail.scrollHeight > rail.clientHeight + 2 && rail.scrollWidth <= rail.clientWidth + 2;
    }
    var active = 0;

    function select(index, focusThumb) {
      index = (index + total) % total;
      if (index === active && stage.childElementCount) return;
      active = index;

      var tpl = pairs[index];
      if (tpl) {
        stage.classList.remove('is-in');
        stage.innerHTML = tpl.innerHTML;
        void stage.offsetWidth;
        stage.classList.add('is-in');
      }

      thumbs.forEach(function (thumb, i) {
        if (i === index) thumb.setAttribute('aria-current', 'true');
        else thumb.removeAttribute('aria-current');
      });

      var thumb = thumbs[index];
      if (counter) counter.textContent = pad(index + 1) + ' / ' + pad(total);
      if (title) title.textContent = thumb.getAttribute('data-title') || '';
      if (sub) sub.textContent = thumb.getAttribute('data-sub') || '';

      if (rail) {
        var railBox = rail.getBoundingClientRect();
        var box = thumb.getBoundingClientRect();
        if (vertical()) {
          var top = rail.scrollTop + box.top - railBox.top - (rail.clientHeight - box.height) / 2;
          rail.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        } else if (rail.scrollWidth > rail.clientWidth) {
          var left = rail.scrollLeft + box.left - railBox.left - (rail.clientWidth - box.width) / 2;
          rail.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
        }
      }
      if (focusThumb) thumb.focus({ preventScroll: true });
    }

    function openGallery(index) {
      var item = sources[index] || sources[0];
      if (item && window.Popup && typeof window.Popup.openTrigger === 'function') window.Popup.openTrigger(item);
    }

    thumbs.forEach(function (thumb, i) {
      thumb.addEventListener('click', function () { select(i, false); });
    });

    var prev = viewer.querySelector('[data-screens-prev]');
    var next = viewer.querySelector('[data-screens-next]');
    if (prev) prev.addEventListener('click', function () { select(active - 1, false); });
    if (next) next.addEventListener('click', function () { select(active + 1, false); });

    if (rail) {
      rail.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); select(active - 1, true); }
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); select(active + 1, true); }
      });

      var updateFade = function () {
        rail.classList.toggle('mask-fade-bottom', vertical() && rail.scrollHeight - rail.scrollTop - rail.clientHeight > 2);
      };
      rail.addEventListener('scroll', updateFade, { passive: true });
      if ('ResizeObserver' in window) new ResizeObserver(updateFade).observe(rail);
      else window.addEventListener('resize', updateFade);
      updateFade();
    }

    stage.addEventListener('click', function () { openGallery(active); });
    stage.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openGallery(active); }
    });

    var open = viewer.querySelector('[data-screens-open]');
    if (open) open.addEventListener('click', function () { openGallery(0); });

    var startX = null;
    stage.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(dx) > 50) select(active + (dx < 0 ? 1 : -1), false);
    });

    stage.classList.add('is-in');
  }

  function init() {
    document.querySelectorAll('[data-screens-viewer]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
  function getTop(button) {
    var selector = button.getAttribute('data-scroll-down-target');
    if (selector) {
      var target = null;
      try { target = document.querySelector(selector); } catch (e) {}
      if (target) return target.getBoundingClientRect().top + window.pageYOffset;
    }
    var scope = button.closest('[data-scroll-down-scope], section') || button.parentElement;
    if (!scope) return null;
    return scope.getBoundingClientRect().bottom + window.pageYOffset;
  }

  function start() {
    document.querySelectorAll('[data-scroll-down]').forEach(function (button) {
      if (button.dataset.scrollDownReady) return;
      button.dataset.scrollDownReady = '1';
      button.addEventListener('click', function () {
        var top = getTop(button);
        if (top === null) return;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: top, behavior: reduce ? 'auto' : 'smooth' });
      });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

(function () {
  'use strict';

  var bars = [];
  var queued = false;

  function pageProgress() {
    var max = document.documentElement.scrollHeight - window.innerHeight;
    return max > 0 ? window.scrollY / max : 0;
  }

  function elementProgress(el) {
    var rect = el.getBoundingClientRect();
    var range = rect.height - window.innerHeight;
    if (range <= 0) return rect.top <= 0 ? 1 : 0;
    return -rect.top / range;
  }

  function update() {
    queued = false;
    var page = null;
    bars.forEach(function (entry) {
      var value;
      if (entry.target) {
        value = elementProgress(entry.target);
      } else {
        if (page === null) page = pageProgress();
        value = page;
      }
      value = Math.min(1, Math.max(0, value));
      entry.bar.style.transform = 'scaleX(' + value.toFixed(4) + ')';
    });
  }

  function queue() {
    if (queued) return;
    queued = true;
    window.requestAnimationFrame(update);
  }

  function init() {
    document.querySelectorAll('[data-scroll-progress]').forEach(function (el) {
      var bar = el.querySelector('.scroll-progress-bar');
      if (!bar) return;
      var selector = el.getAttribute('data-scroll-progress-target');
      var target = null;
      if (selector) {
        try { target = document.querySelector(selector); } catch (e) { target = null; }
        if (!target) return;
      }
      bars.push({ bar: bar, target: target });
    });
    if (!bars.length) return;

    window.addEventListener('scroll', queue, { passive: true });
    window.addEventListener('resize', queue);
    update();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

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
      var filtered = selects.some(function (select) { return window.filtersToolbar.values(select).length > 0; });
      var active = folded.length >= minChars || showAll || filtered;
      if (folded.length < minChars) words = [];
      var shown = 0;

      items.forEach(function (item) {
        var entry = index[item.dataset.itemId];
        var visible = active && !!entry &&
          words.every(function (word) { return entry.keywords.indexOf(word) !== -1; }) &&
          selects.every(function (select) {
            var selected = window.filtersToolbar.values(select);
            var values = entry[select.dataset.filter] || [];
            return !selected.length || selected.some(function (value) { return values.indexOf(value) !== -1; });
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

(function () {
  'use strict';

  function setup(nav) {
    var row = nav.querySelector('.anchor-index-list');
    var toggle = nav.querySelector('.section-nav-toggle');
    var current = nav.querySelector('[data-section-nav-current]');

    function setOpen(open) {
      nav.setAttribute('data-open', open ? 'true' : 'false');
      if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (toggle) {
      toggle.addEventListener('click', function () {
        setOpen(nav.getAttribute('data-open') !== 'true');
      });
      nav.addEventListener('click', function (e) {
        if (e.target.closest && e.target.closest('.anchor-index-link')) setOpen(false);
      });
      document.addEventListener('click', function (e) {
        if (nav.getAttribute('data-open') === 'true' && !nav.contains(e.target)) setOpen(false);
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && nav.getAttribute('data-open') === 'true') {
          setOpen(false);
          toggle.focus();
        }
      });
      window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) setOpen(false);
      });
    }

    if (row) {
      var follow = function () {
        var link = row.querySelector('[aria-current="true"]');
        if (!link) return;
        if (current) current.textContent = link.textContent.trim();
        if (row.scrollWidth <= row.clientWidth) return;
        var item = link.parentElement || link;
        var left = item.offsetLeft - (row.clientWidth - item.offsetWidth) / 2;
        row.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
      };
      new MutationObserver(follow).observe(row, { subtree: true, attributes: true, attributeFilter: ['aria-current'] });
      follow();
    }

    if ('IntersectionObserver' in window) {
      var sentinel = document.createElement('div');
      sentinel.className = 'section-nav-sentinel';
      sentinel.setAttribute('aria-hidden', 'true');
      nav.parentNode.insertBefore(sentinel, nav);
      new IntersectionObserver(function (entries) {
        nav.setAttribute('data-stuck', entries[0].isIntersecting ? 'false' : 'true');
      }, { rootMargin: '-120px 0px 0px 0px' }).observe(sentinel);
    }
  }

  function init() {
    document.querySelectorAll('[data-section-nav]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
  var SEARCH_MIN = 8;
  var uid = 0;
  var boxes = [];
  var escapedAt = 0;

  function fold(text) {
    var s = String(text || '').toLowerCase();
    if (s.normalize) s = s.normalize('NFD').replace(/[̀-ͯ]/g, '');
    return s.replace(/\s+/g, ' ').trim();
  }

  function values(select) {
    return Array.prototype.filter.call(select.options, function (o) { return o.selected && o.value !== ''; })
      .map(function (o) { return o.value; });
  }

  function make(tag, className) {
    var el = document.createElement(tag);
    if (className) el.className = className;
    return el;
  }

  function labelFor(select) {
    if (!select.id) return null;
    var id = window.CSS && CSS.escape ? CSS.escape(select.id) : select.id;
    return document.querySelector('label[for="' + id + '"]');
  }

  function searchable(select) {
    var mode = select.getAttribute('data-select-box-search');
    if (mode === 'on') return true;
    if (mode === 'off') return false;
    var min = parseInt(select.getAttribute('data-search-min'), 10);
    return select.options.length >= (isNaN(min) ? SEARCH_MIN : min);
  }

  function build(select) {
    var id = select.id || ('select-box-search-' + (++uid));
    select.id = id;
    var multiple = select.multiple;
    var label = labelFor(select);
    if (label && !label.id) label.id = id + '-label';

    var box = make('div', 'sbs' + (multiple ? ' sbs-multiple' : ''));
    select.parentNode.insertBefore(box, select);
    box.appendChild(select);
    select.classList.add('sbs-native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    var control = make('button', 'sbs-control');
    control.type = 'button';
    control.setAttribute('aria-haspopup', 'listbox');
    control.setAttribute('aria-expanded', 'false');
    control.setAttribute('aria-controls', id + '-list');
    control.innerHTML = '<span class="sbs-value" id="' + id + '-value"></span><span class="sbs-count" hidden></span><span class="sbs-arrow" aria-hidden="true"></span>';
    if (label) control.setAttribute('aria-labelledby', label.id + ' ' + id + '-value');

    var panel = make('div', 'sbs-panel');
    panel.hidden = true;

    var input = null;
    if (searchable(select)) {
      input = make('input', 'sbs-input');
      input.type = 'search';
      input.autocomplete = 'off';
      input.spellcheck = false;
      input.placeholder = select.getAttribute('data-label-search') || '';
      input.setAttribute('role', 'combobox');
      input.setAttribute('aria-autocomplete', 'list');
      input.setAttribute('aria-expanded', 'true');
      input.setAttribute('aria-controls', id + '-list');
      if (label) input.setAttribute('aria-labelledby', label.id);
      panel.appendChild(input);
    }

    var list = make('ul', 'sbs-list');
    list.id = id + '-list';
    list.tabIndex = -1;
    list.setAttribute('role', 'listbox');
    if (multiple) list.setAttribute('aria-multiselectable', 'true');
    if (label) list.setAttribute('aria-labelledby', label.id);
    panel.appendChild(list);

    var empty = make('p', 'sbs-empty');
    empty.textContent = select.getAttribute('data-label-empty') || '';
    empty.hidden = true;
    panel.appendChild(empty);

    box.appendChild(control);
    box.appendChild(panel);

    var items = Array.prototype.filter.call(select.options, function (o) { return !(multiple && o.value === ''); })
      .map(function (option, i) {
        var li = make('li', 'sbs-option');
        li.id = id + '-option-' + i;
        li.setAttribute('role', 'option');
        li.innerHTML = '<span class="sbs-check" aria-hidden="true"></span><span class="sbs-text"></span>';
        var style = option.getAttribute('style');
        if (style) li.lastChild.setAttribute('style', style);
        li._option = option;
        list.appendChild(li);
        return li;
      });

    var state = { active: null };
    var focusTarget = input || list;

    function visible() {
      return items.filter(function (li) { return !li.hidden && !li._option.disabled; });
    }

    function setActive(li) {
      if (state.active) state.active.classList.remove('is-active');
      state.active = li || null;
      if (!li) {
        focusTarget.removeAttribute('aria-activedescendant');
        return;
      }
      li.classList.add('is-active');
      focusTarget.setAttribute('aria-activedescendant', li.id);
      if (li.offsetTop < list.scrollTop) list.scrollTop = li.offsetTop;
      else if (li.offsetTop + li.offsetHeight > list.scrollTop + list.clientHeight) list.scrollTop = li.offsetTop + li.offsetHeight - list.clientHeight;
    }

    function filter(query) {
      var words = fold(query).split(' ').filter(Boolean);
      items.forEach(function (li) {
        var text = fold(li._option.textContent);
        li.hidden = !words.every(function (word) { return text.indexOf(word) !== -1; });
      });
      var shown = visible();
      empty.hidden = shown.length > 0;
      if (shown.indexOf(state.active) === -1) setActive(shown[0]);
    }

    function sync() {
      var chosen = Array.prototype.filter.call(select.options, function (o) { return o.selected && !(multiple && o.value === ''); });
      items.forEach(function (li) {
        var on = li._option.selected;
        li.lastChild.textContent = li._option.textContent;
        li.classList.toggle('is-selected', on);
        li.setAttribute('aria-selected', on ? 'true' : 'false');
        if (li._option.disabled) li.setAttribute('aria-disabled', 'true');
        else li.removeAttribute('aria-disabled');
      });

      var value = control.querySelector('.sbs-value');
      var count = control.querySelector('.sbs-count');
      var placeholder = chosen.length === 0;
      value.textContent = placeholder
        ? (select.getAttribute('data-placeholder') || '')
        : chosen.map(function (o) { return o.textContent; }).join(', ');
      value.classList.toggle('is-placeholder', placeholder);
      var style = !placeholder && chosen.length === 1 ? chosen[0].getAttribute('style') : null;
      if (style) value.setAttribute('style', style);
      else value.removeAttribute('style');
      count.textContent = chosen.length;
      count.hidden = !multiple || chosen.length < 2;

      box.classList.toggle('is-active', values(select).length > 0);
      box.classList.toggle('is-disabled', select.disabled);
      control.disabled = select.disabled;
    }

    function open() {
      if (select.disabled || !panel.hidden) return;
      boxes.forEach(function (other) { if (other.box !== box) other.close(false); });
      sync();
      panel.hidden = false;
      box.classList.add('is-open');
      control.setAttribute('aria-expanded', 'true');
      if (input) input.value = '';
      filter('');
      var first = items.filter(function (li) { return li._option.selected; })[0];
      setActive(first || visible()[0]);
      place();
      focusTarget.focus({ preventScroll: true });
    }

    function close(focusControl) {
      if (panel.hidden) return;
      panel.hidden = true;
      box.classList.remove('is-open', 'sbs-up');
      control.setAttribute('aria-expanded', 'false');
      if (focusControl) control.focus();
    }

    function place() {
      box.classList.remove('sbs-up');
      var rect = panel.getBoundingClientRect();
      var above = box.getBoundingClientRect().top;
      if (rect.bottom > window.innerHeight && above > rect.height + 8) box.classList.add('sbs-up');
      else if (panel.scrollIntoView) panel.scrollIntoView({ block: 'nearest' });
    }

    function change() {
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function choose(li) {
      if (!li || li._option.disabled) return;
      var option = li._option;
      if (multiple) {
        option.selected = !option.selected;
        change();
        return;
      }
      var changed = !option.selected;
      select.value = option.value;
      if (changed) change();
      close(true);
    }

    function removeLast() {
      var chosen = Array.prototype.filter.call(select.options, function (o) { return o.selected; });
      if (!chosen.length) return;
      chosen[chosen.length - 1].selected = false;
      change();
    }

    function move(step) {
      var shown = visible();
      if (!shown.length) return;
      var index = shown.indexOf(state.active);
      index = index === -1 ? 0 : Math.min(shown.length - 1, Math.max(0, index + step));
      setActive(shown[index]);
    }

    control.addEventListener('click', function () {
      if (panel.hidden) open();
      else close(true);
    });

    control.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        open();
        return;
      }
      if (input && event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
        event.preventDefault();
        open();
        input.value = event.key;
        filter(input.value);
      }
    });

    panel.addEventListener('keydown', function (event) {
      var key = event.key;
      if (key === 'ArrowDown' || key === 'ArrowUp') {
        event.preventDefault();
        move(key === 'ArrowDown' ? 1 : -1);
      } else if ((key === 'Home' || key === 'End') && event.target === list) {
        event.preventDefault();
        var shown = visible();
        setActive(key === 'Home' ? shown[0] : shown[shown.length - 1]);
      } else if (key === 'Enter' || (key === ' ' && event.target === list)) {
        event.preventDefault();
        choose(state.active);
      } else if (key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        escapedAt = Date.now();
        close(true);
      } else if (key === 'Tab') {
        close(false);
      } else if (key === 'Backspace' && multiple && input && input.value === '') {
        event.preventDefault();
        removeLast();
      }
    });

    if (input) input.addEventListener('input', function () { filter(input.value); });

    list.addEventListener('mousedown', function (event) {
      if (input) event.preventDefault();
    });
    list.addEventListener('click', function (event) {
      var li = event.target.closest('.sbs-option');
      if (li) choose(li);
    });
    list.addEventListener('mousemove', function (event) {
      var li = event.target.closest('.sbs-option');
      if (li && li !== state.active && !li._option.disabled) setActive(li);
    });

    box.addEventListener('focusout', function () {
      setTimeout(function () {
        if (!box.contains(document.activeElement)) close(false);
      }, 0);
    });

    select.addEventListener('focus', function () { control.focus(); });
    if (label) {
      label.addEventListener('click', function (event) {
        event.preventDefault();
        control.focus();
      });
    }

    sync();
    return { box: box, select: select, sync: sync, close: close, isOpen: function () { return !panel.hidden; } };
  }

  function init(root) {
    Array.prototype.forEach.call((root || document).querySelectorAll('select[data-select-box-search]'), function (select) {
      if (select.classList.contains('sbs-native')) return;
      boxes.push(build(select));
    });
  }

  function refresh(root) {
    boxes.forEach(function (item) {
      if (!root || root === item.select || (root.contains && root.contains(item.select))) item.sync();
    });
  }

  document.addEventListener('change', function (event) {
    var form = event.target && event.target.form;
    refresh(form || undefined);
  });

  document.addEventListener('reset', function (event) {
    setTimeout(function () { refresh(event.target); }, 0);
  });

  document.addEventListener('pointerdown', function (event) {
    boxes.forEach(function (item) {
      if (item.isOpen() && !item.box.contains(event.target)) item.close(false);
    });
  }, true);

  document.addEventListener('cancel', function (event) {
    var inside = boxes.filter(function (item) { return event.target.contains(item.box); });
    var open = inside.filter(function (item) { return item.isOpen(); });
    if (!open.length && !(inside.length && Date.now() - escapedAt < 300)) return;
    event.preventDefault();
    open.forEach(function (item) { item.close(true); });
  }, true);

  function start() {
    init(document);
    setTimeout(function () { refresh(); }, 0);
  }

  window.selectBoxSearch = { init: init, refresh: refresh, values: values };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

class Svg {
  static render({ svg = '', icon = '', icons = {}, className = 'svg-icon' } = {}) {
    let markup = svg;

    if (!markup && icon) {
      markup = icons[icon] || icons[icon.replace(/-/g, '_')] || icons[icon.replace(/_/g, '-')] || '';
    }

    if (!markup) return null;

    const template = document.createElement('template');
    template.innerHTML = markup.trim();

    const svgEl = template.content.querySelector('svg');
    if (!svgEl) return null;

    svgEl.classList.add(className);
    svgEl.setAttribute('aria-hidden', 'true');

    return svgEl;
  }
}

window.Svg = window.Svg || Svg;
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-text-collapse-toggle]');
        if (!button) return;

        var root = button.closest('[data-text-collapse]');
        if (!root) return;

        var short = root.querySelector('.text-collapse-short');
        var full = root.querySelector('.text-collapse-full');
        var open = button.getAttribute('aria-expanded') !== 'true';

        if (short) short.hidden = open;
        if (full) full.hidden = !open;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        var label = button.querySelector('.text-collapse-label') || button;
        label.textContent = button.getAttribute(open ? 'data-label-less' : 'data-label-more') || (open ? 'Show Less' : 'Show More');

        if (!open && root.getBoundingClientRect().top < 0) {
            root.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }
    });
})();

(function () {
  var root = document.documentElement;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var supported = typeof Element !== 'undefined' && 'animate' in Element.prototype && 'IntersectionObserver' in window;

  if (reduce || !supported) return;
  root.classList.add('tx-js');

  var DEFAULTS = {
    duration: 700,
    delay: 0,
    easing: 'cubic-bezier(0.2, 0.7, 0.2, 1)',
    distance: '40px',
    angle: '12deg',
    blur: '10px',
    threshold: 0.15
  };

  function ms(value, fallback) {
    if (value === null || value === undefined || value === '') return fallback;
    var s = String(value).trim();
    var n = parseFloat(s);
    if (isNaN(n)) return fallback;
    return /ms$/.test(s) ? n : (/s$/.test(s) ? n * 1000 : n);
  }

  function negate(length) {
    var s = String(length).trim();
    return s.charAt(0) === '-' ? s.slice(1) : '-' + s;
  }

  function fromFrame(effects, opts) {
    var frame = {};
    var x = '0px';
    var y = '0px';
    var moved = false;

    effects.forEach(function (effect) {
      switch (effect) {
        case 'fade': frame.opacity = 0; break;
        case 'slide-left': x = negate(opts.distance); moved = true; break;
        case 'slide-right': x = opts.distance; moved = true; break;
        case 'slide-top': y = negate(opts.distance); moved = true; break;
        case 'slide-bottom': y = opts.distance; moved = true; break;
        case 'rotate':
        case 'rotate-left': frame.rotate = negate(opts.angle); break;
        case 'rotate-right': frame.rotate = opts.angle; break;
        case 'zoom':
        case 'zoom-in': frame.scale = String(opts.scale !== null ? opts.scale : 0.85); break;
        case 'zoom-out': frame.scale = String(opts.scale !== null ? opts.scale : 1.15); break;
        case 'blur': frame.filter = 'blur(' + opts.blur + ')'; break;
      }
    });
    if (moved) frame.translate = x + ' ' + y;
    return frame;
  }

  function options(el) {
    var d = el.dataset;
    return read(function (key) { return d['transition' + key.charAt(0).toUpperCase() + key.slice(1)]; }, null);
  }

  function read(get, base) {
    base = base || {};
    var pick = function (key, fallback) {
      var v = get(key);
      if (v !== undefined && v !== null && v !== '') return v;
      return base[key] !== undefined && base[key] !== null ? base[key] : fallback;
    };
    var scale = parseFloat(pick('scale', null));
    var threshold = parseFloat(pick('threshold', DEFAULTS.threshold));
    return {
      duration: Math.max(0, ms(pick('duration', DEFAULTS.duration), DEFAULTS.duration)),
      delay: Math.max(0, ms(pick('delay', DEFAULTS.delay), DEFAULTS.delay)),
      easing: String(pick('easing', DEFAULTS.easing)),
      distance: String(pick('distance', DEFAULTS.distance)),
      angle: String(pick('angle', DEFAULTS.angle)),
      blur: String(pick('blur', DEFAULTS.blur)),
      scale: isNaN(scale) ? null : scale,
      threshold: isNaN(threshold) ? DEFAULTS.threshold : Math.min(1, Math.max(0, threshold)),
      once: String(pick('once', 'true')) !== 'false',
      stagger: Math.max(0, ms(get('stagger'), 0)),
      target: String(get('target') || '').trim(),
      each: String(get('each') || '') === 'true'
    };
  }

  var sliding = 0;
  function trackSliding(animation) {
    if (!animation.txSideways || animation.txTracked) return;
    animation.txTracked = true;
    sliding++;
    root.classList.add('tx-sliding');
    var done = function () {
      animation.txTracked = false;
      sliding = Math.max(0, sliding - 1);
      if (!sliding) root.classList.remove('tx-sliding');
    };
    animation.finished.then(done, done);
  }

  function prepare(el, effects, opts, extraDelay) {
    var frame = fromFrame(effects, opts);
    if (!Object.keys(frame).length) return null;
    var animation;
    try {
      animation = el.animate([frame, {}], {
        duration: opts.duration,
        delay: opts.delay + (extraDelay || 0),
        easing: opts.easing,
        fill: 'backwards'
      });
    } catch (e) {
      return null;
    }
    animation.pause();
    animation.currentTime = 0;
    animation.txSideways = effects.indexOf('slide-left') !== -1 || effects.indexOf('slide-right') !== -1;
    trackSliding(animation);
    return animation;
  }

  var MAX_STAGGER_STEPS = 12;

  function rendered(el) {
    return el.getClientRects().length > 0;
  }

  var entries = new Map();
  var batch = new Map();
  var observers = {};

  function observerFor(threshold) {
    if (observers[threshold]) return observers[threshold];
    observers[threshold] = new IntersectionObserver(function (list, observer) {
      batch.clear();
      list.forEach(function (entry) {
        var item = entries.get(entry.target);
        if (!item) return;
        if (entry.isIntersecting) {
          playItem(entry.target, item, observer);
          return;
        }
        if (!item.opts.once && !item.playing) return;
        if (!item.opts.once) {
          item.playing = false;
          item.animations.forEach(function (a) {
            a.pause();
            a.currentTime = 0;
            trackSliding(a);
          });
        }
      });
    }, { threshold: 0, rootMargin: '0px 0px -' + Math.round(threshold * 100) + '% 0px' });
    return observers[threshold];
  }

  function playItem(target, item, observer) {
    if (item.playing) return;
    item.playing = true;
    if (item.batch) {
      var n = batch.get(item.batch) || 0;
      batch.set(item.batch, n + 1);
      var step = Math.min(n, MAX_STAGGER_STEPS);
      item.animations.forEach(function (a) { a.effect.updateTiming({ delay: item.opts.delay + step * item.opts.stagger }); });
    }
    item.animations.forEach(function (a) { a.play(); });
    if (item.opts.once) {
      observer.unobserve(target);
      entries.delete(target);
    }
  }

  function setup(el) {
    if (el.classList.contains('tx-ready')) return;
    var auto = el.dataset.transitionAuto === 'true';
    if (auto && el.closest('header, footer') && !el.closest('main')) {
      el.classList.add('tx-ready');
      return;
    }
    var nested = auto && el.parentElement && el.parentElement.closest('[data-transition]:not([data-transition="none"])');
    if (!nested) setupOwn(el);
    setupParts(el);
    el.classList.add('tx-ready');
  }

  function setupOwn(el) {
    var effects = String(el.dataset.transition || '').toLowerCase().split(/\s+/).filter(Boolean);
    var opts = options(el);
    var animations = [];

    var targets = null;
    if (opts.target === 'children') {
      targets = Array.prototype.slice.call(el.children);
    } else if (opts.target) {
      try { targets = Array.prototype.slice.call(el.querySelectorAll(opts.target)); } catch (e) { targets = []; }
    }

    if (targets && opts.each) {
      targets.forEach(function (target) {
        var a = prepare(target, effects, opts, 0);
        if (!a) return;
        var observer = observerFor(opts.threshold);
        entries.set(target, { animations: [a], opts: opts, batch: el, observer: observer });
        observer.observe(target);
      });
      return;
    }

    if (targets) {
      targets.forEach(function (target, i) {
        var a = prepare(target, effects, opts, i * opts.stagger);
        if (a) animations.push(a);
      });
    } else {
      var a = prepare(el, effects, opts, 0);
      if (a) animations.push(a);
    }

    if (!animations.length) return;
    var observer = observerFor(opts.threshold);
    entries.set(el, { animations: animations, opts: opts, observer: observer });
    observer.observe(el);
  }

  function setupParts(el) {
    var raw = el.dataset.transitionParts;
    if (!raw) return;
    var parts;
    try { parts = JSON.parse(raw); } catch (e) { return; }
    if (!Array.isArray(parts)) return;
    var base = options(el);
    base.delay = 0;

    parts.forEach(function (part) {
      if (!part || typeof part !== 'object' || !part.target || !part.effect) return;
      var effects = String(part.effect).toLowerCase().split(/\s+/).filter(Boolean);
      var opts = read(function (key) { return part[key]; }, base);
      var targets;
      try { targets = el.querySelectorAll(part.target); } catch (e) { return; }
      var group = {};
      Array.prototype.forEach.call(targets, function (target) {
        if (target.txPart || entries.has(target) || target.hasAttribute('data-transition')) return;
        var a = prepare(target, effects, opts, 0);
        if (!a) return;
        target.txPart = true;
        var observer = observerFor(opts.threshold);
        entries.set(target, { animations: [a], opts: opts, batch: group, observer: observer });
        observer.observe(target);
      });
    });
  }

  var catchUpTimer = 0;
  function catchUp() {
    catchUpTimer = 0;
    if (!entries.size) return;
    var vh = window.innerHeight;
    var atEnd = vh + window.scrollY >= document.documentElement.scrollHeight - 2;
    batch.clear();
    entries.forEach(function (item, target) {
      if (!rendered(target)) return;
      var r = target.getBoundingClientRect();
      var line = vh * (1 - item.opts.threshold);
      if (r.top < line || (atEnd && r.top < vh && r.bottom > 0)) playItem(target, item, item.observer);
    });
  }
  window.addEventListener('scroll', function () {
    if (!entries.size) return;
    clearTimeout(catchUpTimer);
    catchUpTimer = setTimeout(catchUp, 120);
  }, { passive: true });

  function init() {
    document.querySelectorAll('[data-transition]').forEach(setup);
    catchUp();
  }

  window.transitions = { refresh: init };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

(function () {
  'use strict';

  function reducedMotion() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function init(loop) {
    if (!loop || loop.dataset.animated) return;
    if (reducedMotion()) return;

    var list = loop.querySelector('.carousel-loop-list');
    if (!list || !list.children.length) return;

    Array.prototype.slice.call(list.children).forEach(function (item) {
      var copy = item.cloneNode(true);
      copy.setAttribute('aria-hidden', 'true');
      copy.removeAttribute('id');
      copy.querySelectorAll('[id]').forEach(function (el) { el.removeAttribute('id'); });
      copy.querySelectorAll('a, button, input, [tabindex]').forEach(function (el) {
        el.setAttribute('tabindex', '-1');
      });
      list.appendChild(copy);
    });

    loop.dataset.animated = 'true';
  }

  function start() {
    document.querySelectorAll('[data-carousel-loop]').forEach(init);
  }

  window.CarouselLoop = { init: init };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

(function () {
  'use strict';

  var host = window.location.hostname;
  var isFile = window.location.protocol === 'file:';
  var isLocal = isFile
    || /^(localhost|127\.0\.0\.1|\[?::1\]?)$/.test(host)
    || /\.(local|localhost|test)$/.test(host);
  if (!isLocal) return;

  function fix(link) {
    var raw = link.getAttribute('href');
    if (!raw || link.hasAttribute('data-no-html')) return;
    raw = raw.trim();
    if (/^(#|\?|mailto:|tel:|javascript:|data:)/i.test(raw)) return;

    var url;
    try { url = new URL(raw, window.location.href); } catch (e) { return; }
    if (isFile ? url.protocol !== 'file:' : url.origin !== window.location.origin) return;

    var path = url.pathname;
    if (path === '' || path.slice(-1) === '/' || path.indexOf('/dev/platform/') !== -1) return;
    var last = path.slice(path.lastIndexOf('/') + 1);
    if (last === '' || last.indexOf('.') !== -1) return;

    var cut = raw.search(/[?#]/);
    var base = cut === -1 ? raw : raw.slice(0, cut);
    var rest = cut === -1 ? '' : raw.slice(cut);
    if (base === '' || base.slice(-1) === '/') return;

    link.setAttribute('href', base + '.html' + rest);
  }

  function scan(root) {
    if (!root || root.nodeType !== 1) return;
    if (root.matches && root.matches('a[href], area[href]')) fix(root);
    var links = root.querySelectorAll('a[href], area[href]');
    for (var i = 0; i < links.length; i++) fix(links[i]);
  }

  function init() {
    scan(document.body);
    if (!window.MutationObserver) return;
    new MutationObserver(function (records) {
      records.forEach(function (record) {
        if (record.type === 'attributes') fix(record.target);
        else for (var i = 0; i < record.addedNodes.length; i++) scan(record.addedNodes[i]);
      });
    }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['href'] });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
  'use strict';

  var config = null;
  var loaded = false;

  function readConfig() {
    var el = document.querySelector('script[data-seo-ga]');
    if (!el) return null;
    try {
      var cfg = JSON.parse(el.textContent || '{}');
      return cfg && cfg.id ? cfg : null;
    } catch (e) {
      return null;
    }
  }

  function isLocal() {
    var host = window.location.hostname;
    return /^(localhost|127\.0\.0\.1|\[?::1\]?)$/.test(host) || /\.(local|localhost|test)$/.test(host);
  }

  function allowed() {
    if (!config.require_consent) return true;
    var consent = window.cookieNotice ? window.cookieNotice.consent('analytics') : null;
    return consent === null ? config.default_value === true : consent === true;
  }

  function isTagManager() {
    return /^GTM-/i.test(config.id);
  }

  function load() {
    window['ga-disable-' + config.id] = false;
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
    if (loaded) {
      window.gtag('consent', 'update', { analytics_storage: 'granted' });
      return;
    }
    loaded = true;

    var script = document.createElement('script');
    script.async = true;
    if (isTagManager()) {
      window.gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
      window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
      script.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(config.id);
    } else {
      window.gtag('js', new Date());
      window.gtag('config', config.id, { anonymize_ip: true });
      script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.id);
    }
    document.head.appendChild(script);
  }

  function disable() {
    window['ga-disable-' + config.id] = true;
    if (window.gtag) window.gtag('consent', 'update', { analytics_storage: 'denied' });
    var host = window.location.hostname;
    var parts = host.split('.');
    var domains = [''];
    for (var i = 0; i < parts.length - 1; i++) domains.push('; domain=.' + parts.slice(i).join('.'));
    document.cookie.split(';').forEach(function (pair) {
      var name = pair.split('=')[0].trim();
      if (name !== '_ga' && name.indexOf('_ga_') !== 0 && name !== '_gid' && name !== '_gat') return;
      domains.forEach(function (domain) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + domain;
      });
    });
  }

  function init() {
    config = readConfig();
    if (!config) return;
    if (isLocal() && !config.track_localhost) return;

    if (allowed()) load();

    document.addEventListener('cookie-consent:change', function (event) {
      if (!config.require_consent) return;
      if (event.detail && event.detail.analytics) load();
      else disable();
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
    'use strict';

    var ICONS = {
        prev: '<svg viewBox="0 0 320 512" aria-hidden="true"><path fill="currentColor" d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/></svg>',
        next: '<svg viewBox="0 0 320 512" aria-hidden="true"><path fill="currentColor" d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"/></svg>'
    };

    function option(root, opts, name, fallback) {
        if (opts[name] !== undefined) return !!opts[name];
        var attr = root.getAttribute('data-slider-' + name);
        return attr === null ? fallback : attr !== 'false';
    }

    function ui(tag, className, parent) {
        var el = document.createElement(tag);
        el.className = className;
        el.setAttribute('data-utility-slider-ui', '');
        if (parent) parent.appendChild(el);
        return el;
    }

    function pauseVideos(slide) {
        Array.prototype.forEach.call(slide.querySelectorAll('video'), function (video) {
            if (!video.paused) video.pause();
        });
    }

    function init(root, opts) {
        opts = opts || {};
        if (!root) return null;
        if (root.utilitySlider) return root.utilitySlider;

        var viewport = opts.viewport || root.querySelector('.utility-slider-viewport');
        var slides = viewport ? viewport.querySelectorAll('.utility-slider-slide') : [];
        var count = slides.length;
        if (!viewport || !count) return null;

        var current = -1;
        var target = null;
        var counter = null;
        var dots = [];

        function indexFromScroll() {
            var width = viewport.clientWidth || 1;
            return Math.max(0, Math.min(count - 1, Math.round(viewport.scrollLeft / width)));
        }

        function mark(index) {
            if (index === current) return;
            current = index;
            for (var i = 0; i < count; i++) {
                slides[i].classList.toggle('active', i === index);
                if (i !== index) pauseVideos(slides[i]);
            }
            for (var d = 0; d < dots.length; d++) dots[d].classList.toggle('active', d === index);
            if (counter) counter.textContent = (index + 1) + ' / ' + count;
            root.dispatchEvent(new CustomEvent('utility-slider:change', { detail: { index: index, count: count } }));
        }

        function goTo(index, instant) {
            if (index < 0) index = count - 1;
            if (index >= count) index = 0;
            target = instant ? null : index;
            if (instant) {
                viewport.style.scrollBehavior = 'auto';
                viewport.scrollTo({ left: slides[index].offsetLeft, behavior: 'instant' });
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () { viewport.style.scrollBehavior = ''; });
                });
            } else {
                viewport.scrollTo({ left: slides[index].offsetLeft, behavior: 'smooth' });
            }
            mark(index);
        }

        function step(n) {
            goTo((target !== null ? target : current) + n);
        }

        if (count > 1 && option(root, opts, 'arrows', true)) {
            var prev = ui('button', 'utility-slider-arrow utility-slider-prev', root);
            prev.type = 'button';
            prev.setAttribute('aria-label', 'Previous');
            prev.innerHTML = ICONS.prev;
            prev.addEventListener('click', function (e) { e.stopPropagation(); step(-1); });

            var next = ui('button', 'utility-slider-arrow utility-slider-next', root);
            next.type = 'button';
            next.setAttribute('aria-label', 'Next');
            next.innerHTML = ICONS.next;
            next.addEventListener('click', function (e) { e.stopPropagation(); step(1); });
        }

        if (count > 1 && option(root, opts, 'counter', true)) {
            counter = ui('span', 'utility-slider-counter', root);
        }

        if (count > 1 && option(root, opts, 'dots', false)) {
            var dotsEl = ui('div', 'utility-slider-dots', root);
            root.classList.add('utility-slider-has-dots');
            for (var i = 0; i < count; i++) {
                var dot = ui('button', 'utility-slider-dot', dotsEl);
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                dot.addEventListener('click', goTo.bind(null, i, false));
                dots.push(dot);
            }
        }

        function onKey(event) {
            if (event.target && event.target.closest && event.target.closest('.video-container')) return;
            if (event.key === 'ArrowLeft') { event.preventDefault(); step(-1); }
            if (event.key === 'ArrowRight') { event.preventDefault(); step(1); }
        }
        viewport.addEventListener('keydown', onKey);

        var ticking = false;
        function onScroll() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () {
                ticking = false;
                var index = indexFromScroll();
                if (target !== null) {
                    if (index !== target || Math.abs(viewport.scrollLeft - slides[target].offsetLeft) > 2) return;
                    target = null;
                }
                mark(index);
            });
        }
        viewport.addEventListener('scroll', onScroll, { passive: true });

        var api = {
            goTo: function (i) { goTo(i); },
            next: function () { step(1); },
            prev: function () { step(-1); },
            index: function () { return current; },
            count: count,
            destroy: function () {
                viewport.removeEventListener('keydown', onKey);
                viewport.removeEventListener('scroll', onScroll);
                Array.prototype.forEach.call(root.querySelectorAll(':scope > [data-utility-slider-ui]'), function (el) { el.remove(); });
                root.classList.remove('utility-slider-has-dots', 'utility-slider-ready');
                root.removeAttribute('data-initialized');
                delete root.utilitySlider;
            }
        };

        goTo(Math.max(0, Math.min(count - 1, parseInt(opts.start, 10) || 0)), true);
        root.classList.add('utility-slider-ready');
        root.setAttribute('data-initialized', '');
        root.utilitySlider = api;
        return api;
    }

    function initAll(scope) {
        var roots = (scope || document).querySelectorAll('[data-utility-slider]:not([data-initialized])');
        Array.prototype.forEach.call(roots, function (root) { init(root); });
    }

    window.UtilitySlider = { init: init, initAll: initAll };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }
})();

class VideoControls {
  constructor(videoContainer) {
    this.container = videoContainer;
    this.init();
  }

  static initAll() {
    const containers = document.querySelectorAll(
      ".video-container:not([data-video-initialized])",
    );

    containers.forEach((container) => {
      container.setAttribute("data-video-initialized", "true");
      new VideoControls(container);
    });
  }

  init() {
    const playPauseBtn = this.container.querySelector(".play-pause-btn");
    const fullScreenBtn = this.container.querySelector(".full-screen-btn");
    const muteBtn = this.container.querySelector(".mute-btn");
    const speedBtn = this.container.querySelector(".speed-btn");
    const currentTimeElem = this.container.querySelector(".current-time");
    const totalTimeElem = this.container.querySelector(".total-time");
    const volumeSlider = this.container.querySelector(".volume-slider");
    const timelineContainer = this.container.querySelector(
      ".timeline-container",
    );
    const video = this.container.querySelector("video");
    const controlsContainer = this.container.querySelector(
      ".video-controls-container",
    );
    const showPlay = this.container.querySelector(".show-play");
    const showPause = this.container.querySelector(".show-pause");

    if (!video) return;
    const originalSrc = video.getAttribute("src") || video.src;
    if (originalSrc && !originalSrc.startsWith("blob:")) {
      fetch(originalSrc)
        .then((response) => {
          if (!response.ok) throw new Error("Network response was not ok");
          return response.blob();
        })
        .then((blob) => {
          video.src = URL.createObjectURL(blob);
        })
        .catch((error) => {
          console.warn(
            "[VideoControls] Failed to load video as blob, falling back to direct src:",
            error,
          );
        });
    }

    if (showPause) showPause.style.display = "none";

    let isFirstPlay = true;
    let isScrubbing = false;
    let wasPaused;
    let lastVolume = 0.08;
    let playPromise = null;

    function safePlay() {
      playPromise = video.play();
      if (playPromise && typeof playPromise.catch === "function") {
        playPromise.catch(() => {});
      }
    }

    function safePause() {
      if (playPromise !== null) {
        playPromise
          .then(() => {
            video.pause();
          })
          .catch(() => {});
      } else {
        video.pause();
      }
    }

    video.volume = lastVolume;
    video.muted = false;

    if (volumeSlider) {
      volumeSlider.value = lastVolume;
    }

    const self = this;

    document.addEventListener("keydown", (e) => {
      const tagName = document.activeElement.tagName.toLowerCase();
      if (tagName === "input") return;

      const isCurrentVideoFocused =
        document.activeElement === video ||
        self.container.contains(document.activeElement);

      if (!isCurrentVideoFocused) return;

      switch (e.key.toLowerCase()) {
        case " ":
          if (tagName === "button") return;
          e.preventDefault();
        case "k":
          togglePlay();
          break;
        case "f":
          toggleFullScreenMode();
          break;
        case "m":
          toggleMute();
          break;
        case "arrowleft":
        case "j":
          skip(-5);
          break;
        case "arrowright":
        case "l":
          skip(5);
          break;
      }
    });

    video.addEventListener("play", () => {
      if (isFirstPlay && controlsContainer && showPlay && showPause) {
        controlsContainer.style.display = "block";
        showPlay.style.display = "none";
        showPause.style.display = "flex";
        isFirstPlay = false;
      }
    });

    if (timelineContainer) {
      timelineContainer.addEventListener("mousemove", handleTimelineUpdate);
      timelineContainer.addEventListener("mousedown", startScrubbing);
      timelineContainer.addEventListener("touchmove", handleTimelineUpdate, {
        passive: true,
      });
      timelineContainer.addEventListener("touchstart", startScrubbing);

      timelineContainer.addEventListener("click", (e) => {
        e.stopPropagation();
      });
    }

    document.addEventListener("mousemove", (e) => {
      if (isScrubbing) {
        handleTimelineUpdate(e);
        seekToPosition(e);
      }
    });

    document.addEventListener(
      "touchmove",
      (e) => {
        if (isScrubbing) {
          handleTimelineUpdate(e);
          seekToPosition(e);
        }
      },
      { passive: true },
    );

    document.addEventListener("mouseup", stopScrubbing);
    document.addEventListener("touchend", stopScrubbing);

    function stopScrubbing(e) {
      if (!isScrubbing) return;

      isScrubbing = false;
      self.container.classList.remove("scrubbing");

      if (window.DM_VIDEO_DEBUG) {
        console.log("[video seek] mouseup/touchend done", {
          wasPaused,
          currentTimeAfterSeek: video.currentTime,
          willAutoplay: !wasPaused,
        });
      }

      if (!wasPaused) {
        safePlay();
      }
    }
    function getTimelinePercent(e) {
      const rect = timelineContainer.getBoundingClientRect();

      let clientX = 0;
      if (e.clientX !== undefined) {
        clientX = e.clientX;
      } else if (e.touches && e.touches.length > 0) {
        clientX = e.touches[0].clientX;
      } else if (e.changedTouches && e.changedTouches.length > 0) {
        clientX = e.changedTouches[0].clientX;
      }

      if (rect.width === 0) {
        return 0;
      }

      return (
        Math.min(Math.max(0, clientX - rect.left), rect.width) / rect.width
      );
    }
    function getEffectiveDuration() {
      if (isFinite(video.duration) && video.duration > 0) {
        return video.duration;
      }

      if (video.seekable && video.seekable.length > 0) {
        const end = video.seekable.end(video.seekable.length - 1);
        if (isFinite(end) && end > 0) return end;
      }

      return NaN;
    }

    function seekToPosition(e) {
      if (!timelineContainer) return;

      const percent = getTimelinePercent(e);
      const duration = getEffectiveDuration();

      if (!isNaN(duration)) {
        video.currentTime = percent * duration;
      }

      timelineContainer.style.setProperty("--progress-position", percent);
      timelineContainer.style.setProperty("--preview-position", percent);

      if (currentTimeElem) {
        currentTimeElem.textContent = formatDuration(video.currentTime);
      }
    }
    function handleTimelineUpdate(e) {
      if (!timelineContainer) return;

      const percent = getTimelinePercent(e);

      timelineContainer.style.setProperty("--preview-position", percent);

      if (isScrubbing) {
        timelineContainer.style.setProperty("--progress-position", percent);
      }
    }

    function startScrubbing(e) {
      if (!timelineContainer) return;

      if (e.button !== undefined && e.button !== 0) {
        return;
      }

      isScrubbing = true;
      wasPaused = video.paused;

      self.container.classList.add("scrubbing");

      safePause();
      seekToPosition(e);

      e.preventDefault();
    }

    if (speedBtn) {
      speedBtn.addEventListener("click", changePlaybackSpeed);
    }

    function changePlaybackSpeed() {
      let newPlaybackRate = video.playbackRate + 0.25;

      if (newPlaybackRate > 2) {
        newPlaybackRate = 0.25;
      }

      video.playbackRate = newPlaybackRate;
      speedBtn.textContent = `${newPlaybackRate}x`;
    }

    video.addEventListener("loadeddata", () => {
      if (totalTimeElem) {
        totalTimeElem.textContent = formatDuration(getEffectiveDuration());
      }
    });

    video.addEventListener("durationchange", () => {
      if (totalTimeElem) {
        totalTimeElem.textContent = formatDuration(getEffectiveDuration());
      }
    });

    video.addEventListener("timeupdate", () => {
      if (isScrubbing) return;

      if (currentTimeElem) {
        currentTimeElem.textContent = formatDuration(video.currentTime);
      }

      const duration = getEffectiveDuration();
      const percent = isNaN(duration) ? 0 : video.currentTime / duration;

      if (timelineContainer) {
        timelineContainer.style.setProperty("--progress-position", percent);
      }
    });

    const leadingZeroFormatter = new Intl.NumberFormat(undefined, {
      minimumIntegerDigits: 2,
    });

    function formatDuration(time) {
      if (isNaN(time)) return "00:00";

      const seconds = Math.floor(time % 60);
      const minutes = Math.floor(time / 60) % 60;
      const hours = Math.floor(time / 3600);

      if (hours === 0) {
        return `${minutes}:${leadingZeroFormatter.format(seconds)}`;
      }

      return `${hours}:${leadingZeroFormatter.format(minutes)}:${leadingZeroFormatter.format(seconds)}`;
    }

    function skip(duration) {
      video.currentTime += duration;
    }

    if (muteBtn) {
      muteBtn.addEventListener("click", toggleMute);
    }

    if (volumeSlider) {
      volumeSlider.addEventListener("input", (e) => {
        video.volume = e.target.value;
        video.muted = e.target.value == 0;
        updateSliderBackground();
      });
    }

    function toggleMute() {
      video.muted = !video.muted;

      if (video.muted) {
        lastVolume = video.volume > 0 ? video.volume : lastVolume;

        video.volume = 0;

        if (volumeSlider) {
          volumeSlider.value = 0;
        }

        updateSliderBackground();
      } else {
        video.volume = lastVolume;

        if (volumeSlider) {
          volumeSlider.value = lastVolume;
        }

        updateSliderBackground();
      }
    }

    video.addEventListener("volumechange", () => {
      if (!video.muted && video.volume > 0) {
        lastVolume = video.volume;
      }

      updateSliderBackground();
    });

    function updateSliderBackground() {
      if (!volumeSlider) return;

      const percentage = (volumeSlider.value / volumeSlider.max) * 100;

      volumeSlider.style.background = `linear-gradient(to right, var(--dm-color-primary) ${percentage}%, var(--dm-color-transparent) ${percentage}%)`;

      let volumeLevel;

      if (video.muted || video.volume === 0) {
        volumeSlider.value = 0;
        volumeLevel = "muted";
      } else if (video.volume >= 0.5) {
        volumeLevel = "high";
      } else {
        volumeLevel = "low";
      }

      self.container.dataset.volumeLevel = volumeLevel;
    }

    if (fullScreenBtn) {
      fullScreenBtn.addEventListener("click", toggleFullScreenMode);
    }

    function toggleFullScreenMode() {
      if (document.fullscreenElement == null) {
        self.container.requestFullscreen();
      } else {
        document.exitFullscreen();
      }
    }

    document.addEventListener("fullscreenchange", () => {
      self.container.classList.toggle(
        "full-screen",
        document.fullscreenElement === self.container,
      );
    });

    if (playPauseBtn) {
      playPauseBtn.addEventListener("click", togglePlay);
    }

    video.addEventListener("click", togglePlay);

    if (showPlay) {
      showPlay.addEventListener("click", togglePlay);
    }

    if (showPause) {
      showPause.addEventListener("click", togglePlay);
    }

    function togglePlay() {
      if (video.paused) {
        safePlay();

        self.container.classList.remove("paused");

        const thumbnail = self.container.querySelector(".thumbnail");

        if (thumbnail) {
          thumbnail.style.display = "none";
        }
      } else {
        if (playPromise !== null) {
          playPromise
            .then(() => {
              video.pause();
              self.container.classList.add("paused");
            })
            .catch(() => {});
        } else {
          video.pause();
          self.container.classList.add("paused");
        }
      }
    }

    video.addEventListener("play", () => {
      self.container.classList.remove("paused");
    });

    video.addEventListener("pause", () => {
      self.container.classList.add("paused");
    });

    updateSliderBackground();
  }
}

window.VideoControls = window.VideoControls || VideoControls;

document.addEventListener("DOMContentLoaded", () => {
  VideoControls.initAll();
});

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

(function () {
  var SOUND_ICONS =
    '<svg class="video-background-sound-off" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M17 9l4 6M21 9l-4 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
    '<svg class="video-background-sound-on" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M16.5 8.5a5 5 0 0 1 0 7M19 6a8.5 8.5 0 0 1 0 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function num(value, fallback) {
    var n = parseFloat(value);
    return isNaN(n) ? fallback : n;
  }

  function fromAttributes(host) {
    if (host.querySelector(':scope > [data-video-background]')) return;
    var src = host.getAttribute('data-video-bg');
    if (!src) return;

    var wrap = document.createElement('div');
    wrap.className = 'video-background';
    wrap.setAttribute('data-video-background', '');
    wrap.setAttribute('aria-hidden', 'true');
    wrap.dataset.start = host.getAttribute('data-video-bg-start') || '0';
    var end = host.getAttribute('data-video-bg-end');
    var duration = num(host.getAttribute('data-video-bg-duration'), 0);
    if (duration > 0) end = String(num(wrap.dataset.start, 0) + duration);
    if (end && end !== 'auto') wrap.dataset.end = end;
    wrap.dataset.loop = host.getAttribute('data-video-bg-loop') === 'false' ? 'false' : 'true';
    wrap.dataset.audio = host.getAttribute('data-video-bg-audio') === 'true' ? 'true' : 'false';
    var overlay = Math.max(0, Math.min(1, num(host.getAttribute('data-video-bg-overlay'), 0.55)));
    wrap.style.setProperty('--video-bg-overlay', String(overlay));

    var video = document.createElement('video');
    video.className = 'video-background-video';
    video.muted = true;
    video.setAttribute('muted', '');
    video.setAttribute('playsinline', '');
    video.preload = 'metadata';
    var poster = host.getAttribute('data-video-bg-poster');
    if (poster) {
      video.poster = poster;
      wrap.style.setProperty('--video-bg-poster', 'url("' + poster.replace(/"/g, '%22') + '")');
    }
    video.src = src;
    wrap.appendChild(video);

    if (overlay > 0) {
      var tint = document.createElement('span');
      tint.className = 'video-background-overlay';
      wrap.appendChild(tint);
    }

    host.classList.add('has-video-background');
    host.insertBefore(wrap, host.firstChild);

    if (wrap.dataset.audio === 'true') {
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'video-background-sound';
      button.setAttribute('data-video-background-sound', '');
      button.setAttribute('aria-pressed', 'false');
      button.setAttribute('aria-label', 'Turn sound on');
      button.title = 'Sound';
      button.innerHTML = SOUND_ICONS;
      wrap.insertAdjacentElement('afterend', button);
    }
  }

  function setup(wrap) {
    if (wrap.dataset.videoBackgroundReady) return;
    wrap.dataset.videoBackgroundReady = 'true';

    var video = wrap.querySelector('video');
    if (!video) return;
    var start = num(wrap.dataset.start, 0);
    var end = wrap.dataset.end ? num(wrap.dataset.end, 0) : 0;
    var loop = wrap.dataset.loop !== 'false';
    var finished = false;
    var visible = true;

    video.muted = true;
    if (loop && !end && !start) video.loop = true;
    if (start > 0) video.preload = 'auto';

    function inRange(ranges, t) {
      for (var i = 0; i < ranges.length; i++) if (t >= ranges.start(i) && t <= ranges.end(i)) return true;
      return false;
    }
    function seekStart() {
      if (start <= 0) return true;
      if (inRange(video.seekable, start)) {
        video.currentTime = start;
        return true;
      }
      return false;
    }
    function restart() {
      if (!seekStart()) {
        wrap.classList.remove('is-playing');
        video.currentTime = 0;
      }
    }
    function markPlaying() {
      wrap.classList.toggle('is-playing', video.currentTime + 0.25 >= start);
    }

    function play() {
      if (reduceMotion || finished || !visible) return;
      var p = video.play();
      if (p && p.catch) p.catch(function () {});
    }

    video.addEventListener('loadedmetadata', function () {
      if (start >= video.duration) start = 0;
      seekStart();
      play();
    });

    video.addEventListener('timeupdate', function () {
      if (video.currentTime + 0.25 < start) seekStart();
      markPlaying();
      var stop = end && end < video.duration ? end : 0;
      if (!stop) return;
      if (video.currentTime >= stop) {
        if (loop) {
          restart();
        } else {
          video.pause();
          finished = true;
        }
      }
    });

    video.addEventListener('ended', function () {
      if (loop) {
        restart();
        play();
      } else {
        finished = true;
      }
    });

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          visible = entry.isIntersecting;
          if (visible) play();
          else video.pause();
        });
      }, { threshold: 0.05 }).observe(wrap);
    }

    var button = wrap.nextElementSibling;
    if (button && button.hasAttribute('data-video-background-sound')) {
      button.addEventListener('click', function () {
        video.muted = !video.muted;
        var on = !video.muted;
        button.setAttribute('aria-pressed', on ? 'true' : 'false');
        button.setAttribute('aria-label', on ? 'Turn sound off' : 'Turn sound on');
        button.classList.toggle('is-on', on);
        if (on) {
          finished = false;
          play();
        }
      });
    }

    if (video.readyState >= 1) {
      seekStart();
      play();
    }
  }

  function start() {
    document.querySelectorAll('[data-video-bg]').forEach(fromAttributes);
    document.querySelectorAll('[data-video-background]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

(function () {
  'use strict';

  function setup(root) {
    var screen = root.querySelector('[data-video-screen]');
    var items = Array.prototype.slice.call(root.querySelectorAll('[data-video-index]'));
    var title = root.querySelector('[data-video-title]');
    var meta = root.querySelector('[data-video-meta]');
    var detailsBtn = root.querySelector('[data-video-details-open]');
    var list = root.querySelector('[data-video-list]');
    if (!screen || !items.length) return;

    function updateFade() {
      if (!list) return;
      list.classList.toggle('mask-fade-bottom', list.scrollHeight - list.scrollTop - list.clientHeight > 2);
    }
    if (list) {
      list.addEventListener('scroll', updateFade, { passive: true });
      if ('ResizeObserver' in window) new ResizeObserver(updateFade).observe(list);
      else window.addEventListener('resize', updateFade);
      updateFade();
    }

    var active = 0;

    function template(kind, index) {
      return root.querySelector('template[data-video-' + kind + '="' + index + '"]');
    }

    function select(index, autoplay) {
      if (index === active) return;
      var tpl = template('player', index);
      if (!tpl) return;
      active = index;

      screen.querySelectorAll('video').forEach(function (video) { video.pause(); });
      screen.innerHTML = tpl.innerHTML;
      if (window.VideoControls && typeof window.VideoControls.initAll === 'function') window.VideoControls.initAll();

      items.forEach(function (item, i) {
        if (i === index) item.setAttribute('aria-current', 'true');
        else item.removeAttribute('aria-current');
      });

      var item = items[index];
      if (title) title.textContent = item.getAttribute('data-title') || '';
      if (meta) meta.textContent = item.getAttribute('data-meta') || '';
      if (detailsBtn) detailsBtn.hidden = item.getAttribute('data-has-details') !== 'true';

      if (autoplay) {
        var video = screen.querySelector('video');
        var play = screen.querySelector('.show-play');
        if (play) play.click();
        else if (video) video.play().catch(function () {});
      }
    }

    items.forEach(function (item, i) {
      item.addEventListener('click', function () { select(i, false); });
    });

    if (detailsBtn) {
      detailsBtn.addEventListener('click', function () {
        var tpl = template('details', active);
        if (!tpl || !window.Popup || typeof window.Popup.open !== 'function') return;
        screen.querySelectorAll('video').forEach(function (video) { video.pause(); });
        window.Popup.open(tpl.innerHTML, { source: root });
      });
    }
  }

  function init() {
    document.querySelectorAll('[data-video-playlist]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
  'use strict';

  var CURRENT = ['current-date', 'curent-date', 'current_date', 'today', 'now'];
  var json = {};
  var icons = {};

  function lang() {
    return (document.documentElement.getAttribute('lang') || '').toLowerCase().split('-')[0];
  }

  function fetchJson(url) {
    if (!json[url]) {
      json[url] = fetch(url, { cache: 'no-cache' }).then(function (r) {
        if (!r.ok) throw new Error(r.status);
        return r.json();
      });
      json[url].catch(function () { delete json[url]; });
    }
    return json[url];
  }

  function localized(wrap, file, current, pick) {
    var src = wrap.getAttribute('data-src') || '';
    var fallback = wrap.getAttribute('data-default-lang') || 'en';
    var fromDefault = function () {
      return fetchJson(src + fallback + '/' + file).then(pick);
    };
    if (current === fallback) return fromDefault();
    return fetchJson(src + current + '/' + file).then(pick).then(function (value) {
      return value == null ? fromDefault() : value;
    }, fromDefault);
  }

  function load(wrap) {
    var current = lang() || wrap.getAttribute('data-default-lang') || 'en';
    var same = function (v) { return v; };
    var config = localized(wrap, wrap.getAttribute('data-file') || '', current, same);
    var id = wrap.getAttribute('data-project');
    var project = !id ? Promise.resolve(null) : localized(wrap, wrap.getAttribute('data-project-file') || '', current, function (list) {
      var found = (Array.isArray(list) ? list : []).filter(function (p) { return p && p._id === id; })[0];
      return found || null;
    }).catch(function () { return null; });
    return Promise.all([config, project]).then(function (r) {
      return { config: r[0] || {}, project: r[1], lang: current };
    });
  }

  function at(obj, path) {
    return String(path || '').split('.').reduce(function (o, k) { return o && k ? o[k] : o; }, obj);
  }

  function parse(value) {
    var v = String(value || '').trim();
    if (CURRENT.indexOf(v.toLowerCase()) !== -1) return new Date(document.lastModified || Date.now());
    var m = v.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (m) return new Date(+m[1], m[2] - 1, +m[3]);
    m = v.match(/^(\d{1,2})[-./](\d{1,2})[-./](\d{4})$/);
    if (m) return new Date(+m[3], m[2] - 1, +m[1]);
    return null;
  }

  function pad(n) {
    return (n < 10 ? '0' : '') + n;
  }

  function month(date, locale, style) {
    try {
      return new Intl.DateTimeFormat(locale, { month: style }).format(date);
    } catch (e) {
      return String(date.getMonth() + 1);
    }
  }

  function format(date, pattern, locale) {
    var tokens = {
      Y: String(date.getFullYear()),
      y: String(date.getFullYear()).slice(-2),
      d: pad(date.getDate()),
      j: String(date.getDate()),
      m: pad(date.getMonth() + 1),
      n: String(date.getMonth() + 1),
      F: month(date, locale, 'long'),
      M: month(date, locale, 'short')
    };
    return String(pattern || 'M j, Y').replace(/[YydjmnFM]/g, function (t) { return tokens[t]; });
  }

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null) node.textContent = text;
    return node;
  }

  function dateList(result) {
    var cfg = result.config;
    var projectDates = result.project && result.project.data ? result.project.data.date || {} : {};
    var list = el('ul', 'website-info-dates');
    (cfg.dates || []).forEach(function (entry) {
      if (!entry) return;
      var raw = entry.field ? projectDates[entry.field] : entry.date;
      if (!raw) return;
      var date = parse(raw);
      var li = el('li');
      if (entry.text) {
        li.appendChild(el('span', 'website-info-label', entry.text));
        li.appendChild(document.createTextNode(' '));
      }
      var value;
      if (date) {
        value = el('time', 'website-info-date', format(date, cfg.date_format, result.lang));
        value.setAttribute('datetime', date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()));
      } else {
        var progress = /in progress/i.test(raw);
        value = el('span', 'website-info-date' + (progress ? ' is-in-progress' : ''), progress && cfg.in_progress_text ? cfg.in_progress_text : raw);
      }
      li.appendChild(value);
      list.appendChild(li);
    });
    return list.children.length ? list : null;
  }

  function icon(wrap, name) {
    var url = (wrap.getAttribute('data-svg') || '') + encodeURIComponent(name) + '.svg';
    if (!icons[url]) {
      icons[url] = fetch(url).then(function (r) { return r.ok ? r.text() : ''; }).catch(function () { return ''; });
    }
    return icons[url];
  }

  function chip(wrap, item, display) {
    var text = String(item.text || item.svg || '').trim();
    var li = el('li', 'website-info-item');
    li.title = text;
    var showIcon = display !== 'text' && item.svg;
    if (!showIcon || display === 'both') li.appendChild(el('span', 'website-info-item-text', text));
    if (!showIcon) return li;
    if (display === 'icon') li.setAttribute('aria-label', text);
    li.classList.add('has-icon');
    icon(wrap, item.svg).then(function (markup) {
      var t = document.createElement('template');
      t.innerHTML = markup.trim();
      var svg = t.content.querySelector('svg');
      if (svg) {
        svg.setAttribute('class', 'website-info-item-icon');
        svg.setAttribute('aria-hidden', 'true');
        li.insertBefore(svg, li.firstChild);
      } else if (!li.querySelector('.website-info-item-text')) {
        li.appendChild(el('span', 'website-info-item-text', text));
      }
    });
    return li;
  }

  function groups(wrap, result) {
    var data = result.project && result.project.data;
    if (!data) return null;
    var display = result.config.items_display || 'both';
    var box = el('div', 'website-info-groups');
    (result.config.groups || []).forEach(function (group) {
      var items = at(data, group && group.field);
      if (!Array.isArray(items) || !items.length) return;
      var section = el('div', 'website-info-group');
      if (group.text) section.appendChild(el('span', 'website-info-group-title', group.text));
      var list = el('ul', 'website-info-items is-' + display);
      items.forEach(function (item) {
        if (item && (item.text || item.svg)) list.appendChild(chip(wrap, item, display));
      });
      section.appendChild(list);
      box.appendChild(section);
    });
    return box.children.length ? box : null;
  }

  function build(wrap, result) {
    var frag = document.createDocumentFragment();
    var seo = result.project && result.project.data && result.project.data.seo;
    if (seo && seo.title && result.config.show_title !== false) frag.appendChild(el('span', 'website-info-title', seo.title));
    var dates = dateList(result);
    if (dates) frag.appendChild(dates);
    var g = groups(wrap, result);
    if (g) frag.appendChild(g);
    return frag;
  }

  function popover(wrap) {
    return wrap.querySelector('.website-info-dates-popover');
  }

  function close(wrap, focus) {
    var pop = popover(wrap);
    var button = wrap.querySelector('.website-info-dates-button');
    if (!pop || pop.hidden) return;
    pop.hidden = true;
    wrap.classList.remove('is-open');
    if (button) {
      button.setAttribute('aria-expanded', 'false');
      if (focus) button.focus();
    }
  }

  function open(wrap) {
    var pop = popover(wrap);
    var button = wrap.querySelector('.website-info-dates-button');
    if (!pop) return;
    document.querySelectorAll('[data-website-info-dates].is-open').forEach(function (other) {
      if (other !== wrap) close(other);
    });
    pop.hidden = false;
    wrap.classList.add('is-open');
    if (button) button.setAttribute('aria-expanded', 'true');

    var wanted = lang();
    if (pop.getAttribute('data-lang') === wanted && pop.firstChild) return;
    wrap.classList.add('is-loading');
    load(wrap).then(function (result) {
      pop.replaceChildren(build(wrap, result));
      pop.setAttribute('data-lang', wanted);
    }).catch(function () {
      pop.textContent = '—';
    }).then(function () {
      wrap.classList.remove('is-loading');
    });
  }

  function init() {
    document.addEventListener('click', function (event) {
      var button = event.target.closest && event.target.closest('.website-info-dates-button');
      if (button) {
        var wrap = button.closest('[data-website-info-dates]');
        if (wrap.classList.contains('is-open')) close(wrap);
        else open(wrap);
        return;
      }
      document.querySelectorAll('[data-website-info-dates].is-open').forEach(function (wrap) {
        if (!wrap.contains(event.target)) close(wrap);
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      document.querySelectorAll('[data-website-info-dates].is-open').forEach(function (wrap) { close(wrap, true); });
    });

    document.addEventListener('dm:i18n-done', function () {
      document.querySelectorAll('[data-website-info-dates].is-open').forEach(open);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();


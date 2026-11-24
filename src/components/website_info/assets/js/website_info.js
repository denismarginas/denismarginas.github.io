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

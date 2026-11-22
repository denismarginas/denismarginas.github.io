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

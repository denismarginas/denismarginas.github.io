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

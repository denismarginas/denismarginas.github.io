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
    return /^(localhost|127\.0\.0\.1|\[?::1\]?)$/.test(window.location.hostname);
  }

  function allowed() {
    if (!config.require_consent) return true;
    var consent = window.cookieNotice ? window.cookieNotice.consent('analytics') : null;
    return consent === null ? config.default_value === true : consent === true;
  }

  function load() {
    window['ga-disable-' + config.id] = false;
    if (loaded) return;
    loaded = true;

    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', config.id, { anonymize_ip: true });

    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.id);
    document.head.appendChild(script);
  }

  function disable() {
    window['ga-disable-' + config.id] = true;
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

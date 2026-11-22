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

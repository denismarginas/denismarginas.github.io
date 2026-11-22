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

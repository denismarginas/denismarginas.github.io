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

(function () {
  function start() {
    document.querySelectorAll('[data-hero-home-scroll]').forEach(function (button) {
      button.addEventListener('click', function () {
        var hero = button.closest('.hero-home');
        if (!hero) return;
        var top = hero.getBoundingClientRect().bottom + window.pageYOffset;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: top, behavior: reduce ? 'auto' : 'smooth' });
      });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

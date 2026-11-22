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

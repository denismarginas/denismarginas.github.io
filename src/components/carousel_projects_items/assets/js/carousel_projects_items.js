(function () {
  function init(carousel) {
    if (carousel.dataset.animated) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var list = carousel.querySelector('.carousel-list');
    if (!list || !list.children.length) return;

    Array.prototype.slice.call(list.children).forEach(function (item) {
      var copy = item.cloneNode(true);
      copy.setAttribute('aria-hidden', 'true');
      copy.querySelectorAll('a, button, [tabindex]').forEach(function (el) {
        el.setAttribute('tabindex', '-1');
      });
      list.appendChild(copy);
    });

    carousel.dataset.animated = 'true';
  }

  function start() {
    document.querySelectorAll('.carousel-projects-items').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

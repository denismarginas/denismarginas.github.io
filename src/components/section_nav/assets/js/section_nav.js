(function () {
  'use strict';

  function setup(nav) {
    var row = nav.querySelector('.anchor-index-list');
    var toggle = nav.querySelector('.section-nav-toggle');
    var current = nav.querySelector('[data-section-nav-current]');

    function setOpen(open) {
      nav.setAttribute('data-open', open ? 'true' : 'false');
      if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (toggle) {
      toggle.addEventListener('click', function () {
        setOpen(nav.getAttribute('data-open') !== 'true');
      });
      nav.addEventListener('click', function (e) {
        if (e.target.closest && e.target.closest('.anchor-index-link')) setOpen(false);
      });
      document.addEventListener('click', function (e) {
        if (nav.getAttribute('data-open') === 'true' && !nav.contains(e.target)) setOpen(false);
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && nav.getAttribute('data-open') === 'true') {
          setOpen(false);
          toggle.focus();
        }
      });
      window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) setOpen(false);
      });
    }

    if (row) {
      var follow = function () {
        var link = row.querySelector('[aria-current="true"]');
        if (!link) return;
        if (current) current.textContent = link.textContent.trim();
        if (row.scrollWidth <= row.clientWidth) return;
        var item = link.parentElement || link;
        var left = item.offsetLeft - (row.clientWidth - item.offsetWidth) / 2;
        row.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
      };
      new MutationObserver(follow).observe(row, { subtree: true, attributes: true, attributeFilter: ['aria-current'] });
      follow();
    }

    if ('IntersectionObserver' in window) {
      var sentinel = document.createElement('div');
      sentinel.className = 'section-nav-sentinel';
      sentinel.setAttribute('aria-hidden', 'true');
      nav.parentNode.insertBefore(sentinel, nav);
      new IntersectionObserver(function (entries) {
        nav.setAttribute('data-stuck', entries[0].isIntersecting ? 'false' : 'true');
      }, { rootMargin: '-120px 0px 0px 0px' }).observe(sentinel);
    }
  }

  function init() {
    document.querySelectorAll('[data-section-nav]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
  'use strict';

  function pad(n) { return (n < 10 ? '0' : '') + n; }

  function setup(viewer) {
    var stage = viewer.querySelector('[data-screens-stage]');
    var thumbs = Array.prototype.slice.call(viewer.querySelectorAll('[data-screens-index]'));
    var pairs = viewer.querySelectorAll('template[data-screens-pair]');
    var sources = viewer.querySelectorAll('.screens-viewer-source-item');
    var rail = viewer.querySelector('[data-screens-thumbs]');
    var counter = viewer.querySelector('[data-screens-counter]');
    var title = viewer.querySelector('[data-screens-title]');
    var sub = viewer.querySelector('[data-screens-sub]');
    if (!stage || !thumbs.length) return;

    var total = thumbs.length;

    function vertical() {
      return !!rail && rail.scrollHeight > rail.clientHeight + 2 && rail.scrollWidth <= rail.clientWidth + 2;
    }
    var active = 0;

    function select(index, focusThumb) {
      index = (index + total) % total;
      if (index === active && stage.childElementCount) return;
      active = index;

      var tpl = pairs[index];
      if (tpl) {
        stage.classList.remove('is-in');
        stage.innerHTML = tpl.innerHTML;
        void stage.offsetWidth;
        stage.classList.add('is-in');
      }

      thumbs.forEach(function (thumb, i) {
        if (i === index) thumb.setAttribute('aria-current', 'true');
        else thumb.removeAttribute('aria-current');
      });

      var thumb = thumbs[index];
      if (counter) counter.textContent = pad(index + 1) + ' / ' + pad(total);
      if (title) title.textContent = thumb.getAttribute('data-title') || '';
      if (sub) sub.textContent = thumb.getAttribute('data-sub') || '';

      if (rail) {
        var railBox = rail.getBoundingClientRect();
        var box = thumb.getBoundingClientRect();
        if (vertical()) {
          var top = rail.scrollTop + box.top - railBox.top - (rail.clientHeight - box.height) / 2;
          rail.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        } else if (rail.scrollWidth > rail.clientWidth) {
          var left = rail.scrollLeft + box.left - railBox.left - (rail.clientWidth - box.width) / 2;
          rail.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
        }
      }
      if (focusThumb) thumb.focus({ preventScroll: true });
    }

    function openGallery(index) {
      var item = sources[index] || sources[0];
      if (item && window.Popup && typeof window.Popup.openTrigger === 'function') window.Popup.openTrigger(item);
    }

    thumbs.forEach(function (thumb, i) {
      thumb.addEventListener('click', function () { select(i, false); });
    });

    var prev = viewer.querySelector('[data-screens-prev]');
    var next = viewer.querySelector('[data-screens-next]');
    if (prev) prev.addEventListener('click', function () { select(active - 1, false); });
    if (next) next.addEventListener('click', function () { select(active + 1, false); });

    if (rail) {
      rail.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); select(active - 1, true); }
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); select(active + 1, true); }
      });

      var updateFade = function () {
        rail.classList.toggle('mask-fade-bottom', vertical() && rail.scrollHeight - rail.scrollTop - rail.clientHeight > 2);
      };
      rail.addEventListener('scroll', updateFade, { passive: true });
      if ('ResizeObserver' in window) new ResizeObserver(updateFade).observe(rail);
      else window.addEventListener('resize', updateFade);
      updateFade();
    }

    stage.addEventListener('click', function () { openGallery(active); });
    stage.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openGallery(active); }
    });

    var open = viewer.querySelector('[data-screens-open]');
    if (open) open.addEventListener('click', function () { openGallery(0); });

    var startX = null;
    stage.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(dx) > 50) select(active + (dx < 0 ? 1 : -1), false);
    });

    stage.classList.add('is-in');
  }

  function init() {
    document.querySelectorAll('[data-screens-viewer]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

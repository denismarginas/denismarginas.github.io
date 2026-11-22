(function () {
  'use strict';

  function setup(root) {
    var screen = root.querySelector('[data-video-screen]');
    var items = Array.prototype.slice.call(root.querySelectorAll('[data-video-index]'));
    var title = root.querySelector('[data-video-title]');
    var meta = root.querySelector('[data-video-meta]');
    var detailsBtn = root.querySelector('[data-video-details-open]');
    var list = root.querySelector('[data-video-list]');
    if (!screen) return;

    function updateFade() {
      if (!list) return;
      list.classList.toggle('mask-fade-bottom', list.scrollHeight - list.scrollTop - list.clientHeight > 2);
    }
    if (list) {
      list.addEventListener('scroll', updateFade, { passive: true });
      if ('ResizeObserver' in window) new ResizeObserver(updateFade).observe(list);
      else window.addEventListener('resize', updateFade);
      updateFade();
    }

    var active = 0;

    function template(kind, index) {
      return root.querySelector('template[data-video-' + kind + '="' + index + '"]');
    }

    function select(index, autoplay) {
      if (index === active) return;
      var tpl = template('player', index);
      if (!tpl) return;
      active = index;

      screen.querySelectorAll('video').forEach(function (video) { video.pause(); });
      screen.innerHTML = tpl.innerHTML;
      if (window.VideoControls && typeof window.VideoControls.initAll === 'function') window.VideoControls.initAll();

      items.forEach(function (item, i) {
        if (i === index) item.setAttribute('aria-current', 'true');
        else item.removeAttribute('aria-current');
      });

      var item = items[index];
      if (title) title.textContent = item.getAttribute('data-title') || '';
      if (meta) meta.textContent = item.getAttribute('data-meta') || '';
      if (detailsBtn) detailsBtn.hidden = item.getAttribute('data-has-details') !== 'true';

      if (autoplay) {
        var video = screen.querySelector('video');
        var play = screen.querySelector('.show-play');
        if (play) play.click();
        else if (video) video.play().catch(function () {});
      }
    }

    items.forEach(function (item, i) {
      item.addEventListener('click', function () { select(i, false); });
    });

    if (detailsBtn) {
      detailsBtn.addEventListener('click', function () {
        var tpl = template('details', active);
        if (!tpl || !window.Popup || typeof window.Popup.open !== 'function') return;
        screen.querySelectorAll('video').forEach(function (video) { video.pause(); });
        window.Popup.open(tpl.innerHTML, { source: root });
      });
    }
  }

  function init() {
    document.querySelectorAll('[data-project-videos]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

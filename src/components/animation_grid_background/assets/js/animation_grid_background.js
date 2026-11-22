(function () {
  'use strict';

  var grids = [];
  var timer = 0;
  var paused = false;

  function setPaused(value) {
    if (paused === value) return;
    paused = value;
    grids.forEach(function (grid) { grid.classList.toggle('is-paused', value); });
  }

  function onScroll() {
    setPaused(true);
    window.clearTimeout(timer);
    timer = window.setTimeout(function () { setPaused(false); }, 200);
  }

  function init() {
    grids = Array.prototype.slice.call(document.querySelectorAll('.animation-grid-background'));
    if (!grids.length) return;
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

(function () {
  'use strict';

  var CLICKABLE = 'a[href],button,[role="button"],[role="link"],[role="tab"],[role="checkbox"],[role="switch"],label,select,summary,'
    + 'input[type="submit"],input[type="button"],input[type="reset"],input[type="checkbox"],input[type="radio"],'
    + 'input[type="range"],input[type="color"],input[type="file"],[onclick]';
  var TEXT = 'input,textarea,[contenteditable]:not([contenteditable="false"])';
  var MATCH = '[data-cursor],[data-cursor-text],' + CLICKABLE + ',' + TEXT;
  var ICONS = { arrow: 1, external: 1, view: 1, play: 1, drag: 1, zoom: 1 };

  var root, ring, dot, label, html = document.documentElement;
  var x = 0, y = 0, rx = 0, ry = 0, ease = 0.18;
  var running = false, active = false, visible = false, scrollQueued = false;
  var lastTarget = null, current = '';
  var fine, reduced;

  function setVisible(value) {
    if (visible === value) return;
    visible = value;
    root.setAttribute('data-visible', value ? 'true' : 'false');
  }

  function frame() {
    var k = reduced.matches ? 1 : ease;
    rx += (x - rx) * k;
    ry += (y - ry) * k;
    if (Math.abs(x - rx) < 0.1 && Math.abs(y - ry) < 0.1) {
      rx = x;
      ry = y;
      running = false;
    } else {
      window.requestAnimationFrame(frame);
    }
    dot.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0)';
    ring.style.transform = 'translate3d(' + rx + 'px,' + ry + 'px,0)';
  }

  function start() {
    if (running) return;
    running = true;
    window.requestAnimationFrame(frame);
  }

  function resolve(target) {
    var state = 'default', icon = '', text = '', color = '';
    var el = target && target.closest ? target.closest(MATCH) : null;

    if (el) {
      var mode = (el.getAttribute('data-cursor') || '').toLowerCase();
      text = el.getAttribute('data-cursor-text') || '';
      color = el.getAttribute('data-cursor-color') || '';

      if (mode === 'hide' || mode === 'native' || mode === 'none') state = 'hidden';
      else if (text) state = 'label';
      else if (ICONS[mode]) { state = 'icon'; icon = mode; }
      else if (mode === 'hover') state = 'hover';
      else if (mode === 'default') state = 'default';
      else if (el.matches(TEXT) && !el.matches(CLICKABLE)) state = 'hidden';
      else if (el.disabled || el.getAttribute('aria-disabled') === 'true') state = 'default';
      else if (el.matches('a[target="_blank"]')) { state = 'icon'; icon = 'external'; }
      else state = 'hover';
    }

    var key = state + '|' + icon + '|' + text + '|' + color;
    if (key === current) return;
    current = key;

    root.setAttribute('data-state', state);
    if (icon) root.setAttribute('data-icon', icon); else root.removeAttribute('data-icon');
    if (state === 'label') label.textContent = text;
    if (color) root.style.setProperty('--cursor-accent', color); else root.style.removeProperty('--cursor-accent');
  }

  function resolveAtPointer() {
    lastTarget = document.elementFromPoint(x, y);
    resolve(lastTarget);
  }

  function onMove(e) {
    if (e.pointerType && e.pointerType !== 'mouse' && e.pointerType !== 'pen') {
      setVisible(false);
      return;
    }
    x = e.clientX;
    y = e.clientY;
    if (!visible) {
      rx = x;
      ry = y;
      html.classList.add('has-custom-cursor');
      setVisible(true);
    }
    start();
  }

  function onOver(e) {
    if (e.target === lastTarget) return;
    lastTarget = e.target;
    resolve(lastTarget);
  }

  function onDown(e) {
    if (e.pointerType !== 'mouse' && e.pointerType !== 'pen') {
      setVisible(false);
      return;
    }
    root.setAttribute('data-pressed', 'true');
  }

  function onUp() {
    root.setAttribute('data-pressed', 'false');
    if (visible) window.requestAnimationFrame(resolveAtPointer);
  }

  function onScroll() {
    if (!visible || scrollQueued) return;
    scrollQueued = true;
    window.requestAnimationFrame(function () {
      scrollQueued = false;
      resolveAtPointer();
    });
  }

  function onOut(e) {
    if (!e.relatedTarget) setVisible(false);
  }

  function onBlur() {
    setVisible(false);
  }

  function enable() {
    if (active) return;
    active = true;
    window.addEventListener('pointermove', onMove, { passive: true });
    document.addEventListener('pointerover', onOver, { passive: true });
    window.addEventListener('pointerdown', onDown, { passive: true });
    window.addEventListener('pointerup', onUp, { passive: true });
    window.addEventListener('scroll', onScroll, { passive: true, capture: true });
    document.addEventListener('mouseout', onOut, { passive: true });
    window.addEventListener('blur', onBlur);
  }

  function disable() {
    if (!active) return;
    active = false;
    window.removeEventListener('pointermove', onMove);
    document.removeEventListener('pointerover', onOver);
    window.removeEventListener('pointerdown', onDown);
    window.removeEventListener('pointerup', onUp);
    window.removeEventListener('scroll', onScroll, true);
    document.removeEventListener('mouseout', onOut);
    window.removeEventListener('blur', onBlur);
    html.classList.remove('has-custom-cursor');
    setVisible(false);
  }

  function sync() {
    if (fine.matches) enable(); else disable();
  }

  function init() {
    root = document.querySelector('[data-cursor-root]');
    if (!root || !window.matchMedia || !window.requestAnimationFrame) return;
    ring = root.querySelector('.cursor-ring');
    dot = root.querySelector('.cursor-dot');
    label = root.querySelector('.cursor-label');
    if (!ring || !dot || !label) return;

    var value = parseFloat(root.getAttribute('data-cursor-ease'));
    if (value > 0 && value <= 1) ease = value;

    fine = window.matchMedia('(hover: hover) and (pointer: fine)');
    reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (fine.addEventListener) fine.addEventListener('change', sync);
    sync();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

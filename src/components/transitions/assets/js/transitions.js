(function () {
  var root = document.documentElement;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var supported = typeof Element !== 'undefined' && 'animate' in Element.prototype && 'IntersectionObserver' in window;

  if (reduce || !supported) return;
  root.classList.add('tx-js');

  var DEFAULTS = {
    duration: 700,
    delay: 0,
    easing: 'cubic-bezier(0.2, 0.7, 0.2, 1)',
    distance: '40px',
    angle: '12deg',
    blur: '10px',
    threshold: 0.15
  };

  function ms(value, fallback) {
    if (value === null || value === undefined || value === '') return fallback;
    var s = String(value).trim();
    var n = parseFloat(s);
    if (isNaN(n)) return fallback;
    return /ms$/.test(s) ? n : (/s$/.test(s) ? n * 1000 : n);
  }

  function negate(length) {
    var s = String(length).trim();
    return s.charAt(0) === '-' ? s.slice(1) : '-' + s;
  }

  function fromFrame(effects, opts) {
    var frame = {};
    var x = '0px';
    var y = '0px';
    var moved = false;

    effects.forEach(function (effect) {
      switch (effect) {
        case 'fade': frame.opacity = 0; break;
        case 'slide-left': x = negate(opts.distance); moved = true; break;
        case 'slide-right': x = opts.distance; moved = true; break;
        case 'slide-top': y = negate(opts.distance); moved = true; break;
        case 'slide-bottom': y = opts.distance; moved = true; break;
        case 'rotate':
        case 'rotate-left': frame.rotate = negate(opts.angle); break;
        case 'rotate-right': frame.rotate = opts.angle; break;
        case 'zoom':
        case 'zoom-in': frame.scale = String(opts.scale !== null ? opts.scale : 0.85); break;
        case 'zoom-out': frame.scale = String(opts.scale !== null ? opts.scale : 1.15); break;
        case 'blur': frame.filter = 'blur(' + opts.blur + ')'; break;
      }
    });
    if (moved) frame.translate = x + ' ' + y;
    return frame;
  }

  function options(el) {
    var d = el.dataset;
    return read(function (key) { return d['transition' + key.charAt(0).toUpperCase() + key.slice(1)]; }, null);
  }

  function read(get, base) {
    base = base || {};
    var pick = function (key, fallback) {
      var v = get(key);
      if (v !== undefined && v !== null && v !== '') return v;
      return base[key] !== undefined && base[key] !== null ? base[key] : fallback;
    };
    var scale = parseFloat(pick('scale', null));
    var threshold = parseFloat(pick('threshold', DEFAULTS.threshold));
    return {
      duration: Math.max(0, ms(pick('duration', DEFAULTS.duration), DEFAULTS.duration)),
      delay: Math.max(0, ms(pick('delay', DEFAULTS.delay), DEFAULTS.delay)),
      easing: String(pick('easing', DEFAULTS.easing)),
      distance: String(pick('distance', DEFAULTS.distance)),
      angle: String(pick('angle', DEFAULTS.angle)),
      blur: String(pick('blur', DEFAULTS.blur)),
      scale: isNaN(scale) ? null : scale,
      threshold: isNaN(threshold) ? DEFAULTS.threshold : Math.min(1, Math.max(0, threshold)),
      once: String(pick('once', 'true')) !== 'false',
      stagger: Math.max(0, ms(get('stagger'), 0)),
      target: String(get('target') || '').trim(),
      each: String(get('each') || '') === 'true'
    };
  }

  var sliding = 0;
  function trackSliding(animation) {
    if (!animation.txSideways || animation.txTracked) return;
    animation.txTracked = true;
    sliding++;
    root.classList.add('tx-sliding');
    var done = function () {
      animation.txTracked = false;
      sliding = Math.max(0, sliding - 1);
      if (!sliding) root.classList.remove('tx-sliding');
    };
    animation.finished.then(done, done);
  }

  function prepare(el, effects, opts, extraDelay) {
    var frame = fromFrame(effects, opts);
    if (!Object.keys(frame).length) return null;
    var animation;
    try {
      animation = el.animate([frame, {}], {
        duration: opts.duration,
        delay: opts.delay + (extraDelay || 0),
        easing: opts.easing,
        fill: 'backwards'
      });
    } catch (e) {
      return null;
    }
    animation.pause();
    animation.currentTime = 0;
    animation.txSideways = effects.indexOf('slide-left') !== -1 || effects.indexOf('slide-right') !== -1;
    trackSliding(animation);
    return animation;
  }

  var MAX_STAGGER_STEPS = 12;

  function rendered(el) {
    return el.getClientRects().length > 0;
  }

  var entries = new Map();
  var batch = new Map();
  var observers = {};

  function observerFor(threshold) {
    if (observers[threshold]) return observers[threshold];
    observers[threshold] = new IntersectionObserver(function (list, observer) {
      batch.clear();
      list.forEach(function (entry) {
        var item = entries.get(entry.target);
        if (!item) return;
        if (entry.isIntersecting) {
          playItem(entry.target, item, observer);
          return;
        }
        if (!item.opts.once && !item.playing) return;
        if (!item.opts.once) {
          item.playing = false;
          item.animations.forEach(function (a) {
            a.pause();
            a.currentTime = 0;
            trackSliding(a);
          });
        }
      });
    }, { threshold: 0, rootMargin: '0px 0px -' + Math.round(threshold * 100) + '% 0px' });
    return observers[threshold];
  }

  function playItem(target, item, observer) {
    if (item.playing) return;
    item.playing = true;
    if (item.batch) {
      var n = batch.get(item.batch) || 0;
      batch.set(item.batch, n + 1);
      var step = Math.min(n, MAX_STAGGER_STEPS);
      item.animations.forEach(function (a) { a.effect.updateTiming({ delay: item.opts.delay + step * item.opts.stagger }); });
    }
    item.animations.forEach(function (a) { a.play(); });
    if (item.opts.once) {
      observer.unobserve(target);
      entries.delete(target);
    }
  }

  function setup(el) {
    if (el.classList.contains('tx-ready')) return;
    var auto = el.dataset.transitionAuto === 'true';
    if (auto && el.closest('header, footer') && !el.closest('main')) {
      el.classList.add('tx-ready');
      return;
    }
    var nested = auto && el.parentElement && el.parentElement.closest('[data-transition]:not([data-transition="none"])');
    if (!nested) setupOwn(el);
    setupParts(el);
    el.classList.add('tx-ready');
  }

  function setupOwn(el) {
    var effects = String(el.dataset.transition || '').toLowerCase().split(/\s+/).filter(Boolean);
    var opts = options(el);
    var animations = [];

    var targets = null;
    if (opts.target === 'children') {
      targets = Array.prototype.slice.call(el.children);
    } else if (opts.target) {
      try { targets = Array.prototype.slice.call(el.querySelectorAll(opts.target)); } catch (e) { targets = []; }
    }

    if (targets && opts.each) {
      targets.forEach(function (target) {
        var a = prepare(target, effects, opts, 0);
        if (!a) return;
        var observer = observerFor(opts.threshold);
        entries.set(target, { animations: [a], opts: opts, batch: el, observer: observer });
        observer.observe(target);
      });
      return;
    }

    if (targets) {
      targets.forEach(function (target, i) {
        var a = prepare(target, effects, opts, i * opts.stagger);
        if (a) animations.push(a);
      });
    } else {
      var a = prepare(el, effects, opts, 0);
      if (a) animations.push(a);
    }

    if (!animations.length) return;
    var observer = observerFor(opts.threshold);
    entries.set(el, { animations: animations, opts: opts, observer: observer });
    observer.observe(el);
  }

  function setupParts(el) {
    var raw = el.dataset.transitionParts;
    if (!raw) return;
    var parts;
    try { parts = JSON.parse(raw); } catch (e) { return; }
    if (!Array.isArray(parts)) return;
    var base = options(el);
    base.delay = 0;

    parts.forEach(function (part) {
      if (!part || typeof part !== 'object' || !part.target || !part.effect) return;
      var effects = String(part.effect).toLowerCase().split(/\s+/).filter(Boolean);
      var opts = read(function (key) { return part[key]; }, base);
      var targets;
      try { targets = el.querySelectorAll(part.target); } catch (e) { return; }
      var group = {};
      Array.prototype.forEach.call(targets, function (target) {
        if (target.txPart || entries.has(target) || target.hasAttribute('data-transition')) return;
        var a = prepare(target, effects, opts, 0);
        if (!a) return;
        target.txPart = true;
        var observer = observerFor(opts.threshold);
        entries.set(target, { animations: [a], opts: opts, batch: group, observer: observer });
        observer.observe(target);
      });
    });
  }

  var catchUpTimer = 0;
  function catchUp() {
    catchUpTimer = 0;
    if (!entries.size) return;
    var vh = window.innerHeight;
    var atEnd = vh + window.scrollY >= document.documentElement.scrollHeight - 2;
    batch.clear();
    entries.forEach(function (item, target) {
      if (!rendered(target)) return;
      var r = target.getBoundingClientRect();
      var line = vh * (1 - item.opts.threshold);
      if (r.top < line || (atEnd && r.top < vh && r.bottom > 0)) playItem(target, item, item.observer);
    });
  }
  window.addEventListener('scroll', function () {
    if (!entries.size) return;
    clearTimeout(catchUpTimer);
    catchUpTimer = setTimeout(catchUp, 120);
  }, { passive: true });

  function init() {
    document.querySelectorAll('[data-transition]').forEach(setup);
    catchUp();
  }

  window.transitions = { refresh: init };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

(function () {
  'use strict';

  var root = document.querySelector('[data-performance-monitor]');
  if (!root || root.getAttribute('data-ready')) return;
  root.setAttribute('data-ready', '1');

  var STORE_KEY = 'performance_monitor_collapsed';
  var GRAPH_FRAMES = 120;
  var MAX_SAMPLES = 36000;
  var RATES = [30, 48, 50, 60, 72, 75, 90, 100, 120, 144, 165, 175, 240, 360];

  var s;
  function freshStats() {
    return {
      start: performance.now(),
      measured: 0,
      frames: 0,
      deltas: [],
      jank: 0,
      dropped: 0,
      expected: 0,
      blocking: 0,
      longTasks: 0,
      worstFrame: null,
    };
  }
  s = freshStats();

  var vitals = { lcp: null, cls: 0, inp: null };
  var recent = [];
  var refresh = 60;
  var refreshKnown = false;
  var windowFrames = 0;
  var windowStart = performance.now();
  var currentFps = 0;
  var last = 0;
  var scrollRun = null;
  var lastResult = null;

  function percentile(list, p) {
    if (!list.length) return 0;
    var sorted = list.slice().sort(function (a, b) { return a - b; });
    return sorted[Math.min(sorted.length - 1, Math.floor((p / 100) * sorted.length))];
  }

  function budget() { return 1000 / refresh; }

  function jankMs() { return Math.max(budget() * 1.5, 25); }

  function scoreUp(value, good, poor) {
    if (value >= good) return 100;
    if (value <= poor) return 0;
    return ((value - poor) / (good - poor)) * 100;
  }

  function scoreDown(value, good, poor) {
    if (value <= good) return 100;
    if (value >= poor) return 0;
    return ((poor - value) / (poor - good)) * 100;
  }

  function rating(score) {
    if (score >= 90) return { key: 'good', label: 'Good' };
    if (score >= 50) return { key: 'ok', label: 'Needs work' };
    return { key: 'poor', label: 'Poor' };
  }

  function fpsGood() { return Math.min(refresh * 0.95, 90); }

  function estimateRefresh() {
    if (recent.length < 30) return;
    var fast = percentile(recent, 10);
    if (!fast) return;
    var rate = 1000 / fast;
    var best = RATES[0];
    RATES.forEach(function (r) { if (Math.abs(r - rate) < Math.abs(best - rate)) best = r; });
    if (!refreshKnown || best > refresh) { refresh = best; refreshKnown = true; }
  }

  function metrics() {
    var avgFps = s.measured ? (s.frames / s.measured) * 1000 : 0;
    var p99 = percentile(s.deltas, 99);
    var p95 = percentile(s.deltas, 95);
    var low1 = p99 ? 1000 / p99 : 0;
    var jankPct = s.frames ? (s.jank / s.frames) * 100 : 0;
    var droppedPct = s.expected ? Math.min(100, (s.dropped / s.expected) * 100) : 0;
    var blockingPct = s.measured ? (s.blocking / s.measured) * 100 : 0;

    var parts = [
      { w: 30, v: scoreUp(avgFps, fpsGood(), Math.min(refresh * 0.6, 40)) },
      { w: 15, v: scoreUp(low1, Math.min(refresh * 0.75, 60), 24) },
      { w: 20, v: scoreDown(jankPct, 1, 10) },
      { w: 15, v: scoreDown(blockingPct, 1, 10) },
      { w: 10, v: scoreDown(vitals.cls, 0.1, 0.25) },
    ];
    if (vitals.lcp !== null) parts.push({ w: 10, v: scoreDown(vitals.lcp, 2500, 4000) });
    if (vitals.inp !== null) parts.push({ w: 10, v: scoreDown(vitals.inp, 200, 500) });
    var weight = 0, total = 0;
    parts.forEach(function (p) { weight += p.w; total += p.w * p.v; });

    return {
      fps: Math.round(currentFps),
      avgFps: avgFps,
      low1: low1,
      p95: p95,
      jankPct: jankPct,
      droppedPct: droppedPct,
      blockingPct: blockingPct,
      longTasks: s.longTasks,
      blocking: s.blocking,
      refresh: refresh,
      seconds: s.measured / 1000,
      score: s.frames > 20 ? Math.round(total / weight) : null,
    };
  }

  function hint(m) {
    if (m.score === null) return 'Measuring...';
    var issues = [];
    var gpuBound = m.blockingPct < 2;
    if (m.avgFps < fpsGood() * 0.9) issues.push([1 - m.avgFps / fpsGood(), gpuBound
      ? 'Low FPS but scripts are not blocking: painting / GPU is the bottleneck (blur, backdrop-filter, shadows, masks, video). Press "Find cause".'
      : 'Average FPS is low: something repaints or runs every frame.']);
    if (m.jankPct > 2) issues.push([m.jankPct / 20, 'Stutter: ' + m.jankPct.toFixed(1) + '% of frames are late (heavy paint, layout or scripts).']);
    if (m.blockingPct > 2) issues.push([m.blockingPct / 20, 'Long tasks block the main thread ' + m.blockingPct.toFixed(1) + '% of the time.']);
    if (vitals.cls > 0.1) issues.push([vitals.cls, 'Layout shifts (CLS ' + vitals.cls.toFixed(3) + '): reserve space for images / late content.']);
    if (vitals.lcp !== null && vitals.lcp > 2500) issues.push([vitals.lcp / 10000, 'Slow main content (LCP ' + (vitals.lcp / 1000).toFixed(2) + ' s).']);
    if (vitals.inp !== null && vitals.inp > 200) issues.push([vitals.inp / 1000, 'Slow response to clicks / keys (INP ' + Math.round(vitals.inp) + ' ms).']);
    if (gpu.software) issues.push([10, 'The browser draws without the graphics card (' + gpu.name + '): turn on "Use graphics acceleration" in the browser settings.']);
    if (!issues.length) return 'Smooth: frames arrive on time.';
    issues.sort(function (a, b) { return b[0] - a[0]; });
    return issues[0][1];
  }

  var gpu = (function () {
    try {
      var gl = document.createElement('canvas').getContext('webgl');
      if (!gl) return { name: 'no WebGL', software: true };
      var ext = gl.getExtension('WEBGL_debug_renderer_info');
      var name = String(ext ? gl.getParameter(ext.UNMASKED_RENDERER_WEBGL) : gl.getParameter(gl.RENDERER));
      var lose = gl.getExtension('WEBGL_lose_context');
      if (lose) lose.loseContext();
      return { name: name, software: /swiftshader|llvmpipe|basic render|software/i.test(name) };
    } catch (e) {
      return { name: 'unknown', software: false };
    }
  })();

  function frame(now) {
    if (last && !document.hidden) {
      var delta = now - last;
      if (delta > 0 && delta < 1000) {
        var b = budget();
        s.frames++;
        s.measured += delta;
        s.expected += delta / b;
        if (s.deltas.length < MAX_SAMPLES) s.deltas.push(delta);
        if (delta > jankMs()) s.jank++;
        var missed = Math.round(delta / b) - 1;
        if (missed > 0) s.dropped += missed;
        recent.push(delta);
        if (recent.length > GRAPH_FRAMES) recent.shift();
        windowFrames++;
      }
    }
    last = now;

    if (now - windowStart >= 500) {
      currentFps = (windowFrames * 1000) / (now - windowStart);
      windowFrames = 0;
      windowStart = now;
      estimateRefresh();
      update();
    }

    if (scrollRun) stepScroll(now);
    window.requestAnimationFrame(frame);
  }

  document.addEventListener('visibilitychange', function () { last = 0; });

  function observe(type, callback, extra) {
    try {
      var po = new PerformanceObserver(function (list) { list.getEntries().forEach(callback); });
      po.observe(Object.assign({ type: type, buffered: true }, extra || {}));
    } catch (e) {}
  }

  observe('longtask', function (entry) {
    s.longTasks++;
    s.blocking += Math.max(0, entry.duration - 50);
  });

  observe('largest-contentful-paint', function (entry) { vitals.lcp = entry.startTime; });

  observe('layout-shift', function (entry) {
    if (!entry.hadRecentInput) vitals.cls += entry.value;
  });

  observe('event', function (entry) {
    if (!entry.interactionId) return;
    if (vitals.inp === null || entry.duration > vitals.inp) vitals.inp = entry.duration;
  }, { durationThreshold: 40 });

  observe('long-animation-frame', function (entry) {
    if (entry.startTime < s.start) return;
    if (s.worstFrame && entry.duration <= s.worstFrame.duration) return;
    var source = '';
    (entry.scripts || []).forEach(function (script) {
      if (!source || script.duration > source.duration) source = script;
    });
    var label = '';
    if (source) {
      var file = (source.sourceURL || '').split('/').pop().split('?')[0];
      label = (file || source.invoker || 'script') + ' ' + Math.round(source.duration) + ' ms';
    } else {
      label = 'style / layout / paint';
    }
    s.worstFrame = { duration: entry.duration, source: label };
  });

  root.innerHTML = [
    '<div class="pm-head">',
    '  <button class="pm-btn pm-toggle" type="button" data-pm="toggle" title="Collapse / expand">',
    '    <span class="pm-dot" data-pm-dot></span><span class="pm-fps" data-pm-fps>--</span><span class="pm-unit">fps</span>',
    '  </button>',
    '  <span class="pm-score" data-pm-score>--</span>',
    '  <span class="pm-actions">',
    '    <button class="pm-btn" type="button" data-pm="scroll" title="Scroll the page top to bottom and score the run">Scroll test</button>',
    '    <button class="pm-btn" type="button" data-pm="cause" title="Scroll-test with one effect turned off at a time and show what costs the most">Find cause</button>',
    '    <button class="pm-btn" type="button" data-pm="reset" title="Start measuring again">Reset</button>',
    '    <button class="pm-btn" type="button" data-pm="copy" title="Copy a JSON report">Copy</button>',
    '  </span>',
    '</div>',
    '<div class="pm-body">',
    '  <canvas class="pm-graph" data-pm-graph width="248" height="44"></canvas>',
    '  <div class="pm-grid" data-pm-grid></div>',
    '  <div class="pm-hint" data-pm-hint></div>',
    '  <div class="pm-result" data-pm-result hidden></div>',
    '</div>',
  ].join('');

  var el = {
    dot: root.querySelector('[data-pm-dot]'),
    fps: root.querySelector('[data-pm-fps]'),
    score: root.querySelector('[data-pm-score]'),
    graph: root.querySelector('[data-pm-graph]'),
    grid: root.querySelector('[data-pm-grid]'),
    hint: root.querySelector('[data-pm-hint]'),
    result: root.querySelector('[data-pm-result]'),
    scrollBtn: root.querySelector('[data-pm="scroll"]'),
  };

  var collapsed = false;
  try { collapsed = window.localStorage.getItem(STORE_KEY) === '1'; } catch (e) {}
  root.classList.toggle('is-collapsed', collapsed);
  root.hidden = false;

  function level(value, good, ok, higherIsBetter) {
    if (higherIsBetter) return value >= good ? 'good' : value >= ok ? 'ok' : 'poor';
    return value <= good ? 'good' : value <= ok ? 'ok' : 'poor';
  }

  function cell(label, value, state, title) {
    return '<div class="pm-cell" title="' + (title || '') + '"><span class="pm-label">' + label + '</span>'
      + '<span class="pm-value pm-' + (state || 'none') + '">' + value + '</span></div>';
  }

  function update() {
    var m = metrics();
    var fpsState = level(m.fps, Math.min(m.refresh * 0.9, 85), Math.min(m.refresh * 0.6, 45), true);
    el.fps.textContent = m.fps;
    el.dot.className = 'pm-dot pm-' + fpsState;

    if (m.score === null) {
      el.score.textContent = '--';
      el.score.className = 'pm-score';
    } else {
      var r = rating(m.score);
      el.score.textContent = m.score + ' ' + r.label;
      el.score.className = 'pm-score pm-' + r.key;
    }
    if (collapsed) return;

    var b = budget();
    el.grid.innerHTML = [
      cell('Avg FPS', m.avgFps.toFixed(0) + '/' + m.refresh, level(m.avgFps, fpsGood(), Math.min(m.refresh * 0.6, 45), true), 'Average frames per second since reset / display refresh rate'),
      cell('1% low', m.low1.toFixed(0), level(m.low1, Math.min(m.refresh * 0.75, 60), 30, true), 'FPS of the slowest 1% of frames'),
      cell('Frame p95', m.p95.toFixed(1) + ' ms', level(m.p95, b * 1.2, 33.4, false), '95% of frames took at most this long (budget ' + b.toFixed(1) + ' ms)'),
      cell('Jank', m.jankPct.toFixed(1) + '%', level(m.jankPct, 1, 5, false), 'Frames slower than ' + jankMs().toFixed(0) + ' ms (under ' + Math.round(1000 / jankMs()) + ' fps)'),
      cell('Dropped', m.droppedPct.toFixed(1) + '%', level(m.droppedPct, 2, 10, false), 'Frames the display could have shown but did not get'),
      cell('Blocking', m.blockingPct.toFixed(1) + '%', level(m.blockingPct, 1, 5, false), m.longTasks + ' long tasks, ' + Math.round(m.blocking) + ' ms blocked'),
      cell('LCP', vitals.lcp === null ? '--' : (vitals.lcp / 1000).toFixed(2) + ' s', vitals.lcp === null ? 'none' : level(vitals.lcp, 2500, 4000, false), 'Largest Contentful Paint: when the main content showed'),
      cell('CLS', vitals.cls.toFixed(3), level(vitals.cls, 0.1, 0.25, false), 'Cumulative Layout Shift'),
      cell('INP', vitals.inp === null ? '--' : Math.round(vitals.inp) + ' ms', vitals.inp === null ? 'none' : level(vitals.inp, 200, 500, false), 'Slowest click / key response (Interaction to Next Paint)'),
    ].join('');

    var tip = hint(m);
    if (s.worstFrame && s.worstFrame.duration > 100) tip += ' Worst frame ' + Math.round(s.worstFrame.duration) + ' ms (' + s.worstFrame.source + ').';
    el.hint.textContent = tip;

    drawGraph(b);
  }

  function drawGraph(b) {
    var canvas = el.graph;
    var ctx = canvas.getContext('2d');
    var dpr = window.devicePixelRatio || 1;
    var w = canvas.clientWidth || 248, h = canvas.clientHeight || 44;
    if (canvas.width !== Math.round(w * dpr)) { canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr); }
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);

    var max = Math.max(50, b * 3);
    var y = function (ms) { return h - (Math.min(ms, max) / max) * h; };

    ctx.fillStyle = 'rgba(255,255,255,0.18)';
    ctx.fillRect(0, Math.round(y(b)), w, 1);
    ctx.fillStyle = 'rgba(255,90,90,0.25)';
    ctx.fillRect(0, Math.round(y(33.4)), w, 1);

    var barW = w / GRAPH_FRAMES;
    recent.forEach(function (ms, i) {
      ctx.fillStyle = ms <= Math.max(b * 1.5, 17) ? '#3ecf8e' : ms <= 33.4 ? '#f5a524' : '#f55a5a';
      var top = y(ms);
      ctx.fillRect(i * barW, top, Math.max(1, barW - 0.5), h - top);
    });
  }

  function reset() {
    s = freshStats();
    recent = [];
    last = 0;
    el.result.hidden = true;
    update();
  }

  function report(label) {
    var m = metrics();
    return {
      label: label || 'session',
      url: window.location.href,
      date: new Date().toISOString(),
      viewport: window.innerWidth + 'x' + window.innerHeight + ' @' + (window.devicePixelRatio || 1) + 'x',
      userAgent: navigator.userAgent,
      gpu: gpu.name,
      score: m.score,
      rating: m.score === null ? null : rating(m.score).label,
      seconds: +m.seconds.toFixed(1),
      refreshHz: m.refresh,
      avgFps: +m.avgFps.toFixed(1),
      low1Fps: +m.low1.toFixed(1),
      frameP95Ms: +m.p95.toFixed(1),
      jankPct: +m.jankPct.toFixed(2),
      droppedPct: +m.droppedPct.toFixed(2),
      longTasks: m.longTasks,
      blockingMs: Math.round(m.blocking),
      blockingPct: +m.blockingPct.toFixed(2),
      lcpMs: vitals.lcp === null ? null : Math.round(vitals.lcp),
      cls: +vitals.cls.toFixed(4),
      inpMs: vitals.inp === null ? null : Math.round(vitals.inp),
      worstFrame: s.worstFrame,
      domNodes: document.getElementsByTagName('*').length,
      jsHeapMb: performance.memory ? +(performance.memory.usedJSHeapSize / 1048576).toFixed(1) : null,
    };
  }

  function startScroll() {
    if (scrollRun) return stopScroll(true);
    var max = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
    if (max < 10) { el.hint.textContent = 'This page is too short to scroll.'; return; }
    window.scrollTo({ top: 0, behavior: 'instant' });
    reset();
    var duration = Math.min(15000, Math.max(3000, (max / 1200) * 1000));
    scrollRun = { start: 0, duration: duration, max: max };
    el.scrollBtn.textContent = 'Stop';
    root.classList.add('is-testing');
    ['wheel', 'touchstart', 'keydown'].forEach(function (type) { window.addEventListener(type, userAbort, { passive: true }); });
  }

  function userAbort(event) {
    if (event.type === 'keydown' && root.contains(event.target)) return;
    stopScroll(true);
  }

  function stepScroll(now) {
    if (!scrollRun.start) scrollRun.start = now;
    var t = Math.min(1, (now - scrollRun.start) / scrollRun.duration);
    window.scrollTo({ top: scrollRun.max * t, behavior: 'instant' });
    if (t >= 1) stopScroll(false);
  }

  function stopScroll(aborted) {
    scrollRun = null;
    el.scrollBtn.textContent = 'Scroll test';
    root.classList.remove('is-testing');
    ['wheel', 'touchstart', 'keydown'].forEach(function (type) { window.removeEventListener(type, userAbort); });
    if (aborted) { el.hint.textContent = 'Scroll test stopped.'; return; }

    lastResult = report('scroll test');
    var r = lastResult.score === null ? null : rating(lastResult.score);
    el.result.hidden = false;
    el.result.className = 'pm-result' + (r ? ' pm-' + r.key : '');
    el.result.textContent = 'Scroll test: ' + (r ? lastResult.score + ' ' + r.label : '--')
      + ' | avg ' + lastResult.avgFps.toFixed(0) + ' fps, 1% low ' + lastResult.low1Fps.toFixed(0)
      + ', jank ' + lastResult.jankPct.toFixed(1) + '%, ' + lastResult.seconds + ' s';
    if (window.console) console.info('[performance monitor] scroll test', lastResult);
  }

  function copy(button) {
    var text = JSON.stringify(lastResult && !el.result.hidden ? lastResult : report(), null, 2);
    var done = function () {
      button.textContent = 'Copied';
      window.setTimeout(function () { button.textContent = 'Copy'; }, 1200);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () { console.info(text); });
    } else {
      console.info(text);
    }
  }

  var SUSPECTS = [
    { label: 'Backdrop blur', css: '*,*::before,*::after{-webkit-backdrop-filter:none!important;backdrop-filter:none!important}' },
    { label: 'Filters (blur / shadow)', css: '*,*::before,*::after{filter:none!important}' },
    { label: 'CSS animations', css: '*,*::before,*::after{animation-play-state:paused!important}' },
    { label: 'Videos', css: 'video{visibility:hidden!important}', videos: true },
    { label: 'Box shadows', css: '*,*::before,*::after{box-shadow:none!important;text-shadow:none!important}' },
    { label: 'Masks / blend modes', css: '*,*::before,*::after{-webkit-mask-image:none!important;mask-image:none!important;mix-blend-mode:normal!important}' },
    { label: 'Noise overlays', css: '.overlay-noise{display:none!important}' },
    { label: 'Grid background', css: '.animation-grid-background{display:none!important}' },
    { label: '3D / perspective', css: '*{transform-style:flat!important;perspective:none!important}' },
  ];
  var causeRunning = false;

  function measureRun(css, pauseVideos) {
    return new Promise(function (resolve) {
      var style = document.createElement('style');
      style.setAttribute('data-performance-monitor-ab', '');
      style.textContent = css || '';
      document.head.appendChild(style);
      var paused = [];
      if (pauseVideos) document.querySelectorAll('video').forEach(function (v) { if (!v.paused) { v.pause(); paused.push(v); } });
      window.scrollTo({ top: 0, behavior: 'instant' });

      var max = Math.min(4000, Math.max(0, document.documentElement.scrollHeight - window.innerHeight));
      var duration = 2500;
      window.setTimeout(function () {
        var deltas = [], t0 = 0, prev = 0;
        function step(now) {
          if (!t0) { t0 = prev = now; } else { deltas.push(now - prev); prev = now; }
          var t = Math.min(1, (now - t0) / duration);
          window.scrollTo({ top: max * t, behavior: 'instant' });
          if (t < 1) { window.requestAnimationFrame(step); return; }
          style.remove();
          paused.forEach(function (v) { v.play().catch(function () {}); });
          var fps = deltas.length ? (deltas.length * 1000) / (now - t0) : 0;
          resolve({ fps: fps, p95: percentile(deltas, 95) });
        }
        window.requestAnimationFrame(step);
      }, 350);
    });
  }

  function findCause(button) {
    if (causeRunning || scrollRun) return;
    if (document.documentElement.scrollHeight - window.innerHeight < 10) { el.hint.textContent = 'This page is too short to scroll.'; return; }
    causeRunning = true;
    root.classList.add('is-testing');
    button.textContent = 'Testing...';
    el.result.hidden = false;
    el.result.className = 'pm-result';

    var results = [];
    var baseline = null;
    var index = -1;

    function next() {
      el.result.textContent = 'Testing ' + (index + 2) + ' / ' + (SUSPECTS.length + 1) + ': ' + (index < 0 ? 'page as it is' : 'without ' + SUSPECTS[index].label.toLowerCase()) + '... keep the mouse still';
      var suspect = index < 0 ? null : SUSPECTS[index];
      measureRun(suspect ? suspect.css : '', suspect && suspect.videos).then(function (run) {
        if (!suspect) baseline = run;
        else results.push({ label: suspect.label, fps: run.fps, gain: run.fps - baseline.fps, p95: run.p95 });
        index++;
        if (index < SUSPECTS.length) return next();
        done();
      });
    }

    function done() {
      causeRunning = false;
      root.classList.remove('is-testing');
      button.textContent = 'Find cause';
      window.scrollTo({ top: 0, behavior: 'instant' });
      results.sort(function (a, b) { return b.gain - a.gain; });
      var lines = ['Page as it is: ' + baseline.fps.toFixed(0) + ' fps (p95 ' + baseline.p95.toFixed(1) + ' ms)'];
      results.forEach(function (r) {
        var pct = baseline.fps ? (r.gain / baseline.fps) * 100 : 0;
        lines.push((pct >= 15 && r.gain >= 5 ? '! ' : '  ') + 'without ' + r.label.toLowerCase() + ': ' + r.fps.toFixed(0) + ' fps (' + (r.gain >= 0 ? '+' : '') + r.gain.toFixed(0) + ', ' + (pct >= 0 ? '+' : '') + pct.toFixed(0) + '%)');
      });
      var top = results[0];
      var bigGain = top && baseline.fps && top.gain / baseline.fps >= 0.15 && top.gain >= 5;
      lines.push(bigGain ? 'Biggest cost: ' + top.label.toLowerCase() + '.' : 'No single effect stands out: the cost is spread (or it is the page size / resolution).');
      el.result.className = 'pm-result pm-diagnose';
      el.result.textContent = lines.join('\n');
      lastResult = { label: 'find cause', url: window.location.href, gpu: gpu.name, viewport: window.innerWidth + 'x' + window.innerHeight + ' @' + (window.devicePixelRatio || 1) + 'x', refreshHz: refresh, baselineFps: +baseline.fps.toFixed(1), baselineP95Ms: +baseline.p95.toFixed(1), withoutEach: results.map(function (r) { return { off: r.label, fps: +r.fps.toFixed(1), gainFps: +r.gain.toFixed(1), p95Ms: +r.p95.toFixed(1) }; }) };
      if (window.console) console.info('[performance monitor] find cause', lastResult);
    }

    next();
  }

  root.addEventListener('click', function (event) {
    var button = event.target.closest('[data-pm]');
    if (!button) return;
    var action = button.getAttribute('data-pm');
    if (action === 'toggle') {
      collapsed = !collapsed;
      root.classList.toggle('is-collapsed', collapsed);
      try { window.localStorage.setItem(STORE_KEY, collapsed ? '1' : '0'); } catch (e) {}
      update();
    } else if (action === 'reset') {
      reset();
    } else if (action === 'scroll') {
      startScroll();
    } else if (action === 'cause') {
      findCause(button);
    } else if (action === 'copy') {
      copy(button);
    }
  });

  window.performanceMonitor = { report: report, reset: reset, scrollTest: startScroll, findCause: function () { findCause(root.querySelector('[data-pm="cause"]')); } };
  window.requestAnimationFrame(frame);
})();

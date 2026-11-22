(function () {
  var SOUND_ICONS =
    '<svg class="video-background-sound-off" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M17 9l4 6M21 9l-4 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
    '<svg class="video-background-sound-on" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M16.5 8.5a5 5 0 0 1 0 7M19 6a8.5 8.5 0 0 1 0 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function num(value, fallback) {
    var n = parseFloat(value);
    return isNaN(n) ? fallback : n;
  }

  function fromAttributes(host) {
    if (host.querySelector(':scope > [data-video-background]')) return;
    var src = host.getAttribute('data-video-bg');
    if (!src) return;

    var wrap = document.createElement('div');
    wrap.className = 'video-background';
    wrap.setAttribute('data-video-background', '');
    wrap.setAttribute('aria-hidden', 'true');
    wrap.dataset.start = host.getAttribute('data-video-bg-start') || '0';
    var end = host.getAttribute('data-video-bg-end');
    var duration = num(host.getAttribute('data-video-bg-duration'), 0);
    if (duration > 0) end = String(num(wrap.dataset.start, 0) + duration);
    if (end && end !== 'auto') wrap.dataset.end = end;
    wrap.dataset.loop = host.getAttribute('data-video-bg-loop') === 'false' ? 'false' : 'true';
    wrap.dataset.audio = host.getAttribute('data-video-bg-audio') === 'true' ? 'true' : 'false';
    var overlay = Math.max(0, Math.min(1, num(host.getAttribute('data-video-bg-overlay'), 0.55)));
    wrap.style.setProperty('--video-bg-overlay', String(overlay));

    var video = document.createElement('video');
    video.className = 'video-background-video';
    video.muted = true;
    video.setAttribute('muted', '');
    video.setAttribute('playsinline', '');
    video.preload = 'metadata';
    var poster = host.getAttribute('data-video-bg-poster');
    if (poster) {
      video.poster = poster;
      wrap.style.setProperty('--video-bg-poster', 'url("' + poster.replace(/"/g, '%22') + '")');
    }
    video.src = src;
    wrap.appendChild(video);

    if (overlay > 0) {
      var tint = document.createElement('span');
      tint.className = 'video-background-overlay';
      wrap.appendChild(tint);
    }

    host.classList.add('has-video-background');
    host.insertBefore(wrap, host.firstChild);

    if (wrap.dataset.audio === 'true') {
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'video-background-sound';
      button.setAttribute('data-video-background-sound', '');
      button.setAttribute('aria-pressed', 'false');
      button.setAttribute('aria-label', 'Turn sound on');
      button.title = 'Sound';
      button.innerHTML = SOUND_ICONS;
      wrap.insertAdjacentElement('afterend', button);
    }
  }

  function setup(wrap) {
    if (wrap.dataset.videoBackgroundReady) return;
    wrap.dataset.videoBackgroundReady = 'true';

    var video = wrap.querySelector('video');
    if (!video) return;
    var start = num(wrap.dataset.start, 0);
    var end = wrap.dataset.end ? num(wrap.dataset.end, 0) : 0;
    var loop = wrap.dataset.loop !== 'false';
    var finished = false;
    var visible = true;

    video.muted = true;
    if (loop && !end && !start) video.loop = true;
    if (start > 0) video.preload = 'auto';

    function inRange(ranges, t) {
      for (var i = 0; i < ranges.length; i++) if (t >= ranges.start(i) && t <= ranges.end(i)) return true;
      return false;
    }
    function seekStart() {
      if (start <= 0) return true;
      if (inRange(video.seekable, start)) {
        video.currentTime = start;
        return true;
      }
      return false;
    }
    function restart() {
      if (!seekStart()) {
        wrap.classList.remove('is-playing');
        video.currentTime = 0;
      }
    }
    function markPlaying() {
      wrap.classList.toggle('is-playing', video.currentTime + 0.25 >= start);
    }

    function play() {
      if (reduceMotion || finished || !visible) return;
      var p = video.play();
      if (p && p.catch) p.catch(function () {});
    }

    video.addEventListener('loadedmetadata', function () {
      if (start >= video.duration) start = 0;
      seekStart();
      play();
    });

    video.addEventListener('timeupdate', function () {
      if (video.currentTime + 0.25 < start) seekStart();
      markPlaying();
      var stop = end && end < video.duration ? end : 0;
      if (!stop) return;
      if (video.currentTime >= stop) {
        if (loop) {
          restart();
        } else {
          video.pause();
          finished = true;
        }
      }
    });

    video.addEventListener('ended', function () {
      if (loop) {
        restart();
        play();
      } else {
        finished = true;
      }
    });

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          visible = entry.isIntersecting;
          if (visible) play();
          else video.pause();
        });
      }, { threshold: 0.05 }).observe(wrap);
    }

    var button = wrap.nextElementSibling;
    if (button && button.hasAttribute('data-video-background-sound')) {
      button.addEventListener('click', function () {
        video.muted = !video.muted;
        var on = !video.muted;
        button.setAttribute('aria-pressed', on ? 'true' : 'false');
        button.setAttribute('aria-label', on ? 'Turn sound off' : 'Turn sound on');
        button.classList.toggle('is-on', on);
        if (on) {
          finished = false;
          play();
        }
      });
    }

    if (video.readyState >= 1) {
      seekStart();
      play();
    }
  }

  function start() {
    document.querySelectorAll('[data-video-bg]').forEach(fromAttributes);
    document.querySelectorAll('[data-video-background]').forEach(setup);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

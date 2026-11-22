(function () {
    'use strict';

    var ICONS = {
        prev: '<svg viewBox="0 0 320 512" aria-hidden="true"><path fill="currentColor" d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/></svg>',
        next: '<svg viewBox="0 0 320 512" aria-hidden="true"><path fill="currentColor" d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"/></svg>'
    };

    function option(root, opts, name, fallback) {
        if (opts[name] !== undefined) return !!opts[name];
        var attr = root.getAttribute('data-slider-' + name);
        return attr === null ? fallback : attr !== 'false';
    }

    function ui(tag, className, parent) {
        var el = document.createElement(tag);
        el.className = className;
        el.setAttribute('data-utility-slider-ui', '');
        if (parent) parent.appendChild(el);
        return el;
    }

    function pauseVideos(slide) {
        Array.prototype.forEach.call(slide.querySelectorAll('video'), function (video) {
            if (!video.paused) video.pause();
        });
    }

    function init(root, opts) {
        opts = opts || {};
        if (!root) return null;
        if (root.utilitySlider) return root.utilitySlider;

        var viewport = opts.viewport || root.querySelector('.utility-slider-viewport');
        var slides = viewport ? viewport.querySelectorAll('.utility-slider-slide') : [];
        var count = slides.length;
        if (!viewport || !count) return null;

        var current = -1;
        var target = null;
        var counter = null;
        var dots = [];

        function indexFromScroll() {
            var width = viewport.clientWidth || 1;
            return Math.max(0, Math.min(count - 1, Math.round(viewport.scrollLeft / width)));
        }

        function mark(index) {
            if (index === current) return;
            current = index;
            for (var i = 0; i < count; i++) {
                slides[i].classList.toggle('active', i === index);
                if (i !== index) pauseVideos(slides[i]);
            }
            for (var d = 0; d < dots.length; d++) dots[d].classList.toggle('active', d === index);
            if (counter) counter.textContent = (index + 1) + ' / ' + count;
            root.dispatchEvent(new CustomEvent('utility-slider:change', { detail: { index: index, count: count } }));
        }

        function goTo(index, instant) {
            if (index < 0) index = count - 1;
            if (index >= count) index = 0;
            target = instant ? null : index;
            if (instant) {
                viewport.style.scrollBehavior = 'auto';
                viewport.scrollTo({ left: slides[index].offsetLeft, behavior: 'instant' });
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () { viewport.style.scrollBehavior = ''; });
                });
            } else {
                viewport.scrollTo({ left: slides[index].offsetLeft, behavior: 'smooth' });
            }
            mark(index);
        }

        function step(n) {
            goTo((target !== null ? target : current) + n);
        }

        if (count > 1 && option(root, opts, 'arrows', true)) {
            var prev = ui('button', 'utility-slider-arrow utility-slider-prev', root);
            prev.type = 'button';
            prev.setAttribute('aria-label', 'Previous');
            prev.innerHTML = ICONS.prev;
            prev.addEventListener('click', function (e) { e.stopPropagation(); step(-1); });

            var next = ui('button', 'utility-slider-arrow utility-slider-next', root);
            next.type = 'button';
            next.setAttribute('aria-label', 'Next');
            next.innerHTML = ICONS.next;
            next.addEventListener('click', function (e) { e.stopPropagation(); step(1); });
        }

        if (count > 1 && option(root, opts, 'counter', true)) {
            counter = ui('span', 'utility-slider-counter', root);
        }

        if (count > 1 && option(root, opts, 'dots', false)) {
            var dotsEl = ui('div', 'utility-slider-dots', root);
            root.classList.add('utility-slider-has-dots');
            for (var i = 0; i < count; i++) {
                var dot = ui('button', 'utility-slider-dot', dotsEl);
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                dot.addEventListener('click', goTo.bind(null, i, false));
                dots.push(dot);
            }
        }

        function onKey(event) {
            if (event.target && event.target.closest && event.target.closest('.video-container')) return;
            if (event.key === 'ArrowLeft') { event.preventDefault(); step(-1); }
            if (event.key === 'ArrowRight') { event.preventDefault(); step(1); }
        }
        viewport.addEventListener('keydown', onKey);

        var ticking = false;
        function onScroll() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () {
                ticking = false;
                var index = indexFromScroll();
                if (target !== null) {
                    if (index !== target || Math.abs(viewport.scrollLeft - slides[target].offsetLeft) > 2) return;
                    target = null;
                }
                mark(index);
            });
        }
        viewport.addEventListener('scroll', onScroll, { passive: true });

        var api = {
            goTo: function (i) { goTo(i); },
            next: function () { step(1); },
            prev: function () { step(-1); },
            index: function () { return current; },
            count: count,
            destroy: function () {
                viewport.removeEventListener('keydown', onKey);
                viewport.removeEventListener('scroll', onScroll);
                Array.prototype.forEach.call(root.querySelectorAll(':scope > [data-utility-slider-ui]'), function (el) { el.remove(); });
                root.classList.remove('utility-slider-has-dots', 'utility-slider-ready');
                root.removeAttribute('data-initialized');
                delete root.utilitySlider;
            }
        };

        goTo(Math.max(0, Math.min(count - 1, parseInt(opts.start, 10) || 0)), true);
        root.classList.add('utility-slider-ready');
        root.setAttribute('data-initialized', '');
        root.utilitySlider = api;
        return api;
    }

    function initAll(scope) {
        var roots = (scope || document).querySelectorAll('[data-utility-slider]:not([data-initialized])');
        Array.prototype.forEach.call(roots, function (root) { init(root); });
    }

    window.UtilitySlider = { init: init, initAll: initAll };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }
})();

(function () {
    'use strict';

    var ESCAPE_KEY = 27;
    var FIT_MIN_WIDTH = 1280;
    var FIT_MIN_HEIGHT = 1500;
    var FIT_GROW = 1.1;
    var FIT_ICONS = {
        expand: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h18M3 12l4-4M3 12l4 4M21 12l-4-4M21 12l-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        shrink: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12h7M9 12l-4-4M9 12l-4 4M22 12h-7M15 12l4-4M15 12l4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    };

    function emit(name, detail) {
        document.dispatchEvent(new CustomEvent(name, { detail: detail }));
    }

    function createPopup() {
        var existing = document.getElementById('popup');
        if (existing) return existing;

        var tpl = document.querySelector('.popup-template-source');
        var markup = tpl ? tpl.innerHTML : (
            '<div class="popup-backdrop"></div>' +
            '<button type="button" class="popup-close" aria-label="Close">' +
              '<svg width="14" height="14" viewBox="0 0 14 14"><path d="M13 1L1 13M1 1L13 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
            '</button>' +
            '<div class="popup-content">' +
              '<div class="popup-body"></div>' +
            '</div>'
        );

        var popup = document.createElement('div');
        popup.id = 'popup';
        popup.className = 'popup';
        popup.hidden = true;
        popup.innerHTML = markup;
        document.body.appendChild(popup);
        bindPopupEvents(popup);
        return popup;
    }

    var POST_COLORS = ['--post-color-primary', '--post-color-secondary'];

    function applyColors(popup, source) {
        var styles = source ? window.getComputedStyle(source) : null;
        POST_COLORS.forEach(function (name) {
            var value = styles ? styles.getPropertyValue(name).trim() : '';
            if (value) popup.style.setProperty(name, value);
            else popup.style.removeProperty(name);
        });
    }

    function openPopup(html, options) {
        options = options || {};
        var popup = createPopup();
        if (!popup.hidden) closePopup(popup);
        applyColors(popup, options.source || null);
        destroyGallery(popup);
        setFit(popup, false);
        var body = popup.querySelector('.popup-body');
        body.className = 'popup-body' + (options.gallery ? ' has-gallery' : '');

        if (options.node) {
            body.innerHTML = '';
            body.appendChild(wrapPanel(moveIn(popup, options.node), options));
        } else {
            body.innerHTML = html;
        }

        show(popup);
        if (options.gallery) initGallery(popup, body, parseInt(options.index, 10) || 0);
        initVideos(body);
        updateFit(popup);

        popup.dataset.hash = options.hash || '';
        if (options.hash) setHash(options.hash);

        emit('popup:open', { popup: popup, body: body, source: options.source || null });
        return popup;
    }

    function closePopup(popup) {
        if (!popup || popup.hidden) return;
        var body = popup.querySelector('.popup-body');
        emit('popup:close', { popup: popup, body: body });

        destroyGallery(popup);
        setFit(popup, false);
        fitButton(popup).hidden = true;
        popup.hidden = true;
        moveBack(popup);
        if (body) {
            body.innerHTML = '';
            body.classList.remove('has-gallery');
        }

        if (popup.dataset.hash && currentHash() === popup.dataset.hash) setHash('');
        popup.dataset.hash = '';
    }

    function moveIn(popup, node) {
        var placeholder = document.createComment('popup-move');
        node.parentNode.insertBefore(placeholder, node);
        popup.movedNode = { node: node, placeholder: placeholder };
        return node;
    }

    function moveBack(popup) {
        var moved = popup.movedNode;
        if (!moved) return;
        if (moved.placeholder.parentNode) {
            moved.placeholder.parentNode.insertBefore(moved.node, moved.placeholder);
            moved.placeholder.parentNode.removeChild(moved.placeholder);
        }
        popup.movedNode = null;
    }

    function wrapPanel(node, options) {
        if (!options.panel) return node;
        var panel = document.createElement('div');
        panel.className = 'popup-panel';
        if (options.width) panel.style.setProperty('--popup-panel-width', options.width);
        panel.appendChild(node);
        return panel;
    }

    function currentHash() {
        return decodeURIComponent((window.location.hash || '').slice(1));
    }

    function setHash(hash) {
        if (!window.history || !window.history.replaceState) return;
        var url = window.location.pathname + window.location.search + (hash ? '#' + encodeURIComponent(hash) : '');
        window.history.replaceState(window.history.state, '', url);
    }

    function openFromHash() {
        var hash = currentHash();
        if (!hash) return;
        var popup = document.getElementById('popup');
        if (popup && !popup.hidden && popup.dataset.hash === hash) return;

        var triggers = document.querySelectorAll('[data-popup-hash]');
        for (var i = 0; i < triggers.length; i++) {
            if (triggers[i].getAttribute('data-popup-hash') === hash) {
                openTrigger(triggers[i]);
                return;
            }
        }
    }

    function linkHandledByBrowser(link, event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return true;

        var url;
        try { url = new URL(link.href, window.location.href); } catch (e) { return false; }
        var samePage = url.origin === window.location.origin
            && url.pathname === window.location.pathname
            && url.search === window.location.search;
        var id = url.hash ? decodeURIComponent(url.hash.slice(1)) : '';
        return samePage && id !== '' && document.getElementById(id) !== null;
    }

    function show(popup) { popup.hidden = false; }

    function initGallery(popup, body, index) {
        var gallery = body.querySelector('[data-popup-gallery]');
        var slides = gallery ? gallery.querySelectorAll('.popup-slide') : [];
        if (!slides.length || !window.UtilitySlider) return;

        var track = document.createElement('div');
        track.className = 'utility-slider-track';
        Array.prototype.forEach.call(slides, function (slide) {
            slide.classList.add('utility-slider-slide');
            track.appendChild(slide);
        });
        gallery.classList.add('utility-slider-viewport');
        gallery.appendChild(track);

        popup.classList.add('popup-has-gallery');
        window.UtilitySlider.init(popup, { viewport: gallery, start: index, arrows: true, counter: true, dots: false });
    }

    function initVideos(body) {
        var players = body.querySelectorAll('.video-container');
        if (!players.length) return;

        Array.prototype.forEach.call(document.querySelectorAll('video'), function (video) {
            if (!body.contains(video) && !video.paused) video.pause();
        });

        Array.prototype.forEach.call(players, function (player) {
            player.removeAttribute('data-video-initialized');
            player.classList.add('paused');
            player.classList.remove('full-screen', 'scrubbing');
            var thumbnail = player.querySelector('.thumbnail');
            if (thumbnail) thumbnail.style.display = '';
            var showPlay = player.querySelector('.show-play');
            if (showPlay) showPlay.style.display = 'flex';
            var showPause = player.querySelector('.show-pause');
            if (showPause) showPause.style.display = 'none';
            var controls = player.querySelector('.video-controls-container');
            if (controls) controls.style.display = '';
        });

        if (window.VideoControls && typeof window.VideoControls.initAll === 'function') window.VideoControls.initAll();
    }

    function fitButton(popup) {
        var btn = popup.querySelector(':scope > .popup-fit');
        if (btn) return btn;
        btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'popup-fit';
        btn.hidden = true;
        popup.insertBefore(btn, popup.querySelector('.popup-content'));
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            setFit(popup, !popup.classList.contains('popup-fit-width'));
        });
        return btn;
    }

    function setFit(popup, on) {
        popup.classList.toggle('popup-fit-width', on);
        var btn = fitButton(popup);
        var label = on ? 'Fit to screen' : 'Fit to width';
        btn.innerHTML = on ? FIT_ICONS.shrink : FIT_ICONS.expand;
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        btn.setAttribute('aria-label', label);
        btn.title = label;
        Array.prototype.forEach.call(popup.querySelectorAll('.popup-fit-target'), function (img) {
            img.style.transform = '';
            img.dataset.zoomLevel = 0;
            var box = img.closest('.popup-fit-scroll');
            if (box) box.scrollTop = 0;
        });
    }

    function currentImage(popup) {
        var body = popup.querySelector('.popup-body');
        var scope = body && popup.utilitySlider ? body.querySelector('.popup-slide.active') : body;
        if (!scope || scope.textContent.trim() !== '') return null;
        var imgs = scope.querySelectorAll('img');
        return imgs.length === 1 ? imgs[0] : null;
    }

    function canFit(popup, img) {
        var tall = img.naturalHeight > FIT_MIN_HEIGHT
            || (img.naturalWidth >= FIT_MIN_WIDTH && img.naturalHeight >= img.naturalWidth);
        if (!tall) return false;
        var content = popup.querySelector('.popup-content');
        var available = parseFloat(window.getComputedStyle(content).maxWidth) || content.clientWidth;
        return Math.min(available, img.naturalWidth) > img.offsetWidth * FIT_GROW;
    }

    function updateFit(popup) {
        if (popup.hidden) return;
        var btn = fitButton(popup);
        var img = currentImage(popup);
        if (img && !img.complete) {
            btn.hidden = true;
            img.addEventListener('load', function () { updateFit(popup); }, { once: true });
            return;
        }
        var ok = !!img && (img.classList.contains('popup-fit-target') || canFit(popup, img));
        if (ok) {
            img.classList.add('popup-fit-target');
            var wrap = img.parentElement;
            if (!wrap.classList.contains('popup-fit-scroll')) {
                wrap = document.createElement('div');
                wrap.className = 'popup-fit-scroll';
                img.parentNode.insertBefore(wrap, img);
                wrap.appendChild(img);
            }
            wrap.style.setProperty('--popup-fit-max', img.naturalWidth + 'px');
            var content = popup.querySelector('.popup-content');
            if (content && !popup.utilitySlider) content.style.setProperty('--popup-fit-max', img.naturalWidth + 'px');
        }
        btn.hidden = !ok;
    }

    function destroyGallery(popup) {
        if (popup.utilitySlider) popup.utilitySlider.destroy();
        popup.classList.remove('popup-has-gallery');
    }

    function bindPopupEvents(popup) {
        popup.querySelector('.popup-backdrop').addEventListener('click', function () { closePopup(popup); });
        popup.querySelector('.popup-close').addEventListener('click', function () { closePopup(popup); });

        document.addEventListener('keydown', function (e) {
            if (popup.hidden) return;
            if (e.keyCode === ESCAPE_KEY || e.key === 'Escape') closePopup(popup);
            if (!popup.utilitySlider) return;
            if (document.activeElement && document.activeElement.closest && document.activeElement.closest('.video-container')) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); popup.utilitySlider.prev(); }
            if (e.key === 'ArrowRight') { e.preventDefault(); popup.utilitySlider.next(); }
        });

        popup.addEventListener('click', function (event) {
            var img = event.target.closest('img');
            if (!img || !img.closest('.popup-body')) return;
            if (popup.classList.contains('popup-fit-width') && img.classList.contains('popup-fit-target')) return;
            toggleZoom(img, event);
        });

        popup.addEventListener('utility-slider:change', function () { updateFit(popup); });

        var resizeTick = false;
        window.addEventListener('resize', function () {
            if (popup.hidden || resizeTick) return;
            resizeTick = true;
            window.requestAnimationFrame(function () {
                resizeTick = false;
                updateFit(popup);
            });
        });
    }

    function toggleZoom(img, event) {
        var level = parseInt(img.dataset.zoomLevel || 0, 10) + 1;
        var rect = img.getBoundingClientRect();
        img.style.transformOrigin = ((event.clientX - rect.left) / rect.width) * 100 + '% ' + ((event.clientY - rect.top) / rect.height) * 100 + '%';

        if (level === 1) img.style.transform = 'scale(1.25)';
        else if (level === 2) img.style.transform = 'scale(1.5)';
        else if (level === 3) img.style.transform = 'scale(2)';
        else { img.style.transform = 'scale(1)'; level = 0; }
        img.dataset.zoomLevel = level;
    }

    function openTrigger(trigger) {
        var hash = trigger.getAttribute('data-popup-hash') || '';

        if (trigger.hasAttribute('data-popup-move')) {
            var selector = trigger.getAttribute('data-popup-move');
            var node = selector ? document.querySelector(selector) : trigger;
            if (!node) return null;
            return openPopup('', {
                node: node,
                source: trigger,
                hash: hash,
                panel: trigger.hasAttribute('data-popup-panel'),
                width: trigger.getAttribute('data-popup-width') || ''
            });
        }

        var template = trigger.querySelector('template.popup-template');
        if (template) {
            var galleryEl = template.content.querySelector('[data-popup-gallery]');
            var slides = template.content.querySelectorAll('[data-popup-gallery] .popup-slide');
            return openPopup(template.innerHTML, { gallery: !!galleryEl && slides.length > 1, source: trigger, hash: hash });
        }

        var group = trigger.getAttribute('data-popup-group');
        if (group) {
            var members = Array.prototype.slice.call(document.querySelectorAll('[data-popup-group="' + group + '"]'));
            var idx = members.indexOf(trigger);
            var slidesHtml = members.map(function (m) {
                var player = m.querySelector('.video-container');
                if (player) return '<div class="popup-slide popup-slide-video">' + player.outerHTML + '</div>';
                var img = m.tagName === 'IMG' ? m : m.querySelector('img');
                return '<div class="popup-slide">' + (img ? img.outerHTML : m.innerHTML) + '</div>';
            }).join('');
            return openPopup('<div data-popup-gallery>' + slidesHtml + '</div>', { gallery: members.length > 1, index: idx, source: trigger, hash: hash });
        }

        var targetSel = trigger.getAttribute('data-popup-target');
        var content = '';
        if (targetSel) {
            var t = document.querySelector(targetSel);
            content = t ? t.innerHTML : '';
        } else {
            var img = trigger.tagName === 'IMG' ? trigger : trigger.querySelector('img');
            content = img ? img.outerHTML : trigger.innerHTML;
        }
        return openPopup(content, { source: trigger, hash: hash });
    }

    function initTriggers() {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-popup="true"]');
            if (!trigger) return;

            var link = event.target.closest('a[href]');
            if (link && trigger.contains(link)) {
                if (linkHandledByBrowser(link, event)) return;
                event.preventDefault();
            }

            openTrigger(trigger);
        });

        openFromHash();
        window.addEventListener('hashchange', openFromHash);
    }

    document.addEventListener('DOMContentLoaded', initTriggers);

    window.Popup = {
        open: openPopup,
        close: function (popup) { closePopup(popup || document.getElementById('popup')); },
        openTrigger: openTrigger
    };
})();
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-text-collapse-toggle]');
        if (!button) return;

        var root = button.closest('[data-text-collapse]');
        if (!root) return;

        var short = root.querySelector('.text-collapse-short');
        var full = root.querySelector('.text-collapse-full');
        var open = button.getAttribute('aria-expanded') !== 'true';

        if (short) short.hidden = open;
        if (full) full.hidden = !open;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        var label = button.querySelector('.text-collapse-label') || button;
        label.textContent = button.getAttribute(open ? 'data-label-less' : 'data-label-more') || (open ? 'Show Less' : 'Show More');

        if (!open && root.getBoundingClientRect().top < 0) {
            root.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }
    });
})();

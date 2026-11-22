(function () {
    'use strict';

    function openFromHash() {
        var id = decodeURIComponent((window.location.hash || '').slice(1));
        if (!id) return;

        var card = document.getElementById(id);
        if (!card || !card.matches('[data-resume-view]')) return;

        var trigger = card.querySelector('.resume-view-trigger');
        if (!trigger) return;

        card.scrollIntoView({ block: 'center' });
        trigger.click();
    }

    function init() { setTimeout(openFromHash, 0); }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

    window.addEventListener('hashchange', openFromHash);
})();

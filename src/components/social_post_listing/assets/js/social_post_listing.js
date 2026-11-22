(function () {
    'use strict';

    function init() {
        document.querySelectorAll('[data-social-post-index]').forEach(function (nav) {
            var links = {};
            nav.querySelectorAll('a[href^="#"]').forEach(function (a) {
                links[decodeURIComponent(a.getAttribute('href').slice(1))] = a;
            });
            var posts = Object.keys(links).map(function (id) { return document.getElementById(id); }).filter(Boolean);
            if (!posts.length || !('IntersectionObserver' in window)) return;

            var current = null;
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var link = links[entry.target.id];
                    if (!link || link === current) return;
                    if (current) current.removeAttribute('aria-current');
                    link.setAttribute('aria-current', 'true');
                    current = link;
                    var list = nav.querySelector('.social-post-index-list');
                    if (list && list.scrollHeight > list.clientHeight) {
                        var top = link.offsetTop - list.offsetTop;
                        if (top < list.scrollTop || top > list.scrollTop + list.clientHeight - link.offsetHeight) {
                            list.scrollTo({ top: top - list.clientHeight / 2, behavior: 'smooth' });
                        }
                    }
                });
            }, { rootMargin: '-30% 0px -60% 0px' });
            posts.forEach(function (post) { observer.observe(post); });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();

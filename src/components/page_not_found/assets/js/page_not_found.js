(function () {
  function start() {
    document.querySelectorAll('[data-page-not-found-path]').forEach(function (el) {
      var value = el.querySelector('.page-not-found-path-value');
      if (!value || value.textContent.trim() !== '') return;
      var path = decodeURIComponent(window.location.pathname + window.location.search);
      if (!path || path === '/') return;
      value.textContent = path;
      el.hidden = false;
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

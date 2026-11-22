(function () {
  'use strict';

  function init() {
    var dropdowns = Array.prototype.slice.call(document.querySelectorAll('[data-language-dropdown]'));
    if (!dropdowns.length) return;

    document.addEventListener('click', function (event) {
      dropdowns.forEach(function (dropdown) {
        if (dropdown.open && !dropdown.contains(event.target)) dropdown.open = false;
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      dropdowns.forEach(function (dropdown) {
        if (!dropdown.open) return;
        dropdown.open = false;
        var toggle = dropdown.querySelector('summary');
        if (toggle) toggle.focus();
      });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

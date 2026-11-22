(function () {
  var MOBILE = "(max-width: 1024px)";

  function init() {
    var menu = document.querySelector("header .header-menu");
    if (!menu) return;

    var items = Array.prototype.slice.call(menu.querySelectorAll("li.has-submenu"));
    if (!items.length) return;
    var mobile = window.matchMedia(MOBILE);

    function set(li, open) {
      li.classList.toggle("is-open", open);
      var button = li.querySelector(".header-submenu-toggle");
      if (button) button.setAttribute("aria-expanded", open ? "true" : "false");
    }

    items.forEach(function (li) {
      var button = li.querySelector(".header-submenu-toggle");
      if (!button) return;
      button.addEventListener("click", function (event) {
        event.preventDefault();
        var open = !li.classList.contains("is-open");
        if (!mobile.matches) items.forEach(function (other) { if (other !== li) set(other, false); });
        set(li, open);
      });
    });

    function openCurrent() {
      items.forEach(function (li) {
        set(li, mobile.matches && li.classList.contains("is-current-parent"));
      });
    }
    openCurrent();
    if (mobile.addEventListener) mobile.addEventListener("change", openCurrent);

    document.addEventListener("click", function (event) {
      if (mobile.matches) return;
      items.forEach(function (li) { if (!li.contains(event.target)) set(li, false); });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") return;
      items.forEach(function (li) {
        if (!li.classList.contains("is-open") || mobile.matches) return;
        var hadFocus = li.contains(document.activeElement);
        set(li, false);
        if (hadFocus) li.querySelector(".header-submenu-toggle").focus();
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
  } else {
    init();
  }
})();

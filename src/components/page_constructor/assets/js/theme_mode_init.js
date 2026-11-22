(function () {
  "use strict";

  var mode = null;
  try {
    var saved = localStorage.getItem("dm_mode_value");
    if (saved === "dark" || saved === "light") mode = saved;
  } catch (e) {}

  function applyBody(body) {
    if (!mode) {
      mode = body.getAttribute("mode") === "dark" ? "dark" : "light";
    }
    if (body.getAttribute("mode") !== mode) body.setAttribute("mode", mode);
  }

  function syncToggle() {
    var toggle = document.querySelector(".header-theme-mode-toggle");
    if (!toggle || !mode) return;
    toggle.setAttribute("data-mode", mode);
    var input = toggle.querySelector(".header-theme-mode-toggle-input");
    if (input) input.checked = mode === "dark";
    var icons = toggle.querySelectorAll(".header-theme-mode-toggle-icon");
    for (var i = 0; i < icons.length; i++) {
      icons[i].setAttribute("active", icons[i].getAttribute("theme-style") === mode ? "true" : "false");
    }
  }

  if (document.body) {
    applyBody(document.body);
  } else if (window.MutationObserver) {
    var observer = new MutationObserver(function () {
      if (!document.body) return;
      observer.disconnect();
      applyBody(document.body);
    });
    observer.observe(document.documentElement, { childList: true });
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (document.body) applyBody(document.body);
    syncToggle();
  });
})();

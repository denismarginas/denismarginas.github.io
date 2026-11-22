(function () {
  const STORAGE_KEY = "dm_mode_value";
  const TOGGLE_SELECTOR = ".header-theme-mode-toggle";

  function readMode() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved === "dark" || saved === "light") {
        return saved;
      }
    } catch (e) {}

    const bodyMode =
      typeof document.body !== "undefined" && document.body
        ? document.body.getAttribute("mode")
        : null;
    return bodyMode === "dark" || bodyMode === "light" ? bodyMode : "light";
  }

  function saveMode(mode) {
    try {
      localStorage.setItem(STORAGE_KEY, mode);
    } catch (e) {}
  }

  function applyMode(mode) {
    if (document.body) {
      document.body.setAttribute("mode", mode);
    }
  }

  function syncIcons(toggle, mode) {
    const icons = toggle.querySelectorAll(".header-theme-mode-toggle-icon");
    icons.forEach((icon) => {
      icon.setAttribute("active", icon.getAttribute("theme-style") === mode ? "true" : "false");
    });
  }

  function initHeaderMode() {
    const toggle = document.querySelector(TOGGLE_SELECTOR);
    const mode = readMode();

    applyMode(mode);

    if (!toggle) return;

    const input = toggle.querySelector(".header-theme-mode-toggle-input");
    input.checked = mode === "dark";
    toggle.setAttribute("data-mode", mode);
    syncIcons(toggle, mode);

    toggle.addEventListener("mousedown", (event) => {
      if (event.detail > 1) event.preventDefault();
    });

    input.addEventListener("change", () => {
      const next = input.checked ? "dark" : "light";
      applyMode(next);
      toggle.setAttribute("data-mode", next);
      syncIcons(toggle, next);
      saveMode(next);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeaderMode, {
      once: true,
    });
  } else {
    initHeaderMode();
  }
})();

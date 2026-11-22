(function () {
  const TOGGLE_SELECTOR = ".header-menu-navbar-toggle";
  const NAVBAR_SELECTOR = ".header-menu";

  function initHeaderMenu() {
    const toggle = document.querySelector(TOGGLE_SELECTOR);
    const navbar = document.querySelector(NAVBAR_SELECTOR);

    if (!toggle) {
      console.warn("Header menu script: toggle element was not found.");
      return;
    }

    toggle.addEventListener("click", () => {
      const expanded = toggle.getAttribute("aria-expanded") === "true";
      const nextState = expanded ? "false" : "true";

      toggle.setAttribute("aria-expanded", nextState);
      toggle.classList.toggle("active", !expanded);
      document.body.setAttribute("data-overlay", nextState);

      if (navbar) {
        navbar.setAttribute("display-navbar", nextState);
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeaderMenu, {
      once: true,
    });
  } else {
    initHeaderMenu();
  }
})();

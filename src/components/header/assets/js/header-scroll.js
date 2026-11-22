(function () {
  const SCROLL_THRESHOLD = 80;
  const LOGO_SELECTOR = ".logo";
  const LOGO_CLASS_TOP = "logo-shape-circle";
  const LOGO_CLASS_SCROLLED = "logo-shape-square";

  function initHeaderScroll() {
    const header = document.querySelector("header");

    if (!header) {
      console.warn("Header scroll script: <header> element was not found.");
      return;
    }

    const logo = header.querySelector(LOGO_SELECTOR);

    const updateScrolled = () => {
      const isScrolled = window.scrollY > SCROLL_THRESHOLD;
      header.setAttribute("data-scrolled", isScrolled ? "true" : "false");

      if (logo) {
        logo.classList.toggle(LOGO_CLASS_TOP, !isScrolled);
        logo.classList.toggle(LOGO_CLASS_SCROLLED, isScrolled);
      }
    };

    updateScrolled();
    window.addEventListener("scroll", updateScrolled, { passive: true });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeaderScroll, {
      once: true,
    });
  } else {
    initHeaderScroll();
  }
})();

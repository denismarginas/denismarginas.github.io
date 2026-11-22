function addJavaScriptAttribute() {
  let duration = 0;

  const applyAttribute = () => {
    if (document.body) {
      document.body.setAttribute("java-script", "true");
    } else {
      requestAnimationFrame(applyAttribute);
    }
  };

  setTimeout(() => {
    applyAttribute();
  }, duration);
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", addJavaScriptAttribute);
} else {
  addJavaScriptAttribute();
}

function splitList(value) {
  return String(value || "")
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
}

function setStatus(message) {
  statusElement.textContent = message;
}

function t(path, fallback) {
  const value = path.split(".").reduce((current, key) => {
    if (current && typeof current === "object" && key in current) {
      return current[key];
    }
    return undefined;
  }, strings);

  return typeof value === "string" ? value : fallback;
}

function formatText(template, replacements) {
  return Object.keys(replacements).reduce(
    (result, key) => result.replaceAll(`{${key}}`, replacements[key]),
    template,
  );
}

function applyStaticStrings() {
  root.querySelectorAll("[data-platform-i18n]").forEach((element) => {
    const path = element.dataset.platformI18n;
    const fallback = element.dataset.platformFallback || element.textContent || "";
    element.textContent = t(path, fallback);
  });

  root.querySelectorAll("[data-platform-placeholder]").forEach((element) => {
    const path = element.dataset.platformPlaceholder;
    const fallback = element.getAttribute("placeholder") || "";
    element.setAttribute("placeholder", t(path, fallback));
  });

  document.title = t("title", document.title);
}

async function loadStrings() {
  const response = await fetch(stringsUrl, {
    headers: { Accept: "application/json" },
  });

  if (!response.ok) {
    throw new Error(`Strings failed (${response.status})`);
  }

  const data = await response.json();
  Object.assign(strings, data);
  applyStaticStrings();
}

function uid(prefix) {
  return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`;
}

function queueSave() {
  window.clearTimeout(saveTimer);
  saveTimer = window.setTimeout(() => {
    saveState();
  }, 120);
}

async function loadState() {
  setStatus(t("canvas.loading"));
  const response = await fetch(apiUrl, {
    headers: { Accept: "application/json" },
  });
  if (!response.ok) {
    throw new Error(`Load failed (${response.status})`);
  }
  const data = await response.json();

  state.cards = Array.isArray(data.cards) ? data.cards : [];
  state.links = Array.isArray(data.links) ? data.links : [];
  state.variables = Array.isArray(data.variables) ? data.variables : [];

  if (!selectedCardId && state.cards.length) {
    selectedCardId = state.cards[0].id;
  }

  renderAll();
  fitView();
  setStatus(t("canvas.loaded"));
}

async function saveState() {
  try {
    const response = await fetch(apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(state),
    });

    if (!response.ok) {
      throw new Error(`Save failed (${response.status})`);
    }

    setStatus(t("canvas.saved"));
  } catch (error) {
    setStatus(error.message);
  }
}

async function compileScss() {
  setStatus("Compiling SCSS...");
  try {
    const response = await fetch("api/compile-scss.php", {
      headers: { Accept: "application/json" },
    });
    const data = await response.json();
    if (data.error_count > 0) {
      setStatus(`SCSS: ${data.success_count} OK, ${data.error_count} errors`);
    } else {
      setStatus(`SCSS: ${data.total} components compiled successfully`);
    }
  } catch (error) {
    setStatus("SCSS compile failed: " + error.message);
  }
}

async function translateData() {
  setStatus("Translating data...");
  try {
    const response = await fetch("api/translate-data.php", {
      method: "POST",
      headers: { Accept: "application/json" },
    });
    const data = await response.json();
    const langs = (data.languages || []).map((lang) => lang.iso).join(", ");
    if (data.error_count > 0) {
      setStatus(`Translation: ${data.success_count} OK, ${data.error_count} errors (${langs})`);
    } else {
      setStatus(`Translation: ${data.total} files OK (${langs})`);
    }
  } catch (error) {
    setStatus("Translation failed: " + error.message);
  }
}

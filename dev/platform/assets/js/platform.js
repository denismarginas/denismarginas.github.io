let root, canvas, viewport, cardsLayer, linksLayer, statusElement,
    inspectorElement, variableListElement, linkListElement,
    cardForm, variableForm, zoomLevelElement;

const state = { cards: [], links: [], variables: [] };
const strings = {};

let selectedCardId = null;
let pendingPort = null;
let dragging = null;
let saveTimer = null;

let panX = 0, panY = 0, zoom = 1;
let isPanning = false, panStartX = 0, panStartY = 0;

let apiUrl = "api/cards.php";
let stringsUrl = "data/strings.json";

document.addEventListener("DOMContentLoaded", () => {
  root = document.querySelector("[data-platform-root]");
  if (!root) return;

  apiUrl = root.dataset.apiUrl || "api/cards.php";
  stringsUrl = root.dataset.stringsUrl || "data/strings.json";

  canvas = root.querySelector("[data-platform-canvas]");
  viewport = root.querySelector("[data-platform-viewport]");
  cardsLayer = root.querySelector("[data-platform-cards]");
  linksLayer = root.querySelector("[data-platform-links]");
  statusElement = root.querySelector("[data-platform-status]");
  inspectorElement = root.querySelector("[data-platform-inspector]");
  variableListElement = root.querySelector("[data-platform-variable-list]");
  linkListElement = root.querySelector("[data-platform-link-list]");
  cardForm = root.querySelector("[data-platform-card-form]");
  variableForm = root.querySelector("[data-platform-variable-form]");
  zoomLevelElement = root.querySelector("[data-platform-zoom]");

  root.querySelectorAll("[data-platform-action]").forEach((button) => {
    button.addEventListener("click", () => {
      const action = button.dataset.platformAction;
      if (action === "reload") loadState();
      if (action === "save") saveState();
      if (action === "fit-view") fitView();
    });
  });

  canvas.addEventListener("pointerdown", onCanvasPointerDown);
  window.addEventListener("pointermove", onCanvasPointerMove);
  window.addEventListener("pointerup", onCanvasPointerUp);
  canvas.addEventListener("wheel", onCanvasWheel, { passive: false });

  window.addEventListener("resize", () => { renderLinks(); });

  loadStrings()
    .then(() => loadState())
    .catch((error) => { setStatus(error.message); });
});

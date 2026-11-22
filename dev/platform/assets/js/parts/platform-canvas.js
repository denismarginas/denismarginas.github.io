function onCanvasPointerDown(event) {
  if (event.target.closest("[data-card-id]")) return;
  if (event.target.closest("button")) return;

  isPanning = true;
  panStartX = event.clientX - panX;
  panStartY = event.clientY - panY;
  canvas.classList.add("is-panning");
  canvas.setPointerCapture(event.pointerId);
}

function onCanvasPointerMove(event) {
  if (!isPanning) return;
  panX = event.clientX - panStartX;
  panY = event.clientY - panStartY;
  updateTransform();
}

function onCanvasPointerUp() {
  if (!isPanning) return;
  isPanning = false;
  canvas.classList.remove("is-panning");
}

function onCanvasWheel(event) {
  event.preventDefault();

  const rect = canvas.getBoundingClientRect();
  const mx = event.clientX - rect.left;
  const my = event.clientY - rect.top;

  const delta = -event.deltaY * 0.001;
  const newZoom = Math.max(0.1, Math.min(5, zoom * (1 + delta)));

  panX = mx - (mx - panX) * (newZoom / zoom);
  panY = my - (my - panY) * (newZoom / zoom);
  zoom = newZoom;

  updateTransform();
}

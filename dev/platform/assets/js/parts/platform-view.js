function updateTransform() {
  viewport.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom})`;
  updateGrid();
  updateZoomDisplay();
  if (typeof renderLinks === "function") renderLinks();
}

function updateGrid() {
  canvas.style.setProperty("--platform-canvas-grid-sub", `${15 * zoom}px`);
  canvas.style.setProperty("--platform-canvas-grid-main-size", `${150 * zoom}px`);
  canvas.style.backgroundPosition = `${panX}px ${panY}px`;
}

function updateZoomDisplay() {
  if (zoomLevelElement) {
    zoomLevelElement.textContent = `${Math.round(zoom * 100)}%`;
  }
}

function fitView() {
  if (!state.cards.length) {
    panX = 0;
    panY = 0;
    zoom = 1;
    updateTransform();
    return;
  }

  const nodeWidth = 280;
  const nodeHeight = 160;

  let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
  state.cards.forEach(card => {
    const x = card.x || 80;
    const y = card.y || 80;
    if (x < minX) minX = x;
    if (y < minY) minY = y;
    if (x + nodeWidth > maxX) maxX = x + nodeWidth;
    if (y + nodeHeight > maxY) maxY = y + nodeHeight;
  });

  const cw = canvas.clientWidth;
  const ch = canvas.clientHeight;
  const contentW = maxX - minX;
  const contentH = maxY - minY;
  const pad = 80;

  const sx = (cw - pad * 2) / contentW;
  const sy = (ch - pad * 2) / contentH;
  zoom = Math.min(sx, sy, 2);
  zoom = Math.max(zoom, 0.1);

  const cx = (minX + maxX) / 2;
  const cy = (minY + maxY) / 2;
  panX = cw / 2 - cx * zoom;
  panY = ch / 2 - cy * zoom;

  updateTransform();
}

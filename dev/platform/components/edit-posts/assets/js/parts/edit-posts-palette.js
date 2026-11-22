function togglePalette() {
  if (!paletteVisible) {
    if (!paletteListEl.children.length) renderPalette();
    paletteEl.style.display = 'block';
    paletteVisible = true;
  } else {
    paletteEl.style.display = 'none';
    paletteVisible = false;
  }
}

function renderPalette(filter) {
  paletteListEl.innerHTML = '';
  const q = (filter || '').toLowerCase();
  const filtered = components.filter(c => c.name.toLowerCase().includes(q) || c.description.toLowerCase().includes(q));

  filtered.forEach(c => {
    const el = document.createElement('div');
    el.className = 'platform-ep-palette-item';
    el.draggable = true;
    el.innerHTML = `<span class="platform-ep-palette-item-name">${escapeHtml(c.name)}</span><span class="platform-ep-palette-item-desc">${escapeHtml(c.description || '')}</span>`;

    el.addEventListener('dragstart', (e) => {
      e.dataTransfer.effectAllowed = 'copy';
      e.dataTransfer.setData('text/plain', 'new:' + c.name);
      el.classList.add('platform-ep-dragging');
    });

    el.addEventListener('dragend', () => {
      el.classList.remove('platform-ep-dragging');
    });

    el.addEventListener('click', () => {
      addComponentToContent(c.name);
    });

    paletteListEl.appendChild(el);
  });

  if (!filtered.length) {
    paletteListEl.innerHTML = '<div style="color:var(--platform-color-text-secondary);font-size:12px;padding:12px;text-align:center">' + escapeHtml(tr('editPosts.noComponentsMatch', 'No components match.')) + '</div>';
  }
}

function addComponentToContent(name) {
  if (!currentPost) return;
  if (!currentPost.content) currentPost.content = [];
  currentPost.content.push({ component: name, data: {} });
  markDirty();
  renderContent();
  setStatus(tr('editPosts.added', 'Added: {name}', { name }));
}

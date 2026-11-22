function wireContentDrag(el, index) {
  el.addEventListener('dragstart', (e) => {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', String(index));
    el.classList.add('platform-ep-dragging');
  });

  el.addEventListener('dragend', () => {
    el.classList.remove('platform-ep-dragging');
    contentListEl.querySelectorAll('.platform-ep-drag-over').forEach(e => e.classList.remove('platform-ep-drag-over'));
  });

  el.addEventListener('dragover', (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    contentListEl.querySelectorAll('.platform-ep-drag-over').forEach(e => e.classList.remove('platform-ep-drag-over'));
    el.classList.add('platform-ep-drag-over');
  });

  el.addEventListener('dragleave', () => {
    el.classList.remove('platform-ep-drag-over');
  });

  el.addEventListener('drop', (e) => {
    e.preventDefault();
    el.classList.remove('platform-ep-drag-over');
    const fromIdx = parseInt(e.dataTransfer.getData('text/plain'));
    const toIdx = index;
    if (fromIdx === toIdx) return;
    const items = currentPost.content || [];
    const [moved] = items.splice(fromIdx, 1);
    items.splice(toIdx, 0, moved);
    markDirty();
    renderContent();
  });
}

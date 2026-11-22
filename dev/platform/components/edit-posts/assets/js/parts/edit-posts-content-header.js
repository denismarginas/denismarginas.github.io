function buildContentHeader(compName, isTopLevel) {
  const header = document.createElement('div');
  header.className = 'platform-ep-content-item-header';
  header.innerHTML = `
    ${isTopLevel ? '<span class="platform-ep-content-item-drag">&#9776;</span>' : '<span class="platform-ep-content-item-drag" style="visibility:hidden">&#9776;</span>'}
    <span class="platform-ep-content-item-name">${escapeHtml(compName)}</span>
    <div class="platform-ep-content-item-actions">
      <button class="platform-button platform-button-sm platform-button-ghost" data-ep-action="remove-content" style="color:var(--platform-danger)">${PlatformSvg.render('close', { size: 14 })}</button>
    </div>
  `;
  return header;
}

function buildAddChildRow(item) {
  const addRow = document.createElement('div');
  addRow.className = 'platform-ep-add-child-row';

  const addBtn = document.createElement('button');
  addBtn.className = 'platform-button platform-button-sm platform-button-ghost';
  addBtn.innerHTML = PlatformSvg.render('add', { size: 14 }) + '<span>Add Child</span>';
  addRow.appendChild(addBtn);

  const selector = document.createElement('select');
  selector.className = 'platform-ep-input';
  selector.style.cssText = 'display:none;width:auto;min-width:160px';
  addRow.appendChild(selector);

  function populateSelector() {
    selector.innerHTML = '<option value="">-- select --</option>' +
      components.map(c => '<option value="' + escapeHtml(c.name) + '">' + escapeHtml(c.name) + '</option>').join('');
  }

  addBtn.addEventListener('click', () => {
    populateSelector();
    addBtn.style.display = 'none';
    selector.style.display = 'inline-block';
    selector.focus();
  });

  selector.addEventListener('change', () => {
    if (!selector.value) return;
    if (!item.children) item.children = [];
    item.children.push({ component: selector.value, data: {} });
    selector.value = '';
    selector.style.display = 'none';
    addBtn.style.display = '';
    markDirty();
    renderContent();
  });

  selector.addEventListener('blur', () => {
    setTimeout(() => {
      selector.style.display = 'none';
      addBtn.style.display = '';
    }, 200);
  });

  return addRow;
}

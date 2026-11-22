function buildAddFieldRow(item) {
  const addFieldRow = document.createElement('div');
  addFieldRow.className = 'platform-ep-add-field-row';

  const addFieldBtn = document.createElement('button');
  addFieldBtn.className = 'platform-button platform-button-sm platform-button-ghost';
  addFieldBtn.innerHTML = PlatformSvg.render('add', { size: 14 }) + '<span>Add Field</span>';
  addFieldRow.appendChild(addFieldBtn);

  const fieldKeyInput = document.createElement('input');
  fieldKeyInput.className = 'platform-ep-input';
  fieldKeyInput.placeholder = 'key';
  fieldKeyInput.style.cssText = 'display:none;width:100px';

  const fieldValInput = document.createElement('input');
  fieldValInput.className = 'platform-ep-input';
  fieldValInput.placeholder = 'value';
  fieldValInput.style.cssText = 'display:none;flex:1';

  const confirmBtn = document.createElement('button');
  confirmBtn.className = 'platform-button platform-button-sm platform-button-primary';
  confirmBtn.innerHTML = PlatformSvg.render('check', { size: 14 }) + '<span>Add</span>';
  confirmBtn.style.cssText = 'display:none';

  addFieldBtn.addEventListener('click', () => {
    addFieldBtn.style.display = 'none';
    fieldKeyInput.style.display = '';
    fieldValInput.style.display = '';
    confirmBtn.style.display = '';
    fieldKeyInput.value = '';
    fieldValInput.value = '';
    fieldKeyInput.focus();
  });

  function cancelAddField() {
    addFieldBtn.style.display = '';
    fieldKeyInput.style.display = 'none';
    fieldValInput.style.display = 'none';
    confirmBtn.style.display = 'none';
  }

  confirmBtn.addEventListener('click', () => {
    const k = fieldKeyInput.value.trim();
    if (!k) return;
    if (!item.data) item.data = {};
    item.data[k] = fieldValInput.value;
    cancelAddField();
    markDirty();
    renderContent();
  });

  fieldKeyInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') confirmBtn.click();
    if (e.key === 'Escape') cancelAddField();
  });
  fieldValInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') confirmBtn.click();
    if (e.key === 'Escape') cancelAddField();
  });

  addFieldRow.appendChild(fieldKeyInput);
  addFieldRow.appendChild(fieldValInput);
  addFieldRow.appendChild(confirmBtn);
  return addFieldRow;
}

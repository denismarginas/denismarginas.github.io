async function loadPost(postId) {
  setStatus(tr('editPosts.loadingPostTypes', 'Loading post...'));
  const data = await apiFetch(`${apiUrl}?post_id=${encodeURIComponent(postId)}`);
  currentPost = data.post;
  currentFile = data.file;
  originalPostId = currentPost._id || currentPost.post_id;
  renderEditor();
  editorEl.style.display = 'flex';
  setStatus(tr('editPosts.postLoaded', 'Post loaded'));
}

function renderEditor() {
  if (!currentPost) return;
  const jsonToggle = epRoot.querySelector('[data-ep-toggle-json]');
  if (jsonToggle) jsonToggle.checked = false;
  const jsonEditorEl = epRoot.querySelector('[data-ep-json-editor]');
  if (jsonEditorEl) jsonEditorEl.style.display = 'none';
  jsonEditorInstance = null;
  if (fieldsContainer) fieldsContainer.style.display = '';

  postTitleEl.textContent = currentPost.data?.title || currentPost.data?.seo?.title || currentPost.title || '(untitled)';

  postIdEl.innerHTML = '';
  const idInput = document.createElement('input');
  idInput.className = 'platform-ep-input';
  idInput.style.cssText = 'width:auto;font-size:11px;padding:2px 6px;min-width:180px';
  idInput.value = currentPost._id || currentPost.post_id;
  idInput.addEventListener('change', () => {
    currentPost._id = idInput.value;
    if (!currentPost.post_id) currentPost.post_id = idInput.value;
    updateViewButton();
    markDirty();
  });
  postIdEl.appendChild(idInput);

  updateViewButton();
  const viewBtn = epRoot.querySelector('[data-ep-action="view"]');
  if (viewBtn) viewBtn.style.display = 'inline-flex';

  renderFields();
  renderContent();
}

function updateViewButton() {
  const viewBtn = epRoot.querySelector('[data-ep-action="view"]');
  if (viewBtn && currentPost) {
    viewBtn.href = resolveApiUrl('/preview/?_id=' + encodeURIComponent(currentPost._id || currentPost.post_id));
  }
}

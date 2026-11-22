document.addEventListener('DOMContentLoaded', async () => {
  epRoot = document.querySelector('[data-ep-root]');
  if (!epRoot) return;

  apiUrl = resolveApiUrl('/api/posts.php');
  componentsApiUrl = resolveApiUrl('/api/list-components.php');
  stringsUrl = resolveApiUrl('/data/strings.json');

  statusElement = epRoot.querySelector('[data-ep-status]') || epRoot.querySelector('[data-platform-status]');
  postTypesEl = epRoot.querySelector('[data-ep-post-types]');
  postListWrap = epRoot.querySelector('[data-ep-post-list-wrap]');
  postListEl = epRoot.querySelector('[data-ep-post-list]');
  searchInput = epRoot.querySelector('[data-ep-search]');
  routableWrap = epRoot.querySelector('[data-ep-routable-wrap]');
  routableInput = epRoot.querySelector('[data-ep-routable]');
  editorEl = epRoot.querySelector('[data-ep-editor]');
  postTitleEl = epRoot.querySelector('[data-ep-post-title]');
  postIdEl = epRoot.querySelector('[data-ep-post-id]');
  fieldsContainer = epRoot.querySelector('[data-ep-fields-container]');
  contentListEl = epRoot.querySelector('[data-ep-content-list]');
  paletteEl = epRoot.querySelector('[data-ep-palette]');
  paletteListEl = epRoot.querySelector('[data-ep-palette-list]');
  paletteSearchInput = epRoot.querySelector('[data-ep-palette-search]');

  const jsonEditorEl = epRoot.querySelector('[data-ep-json-editor]');
  const jsonToggle = epRoot.querySelector('[data-ep-toggle-json]');
  if (jsonToggle) {
    jsonToggle.addEventListener('change', () => {
      if (jsonToggle.checked) {
        if (!currentPost) { jsonToggle.checked = false; return; }
        fieldsContainer.style.display = 'none';
        jsonEditorEl.style.display = '';
        jsonEditorInstance = new JsonEditor(jsonEditorEl, currentPost, {
          onChange: (val) => { currentPost = val; markDirty(); },
        });
        setStatus(tr('editPosts.jsonModeOn', 'JSON mode'));
      } else {
        jsonEditorEl.style.display = 'none';
        jsonEditorInstance = null;
        fieldsContainer.style.display = '';
        renderEditor();
        setStatus(tr('editPosts.jsonModeOff', 'Standard mode'));
      }
    });
  }

  epRoot.querySelectorAll('[data-ep-action]').forEach(btn => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.epAction;
      if (action === 'back') window.location.href = postListUrl();
      if (action === 'save') savePost();
      if (action === 'duplicate') duplicatePost();
      if (action === 'delete') deletePost();
      if (action === 'toggle-components') togglePalette();
      if (action === 'close-palette') { paletteEl.style.display = 'none'; paletteVisible = false; }
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', () => renderPostList());
  }

  if (routableInput) {
    routableInput.addEventListener('change', () => {
      const typeDef = postTypes.find(t => t.post_type === currentPostType);
      if (!typeDef) return;
      updateTypeRoutable('post', currentPostType, routableInput.checked);
    });
  }

  if (paletteSearchInput) {
    paletteSearchInput.addEventListener('input', () => renderPalette(paletteSearchInput.value));
  }

  if (contentListEl) {
    contentListEl.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
    });

    contentListEl.addEventListener('drop', (e) => {
      e.preventDefault();
      const data = e.dataTransfer.getData('text/plain');
      if (data && data.startsWith('new:')) {
        const name = data.substring(4);
        addComponentToContent(name);
      }
    });
  }

  setStatus(tr('editPosts.loading', 'Loading...'));
  try {
    await loadEditPostStrings();
    await Promise.all([loadPostTypes(), loadComponents()]);
    await openRequestedPost();
  } catch (e) {
    setStatus(tr('editPosts.errorLoading', 'Error loading data: {message}', { message: e.message }));
    console.error('Edit posts init error:', e);
  }
});

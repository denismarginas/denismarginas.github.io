async function loadPostTypes() {
  try {
    setStatus(tr('editPosts.loadingPostTypes', 'Loading post types...'));
    const data = await apiFetch(apiUrl);
    postTypes = data.types || [];
    renderPostTypes();
    setStatus(tr('editPosts.ready', 'Ready'));
  } catch (e) {
    setStatus(tr('editPosts.error', 'Error: {message}', { message: e.message }));
    postTypesEl.innerHTML = '<div class="platform-list-item" style="color:var(--platform-color-status-error)">' + escapeHtml(tr('editPosts.error', 'Error: {message}', { message: e.message })) + '</div>';
  }
}

function renderPostTypes() {
  postTypesEl.innerHTML = '';
  if (!postTypes.length) {
    postTypesEl.innerHTML = '<div class="platform-list-item" style="color:var(--platform-color-text-secondary)">' + escapeHtml(tr('editPosts.noPostTypes', 'No post types found. Configure SelectFile cards in the workflow first.')) + '</div>';
    return;
  }
  postTypes.forEach(t => {
    const item = document.createElement('div');
    item.className = 'platform-list-item platform-ep-post-type-item' + (t.post_type === currentPostType ? ' is-active' : '');
    item.dataset.epPostType = t.post_type;
    item.innerHTML = `<span>${escapeHtml(t.title || t.post_type)}</span><span class="platform-ep-post-type-count">${t.count}</span>`;
    item.addEventListener('click', () => selectPostType(t.post_type));
    postTypesEl.appendChild(item);
  });
}

async function selectPostType(postType) {
  currentPostType = postType;
  currentPost = null;
  editorEl.style.display = 'none';

  const typeDef = postTypes.find(t => t.post_type === postType);
  const globalContentPath = typeDef?.global_content_path || '';
  const globalImgPath = typeDef?.global_img_path || '';
  const globalVidPath = typeDef?.global_vid_path || '';
  const imgDir = globalImgPath.split('/').pop() || postType;
  const vidDir = globalVidPath.split('/').pop() || postType;
  epRoot.dataset.globalContentDir = globalContentPath.split('/').filter(Boolean).pop() || '';
  epRoot.dataset.globalImgDir = imgDir;
  epRoot.dataset.globalVidDir = vidDir;

  if (routableInput) routableInput.checked = !!typeDef?.routable;
  if (routableWrap) routableWrap.style.display = 'flex';

  renderPostTypes();
  setStatus(tr('editPosts.loadingPostTypes', 'Loading...'));
  const data = await apiFetch(`${apiUrl}?post_type=${encodeURIComponent(postType)}`);
  posts = data.posts || [];
  currentFile = data.file || '';
  renderPostList();
  postListWrap.style.display = 'block';
  setStatus(tr('editPosts.postsLoaded', '{count} {type} posts loaded', { count: posts.length, type: postType }));
}

function renderPostList() {
  postListEl.innerHTML = '';
  const q = (searchInput.value || '').toLowerCase();
  const filtered = posts.filter(p => p.title.toLowerCase().includes(q) || (p._id || p.post_id || '').toLowerCase().includes(q));
  if (!filtered.length) {
    postListEl.innerHTML = '<div class="platform-list-item" style="color:var(--platform-color-text-secondary)">' + escapeHtml(tr('editPosts.noPostsMatch', 'No posts match your search.')) + '</div>';
    return;
  }
  filtered.forEach(p => {
    const postId = p._id || p.post_id;
    const item = document.createElement('div');
    item.className = 'platform-list-item platform-ep-post-item' + (currentPost && (currentPost._id || currentPost.post_id) === postId ? ' is-active' : '');
    item.innerHTML = `<div class="platform-ep-post-item-title">${escapeHtml(p.title || '(no title)')}</div><div class="platform-ep-post-item-id">${escapeHtml(postId)}</div>`;
    item.addEventListener('click', () => loadPost(postId));
    postListEl.appendChild(item);
  });
}

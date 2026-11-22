async function loadComponents() {
  try {
    const data = await apiFetch(componentsApiUrl);
    components = data.components || [];
  } catch (e) {
    components = [];
  }
}

async function updateTypeRoutable(kind, typeKey, routable) {
  try {
    const res = await fetch(resolveApiUrl('/api/json-file.php') + '?path=settings/data_settings_types.json');
    const result = await res.json();
    if (!result.ok) return;
    const registry = result.content || {};
    if (registry[kind] && registry[kind][typeKey]) {
      registry[kind][typeKey].routable = !!routable;
    }
    await fetch(resolveApiUrl('/api/json-file.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ path: 'settings/data_settings_types.json', content: registry }),
    });
  } catch (e) {
    console.error('updateTypeRoutable error:', e);
  }
}

function markDirty() {
}

async function savePost() {
  if (!currentPost || !currentFile) {
    setStatus('No post to save');
    return;
  }
  setStatus(tr('editPosts.saving', 'Saving...'));
  try {
    const data = await apiFetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ _id: currentPost._id || currentPost.post_id, file: currentFile, data: currentPost, original__id: originalPostId, create: isNewPost }),
    });
    isNewPost = false;
    originalPostId = currentPost._id || currentPost.post_id;
    PlatformEntry.setId(originalPostId);
    setStatus(tr('editPosts.saved', 'Post saved'));
  } catch (e) {
    setStatus(tr('editPosts.saveFailed', 'Save failed: {message}', { message: e.message }));
  }
}

async function deletePost() {
  if (!currentPost || !currentFile) return;
  if (!confirm(tr('editPosts.confirmDelete', 'Delete this post permanently?'))) return;
  const postId = originalPostId || currentPost._id || currentPost.post_id;
  if (isNewPost) {
    window.location.href = postListUrl();
    return;
  }
  try {
    await apiFetch(apiUrl, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ _id: postId, file: currentFile }),
    });
    setStatus(tr('editPosts.deleted', 'Post deleted'));
    window.location.href = postListUrl();
  } catch (e) {
    setStatus(tr('editPosts.deleteFailed', 'Delete failed: {message}', { message: e.message }));
  }
}

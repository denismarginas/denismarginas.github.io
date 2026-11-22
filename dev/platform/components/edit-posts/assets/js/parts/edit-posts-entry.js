let isNewPost = false;

function postListUrl() {
  return PlatformEntry.listUrl('post', currentPostType || PlatformEntry.type());
}

async function openRequestedPost() {
  let type = PlatformEntry.type();
  const id = PlatformEntry.id();

  try {
    if (!postTypes.some(t => t.post_type === type) && id) {
      const found = await apiFetch(`${apiUrl}?post_id=${encodeURIComponent(id)}`);
      type = found.post_type || '';
    }
    if (postTypes.some(t => t.post_type === type)) await selectPostType(type);

    if (PlatformEntry.isNew()) {
      createNewPost();
      return;
    }
    await loadPost(id);
  } catch (e) {
    PlatformEntry.showMissing(
      epRoot.querySelector('.platform-ep-layout'),
      tr('editPosts.notFound', 'No post with _id "{id}" ({message}).', { id, message: e.message }),
      postListUrl(),
    );
  }
}

function createNewPost() {
  const typeDef = postTypes.find(t => t.post_type === currentPostType);
  const id = (currentPostType || 'post') + '-' + Date.now().toString(36);
  const singular = (typeDef?.title || currentPostType || 'Post').replace(/s$/, '');

  currentPost = {
    _id: id,
    settings: { render: true },
    data: {
      seo: { title: 'New ' + singular, description: '', slug: id, index: true },
      date: { publish: new Date().toISOString().slice(0, 10) },
    },
    content: [],
  };
  currentFile = currentFile || typeDef?.file || '';
  originalPostId = '';
  isNewPost = true;
  renderEditor();
  editorEl.style.display = 'flex';
  setStatus(tr('editPosts.newPost', 'New post: fill it in and save to create it'));
}

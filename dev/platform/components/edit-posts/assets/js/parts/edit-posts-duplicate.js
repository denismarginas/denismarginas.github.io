function duplicatePost() {
  if (!currentPost) return;
  const clone = JSON.parse(JSON.stringify(currentPost));
  clone._id = (currentPost._id || currentPost.post_id) + '-copy';
  if (clone.post_id) clone.post_id = clone._id;
  if (clone.data?.seo?.slug) clone.data.seo.slug += '-copy';
  if (clone.data?.seo?.title) clone.data.seo.title += ' (copy)';

  currentPost = clone;
  originalPostId = '';
  isNewPost = true;
  renderEditor();
  setStatus(tr('editPosts.duplicated', 'Duplicated as: {id} (save to create it)', { id: clone._id }));
}

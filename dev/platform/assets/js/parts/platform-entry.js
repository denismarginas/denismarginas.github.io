window.PlatformEntry = (() => {
  const params = new URLSearchParams(window.location.search);
  const EDIT_PAGES = { post: 'edit-posts', taxonomy: 'edit-taxonomies', collection: 'edit-collections' };

  return {
    type: () => params.get('type') || '',
    id: () => params.get('_id') || '',
    isNew: () => params.get('new') === '1',

    listUrl(kind, type) {
      return '?page=content&kind=' + encodeURIComponent(kind) + (type ? '&type=' + encodeURIComponent(type) : '');
    },

    editUrl(kind, type, id) {
      return '?page=' + (EDIT_PAGES[kind] || 'edit-posts') + '&type=' + encodeURIComponent(type)
        + (id ? '&_id=' + encodeURIComponent(id) : '&new=1');
    },

    setId(id) {
      try {
        const url = new URL(window.location.href);
        url.searchParams.set('_id', id);
        url.searchParams.delete('new');
        window.history.replaceState(null, '', url.toString());
      } catch (e) {
      }
    },

    showMissing(root, message, backUrl, backLabel) {
      const card = document.createElement('div');
      card.className = 'platform-card platform-entry-missing';
      card.innerHTML = '<h2 class="platform-card-title"></h2><p class="platform-empty"></p>'
        + '<a class="platform-button platform-button-sm platform-button-ghost"></a>';
      card.querySelector('h2').textContent = 'Not found';
      card.querySelector('p').textContent = message;
      const link = card.querySelector('a');
      link.href = backUrl;
      link.textContent = backLabel || 'Back to the list';
      root.innerHTML = '';
      root.appendChild(card);
    },
  };
})();

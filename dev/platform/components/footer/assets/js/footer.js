document.addEventListener('keydown', (event) => {
  if (document.querySelector('[data-cs-shortcut="k"]')) return;
  const target = event.target;
  const typing = target && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName));
  if ((event.ctrlKey || event.metaKey) && !event.shiftKey && !event.altKey && event.key.toLowerCase() === 'k' && !typing) {
    const link = document.querySelector('[data-footer-search]');
    if (!link) return;
    event.preventDefault();
    window.location.href = link.getAttribute('href');
  }
});

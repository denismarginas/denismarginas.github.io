class Svg {
  static render({ svg = '', icon = '', icons = {}, className = 'svg-icon' } = {}) {
    let markup = svg;

    if (!markup && icon) {
      markup = icons[icon] || icons[icon.replace(/-/g, '_')] || icons[icon.replace(/_/g, '-')] || '';
    }

    if (!markup) return null;

    const template = document.createElement('template');
    template.innerHTML = markup.trim();

    const svgEl = template.content.querySelector('svg');
    if (!svgEl) return null;

    svgEl.classList.add(className);
    svgEl.setAttribute('aria-hidden', 'true');

    return svgEl;
  }
}

window.Svg = window.Svg || Svg;
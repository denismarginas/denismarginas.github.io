class Hero {
  static TEMPLATE_URL = 'components/hero/assets/html/template.html';

  static render({ title = '', description = '', background = '', layout = 'standard', className = 'hero' } = {}) {
    const block = document.createElement('div');
    block.className = className;
    block.dataset.layout = layout;

    const bg = document.createElement('div');
    bg.className = 'hero-img-bg';
    if (background) {
      bg.style.backgroundImage = `url("${background}")`;
    }
    block.appendChild(bg);

    if (title || description) {
      const textContent = document.createElement('div');
      textContent.className = 'container-sm';

      if (title) {
        const heading = document.createElement('h2');
        heading.textContent = title;
        textContent.appendChild(heading);
      }

      if (description) {
        const paragraph = document.createElement('p');
        paragraph.textContent = description;
        textContent.appendChild(paragraph);
      }

      block.appendChild(textContent);
    }

    return block;
  }
}

window.Hero = window.Hero || Hero;

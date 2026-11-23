(function () {
  var SEARCH_MIN = 8;
  var uid = 0;
  var boxes = [];
  var escapedAt = 0;

  function fold(text) {
    var s = String(text || '').toLowerCase();
    if (s.normalize) s = s.normalize('NFD').replace(/[̀-ͯ]/g, '');
    return s.replace(/\s+/g, ' ').trim();
  }

  function values(select) {
    return Array.prototype.filter.call(select.options, function (o) { return o.selected && o.value !== ''; })
      .map(function (o) { return o.value; });
  }

  function make(tag, className) {
    var el = document.createElement(tag);
    if (className) el.className = className;
    return el;
  }

  function labelFor(select) {
    if (!select.id) return null;
    var id = window.CSS && CSS.escape ? CSS.escape(select.id) : select.id;
    return document.querySelector('label[for="' + id + '"]');
  }

  function searchable(select) {
    var mode = select.getAttribute('data-select-box-search');
    if (mode === 'on') return true;
    if (mode === 'off') return false;
    var min = parseInt(select.getAttribute('data-search-min'), 10);
    return select.options.length >= (isNaN(min) ? SEARCH_MIN : min);
  }

  function build(select) {
    var id = select.id || ('select-box-search-' + (++uid));
    select.id = id;
    var multiple = select.multiple;
    var label = labelFor(select);
    if (label && !label.id) label.id = id + '-label';

    var box = make('div', 'sbs' + (multiple ? ' sbs-multiple' : ''));
    select.parentNode.insertBefore(box, select);
    box.appendChild(select);
    select.classList.add('sbs-native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    var control = make('button', 'sbs-control');
    control.type = 'button';
    control.setAttribute('aria-haspopup', 'listbox');
    control.setAttribute('aria-expanded', 'false');
    control.setAttribute('aria-controls', id + '-list');
    control.innerHTML = '<span class="sbs-value" id="' + id + '-value"></span><span class="sbs-count" hidden></span><span class="sbs-arrow" aria-hidden="true"></span>';
    if (label) control.setAttribute('aria-labelledby', label.id + ' ' + id + '-value');

    var panel = make('div', 'sbs-panel');
    panel.hidden = true;

    var input = null;
    if (searchable(select)) {
      input = make('input', 'sbs-input');
      input.type = 'search';
      input.autocomplete = 'off';
      input.spellcheck = false;
      input.placeholder = select.getAttribute('data-label-search') || '';
      input.setAttribute('role', 'combobox');
      input.setAttribute('aria-autocomplete', 'list');
      input.setAttribute('aria-expanded', 'true');
      input.setAttribute('aria-controls', id + '-list');
      if (label) input.setAttribute('aria-labelledby', label.id);
      panel.appendChild(input);
    }

    var list = make('ul', 'sbs-list');
    list.id = id + '-list';
    list.tabIndex = -1;
    list.setAttribute('role', 'listbox');
    if (multiple) list.setAttribute('aria-multiselectable', 'true');
    if (label) list.setAttribute('aria-labelledby', label.id);
    panel.appendChild(list);

    var empty = make('p', 'sbs-empty');
    empty.textContent = select.getAttribute('data-label-empty') || '';
    empty.hidden = true;
    panel.appendChild(empty);

    box.appendChild(control);
    box.appendChild(panel);

    var items = Array.prototype.filter.call(select.options, function (o) { return !(multiple && o.value === ''); })
      .map(function (option, i) {
        var li = make('li', 'sbs-option');
        li.id = id + '-option-' + i;
        li.setAttribute('role', 'option');
        li.innerHTML = '<span class="sbs-check" aria-hidden="true"></span><span class="sbs-text"></span>';
        var style = option.getAttribute('style');
        if (style) li.lastChild.setAttribute('style', style);
        li._option = option;
        list.appendChild(li);
        return li;
      });

    var state = { active: null };
    var focusTarget = input || list;

    function visible() {
      return items.filter(function (li) { return !li.hidden && !li._option.disabled; });
    }

    function setActive(li) {
      if (state.active) state.active.classList.remove('is-active');
      state.active = li || null;
      if (!li) {
        focusTarget.removeAttribute('aria-activedescendant');
        return;
      }
      li.classList.add('is-active');
      focusTarget.setAttribute('aria-activedescendant', li.id);
      if (li.offsetTop < list.scrollTop) list.scrollTop = li.offsetTop;
      else if (li.offsetTop + li.offsetHeight > list.scrollTop + list.clientHeight) list.scrollTop = li.offsetTop + li.offsetHeight - list.clientHeight;
    }

    function filter(query) {
      var words = fold(query).split(' ').filter(Boolean);
      items.forEach(function (li) {
        var text = fold(li._option.textContent);
        li.hidden = !words.every(function (word) { return text.indexOf(word) !== -1; });
      });
      var shown = visible();
      empty.hidden = shown.length > 0;
      if (shown.indexOf(state.active) === -1) setActive(shown[0]);
    }

    function sync() {
      var chosen = Array.prototype.filter.call(select.options, function (o) { return o.selected && !(multiple && o.value === ''); });
      items.forEach(function (li) {
        var on = li._option.selected;
        li.lastChild.textContent = li._option.textContent;
        li.classList.toggle('is-selected', on);
        li.setAttribute('aria-selected', on ? 'true' : 'false');
        if (li._option.disabled) li.setAttribute('aria-disabled', 'true');
        else li.removeAttribute('aria-disabled');
      });

      var value = control.querySelector('.sbs-value');
      var count = control.querySelector('.sbs-count');
      var placeholder = chosen.length === 0;
      value.textContent = placeholder
        ? (select.getAttribute('data-placeholder') || '')
        : chosen.map(function (o) { return o.textContent; }).join(', ');
      value.classList.toggle('is-placeholder', placeholder);
      var style = !placeholder && chosen.length === 1 ? chosen[0].getAttribute('style') : null;
      if (style) value.setAttribute('style', style);
      else value.removeAttribute('style');
      count.textContent = chosen.length;
      count.hidden = !multiple || chosen.length < 2;

      box.classList.toggle('is-active', values(select).length > 0);
      box.classList.toggle('is-disabled', select.disabled);
      control.disabled = select.disabled;
    }

    function open() {
      if (select.disabled || !panel.hidden) return;
      boxes.forEach(function (other) { if (other.box !== box) other.close(false); });
      sync();
      panel.hidden = false;
      box.classList.add('is-open');
      control.setAttribute('aria-expanded', 'true');
      if (input) input.value = '';
      filter('');
      var first = items.filter(function (li) { return li._option.selected; })[0];
      setActive(first || visible()[0]);
      place();
      focusTarget.focus({ preventScroll: true });
    }

    function close(focusControl) {
      if (panel.hidden) return;
      panel.hidden = true;
      box.classList.remove('is-open', 'sbs-up');
      control.setAttribute('aria-expanded', 'false');
      if (focusControl) control.focus();
    }

    function place() {
      box.classList.remove('sbs-up');
      var rect = panel.getBoundingClientRect();
      var above = box.getBoundingClientRect().top;
      if (rect.bottom > window.innerHeight && above > rect.height + 8) box.classList.add('sbs-up');
      else if (panel.scrollIntoView) panel.scrollIntoView({ block: 'nearest' });
    }

    function change() {
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function choose(li) {
      if (!li || li._option.disabled) return;
      var option = li._option;
      if (multiple) {
        option.selected = !option.selected;
        change();
        return;
      }
      var changed = !option.selected;
      select.value = option.value;
      if (changed) change();
      close(true);
    }

    function removeLast() {
      var chosen = Array.prototype.filter.call(select.options, function (o) { return o.selected; });
      if (!chosen.length) return;
      chosen[chosen.length - 1].selected = false;
      change();
    }

    function move(step) {
      var shown = visible();
      if (!shown.length) return;
      var index = shown.indexOf(state.active);
      index = index === -1 ? 0 : Math.min(shown.length - 1, Math.max(0, index + step));
      setActive(shown[index]);
    }

    control.addEventListener('click', function () {
      if (panel.hidden) open();
      else close(true);
    });

    control.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        open();
        return;
      }
      if (input && event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
        event.preventDefault();
        open();
        input.value = event.key;
        filter(input.value);
      }
    });

    panel.addEventListener('keydown', function (event) {
      var key = event.key;
      if (key === 'ArrowDown' || key === 'ArrowUp') {
        event.preventDefault();
        move(key === 'ArrowDown' ? 1 : -1);
      } else if ((key === 'Home' || key === 'End') && event.target === list) {
        event.preventDefault();
        var shown = visible();
        setActive(key === 'Home' ? shown[0] : shown[shown.length - 1]);
      } else if (key === 'Enter' || (key === ' ' && event.target === list)) {
        event.preventDefault();
        choose(state.active);
      } else if (key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        escapedAt = Date.now();
        close(true);
      } else if (key === 'Tab') {
        close(false);
      } else if (key === 'Backspace' && multiple && input && input.value === '') {
        event.preventDefault();
        removeLast();
      }
    });

    if (input) input.addEventListener('input', function () { filter(input.value); });

    list.addEventListener('mousedown', function (event) {
      if (input) event.preventDefault();
    });
    list.addEventListener('click', function (event) {
      var li = event.target.closest('.sbs-option');
      if (li) choose(li);
    });
    list.addEventListener('mousemove', function (event) {
      var li = event.target.closest('.sbs-option');
      if (li && li !== state.active && !li._option.disabled) setActive(li);
    });

    box.addEventListener('focusout', function () {
      setTimeout(function () {
        if (!box.contains(document.activeElement)) close(false);
      }, 0);
    });

    select.addEventListener('focus', function () { control.focus(); });
    if (label) {
      label.addEventListener('click', function (event) {
        event.preventDefault();
        control.focus();
      });
    }

    sync();
    return { box: box, select: select, sync: sync, close: close, isOpen: function () { return !panel.hidden; } };
  }

  function init(root) {
    Array.prototype.forEach.call((root || document).querySelectorAll('select[data-select-box-search]'), function (select) {
      if (select.classList.contains('sbs-native')) return;
      boxes.push(build(select));
    });
  }

  function refresh(root) {
    boxes.forEach(function (item) {
      if (!root || root === item.select || (root.contains && root.contains(item.select))) item.sync();
    });
  }

  document.addEventListener('change', function (event) {
    var form = event.target && event.target.form;
    refresh(form || undefined);
  });

  document.addEventListener('reset', function (event) {
    setTimeout(function () { refresh(event.target); }, 0);
  });

  document.addEventListener('pointerdown', function (event) {
    boxes.forEach(function (item) {
      if (item.isOpen() && !item.box.contains(event.target)) item.close(false);
    });
  }, true);

  document.addEventListener('cancel', function (event) {
    var inside = boxes.filter(function (item) { return event.target.contains(item.box); });
    var open = inside.filter(function (item) { return item.isOpen(); });
    if (!open.length && !(inside.length && Date.now() - escapedAt < 300)) return;
    event.preventDefault();
    open.forEach(function (item) { item.close(true); });
  }, true);

  function start() {
    init(document);
    setTimeout(function () { refresh(); }, 0);
  }

  window.selectBoxSearch = { init: init, refresh: refresh, values: values };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

(function () {
  function labelOf(form, select) {
    var label = select.id ? form.querySelector('label[for="' + select.id + '"]') : null;
    return label ? label.textContent.replace(/[:\s]+$/, '') : select.name;
  }

  var STORE = 'ft-view:';

  function stored(name) {
    try { return window.localStorage.getItem(STORE + name); } catch (e) { return null; }
  }

  function store(name, value) {
    try {
      if (value === null) window.localStorage.removeItem(STORE + name);
      else window.localStorage.setItem(STORE + name, value);
    } catch (e) {}
  }

  function siteHeader(form) {
    var headers = document.querySelectorAll('header');
    for (var i = 0; i < headers.length; i++) {
      if (form.contains(headers[i])) continue;
      var position = getComputedStyle(headers[i]).position;
      if (position === 'fixed' || position === 'sticky') return headers[i];
    }
    return null;
  }

  function init(form, opts) {
    opts = opts || {};
    var list = opts.list || null;
    var input = opts.input || null;
    var selects = opts.selects || [];
    var amounts = Array.prototype.slice.call(form.querySelectorAll('.query-amount'));
    var badge = form.querySelector('.ft-badge');
    var chips = form.querySelector('.ft-chips');
    var clearBtn = form.querySelector('.ft-clear');
    var results = form.querySelector('.ft-results');
    var drawer = form.querySelector('.ft-drawer');
    var filtersBtn = form.querySelector('.toggle-filters');
    var drawerInput = form.querySelector('.ft-drawer-search-input');

    function syncDrawerInput() {
      if (drawerInput && input && document.activeElement !== drawerInput && drawerInput.value !== input.value) drawerInput.value = input.value;
    }

    if (drawerInput && input) {
      drawerInput.value = input.value;
      drawerInput.addEventListener('input', function () {
        input.value = drawerInput.value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
      drawerInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') event.preventDefault();
      });
      input.addEventListener('input', syncDrawerInput);
    }

    function addChip(text, onRemove) {
      var li = document.createElement('li');
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'ft-chip';
      button.setAttribute('aria-label', ((chips && chips.getAttribute('data-label-remove')) || 'Remove') + ' ' + text);
      button.innerHTML = '<span class="ft-chip-text"></span><span class="ft-chip-x" aria-hidden="true">&times;</span>';
      button.firstChild.textContent = text;
      button.addEventListener('click', onRemove);
      li.appendChild(button);
      chips.appendChild(li);
    }

    function update(shown) {
      var query = input ? input.value.trim() : '';
      syncDrawerInput();
      var active = selects.filter(function (select) { return !!select.value; });

      if (typeof shown === 'number') amounts.forEach(function (el) { el.textContent = shown; });
      if (badge) {
        badge.textContent = active.length;
        badge.hidden = active.length === 0;
      }
      form.classList.toggle('has-active-filters', active.length > 0);

      if (chips) {
        chips.textContent = '';
        active.forEach(function (select) {
          addChip(labelOf(form, select) + ': ' + select.options[select.selectedIndex].textContent, function () {
            select.value = '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
          });
        });
        if (query && input) {
          addChip('"' + query + '"', function () {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
          });
        }
      }
      if (clearBtn) clearBtn.hidden = active.length === 0 && !query;
      if (filtersBtn && drawer) filtersBtn.setAttribute('aria-expanded', drawer.open ? 'true' : 'false');
    }

    if (input) {
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && input.value) {
          event.preventDefault();
          input.value = '';
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });
    }

    if (drawer && filtersBtn) {
      var closeDrawer = function () {
        if (typeof drawer.close === 'function' && drawer.open) drawer.close();
        else drawer.removeAttribute('open');
        filtersBtn.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('ft-drawer-open');
      };
      filtersBtn.addEventListener('click', function () {
        if (typeof drawer.showModal === 'function') drawer.showModal();
        else drawer.setAttribute('open', '');
        filtersBtn.setAttribute('aria-expanded', 'true');
        document.documentElement.classList.add('ft-drawer-open');
      });
      drawer.addEventListener('close', function () {
        filtersBtn.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('ft-drawer-open');
        filtersBtn.focus();
      });
      drawer.querySelectorAll('.ft-drawer-close, .ft-apply').forEach(function (button) {
        button.addEventListener('click', closeDrawer);
      });
      drawer.addEventListener('click', function (event) {
        if (event.target === drawer) closeDrawer();
      });
    }

    var toolbar = form.querySelector('.ft-toolbar');
    var placeholder = document.createElement('div');
    placeholder.className = 'ft-sticky-placeholder';
    placeholder.setAttribute('aria-hidden', 'true');
    if (toolbar) toolbar.parentNode.insertBefore(placeholder, toolbar.nextSibling);
    var sticky = { on: false, stuck: false, frame: 0 };

    function setStuck(stuck, top) {
      if (stuck) {
        var rect = form.getBoundingClientRect();
        var viewport = document.documentElement.clientWidth;
        form.style.setProperty('--ft-stuck-top', top + 'px');
        form.style.setProperty('--ft-stuck-left', rect.left + 'px');
        form.style.setProperty('--ft-stuck-right', Math.max(0, viewport - rect.right) + 'px');
        form.style.setProperty('--ft-stuck-width', rect.width + 'px');
        if (!sticky.stuck) placeholder.style.height = toolbar.offsetHeight + 'px';
      }
      if (stuck !== sticky.stuck) {
        sticky.stuck = stuck;
        form.classList.toggle('is-stuck', stuck);
        toolbar.classList.toggle('is-stuck', stuck);
      }
    }

    function measure() {
      sticky.frame = 0;
      if (!toolbar || !sticky.on) { if (toolbar) setStuck(false); return; }
      var header = siteHeader(form);
      var top = (header ? Math.max(0, header.getBoundingClientRect().bottom) : 0) + 8;
      var anchor = (sticky.stuck ? placeholder : toolbar).getBoundingClientRect();
      var end = (list || form).getBoundingClientRect().bottom;
      setStuck(anchor.top < top && end > top + toolbar.offsetHeight + 24, top);
    }

    function queue() {
      if (!sticky.frame) sticky.frame = window.requestAnimationFrame(measure);
    }
    window.addEventListener('scroll', queue, { passive: true });
    window.addEventListener('resize', queue);

    var views = Object.assign({
      search: function (value) {
        var hide = value === 'hide';
        form.classList.toggle('ft-search-hidden', hide);
        if (hide && input && input.value) {
          input.value = '';
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
      },
      results: function (value) { if (results) results.hidden = value === 'hide'; },
      sticky: function (value) { sticky.on = value === 'yes'; }
    }, opts.views || {});
    var segments = Array.prototype.slice.call(form.querySelectorAll('.ft-segment[data-view]'));
    function syncViews() {
      segments.forEach(function (box) {
        var checked = box.querySelector('input[type="radio"]:checked');
        if (checked && views[box.dataset.view]) views[box.dataset.view](checked.value, list);
      });
      if (sticky.stuck) setStuck(false);
      queue();
    }
    segments.forEach(function (box) {
      var saved = stored(box.dataset.view);
      var radio = saved !== null ? box.querySelector('input[type="radio"][value="' + saved + '"]') : null;
      if (radio) radio.checked = true;
      box.addEventListener('change', function (event) {
        if (event.target && event.target.value) store(box.dataset.view, event.target.value);
        syncViews();
      });
    });
    form.addEventListener('reset', function () {
      segments.forEach(function (box) { store(box.dataset.view, null); });
      setTimeout(syncViews, 0);
    });
    syncViews();

    return { update: update, syncViews: syncViews };
  }

  window.filtersToolbar = { init: init };
})();

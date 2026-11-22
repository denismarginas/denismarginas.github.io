(function () {
  function fieldsOf(scope) {
    return Array.prototype.slice.call(scope.querySelectorAll('input[name], textarea[name], select[name]'))
      .filter(function (el) { return el.type !== 'hidden'; });
  }

  function validate(step) {
    var fields = fieldsOf(step);
    for (var i = 0; i < fields.length; i++) {
      if (!fields[i].checkValidity()) {
        fields[i].reportValidity();
        return false;
      }
    }
    return true;
  }

  function valueOf(el) {
    if (el.type === 'radio' || el.type === 'checkbox') return el.checked ? el.value : '';
    return (el.value || '').trim();
  }

  function init(form) {
    if (form.dataset.formReady === 'true') return;
    form.dataset.formReady = 'true';

    var steps = Array.prototype.slice.call(form.querySelectorAll('.form-step'));
    var status = form.querySelector('.form-status');
    var statusLink = form.querySelector('.form-status-link');
    if (!steps.length) return;

    var history = [steps[0].dataset.step];

    function stepEl(id) {
      for (var i = 0; i < steps.length; i++) if (steps[i].dataset.step === String(id)) return steps[i];
      return null;
    }

    function current() {
      return stepEl(history[history.length - 1]);
    }

    function show(id) {
      steps.forEach(function (s) { s.hidden = s.dataset.step !== String(id); });
      var target = stepEl(id);
      var first = target && target.querySelector('input:not([type="hidden"]), textarea, select');
      if (first && form.dataset.focus === 'true') first.focus({ preventScroll: true });
      form.dataset.focus = 'true';
      if (status) status.hidden = true;
      updateProgress(id);
    }

    var progressItems = Array.prototype.slice.call(form.querySelectorAll('.form-progress-item'));
    function updateProgress(id) {
      var active = -1;
      progressItems.forEach(function (item, i) {
        if ((item.dataset.steps || '').split(' ').indexOf(String(id)) !== -1) active = i;
      });
      if (active === -1) return;
      progressItems.forEach(function (item, i) {
        item.classList.toggle('is-active', i === active);
        item.classList.toggle('is-done', i < active);
        if (i === active) item.setAttribute('aria-current', 'step');
        else item.removeAttribute('aria-current');
      });
    }

    function resolveStep(value) {
      if (!value) return null;
      if (value.charAt(0) !== '@') return value;
      var checked = form.querySelector('input[name="' + value.slice(1) + '"]:checked');
      return checked ? checked.dataset.step || null : null;
    }

    form.addEventListener('click', function (event) {
      var next = event.target.closest('.form-next');
      var prev = event.target.closest('.form-prev');

      if (next) {
        var step = current();
        if (step && !validate(step)) return;
        var target = resolveStep(next.dataset.step);
        if (!target || !stepEl(target)) return;
        history.push(target);
        show(target);
      } else if (prev) {
        if (prev.dataset.step) {
          var index = history.lastIndexOf(prev.dataset.step);
          history = index >= 0 ? history.slice(0, index + 1) : [prev.dataset.step];
        } else if (history.length > 1) {
          history.pop();
        }
        show(history[history.length - 1]);
      }
    });

    form.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' || event.target.tagName === 'TEXTAREA') return;
      var step = current();
      var next = step && step.querySelector('.form-next');
      if (next) { event.preventDefault(); next.click(); }
    });

    function compose() {
      var finals = {};
      var isSteps = form.dataset.formType === 'steps';
      var scopes = isSteps ? history.map(stepEl).filter(Boolean) : [form];

      scopes.forEach(function (scope) {
        fieldsOf(scope).forEach(function (el) {
          var target = el.dataset.target;
          var value = valueOf(el);
          if (!target || !value) return;
          (finals[target] = finals[target] || []).push({ label: el.dataset.label || '', value: value });
        });
      });

      var params = [];
      var finalEls = isSteps
        ? Array.prototype.slice.call(form.querySelectorAll('.form-final'))
        : fieldsOf(form).filter(function (el) { return el.dataset.externalName; });

      finalEls.forEach(function (el) {
        var name = isSteps ? el.dataset.final : el.dataset.target;
        var parts = finals[name] || [];
        var lines = el.dataset.compose === 'lines';
        var value = parts.map(function (p) {
          return lines && p.label ? p.label + ': ' + p.value : p.value;
        }).join(lines ? '\n' : ', ');
        if (isSteps) el.value = value;
        if (el.dataset.externalName && value) params.push(encodeURIComponent(el.dataset.externalName) + '=' + encodeURIComponent(value));
      });

      return params;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var step = form.dataset.formType === 'steps' ? current() : form;
      if (step && !validate(step)) return;

      var params = compose();
      var base = form.dataset.externalUrl || '';
      if (!base) return;

      var url = base;
      if (form.dataset.externalType === 'google-forms') {
        url = base + (base.indexOf('?') === -1 ? '?' : '&') + 'usp=pp_url' + (params.length ? '&' + params.join('&') : '');
      }

      if (statusLink) statusLink.href = url;
      if (status) status.hidden = false;
      window.open(url, '_blank', 'noopener');
    });

    form.addEventListener('reset', function () {
      history = [steps[0].dataset.step];
      form.dataset.focus = 'false';
      show(history[0]);
    });
  }

  function initAll(scope) {
    (scope || document).querySelectorAll('form.form-item').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initAll(); });
  } else {
    initAll();
  }

  document.addEventListener('popup:open', function (event) {
    if (event.detail && event.detail.body) initAll(event.detail.body);
  });

  window.FormItem = { init: init, initAll: initAll };
})();

(function () {
  'use strict';

  function init() {
    var root = document.querySelector('[data-settings-tools]');
    if (!root) return;
    var status = root.querySelector('[data-settings-tools-status]');

    root.addEventListener('change', function (event) {
      var input = event.target.closest('[data-settings-tool]');
      if (!input) return;
      var tools = {};
      tools[input.getAttribute('data-settings-tool')] = input.checked;
      if (status) status.textContent = 'Saving...';

      fetch(root.getAttribute('data-settings-api'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ tools: tools }),
      })
        .then(function (response) { return response.json().then(function (data) { return { ok: response.ok && data.ok, data: data }; }); })
        .then(function (result) {
          if (!result.ok) throw new Error(result.data.message || 'Could not save');
          if (status) status.textContent = 'Saved';
          window.setTimeout(function () { if (status && status.textContent === 'Saved') status.textContent = ''; }, 1500);
        })
        .catch(function (error) {
          input.checked = !input.checked;
          if (status) status.textContent = error.message;
        });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

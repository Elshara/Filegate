window.fg_registerDatasetViewer = function (selector, fallbackOutputSelector) {
  if (!window.fetch) {
    return;
  }

  var buttons = document.querySelectorAll(selector);
  buttons.forEach(function (button) {
    var expose = button.getAttribute('data-expose');
    if (expose !== 'true') {
      button.disabled = true;
      button.title = 'This dataset is not exposed via the API.';
      return;
    }

    button.addEventListener('click', function () {
      var dataset = button.getAttribute('data-dataset');
      if (!dataset) {
        return;
      }

      var targetSelector = button.getAttribute('data-output') || fallbackOutputSelector;
      var output = targetSelector ? document.querySelector(targetSelector) : null;
      if (!output) {
        return;
      }

      output.hidden = false;
      output.textContent = 'Loading ' + dataset + '…';

      fetch('/dataset.php?name=' + encodeURIComponent(dataset), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('Request failed');
        }
        return response.json();
      }).then(function (payload) {
        if (payload && payload.status === 'ok') {
          output.textContent = JSON.stringify(payload.data, null, 2);
        } else {
          output.textContent = 'Unable to load dataset.';
        }
      }).catch(function (error) {
        output.textContent = 'Error loading dataset: ' + error.message;
      });
    });
  });
};

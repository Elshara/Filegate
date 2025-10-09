window.fg_registerThemePreview = function (selector) {
  var forms = document.querySelectorAll(selector || 'form[data-theme-preview]');
  if (!forms.length) {
    return;
  }

  var parseTokens = function (payload) {
    if (!payload) {
      return {};
    }
    try {
      return JSON.parse(payload);
    } catch (error) {
      return {};
    }
  };

  forms.forEach(function (form) {
    var inputs = Array.prototype.slice.call(form.querySelectorAll('[data-theme-token-input]'));
    if (!inputs.length) {
      return;
    }

    var previewTarget = form.querySelector('[data-theme-preview-target]');
    var applyToRoot = form.hasAttribute('data-theme-preview-global');
    var resetButton = form.querySelector('[data-theme-reset]');
    var themeSelector = form.querySelector('[data-theme-selector]');
    var formDefaults = parseTokens(form.getAttribute('data-theme-values'));

    var applyTokens = function (tokens) {
      var rootTarget = applyToRoot ? document.documentElement : null;
      var localTarget = previewTarget && previewTarget !== rootTarget ? previewTarget : null;
      var applyTo = function (target) {
        if (!target) {
          return;
        }
        Object.keys(tokens).forEach(function (cssVar) {
          if (cssVar) {
            target.style.setProperty(cssVar, tokens[cssVar]);
          }
        });
      };
      applyTo(rootTarget || previewTarget);
      if (localTarget) {
        applyTo(localTarget);
      }
    };

    var gatherTokens = function () {
      var tokens = {};
      inputs.forEach(function (input) {
        var cssVar = input.getAttribute('data-css-variable');
        if (!cssVar) {
          return;
        }
        tokens[cssVar] = input.value;
      });
      return tokens;
    };

    var syncInputs = function (values) {
      inputs.forEach(function (input) {
        var match = input.name.match(/tokens\[(.+?)\]/);
        var key = match ? match[1] : null;
        if (!key || typeof values[key] === 'undefined') {
          return;
        }
        input.value = values[key];
      });
      applyTokens(gatherTokens());
    };

    var handleSelectorChange = function () {
      if (!themeSelector) {
        return;
      }
      var option = themeSelector.options[themeSelector.selectedIndex];
      if (!option) {
        return;
      }
      var optionTokens = parseTokens(option.getAttribute('data-theme-values'));
      syncInputs(optionTokens);
    };

    inputs.forEach(function (input) {
      input.addEventListener('input', function () {
        applyTokens(gatherTokens());
      });
      input.addEventListener('change', function () {
        applyTokens(gatherTokens());
      });
    });

    if (themeSelector) {
      themeSelector.addEventListener('change', handleSelectorChange);
    }

    if (resetButton) {
      resetButton.addEventListener('click', function (event) {
        event.preventDefault();
        var baseValues = {};
        if (themeSelector) {
          var option = themeSelector.options[themeSelector.selectedIndex];
          baseValues = parseTokens(option ? option.getAttribute('data-theme-values') : null);
        }
        if (!Object.keys(baseValues).length) {
          baseValues = formDefaults;
        }
        syncInputs(baseValues);
      });
    }

    if (!themeSelector && Object.keys(formDefaults).length) {
      syncInputs(formDefaults);
    } else {
      applyTokens(gatherTokens());
    }
  });
};

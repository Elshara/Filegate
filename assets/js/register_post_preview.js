window.fg_registerPostPreview = function (formSelector, defaultPreviewSelector) {
  var forms = document.querySelectorAll(formSelector);
  forms.forEach(function (form) {
    var textarea = form.querySelector('[data-preview-source]');
    if (!textarea) {
      return;
    }

    var targetSelector = form.getAttribute('data-preview-target') || defaultPreviewSelector;
    if (!targetSelector) {
      return;
    }

    var preview = document.querySelector(targetSelector);
    if (!preview) {
      return;
    }

    var bodyAttributes = document.body || null;
    var embedPolicy = bodyAttributes ? bodyAttributes.getAttribute('data-embed-policy') || 'enabled' : 'enabled';
    var statisticsPolicy = bodyAttributes ? bodyAttributes.getAttribute('data-statistics-visibility') || 'public' : 'public';
    var body = preview.querySelector('[data-preview-body]');
    var embedsContainer = preview.querySelector('[data-preview-embeds]');
    var statsContainer = preview.querySelector('[data-preview-stats]');
    var placeholder = preview.querySelector('.preview-placeholder');

    var renderPreview = function () {
      var raw = textarea.value || '';
      if (!raw.trim()) {
        if (body) {
          body.innerHTML = '';
        }
        if (embedsContainer) {
          embedsContainer.innerHTML = '';
        }
        if (statsContainer) {
          statsContainer.innerHTML = '';
        }
        if (placeholder) {
          placeholder.hidden = false;
        }
        preview.setAttribute('hidden', 'hidden');
        return;
      }

      preview.removeAttribute('hidden');
      if (placeholder) {
        placeholder.hidden = true;
      }

      var sanitized = typeof window.fg_sanitizePreviewHtml === 'function' ? window.fg_sanitizePreviewHtml(raw) : raw;
      if (body) {
        body.innerHTML = sanitized;
      }

      var urls = typeof window.fg_extractUrls === 'function' ? window.fg_extractUrls(sanitized) : [];
      var embeds = [];
      if (Array.isArray(urls) && typeof window.fg_detectEmbed === 'function') {
        urls.forEach(function (url) {
          var embed = window.fg_detectEmbed(url);
          if (embed) {
            embeds.push(embed);
          }
        });
      }

      if (embedsContainer) {
        embedsContainer.innerHTML = '';
        if (embedPolicy !== 'disabled') {
          embeds.forEach(function (embed) {
            if (typeof window.fg_renderEmbedFragment === 'function') {
              embedsContainer.insertAdjacentHTML('beforeend', window.fg_renderEmbedFragment(embed));
            }
          });
          embedsContainer.hidden = embeds.length === 0;
        } else {
          embedsContainer.hidden = true;
        }
      }

      if (statsContainer) {
        statsContainer.innerHTML = '';
        var stats = null;
        if (statisticsPolicy !== 'hidden' && typeof window.fg_calculatePreviewStatistics === 'function') {
          stats = window.fg_calculatePreviewStatistics(sanitized, embeds);
        }
        if (stats) {
          Object.keys(stats).forEach(function (key) {
            var value = stats[key];
            var label = key.replace(/_/g, ' ');
            statsContainer.insertAdjacentHTML('beforeend', '<div><dt>' + label + '</dt><dd>' + value + '</dd></div>');
          });
        }
        statsContainer.hidden = !stats;
      }
    };

    textarea.addEventListener('input', renderPreview);
    renderPreview();
  });
};

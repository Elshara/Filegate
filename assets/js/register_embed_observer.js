window.fg_registerEmbedObserver = function (selector) {
  var body = document.body || null;
  var embedPolicy = body ? body.getAttribute('data-embed-policy') || 'enabled' : 'enabled';
  if (embedPolicy === 'disabled') {
    return;
  }

  var renderEmbeds = function (element) {
    if (!element || element.getAttribute('data-embeds-rendered') === 'true') {
      return;
    }
    var payload = element.getAttribute('data-embeds');
    if (!payload) {
      return;
    }
    var embeds;
    try {
      embeds = JSON.parse(payload);
    } catch (error) {
      embeds = [];
    }
    if (!Array.isArray(embeds) || embeds.length === 0) {
      return;
    }
    var container = element.querySelector('.post-embeds');
    if (!container) {
      container = document.createElement('div');
      container.className = 'post-embeds';
      element.appendChild(container);
    }
    container.innerHTML = '';
    embeds.forEach(function (embed) {
      if (typeof window.fg_renderEmbedFragment === 'function') {
        container.insertAdjacentHTML('beforeend', window.fg_renderEmbedFragment(embed));
      }
    });
    element.setAttribute('data-embeds-rendered', 'true');
  };

  document.querySelectorAll(selector).forEach(renderEmbeds);

  if (window.MutationObserver) {
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === Node.ELEMENT_NODE) {
            if (node.matches && node.matches(selector)) {
              renderEmbeds(node);
            }
            node.querySelectorAll && node.querySelectorAll(selector).forEach(renderEmbeds);
          }
        });
      });
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
};

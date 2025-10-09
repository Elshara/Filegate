window.fg_extractUrls = function (html) {
  if (typeof html !== 'string') {
    return [];
  }

  var container = document.createElement('div');
  container.innerHTML = html;

  var urls = [];
  container.querySelectorAll('[href]').forEach(function (node) {
    var value = node.getAttribute('href');
    if (value) {
      urls.push(value.trim());
    }
  });
  container.querySelectorAll('[src]').forEach(function (node) {
    var value = node.getAttribute('src');
    if (value) {
      urls.push(value.trim());
    }
  });

  var pattern = /https?:\/\/[^\s"<]+/ig;
  var match;
  while ((match = pattern.exec(html)) !== null) {
    urls.push(match[0]);
  }

  var filtered = urls.filter(function (value) {
    if (!value) {
      return false;
    }
    var lower = value.toLowerCase();
    if (lower.indexOf('javascript:') === 0) {
      return false;
    }
    if (lower.indexOf('mailto:') === 0) {
      return false;
    }
    return true;
  });

  return Array.from(new Set(filtered));
};

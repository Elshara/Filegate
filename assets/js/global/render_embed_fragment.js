window.fg_renderEmbedFragment = function (embed) {
  if (!embed || typeof embed !== 'object') {
    return '';
  }

  var cssClass = embed.class || embed.className || 'embed-fragment';
  var label = embed.label || 'Embed';
  var type = embed.type || 'external';
  var url = embed.url || '#';
  var html = embed.html || '';

  if (!html) {
    return '<div class="embed-fragment ' + cssClass + '"><a href="' + url + '" rel="noopener" target="_blank">' + label + '</a></div>';
  }

  return '<figure class="embed-fragment ' + cssClass + '" data-embed-type="' + type + '"><div class="embed-media">' + html + '</div><figcaption><a href="' + url + '" rel="noopener" target="_blank">' + label + '</a></figcaption></figure>';
};

window.fg_calculatePreviewStatistics = function (html, embeds) {
  var textOnly = '';
  if (typeof html === 'string') {
    textOnly = html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  }

  var wordCount = textOnly ? textOnly.split(/\s+/).length : 0;
  var characterCount = textOnly ? textOnly.replace(/\s+/g, '').length : 0;
  var headingMatches = typeof html === 'string' ? html.match(/<h[1-6][^>]*>/gi) : null;
  var headingCount = headingMatches ? headingMatches.length : 0;
  var embedCount = Array.isArray(embeds) ? embeds.length : 0;

  return {
    word_count: wordCount,
    character_count: characterCount,
    embed_count: embedCount,
    heading_count: headingCount
  };
};

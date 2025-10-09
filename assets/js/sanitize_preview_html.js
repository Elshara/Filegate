window.fg_sanitizePreviewHtml = function (html) {
  if (typeof html !== 'string') {
    return '';
  }

  var allowedTags = {
    a: true, abbr: true, address: true, article: true, aside: true, audio: true,
    b: true, bdi: true, bdo: true, blockquote: true, br: true, button: true,
    canvas: true, caption: true, cite: true, code: true, data: true, datalist: true,
    dd: true, del: true, details: true, dfn: true, div: true, dl: true, dt: true,
    em: true, figcaption: true, figure: true, footer: true, form: true,
    h1: true, h2: true, h3: true, h4: true, h5: true, h6: true, header: true,
    hr: true, i: true, iframe: true, img: true, input: true, ins: true, kbd: true,
    label: true, legend: true, li: true, main: true, mark: true, meter: true,
    nav: true, object: true, ol: true, optgroup: true, option: true, output: true,
    p: true, picture: true, pre: true, progress: true, q: true, rp: true, rt: true,
    ruby: true, s: true, samp: true, section: true, select: true, small: true,
    span: true, strong: true, sub: true, summary: true, sup: true, svg: true,
    table: true, tbody: true, td: true, template: true, textarea: true, tfoot: true,
    th: true, thead: true, time: true, tr: true, u: true, ul: true, var: true,
    video: true, wbr: true
  };

  var allowedAttributes = ['href', 'src', 'alt', 'title', 'role', 'id', 'class', 'target', 'rel', 'controls', 'loop', 'muted', 'poster', 'width', 'height', 'loading', 'name', 'value', 'type', 'placeholder', 'maxlength', 'rows', 'cols'];

  var template = document.createElement('template');
  template.innerHTML = html;

  var removeDisallowed = function (node) {
    if (!node) {
      return;
    }

    if (node.nodeType === Node.ELEMENT_NODE) {
      var tagName = node.tagName.toLowerCase();
      if (!allowedTags[tagName]) {
        while (node.firstChild) {
          node.parentNode.insertBefore(node.firstChild, node);
        }
        node.parentNode.removeChild(node);
        return;
      }

      var attributes = Array.prototype.slice.call(node.attributes);
      attributes.forEach(function (attribute) {
        var name = attribute.name.toLowerCase();
        if (name.indexOf('on') === 0) {
          node.removeAttribute(attribute.name);
          return;
        }
        if (name.indexOf('aria-') === 0 || name.indexOf('data-') === 0) {
          return;
        }
        if (allowedAttributes.indexOf(name) === -1) {
          node.removeAttribute(attribute.name);
          return;
        }
        if ((name === 'href' || name === 'src') && !/^https?:|^\//i.test(attribute.value)) {
          node.removeAttribute(attribute.name);
        }
      });
    } else if (node.nodeType === Node.COMMENT_NODE) {
      node.parentNode.removeChild(node);
      return;
    }

    var child = node.firstChild;
    while (child) {
      var next = child.nextSibling;
      removeDisallowed(child);
      child = next;
    }
  };

  removeDisallowed(template.content);
  return template.innerHTML;
};

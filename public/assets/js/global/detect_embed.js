window.fg_detectEmbed = function (url) {
  if (typeof url !== 'string' || url.length === 0) {
    return null;
  }

  var providers = [
    {
      key: 'youtube',
      label: 'YouTube',
      type: 'video',
      className: 'embed-youtube',
      pattern: /^(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/i,
      template: function (id) {
        return '<iframe src="https://www.youtube.com/embed/' + id + '" title="YouTube video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
      }
    },
    {
      key: 'vimeo',
      label: 'Vimeo',
      type: 'video',
      className: 'embed-vimeo',
      pattern: /^(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(?:video\/)?([0-9]{6,})/i,
      template: function (id) {
        return '<iframe src="https://player.vimeo.com/video/' + id + '" title="Vimeo video" loading="lazy" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
      }
    },
    {
      key: 'soundcloud',
      label: 'SoundCloud',
      type: 'audio',
      className: 'embed-soundcloud',
      pattern: /^(?:https?:\/\/)?(?:www\.)?soundcloud\.com\/([A-Za-z0-9_\-\/]+)/i,
      template: function (id) {
        return '<iframe src="https://w.soundcloud.com/player/?url=https://soundcloud.com/' + id + '" title="SoundCloud track" loading="lazy"></iframe>';
      }
    },
    {
      key: 'codepen',
      label: 'CodePen',
      type: 'interactive',
      className: 'embed-codepen',
      pattern: /^(?:https?:\/\/)?codepen\.io\/([A-Za-z0-9_-]+\/(?:pen|embed|project|full)\/[^\s\/]+)/i,
      template: function (id) {
        return '<iframe src="https://codepen.io/' + id + '" title="CodePen embed" loading="lazy"></iframe>';
      }
    }
  ];

  for (var i = 0; i < providers.length; i += 1) {
    var provider = providers[i];
    var match = url.match(provider.pattern);
    if (match) {
      var identifier = encodeURIComponent(match[1] || url);
      var html = provider.template ? provider.template(identifier) : '';
      return {
        provider: provider.key,
        label: provider.label,
        type: provider.type,
        url: url,
        html: html,
        class: provider.className
      };
    }
  }

  var urlWithoutQuery = url.split('?')[0];
  var extension = '';
  if (typeof urlWithoutQuery === 'string' && urlWithoutQuery.lastIndexOf('.') !== -1) {
    extension = urlWithoutQuery.substring(urlWithoutQuery.lastIndexOf('.') + 1).toLowerCase();
  }

  if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif'].indexOf(extension) !== -1) {
    return {
      provider: 'image',
      label: 'Image',
      type: 'image',
      url: url,
      html: '<img src="' + url + '" alt="Embedded image" loading="lazy">',
      class: 'embed-image'
    };
  }

  if (['mp3', 'ogg', 'wav', 'aac'].indexOf(extension) !== -1) {
    return {
      provider: 'audio',
      label: 'Audio',
      type: 'audio',
      url: url,
      html: '<audio controls preload="metadata" src="' + url + '"></audio>',
      class: 'embed-audio'
    };
  }

  if (['mp4', 'webm', 'ogv', 'mov'].indexOf(extension) !== -1) {
    return {
      provider: 'video',
      label: 'Video',
      type: 'video',
      url: url,
      html: '<video controls preload="metadata"><source src="' + url + '"></video>',
      class: 'embed-video'
    };
  }

  return null;
};

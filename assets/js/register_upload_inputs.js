window.fg_registerUploadInputs = function (selector) {
  var inputs = document.querySelectorAll(selector);
  inputs.forEach(function (input) {
    var form = input.closest('form');
    var preview = null;
    if (form) {
      var target = form.getAttribute('data-preview-target');
      if (target) {
        preview = document.querySelector(target);
      }
    }
    var list = preview ? preview.querySelector('[data-upload-list]') : null;
    var max = parseInt(input.getAttribute('data-max') || '0', 10);

    var render = function () {
      if (!list) {
        return;
      }
      list.innerHTML = '';
      var files = input.files || [];
      if (!files.length) {
        list.hidden = true;
        return;
      }

      var limit = max > 0 ? Math.min(files.length, max) : files.length;
      for (var i = 0; i < limit; i += 1) {
        var file = files[i];
        var li = document.createElement('li');
        li.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
        list.appendChild(li);
      }
      if (files.length > limit) {
        var notice = document.createElement('li');
        notice.textContent = 'Only ' + limit + ' attachments will be uploaded.';
        notice.className = 'upload-limit-notice';
        list.appendChild(notice);
      }
      list.hidden = false;
    };

    input.addEventListener('change', render);
  });
};

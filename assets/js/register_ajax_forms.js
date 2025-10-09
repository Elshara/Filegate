window.fg_registerAjaxForms = function (selector) {
  if (!window.fetch) {
    return;
  }

  var forms = document.querySelectorAll(selector);
  forms.forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.dataset || !form.dataset.ajax) {
        return;
      }

      event.preventDefault();
      var formData = new FormData(form);
      fetch(form.action, {
        method: form.method || 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('Request failed');
        }
        return response.json();
      }).then(function (data) {
        if (form.dataset.ajax === 'toggle-like' && data && data.status === 'ok') {
          var button = form.querySelector('[data-like-button]');
          if (button) {
            var liked = data.liked ? 'true' : 'false';
            button.setAttribute('data-liked', liked);
            var labelSpan = button.querySelector('.label');
            var countSpan = button.querySelector('.count');
            var likedLabel = button.getAttribute('data-like-label-liked') || 'Unlike';
            var unlikedLabel = button.getAttribute('data-like-label-unliked') || 'Like';
            if (labelSpan) {
              labelSpan.textContent = data.liked ? likedLabel : unlikedLabel;
            }
            if (countSpan && typeof data.likes === 'number') {
              countSpan.textContent = String(data.likes);
            }
          }
        }
      }).catch(function () {
        form.submit();
      });
    });
  });
};

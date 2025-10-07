window.fg_initClient = function () {
  if (typeof window.fg_registerAjaxForms === 'function') {
    window.fg_registerAjaxForms('form[data-ajax]');
  }
  if (typeof window.fg_registerPostPreview === 'function') {
    window.fg_registerPostPreview('.post-composer', '[data-preview-output]');
  }
  if (typeof window.fg_registerUploadInputs === 'function') {
    window.fg_registerUploadInputs('input[data-upload-input]');
  }
  if (typeof window.fg_registerEmbedObserver === 'function') {
    window.fg_registerEmbedObserver('.post-content[data-embeds]');
  }
  if (typeof window.fg_registerDatasetViewer === 'function') {
    window.fg_registerDatasetViewer('.dataset-viewer', null);
  }
};

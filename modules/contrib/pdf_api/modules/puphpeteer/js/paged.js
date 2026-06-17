/**
 * @file
 * Attaches behaviour to trigger Paged.js.
 */

(function pdfApiPuphpeteerPagesBehaviors(Drupal) {
  let timeout = false;
  let done = false;

  window.PagedConfig = {
    auto: false,
  };

  Drupal.behaviors.pagedJs = {
    attach() {
      if (done) {
        return;
      }

      if (timeout) {
        clearTimeout(timeout);
      }

      timeout = setTimeout(() => {
        done = true;
        window.PagedPolyfill.preview();
      }, 2500);
    },
  };
})(Drupal);


(function (global, factory) {
  'use strict';

  var api = factory();

  if (typeof module === 'object' && module !== null && typeof module.exports === 'object') {
    module.exports = api;
  } else {
    global.PawApp = api;
    api.autoStart(global);
  }
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';

  var DEFAULT_CONFIRM_MESSAGE = 'Are you sure?';
  var ALERT_DISMISS_DELAY_MS = 6000;
  var FADE_MS = 400;

  
  function findConfirmable(target) {
    if (!target || typeof target.closest !== 'function') {
      return null;
    }
    return target.closest('[data-confirm]');
  }

  function confirmMessageFor(el) {
    var message = el.getAttribute('data-confirm');
    return message && message.trim() !== '' ? message : DEFAULT_CONFIRM_MESSAGE;
  }

  
  function handleConfirmClick(event, confirmFn) {
    var el = findConfirmable(event.target);
    if (!el) {
      return true;
    }

    if (confirmFn(confirmMessageFor(el))) {
      return true;
    }

    event.preventDefault();
    if (typeof event.stopImmediatePropagation === 'function') {
      event.stopImmediatePropagation();
    }
    return false;
  }

  
  function handleConfirmSubmit(event, confirmFn) {
    var form = event.target;
    if (!form || typeof form.hasAttribute !== 'function' || !form.hasAttribute('data-confirm')) {
      return true;
    }

    if (confirmFn(confirmMessageFor(form))) {
      return true;
    }

    event.preventDefault();
    return false;
  }

  
  function handleFilePreview(event, doc, FileReaderCtor) {
    var input = event.target;

    if (!input || input.tagName !== 'INPUT' || input.type !== 'file') {
      return false;
    }

    var selector = input.getAttribute('data-preview');
    if (!selector) {
      return false;
    }

    var preview = doc.querySelector(selector);
    if (!preview) {
      return false;
    }

    var file = input.files && input.files[0];
    if (!file) {
      hidePreview(preview);
      return false;
    }

    if (typeof file.type !== 'string' || file.type.indexOf('image/') !== 0) {
      hidePreview(preview);
      return false;
    }

    var reader = new FileReaderCtor();
    reader.onload = function (evt) {
      preview.src = evt && evt.target ? evt.target.result : '';
      preview.style.display = 'block';
      preview.removeAttribute('hidden');
    };
    reader.readAsDataURL(file);

    return true;
  }

  function hidePreview(preview) {
    preview.src = '';
    preview.style.display = 'none';
    preview.setAttribute('hidden', 'hidden');
  }

  
  function dismissAlert(alert, bootstrapRef, win) {
    if (bootstrapRef && bootstrapRef.Alert && typeof bootstrapRef.Alert.getOrCreateInstance === 'function') {
      bootstrapRef.Alert.getOrCreateInstance(alert).close();
      return 'bootstrap';
    }

    alert.style.transition = 'opacity ' + FADE_MS + 'ms';
    alert.style.opacity = '0';
    win.setTimeout(function () {
      if (alert.parentNode) {
        alert.remove();
      }
    }, FADE_MS);

    return 'manual';
  }

  
  function autoDismissAlerts(doc, win, bootstrapRef, delayMs) {
    var alerts = doc.querySelectorAll('.alert.alert-dismissible');
    var scheduled = 0;

    Array.prototype.forEach.call(alerts, function (alert) {
      win.setTimeout(function () {
        if (alert.matches(':hover') || alert.contains(doc.activeElement)) {
          return;
        }
        dismissAlert(alert, bootstrapRef, win);
      }, typeof delayMs === 'number' ? delayMs : ALERT_DISMISS_DELAY_MS);
      scheduled += 1;
    });

    return scheduled;
  }

  
  function init(doc, options) {
    var opts = options || {};
    var win = opts.window || doc.defaultView || {
      setTimeout: function (fn) { return fn(); }
    };
    var confirmFn = opts.confirm || function (message) { return win.confirm(message); };
    var FileReaderCtor = opts.FileReader || win.FileReader;
    var getBootstrap = opts.getBootstrap || function () { return win.bootstrap; };

    doc.addEventListener('click', function (event) {
      handleConfirmClick(event, confirmFn);
    });

    doc.addEventListener('submit', function (event) {
      handleConfirmSubmit(event, confirmFn);
    });

    doc.addEventListener('change', function (event) {
      if (FileReaderCtor) {
        handleFilePreview(event, doc, FileReaderCtor);
      }
    });

    function start() {
      autoDismissAlerts(doc, win, getBootstrap(), opts.dismissDelayMs);
    }

    if (doc.readyState === 'loading') {
      doc.addEventListener('DOMContentLoaded', start);
    } else {
      start();
    }
  }

  function autoStart(global) {
    init(global.document, { window: global });
  }

  return {
    DEFAULT_CONFIRM_MESSAGE: DEFAULT_CONFIRM_MESSAGE,
    ALERT_DISMISS_DELAY_MS: ALERT_DISMISS_DELAY_MS,
    findConfirmable: findConfirmable,
    confirmMessageFor: confirmMessageFor,
    handleConfirmClick: handleConfirmClick,
    handleConfirmSubmit: handleConfirmSubmit,
    handleFilePreview: handleFilePreview,
    dismissAlert: dismissAlert,
    autoDismissAlerts: autoDismissAlerts,
    init: init,
    autoStart: autoStart
  };
});

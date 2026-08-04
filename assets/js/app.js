(function () {
  'use strict';

  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;
    var message = el.getAttribute('data-confirm') || 'Are you sure?';
    if (!window.confirm(message)) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.hasAttribute('data-confirm')) return;
    var message = form.getAttribute('data-confirm') || 'Are you sure?';
    if (!window.confirm(message)) {
      e.preventDefault();
    }
  });

  document.addEventListener('change', function (e) {
    var input = e.target;
    if (input.tagName !== 'INPUT' || input.type !== 'file') return;
    var previewSelector = input.getAttribute('data-preview');
    if (!previewSelector) return;

    var preview = document.querySelector(previewSelector);
    if (!preview) return;

    var file = input.files && input.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      preview.src = '';
      preview.style.display = 'none';
      return;
    }

    var reader = new FileReader();
    reader.onload = function (evt) {
      preview.src = evt.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });

  function autoDismissAlerts() {
    var alerts = document.querySelectorAll('.alert.alert-dismissible');
    alerts.forEach(function (alert) {
      setTimeout(function () {
        var bsAlert = bootstrap && bootstrap.Alert ? bootstrap.Alert.getOrCreateInstance(alert) : null;
        if (bsAlert) {
          bsAlert.close();
        } else {
          alert.style.transition = 'opacity 0.5s';
          alert.style.opacity = '0';
          setTimeout(function () {
            alert.remove();
          }, 500);
        }
      }, 5000);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoDismissAlerts);
  } else {
    autoDismissAlerts();
  }
})();

// Disable pinch zoom on mobile devices
document.addEventListener('touchstart', function (e) {
  if (e.touches.length > 1) {
    e.preventDefault();
  }
}, { passive: false });
document.addEventListener('touchmove', function (e) {
  if (e.touches.length > 1) {
    e.preventDefault();
  }
}, { passive: false });

// Prevent double-tap zoom on iOS
var lastTouchEnd = 0;
document.addEventListener('touchend', function (e) {
  var now = Date.now();
  if (now - lastTouchEnd <= 300) {
    e.preventDefault();
  }
  lastTouchEnd = now;
}, false);

// ----------- Genel Popup Fonksiyonlari -----------
let alertModalInstance, confirmModalInstance;

function showAlert(message, callback) {
  const modalEl = document.getElementById('alertModal');
  if (!modalEl) return alert(message);
  modalEl.querySelector('.modal-body').textContent = message;
  if (!alertModalInstance) {
    alertModalInstance = new bootstrap.Modal(modalEl);
  }
  modalEl.querySelector('.ok-btn').onclick = function () {
    alertModalInstance.hide();
    if (callback) callback();
  };
  alertModalInstance.show();
}

function showConfirmModal(event, message) {
  event.preventDefault();
  const element = (event.currentTarget || event.target).closest('a,button');

  const modalEl = document.getElementById('confirmModal');
  if (!modalEl) {
    if (confirm(message)) {
      if (element && element.tagName === 'A') {
        window.location = element.href;
      } else if (element && element.form) {
        if (typeof element.form.requestSubmit === 'function') {
          element.form.requestSubmit(element);
        } else {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = element.name;
          input.value = element.value;
          element.form.appendChild(input);
          element.form.submit();
        }
      }
    }
    return false;
  }

  modalEl.querySelector('.modal-body').textContent = message;
  if (!confirmModalInstance) {
    confirmModalInstance = new bootstrap.Modal(modalEl);
  }
  const yesBtn = modalEl.querySelector('.yes-btn');
  const noBtn = modalEl.querySelector('.no-btn');
  yesBtn.onclick = function () {
    confirmModalInstance.hide();
    if (!element) return;
    if (element.tagName === 'A') {
      window.location = element.href;
    } else if (element.form) {
      if (typeof element.form.requestSubmit === 'function') {
        element.form.requestSubmit(element);
      } else {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = element.name;
        input.value = element.value;
        element.form.appendChild(input);
        element.form.submit();
      }
    }
  };
  noBtn.onclick = function () {
    confirmModalInstance.hide();
  };
  confirmModalInstance.show();
  return false;
}

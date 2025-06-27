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
  const modalEl = document.getElementById('confirmModal');
  if (!modalEl) return confirm(message);
  modalEl.querySelector('.modal-body').textContent = message;
  if (!confirmModalInstance) {
    confirmModalInstance = new bootstrap.Modal(modalEl);
  }
  const yesBtn = modalEl.querySelector('.yes-btn');
  const noBtn = modalEl.querySelector('.no-btn');
  yesBtn.onclick = function () {
    confirmModalInstance.hide();
    if (event.target.tagName === 'A') {
      window.location = event.target.href;
    } else if (event.target.form) {
      event.target.form.submit();
    }
  };
  noBtn.onclick = function () {
    confirmModalInstance.hide();
  };
  confirmModalInstance.show();
  return false;
}

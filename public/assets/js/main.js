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

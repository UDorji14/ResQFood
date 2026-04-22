/* ResQFood — App JS (backend interactions) */

// Auto-hide flash messages after 5 seconds
document.querySelectorAll('.flash').forEach(function(el) {
  setTimeout(function() {
    el.style.transition = 'opacity 0.4s, transform 0.4s';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-4px)';
    setTimeout(function() { el.remove(); }, 400);
  }, 5000);
});

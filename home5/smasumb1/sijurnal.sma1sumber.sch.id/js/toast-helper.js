// Simple Bootstrap-style toast helper
(function(){
  window.showToast = function(message, type = 'info', timeout = 5000) {
    var container = document.getElementById('globalToastContainer');
    if (!container) return console.warn('Toast container not found');

    var id = 'toast-' + Date.now() + '-' + Math.floor(Math.random()*1000);
    var bgClass = 'bg-info';
    var textClass = 'text-white';
    if (type === 'success') { bgClass = 'bg-success'; }
    if (type === 'error' || type === 'danger') { bgClass = 'bg-danger'; }
    if (type === 'warning') { bgClass = 'bg-warning'; textClass = 'text-dark'; }

    var toast = document.createElement('div');
    toast.className = 'toast ' + bgClass + ' ' + textClass;
    toast.id = id;
    toast.style.minWidth = '200px';
    toast.style.marginBottom = '0.5rem';
    toast.style.padding = '0.75rem 1rem';
    toast.style.borderRadius = '0.375rem';
    toast.style.boxShadow = '0 2px 6px rgba(0,0,0,0.15)';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
    toast.innerHTML = '<div class="toast-body">' + escapeHtml(String(message)) + '</div>';

    container.appendChild(toast);

    // animate in
    requestAnimationFrame(function(){
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0)';
    });

    // auto remove
    setTimeout(function(){
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-8px)';
    // Override window.alert to use non-blocking toasts by default
    (function(){
      try {
        var _origAlert = window.alert;
        window.alert = function(msg){
          try {
            showToast(String(msg), 'info', 4500);
          } catch (e) {
            // fallback to native
            _origAlert(msg);
          }
        };
      } catch (e) {
        console.warn('Unable to override window.alert', e);
      }
    })();
  })();
    }, timeout);
  };

  function escapeHtml(text) {
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>\"']/g, function(m){ return map[m]; });
  }
})();

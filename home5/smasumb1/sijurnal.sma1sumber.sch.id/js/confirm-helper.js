// showConfirm helper using SweetAlert2 if available, fallback to window.confirm
(function(){
  window.showConfirm = function(message, title) {
    title = title || '';
    if (window.Swal && typeof Swal.fire === 'function') {
      return Swal.fire({
        title: title,
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal',
        reverseButtons: true
      }).then(res => !!res.isConfirmed);
    }
    return Promise.resolve(window.confirm(message));
  };

  // Auto-convert inline onclick="return confirm('...')" to non-blocking showConfirm
  document.addEventListener('DOMContentLoaded', function() {
    try {
      var nodes = document.querySelectorAll('[onclick]');
      nodes.forEach(function(el){
        var oc = el.getAttribute('onclick');
        if (!oc) return;
        // match patterns like: return confirm('Message'); or return confirm("Message");
        var m = oc.match(/^\s*return\s+confirm\((['\"])([\s\S]*)\1\)\s*;?\s*$/);
        if (!m) return;
        var msg = m[2];
        // remove original inline onclick
        el.removeAttribute('onclick');
        el.addEventListener('click', function(evt){
          // If element is a link, prevent default navigation until confirmed
          if (el.tagName.toLowerCase() === 'a' && el.href) evt.preventDefault();
          evt.stopImmediatePropagation();
          showConfirm(msg).then(function(ok){
            if (!ok) return;
            if (el.tagName.toLowerCase() === 'a' && el.href) {
              window.location.href = el.href;
            } else if (el.closest('form') && (el.type === 'submit' || el.getAttribute('type') === 'submit')) {
              el.closest('form').submit();
            } else {
              // fallback: if original onclick intended to allow default action, trigger it
              if (el.tagName.toLowerCase() === 'button') el.click();
            }
          });
        }, {capture: true});
      });
    } catch (e) {
      console.error('confirm-helper init error', e);
    }
  });
})();

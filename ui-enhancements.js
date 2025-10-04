document.addEventListener('DOMContentLoaded', function() {
  // Add smooth transitions for loading states
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
      this.classList.add('loading');
    });
  });

  // Add responsive table handling
  document.querySelectorAll('.table').forEach(table => {
    const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent);
    
    if (window.innerWidth < 768) {
      table.querySelectorAll('td').forEach((td, index) => {
        td.setAttribute('data-label', headers[index % headers.length]);
      });
    }
  });

  // Add smooth notifications
  function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.remove();
    }, 5000);
  }

  window.showNotification = showNotification;
});

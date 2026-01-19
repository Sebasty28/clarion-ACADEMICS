// Toast notification system
function showToast(message, type = 'success') {
  const container = document.getElementById('toastContainer') || createToastContainer();
  
  const toast = document.createElement('div');
  toast.className = `auth-toast ${type}`;
  const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
  toast.innerHTML = `
    <i class="bi ${icon} me-2"></i>
    <span>${message}</span>
  `;
  container.appendChild(toast);
  
  setTimeout(() => toast.classList.add('show'), 10);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

function createToastContainer() {
  const container = document.createElement('div');
  container.id = 'toastContainer';
  container.style.cssText = 'position:fixed;bottom:30px;right:30px;display:flex;flex-direction:column;gap:10px;z-index:9999;';
  document.body.appendChild(container);
  return container;
}

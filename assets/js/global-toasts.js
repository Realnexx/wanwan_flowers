(() => {
  const region = document.querySelector('#global-toast-region');
  if (!region) return;
  const labels = { success: 'Saved successfully', error: 'Please check', warning: 'Please note', info: 'Notification' };
  window.showToast = (message, type = 'success') => {
    if (type === 'danger') type = 'error';
    if (!labels[type]) type = 'info';
    if (!message) return;
    while (region.children.length >= 3) region.firstElementChild.remove();
    const toast = document.createElement('div');
    toast.className = `global-toast toast-${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-atomic', 'true');
    const icon = document.createElement('span');
    icon.className = 'global-toast-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = type === 'success' ? '✓' : type === 'error' ? '!' : 'i';
    const content = document.createElement('div');
    const heading = document.createElement('strong'); heading.textContent = labels[type];
    const text = document.createElement('p'); text.textContent = String(message);
    content.append(heading, text);
    const close = document.createElement('button');
    close.type = 'button'; close.setAttribute('aria-label', 'Dismiss notification'); close.textContent = '×';
    toast.append(icon, content, close); region.appendChild(toast);
    let timer;
    const dismiss = () => { clearTimeout(timer); toast.remove(); };
    const resume = () => { clearTimeout(timer); timer = setTimeout(dismiss, type === 'error' ? 8500 : 5000); };
    close.addEventListener('click', dismiss);
    toast.addEventListener('mouseenter', () => clearTimeout(timer));
    toast.addEventListener('mouseleave', resume);
    toast.addEventListener('focusin', () => clearTimeout(timer));
    toast.addEventListener('focusout', resume);
    resume();
    return dismiss;
  };
  try {
    const messages = JSON.parse(document.querySelector('#global-toast-messages')?.textContent || '[]');
    messages.forEach(notice => window.showToast(notice.message, notice.type));
  } catch { /* An invalid notice must never interrupt the page. */ }
  window.addEventListener('wanwan:toast', event => window.showToast(event.detail?.message, event.detail?.type));
})();

(() => {
  const dialog = document.querySelector('#customer-signout-dialog');
  if (!dialog) return;
  const confirm = document.querySelector('#confirm-customer-signout');
  let pendingForm = null, trigger = null;
  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || new URL(form.action, location.href).searchParams.get('route') !== 'logout') return;
    event.preventDefault();
    pendingForm = form;
    trigger = event.submitter || document.activeElement;
    confirm.disabled = false;
    if (!dialog.open) dialog.showModal();
  });
  dialog.querySelectorAll('[data-cancel-signout]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('click', event => {
    if (event.target !== dialog) return;
    const box = dialog.getBoundingClientRect();
    if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
  });
  dialog.addEventListener('close', () => {
    pendingForm = null;
    trigger?.focus();
  });
  confirm.addEventListener('click', () => {
    if (!pendingForm || confirm.disabled) return;
    confirm.disabled = true;
    // Submit the original POST form with its original CSRF token, once confirmed.
    HTMLFormElement.prototype.submit.call(pendingForm);
  });
})();

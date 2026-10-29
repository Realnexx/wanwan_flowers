(() => {
  'use strict';
  document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (!input) return;
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.textContent = visible ? 'Hide' : 'Show';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
  }));

  document.querySelectorAll('.signin-form').forEach(form => {
    const username = form.querySelector('[name=username]');
    const password = form.querySelector('[name=password]');
    const confirmation = form.querySelector('[name=confirm_password]');
    const reqUser = form.querySelector('[data-req="user"]');
    const reqLength = form.querySelector('[data-req="length"]');
    const reqMatch = form.querySelector('[data-req="match"]');

    const setPill = (pill, met) => {
      if (!pill) return;
      pill.classList.toggle('is-met', met);
      const icon = pill.querySelector('i');
      if (icon) {
        icon.className = met ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
      }
    };

    const validate = () => {
      if (password) {
        password.setCustomValidity(
          password.value && new TextEncoder().encode(password.value).length > 72
            ? 'This password is too long. Try a shorter passphrase.'
            : ''
        );
      }
      if (confirmation) {
        confirmation.setCustomValidity(
          confirmation.value !== password.value ? 'Your passwords do not match.' : ''
        );
      }
      if (reqUser && username) {
        setPill(reqUser, /^[A-Za-z0-9_.\-]{3,40}$/.test(username.value));
      }
      if (reqLength && password) {
        const bytes = new TextEncoder().encode(password.value).length;
        setPill(reqLength, password.value.length >= 12 && bytes <= 72);
      }
      if (reqMatch && password && confirmation) {
        setPill(reqMatch, confirmation.value.length >= 12 && confirmation.value === password.value);
      }
    };

    if (username) username.addEventListener('input', validate);
    if (password) password.addEventListener('input', validate);
    if (confirmation) confirmation.addEventListener('input', validate);
    validate();

    const submit = form.querySelector('button[type=submit]');
    if (!submit) return;
    const label = submit.querySelector('.signin-button-label');
    const original = label ? label.textContent : '';
    form.addEventListener('submit', () => {
      submit.disabled = true;
      submit.setAttribute('aria-busy', 'true');
      if (label) label.textContent = 'Please wait…';
    });
    window.addEventListener('pageshow', () => {
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
      if (label) label.textContent = original;
    });
  });

  document.querySelectorAll('.signin-feedback button').forEach(button =>
    button.addEventListener('click', () => button.closest('.signin-feedback').remove())
  );
})();

const menu = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#navigation');
const closeMenu = () => {
  menu?.setAttribute('aria-expanded', 'false');
  navigation?.classList.remove('open');
};
menu?.addEventListener('click', () => {
  const expanded = menu.getAttribute('aria-expanded') === 'true';
  menu.setAttribute('aria-expanded', String(!expanded));
  document.querySelector('#navigation').classList.toggle('open', !expanded);
});
navigation?.addEventListener('click', event => {
  if (event.target.closest('a')) closeMenu();
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && menu?.getAttribute('aria-expanded') === 'true') {
    closeMenu();
    menu.focus();
  }
});
document.addEventListener('click', event => {
  if (!event.target.closest('.header')) closeMenu();
});
const header = document.querySelector('.header');
let scrollPending = false;
const updateHeader = () => {
  header?.classList.toggle('is-scrolled', window.scrollY > 24);
  scrollPending = false;
};
window.addEventListener('scroll', () => {
  if (!scrollPending) {
    scrollPending = true;
    requestAnimationFrame(updateHeader);
  }
}, { passive: true });
updateHeader();

// AOS-style one-time reveals and staggered entrances, using browser APIs.
// Keep every page readable if animation support or JavaScript is unavailable.
const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
let revealObserver;
let runningAnimations = [];
const revealTargets = document.querySelectorAll(
  '.hero-copy > *, .hero-art, .hero-bottom, .page-heading > *, ' +
  '.section-heading, .product, .occasion > .eyebrow, .occasion > h2, ' +
  '.occasion-links > a, .story-block > *, .detail > *, .info-layout > *, ' +
  '.values > div, .bag-row, .bag-total, .empty, .catalog-tools, ' +
  '.closing > *, .footer-top > *'
);
function resetMotion() {
  revealObserver?.disconnect();
  runningAnimations.forEach(animation => animation.cancel());
  runningAnimations = [];
  revealTargets.forEach(element => element.classList.remove('reveal-ready', 'is-revealed'));
}
function startMotion() {
  resetMotion();
  if (motionPreference.matches || !('IntersectionObserver' in window)) return;
  revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-revealed');
      revealObserver.unobserve(entry.target);
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });
  revealTargets.forEach(element => {
    const siblings = [...element.parentElement.children];
    const index = siblings.indexOf(element);
    element.style.setProperty('--reveal-delay', `${Math.min(index, 3) * 85}ms`);
    element.classList.add('reveal-ready');
    revealObserver.observe(element);
  });
  if (Element.prototype.animate) {
    document.querySelectorAll('.bloom-seal, .art-spark').forEach((element, index) => {
      runningAnimations.push(element.animate(
        [{ translate: '0 0' }, { translate: `0 ${index ? -9 : 7}px` }, { translate: '0 0' }],
        { duration: 4500 + index * 1000, iterations: Infinity, easing: 'ease-in-out' }
      ));
    });
  }
}
startMotion();
motionPreference.addEventListener('change', startMotion);
// Back/forward navigation must never restore an outgoing, faded page.
window.addEventListener('pageshow', () => document.body.classList.remove('page-leaving'));
document.addEventListener('click', event => {
  const link = event.target.closest('a[href]');
  if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download') || motionPreference.matches) return;
  const target = new URL(link.href, window.location.href);
  if (target.origin !== location.origin || target.pathname !== location.pathname || target.hash || target.href === location.href) return;
  event.preventDefault();
  document.body.classList.add('page-leaving');
  window.setTimeout(() => window.location.assign(target.href), 160);
});
const enquiry = document.querySelector('#enquiry-form');
enquiry?.addEventListener('submit', event => {
  event.preventDefault();
  const data = new FormData(enquiry);
  document.querySelector('#enquiry-output').value = `Flower enquiry from ${data.get('name')}\nEmail: ${data.get('email')}\nOccasion: ${data.get('occasion')}\n\n${data.get('message')}`;
  document.querySelector('#enquiry-result').hidden = false;
  document.querySelector('#enquiry-output').focus();
});
document.querySelector('#copy-enquiry')?.addEventListener('click', async () => {
  const output = document.querySelector('#enquiry-output');
  try {
    await navigator.clipboard.writeText(output.value);
    document.querySelector('#copy-status').textContent = 'Copied. Share this message through your usual shop contact channel.';
  } catch {
    output.focus(); output.select();
    document.querySelector('#copy-status').textContent = 'Select and copy the message above to share it.';
  }
});

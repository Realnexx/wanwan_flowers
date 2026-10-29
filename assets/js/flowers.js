const menu = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#navigation');
const backdrop = document.querySelector('.nav-backdrop');

const setMenuOpen = open => {
  if (!menu || !navigation) return;
  menu.setAttribute('aria-expanded', String(open));
  menu.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
  const menuIcon = menu.querySelector('.fa-solid');
  menuIcon?.classList.toggle('fa-bars', !open);
  menuIcon?.classList.toggle('fa-xmark', open);
  navigation.classList.toggle('open', open);
  if (backdrop) backdrop.hidden = !open;
};

const closeMenu = () => setMenuOpen(false);

menu?.addEventListener('click', () => {
  const expanded = menu.getAttribute('aria-expanded') === 'true';
  setMenuOpen(!expanded);
});

backdrop?.addEventListener('click', closeMenu);

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
  '.section-heading, .product, .occasion-header > *, .occasion-card, ' +
  '.process-card, .story-block > *, .testimonial-card, .detail > *, ' +
  '.info-layout > *, .value-card, .stat-card, .zone-card, .bag-row, ' +
  '.bag-total, .empty, .catalog-tools, .bespoke-banner, .closing-inner > *, .footer-top > *'
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
    element.style.setProperty('--reveal-delay', `${Math.min(index, 3) * 80}ms`);
    element.classList.add('reveal-ready');
    revealObserver.observe(element);
  });

  if (Element.prototype.animate) {
    document.querySelectorAll('.bloom-seal, .art-spark, .floating-card').forEach((element, index) => {
      runningAnimations.push(element.animate(
        [{ translate: '0 0' }, { translate: `0 ${index % 2 ? -7 : 6}px` }, { translate: '0 0' }],
        { duration: 4200 + index * 700, iterations: Infinity, easing: 'ease-in-out' }
      ));
    });
  }
}

startMotion();
motionPreference.addEventListener('change', startMotion);

// Lazy-loading image skeleton shimmer cleanup
document.querySelectorAll('.product-photo.is-loading img, .catalog-spotlight-media.is-loading img').forEach(img => {
  const finishLoad = () => img.closest('.is-loading')?.classList.remove('is-loading');
  if (img.complete) {
    finishLoad();
  } else {
    img.addEventListener('load', finishLoad, { once: true });
    img.addEventListener('error', finishLoad, { once: true });
  }
});

// Instant catalog filtering, mobile drawer modal, skeletal shimmer, budget chips, sorting & live search
const catalogGrid = document.querySelector('#catalog-grid');
const catalogSkeleton = document.querySelector('#catalog-skeleton');
const catalogTools = document.querySelector('#catalog-tools');
const catalogDrawerBackdrop = document.querySelector('#catalog-drawer-backdrop');
const mobileFilterOpen = document.querySelector('#mobile-filter-open');
const mobileFilterBadge = document.querySelector('#mobile-filter-badge');
const catalogDrawerClose = document.querySelector('#catalog-drawer-close');
const catalogDrawerReset = document.querySelector('#catalog-drawer-reset');
const catalogDrawerApply = document.querySelector('#catalog-drawer-apply');
const drawerResultCount = document.querySelector('#drawer-result-count');
const catalogSearchForm = document.querySelector('#catalog-search-form');
const catalogSearch = document.querySelector('#catalog-search');
const catalogSearchClear = document.querySelector('#catalog-search-clear');
const catalogMobileSearchForm = document.querySelector('#catalog-mobile-search-form');
const catalogMobileSearch = document.querySelector('#catalog-mobile-search');
const catalogMobileSearchClear = document.querySelector('#catalog-mobile-search-clear');
const catalogSort = document.querySelector('#catalog-sort');
const catalogEmpty = document.querySelector('#catalog-empty');
const catalogVisibleCount = document.querySelector('#catalog-visible-count');
const catalogActivePill = document.querySelector('#catalog-active-filter-pill');
const catalogResetInline = document.querySelector('#catalog-reset-inline');
const catalogEmptyReset = document.querySelector('#catalog-empty-reset');
const catalogOccasionInput = document.querySelector('#catalog-occasion-input');
const occasionTabs = [...document.querySelectorAll('[data-filter-occasion]')];
const priceChips = [...document.querySelectorAll('[data-price-range]')];
const viewButtons = [...document.querySelectorAll('[data-grid-cols]')];

if (catalogGrid) {
  const allCards = [...catalogGrid.querySelectorAll('.product')];
  const skeletonCards = catalogSkeleton ? [...catalogSkeleton.querySelectorAll('.skeleton-card')] : [];
  let activeOccasion = catalogOccasionInput?.value || '';
  let activePriceRange = 'all';
  let skeletonTimer = null;

  const matchesPriceRange = (price, range) => {
    if (range === 'under-1800') return price < 1800;
    if (range === '1800-2200') return price >= 1800 && price <= 2200;
    if (range === 'over-2200') return price > 2200;
    return true;
  };

  const priceRangeLabels = {
    'under-1800': 'Under ₱1,800',
    '1800-2200': '₱1,800 – ₱2,200',
    'over-2200': '₱2,200+'
  };

  const syncUrlState = (occasion, query) => {
    if (!window.history?.replaceState) return;
    const url = new URL(window.location.href);
    if (occasion) url.searchParams.set('occasion', occasion);
    else url.searchParams.delete('occasion');
    if (query) url.searchParams.set('q', query);
    else url.searchParams.delete('q');
    window.history.replaceState(window.history.state, '', url.toString());
  };

  const openFilterDrawer = () => {
    if (!catalogTools) return;
    const prevScrollY = window.scrollY;
    if (catalogDrawerBackdrop) {
      catalogDrawerBackdrop.hidden = false;
      requestAnimationFrame(() => catalogDrawerBackdrop.classList.add('is-open'));
    }
    catalogTools.classList.add('is-drawer-open');
    mobileFilterOpen?.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    window.scrollTo({ top: prevScrollY, left: window.scrollX, behavior: 'instant' });
  };

  const closeFilterDrawer = () => {
    if (!catalogTools || !catalogTools.classList.contains('is-drawer-open')) return;
    const prevScrollY = window.scrollY;
    catalogTools.classList.remove('is-drawer-open');
    mobileFilterOpen?.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    if (catalogDrawerBackdrop) {
      catalogDrawerBackdrop.classList.remove('is-open');
      window.setTimeout(() => {
        if (!catalogTools.classList.contains('is-drawer-open')) {
          catalogDrawerBackdrop.hidden = true;
        }
      }, 280);
    }
    window.scrollTo({ top: prevScrollY, left: window.scrollX, behavior: 'instant' });
  };

  const applyCatalogView = ({ withSkeleton = false } = {}) => {
    const prevScrollY = window.scrollY;
    const prevScrollX = window.scrollX;
    const rawQuery = (catalogSearch?.value || '').trim();
    const query = rawQuery.toLowerCase();
    const sortBy = catalogSort?.value || 'featured';
    let visibleCount = 0;

    if (catalogMobileSearch && catalogMobileSearch.value !== (catalogSearch?.value || '')) {
      catalogMobileSearch.value = catalogSearch?.value || '';
    }
    if (catalogSearchClear) {
      catalogSearchClear.hidden = rawQuery.length === 0;
    }
    if (catalogMobileSearchClear) {
      catalogMobileSearchClear.hidden = rawQuery.length === 0;
    }

    // Update dynamic counts on budget chips (respecting occasion + search)
    priceChips.forEach(chip => {
      const range = chip.dataset.priceRange || 'all';
      const countBadge = chip.querySelector('.price-chip-count');
      if (!countBadge) return;
      let count = 0;
      allCards.forEach(card => {
        const name = card.dataset.name || '';
        const cardOccasion = card.dataset.occasion || '';
        const price = Number(card.dataset.price || 0);
        if ((!activeOccasion || cardOccasion === activeOccasion) && (!query || name.includes(query)) && matchesPriceRange(price, range)) {
          count++;
        }
      });
      countBadge.textContent = String(count);
    });

    // Update dynamic counts on occasion tabs (respecting budget + search)
    occasionTabs.forEach(tab => {
      const tabOccasion = tab.dataset.filterOccasion || '';
      const countBadge = tab.querySelector('.filter-count');
      if (!countBadge) return;
      let count = 0;
      allCards.forEach(card => {
        const name = card.dataset.name || '';
        const cardOccasion = card.dataset.occasion || '';
        const price = Number(card.dataset.price || 0);
        if ((!tabOccasion || cardOccasion === tabOccasion) && (!query || name.includes(query)) && matchesPriceRange(price, activePriceRange)) {
          count++;
        }
      });
      countBadge.textContent = String(count);
    });

    allCards.forEach(card => {
      const name = card.dataset.name || '';
      const cardOccasion = card.dataset.occasion || '';
      const price = Number(card.dataset.price || 0);

      const matchesOccasion = !activeOccasion || cardOccasion === activeOccasion;
      const matchesQuery = !query || name.includes(query);
      const matchesPrice = matchesPriceRange(price, activePriceRange);
      const matches = matchesOccasion && matchesQuery && matchesPrice;

      card.hidden = !matches;
      if (matches) {
        card.classList.add('is-revealed');
        visibleCount++;
      }
    });

    const sorted = [...allCards].sort((a, b) => {
      const priceA = Number(a.dataset.price || 0);
      const priceB = Number(b.dataset.price || 0);
      if (sortBy === 'price-asc') return priceA - priceB;
      if (sortBy === 'price-desc') return priceB - priceA;
      if (sortBy === 'name-asc') return (a.dataset.title || '').localeCompare(b.dataset.title || '');
      return allCards.indexOf(a) - allCards.indexOf(b);
    });
    sorted.forEach((card, idx) => {
      card.style.order = String(idx);
    });

    if (catalogVisibleCount) catalogVisibleCount.textContent = String(visibleCount);
    if (drawerResultCount) drawerResultCount.textContent = String(visibleCount);

    const activeLabels = [];
    if (activeOccasion) activeLabels.push(activeOccasion);
    if (activePriceRange !== 'all' && priceRangeLabels[activePriceRange]) {
      activeLabels.push(priceRangeLabels[activePriceRange]);
    }
    if (rawQuery) activeLabels.push(`“${rawQuery}”`);

    const activeFilterCount = activeLabels.length + (sortBy !== 'featured' ? 1 : 0);
    if (mobileFilterBadge) {
      mobileFilterBadge.hidden = activeFilterCount === 0;
      mobileFilterBadge.textContent = String(activeFilterCount);
    }

    const hasFilters = activeLabels.length > 0;
    if (catalogActivePill) {
      catalogActivePill.hidden = !hasFilters;
      catalogActivePill.textContent = activeLabels.join(' • ');
    }
    if (catalogResetInline) {
      catalogResetInline.hidden = !hasFilters;
    }

    // Optional Skeletal Loading Shimmer Transition
    clearTimeout(skeletonTimer);
    if (withSkeleton && catalogSkeleton && !motionPreference.matches) {
      const skeletonCount = Math.max(Math.min(visibleCount || 3, 6), 2);
      skeletonCards.forEach((sk, idx) => {
        sk.hidden = idx >= skeletonCount;
      });
      catalogSkeleton.className = catalogGrid.className + ' catalog-skeleton-grid';
      catalogSkeleton.hidden = false;
      catalogGrid.hidden = true;
      if (catalogEmpty) catalogEmpty.hidden = true;

      skeletonTimer = window.setTimeout(() => {
        const currentY = window.scrollY;
        catalogSkeleton.hidden = true;
        catalogGrid.hidden = false;
        if (catalogEmpty) catalogEmpty.hidden = visibleCount > 0;
        if (Math.abs(window.scrollY - currentY) > 1) {
          window.scrollTo({ top: currentY, left: window.scrollX, behavior: 'instant' });
        }
      }, 220);
    } else {
      if (catalogSkeleton) catalogSkeleton.hidden = true;
      catalogGrid.hidden = false;
      if (catalogEmpty) catalogEmpty.hidden = visibleCount > 0;
    }

    syncUrlState(activeOccasion, rawQuery);

    if (Math.abs(window.scrollY - prevScrollY) > 1) {
      window.scrollTo({ top: prevScrollY, left: prevScrollX, behavior: 'instant' });
    }
  };

  mobileFilterOpen?.addEventListener('click', event => {
    event.preventDefault();
    openFilterDrawer();
  });

  catalogDrawerClose?.addEventListener('click', event => {
    event.preventDefault();
    closeFilterDrawer();
  });

  catalogDrawerBackdrop?.addEventListener('click', () => {
    closeFilterDrawer();
  });

  catalogDrawerApply?.addEventListener('click', event => {
    event.preventDefault();
    closeFilterDrawer();
    applyCatalogView({ withSkeleton: true });
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && catalogTools?.classList.contains('is-drawer-open')) {
      closeFilterDrawer();
    }
  });

  catalogSearchForm?.addEventListener('submit', event => {
    event.preventDefault();
    const prevScrollY = window.scrollY;
    applyCatalogView({ withSkeleton: true });
    if (window.matchMedia('(max-width: 820px)').matches) {
      catalogSearch?.blur();
    }
    window.scrollTo({ top: prevScrollY, left: window.scrollX, behavior: 'instant' });
  });

  catalogMobileSearchForm?.addEventListener('submit', event => {
    event.preventDefault();
    const prevScrollY = window.scrollY;
    if (catalogSearch && catalogMobileSearch) {
      catalogSearch.value = catalogMobileSearch.value;
    }
    applyCatalogView({ withSkeleton: true });
    catalogMobileSearch?.blur();
    window.scrollTo({ top: prevScrollY, left: window.scrollX, behavior: 'instant' });
  });

  catalogMobileSearch?.addEventListener('input', () => {
    if (catalogSearch) {
      catalogSearch.value = catalogMobileSearch.value;
    }
    applyCatalogView({ withSkeleton: false });
  });

  catalogMobileSearchClear?.addEventListener('click', event => {
    event.preventDefault();
    const prevScrollY = window.scrollY;
    if (catalogMobileSearch) catalogMobileSearch.value = '';
    if (catalogSearch) catalogSearch.value = '';
    catalogMobileSearch?.focus({ preventScroll: true });
    applyCatalogView({ withSkeleton: false });
    window.scrollTo({ top: prevScrollY, left: window.scrollX, behavior: 'instant' });
  });

  occasionTabs.forEach(tab => {
    tab.addEventListener('click', event => {
      event.preventDefault();
      activeOccasion = tab.dataset.filterOccasion || '';
      if (catalogOccasionInput) catalogOccasionInput.value = activeOccasion;
      occasionTabs.forEach(item => {
        const selected = item === tab;
        item.classList.toggle('selected', selected);
        item.setAttribute('aria-selected', selected ? 'true' : 'false');
      });
      const isDrawerOpen = catalogTools?.classList.contains('is-drawer-open');
      applyCatalogView({ withSkeleton: !isDrawerOpen });
    });
  });

  priceChips.forEach(chip => {
    chip.addEventListener('click', event => {
      event.preventDefault();
      activePriceRange = chip.dataset.priceRange || 'all';
      priceChips.forEach(item => {
        const isSelected = item === chip;
        item.classList.toggle('active', isSelected);
        item.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });
      const isDrawerOpen = catalogTools?.classList.contains('is-drawer-open');
      applyCatalogView({ withSkeleton: !isDrawerOpen });
    });
  });

  viewButtons.forEach(btn => {
    btn.addEventListener('click', event => {
      event.preventDefault();
      const prevScrollY = window.scrollY;
      const targetMode = btn.dataset.gridCols;
      const isAlt = targetMode === 'alt';
      catalogGrid.classList.toggle('grid-cols-2', isAlt);
      catalogGrid.classList.toggle('grid-cols-alt', isAlt);
      viewButtons.forEach(item => item.classList.toggle('active', item.dataset.gridCols === targetMode));
      if (Math.abs(window.scrollY - prevScrollY) > 1) {
        window.scrollTo({ top: prevScrollY, left: window.scrollX, behavior: 'instant' });
      }
    });
  });

  const resetAllCatalogFilters = event => {
    if (event) event.preventDefault();
    activeOccasion = '';
    activePriceRange = 'all';
    if (catalogOccasionInput) catalogOccasionInput.value = '';
    if (catalogSearch) catalogSearch.value = '';
    if (catalogMobileSearch) catalogMobileSearch.value = '';
    if (catalogSort) catalogSort.value = 'featured';
    occasionTabs.forEach(item => {
      const isAll = (item.dataset.filterOccasion || '') === '';
      item.classList.toggle('selected', isAll);
      item.setAttribute('aria-selected', isAll ? 'true' : 'false');
    });
    priceChips.forEach(item => {
      const isAll = item.dataset.priceRange === 'all';
      item.classList.toggle('active', isAll);
      item.setAttribute('aria-pressed', isAll ? 'true' : 'false');
    });
    const isDrawerOpen = catalogTools?.classList.contains('is-drawer-open');
    applyCatalogView({ withSkeleton: !isDrawerOpen });
  };

  catalogSearchClear?.addEventListener('click', event => {
    event.preventDefault();
    if (catalogSearch) {
      const prevScrollY = window.scrollY;
      catalogSearch.value = '';
      if (catalogMobileSearch) catalogMobileSearch.value = '';
      catalogSearch.focus({ preventScroll: true });
      applyCatalogView({ withSkeleton: false });
      window.scrollTo({ top: prevScrollY, left: window.scrollX, behavior: 'instant' });
    }
  });

  catalogDrawerReset?.addEventListener('click', resetAllCatalogFilters);
  catalogResetInline?.addEventListener('click', resetAllCatalogFilters);
  catalogEmptyReset?.addEventListener('click', resetAllCatalogFilters);
  catalogSearch?.addEventListener('input', () => applyCatalogView({ withSkeleton: false }));
  catalogSort?.addEventListener('change', () => {
    const isDrawerOpen = catalogTools?.classList.contains('is-drawer-open');
    applyCatalogView({ withSkeleton: !isDrawerOpen });
  });
}

// Detail page size tier price preview
const detailPrice = document.querySelector('#detail-price');
const tierInputs = document.querySelectorAll('input[name="tier"]');
if (detailPrice && tierInputs.length) {
  const basePrice = Number(detailPrice.dataset.basePrice || 0);
  tierInputs.forEach(input => {
    input.addEventListener('change', () => {
      document.querySelectorAll('.size-option').forEach(opt => opt.classList.remove('active'));
      input.closest('.size-option')?.classList.add('active');
      const multiplier = Number(input.dataset.multiplier || 1);
      const computed = multiplier === 1 ? basePrice : Math.round((basePrice * multiplier) / 10) * 10;
      detailPrice.textContent = `₱${computed.toLocaleString('en-PH')}`;
    });
  });
}

// Back/forward navigation must never restore an outgoing, faded page.
window.addEventListener('pageshow', () => document.body.classList.remove('page-leaving'));
document.addEventListener('click', event => {
  const link = event.target.closest('a[href]');
  if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download') || motionPreference.matches) return;
  const target = new URL(link.href, window.location.href);
  if (target.origin !== location.origin || target.pathname !== location.pathname || target.hash || target.href === location.href) return;
  event.preventDefault();
  document.body.classList.add('page-leaving');
  window.setTimeout(() => window.location.assign(target.href), 150);
});

const enquiry = document.querySelector('#enquiry-form');
enquiry?.addEventListener('submit', event => {
  event.preventDefault();
  const data = new FormData(enquiry);
  const dateLine = data.get('date') ? `\nPreferred date: ${data.get('date')}` : '';
  document.querySelector('#enquiry-output').value = `Flower enquiry from ${data.get('name')}\nContact: ${data.get('email')}\nOccasion: ${data.get('occasion')}${dateLine}\n\n${data.get('message')}`;
  document.querySelector('#enquiry-result').hidden = false;
  document.querySelector('#enquiry-output').focus();
});

document.querySelector('#copy-enquiry')?.addEventListener('click', async () => {
  const output = document.querySelector('#enquiry-output');
  try {
    await navigator.clipboard.writeText(output.value);
    document.querySelector('#copy-status').textContent = 'Copied! Share this message through your usual shop contact channel.';
  } catch {
    output.focus();
    output.select();
    document.querySelector('#copy-status').textContent = 'Select and copy the message above to share it.';
  }
});

// Save before celebrating: the bag animation only runs after a confirmed update.
const floatingBag = document.querySelector('.mobile-view-bag');
const bagFeedback = document.querySelector('.bag-feedback');
let bagFeedbackTimer;
document.querySelectorAll('.quick-add-form, .detail-form').forEach(form => {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"], button:not([type])');
    if (button?.disabled) return;
    if (button) button.disabled = true;
    try {
      const response = await fetch(form.action || location.href, {
        method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }
      });
      if (!response.ok) throw new Error('Unable to update bag');
      const result = await response.json();
      if (!Number.isInteger(result.count)) throw new Error('Invalid bag response');
      document.querySelectorAll('.bag-count, .mobile-bag-count').forEach(node => node.textContent = result.count);
      document.querySelectorAll('.customer-nav-bag-count').forEach(node => node.textContent = result.count > 0 ? ` (${result.count})` : '');
      document.querySelectorAll('.bag-link').forEach(node => {
        node.classList.toggle('has-items', result.count > 0);
        node.setAttribute('aria-label', `Shopping bag, ${result.count} items`);
      });
      if (floatingBag) floatingBag.hidden = result.count === 0;
      const target = matchMedia('(max-width: 820px)').matches ? (floatingBag || document.querySelector('.customer-bottom-nav a[href="?route=flowers/bag"]')) : document.querySelector('.bag-link');
      if (!motionPreference.matches && target && button && Element.prototype.animate) {
        const start = button.getBoundingClientRect();
        const end = target.getBoundingClientRect();
        const flower = document.createElement('span');
        flower.className = 'flying-flower'; flower.innerHTML = '<i class="fa-solid fa-seedling" aria-hidden="true"></i>';  flower.setAttribute('aria-hidden', 'true');
        flower.style.left = `${start.left + start.width / 2 - 22}px`;
        flower.style.top = `${start.top + start.height / 2 - 22}px`;
        document.body.appendChild(flower);
        const dx = end.left + end.width / 2 - start.left - start.width / 2;
        const dy = end.top + end.height / 2 - start.top - start.height / 2;
        const flight = flower.animate([
          { transform: 'translate(0,0) scale(1)', opacity: 1 },
          { transform: `translate(${dx * .45}px,${dy * .45 - 90}px) rotate(140deg) scale(1.2)`, opacity: 1, offset: .45 },
          { transform: `translate(${dx}px,${dy}px) rotate(300deg) scale(.25)`, opacity: 0 }
        ], { duration: 800, easing: 'ease-in-out' });
        flight.finished.then(() => {
          flower.remove();
          target.animate([{ scale: '1' }, { scale: '1.07' }, { scale: '1' }], { duration: 300 });
        }).catch(() => flower.remove());
      }
      bagFeedback.textContent = 'Flowers added to your bag.';
    } catch {
      bagFeedback.textContent = 'Could not update your bag. Please try again.';
    } finally {
      if (button) button.disabled = false;
      clearTimeout(bagFeedbackTimer);
      bagFeedbackTimer = setTimeout(() => { bagFeedback.textContent = ''; }, 3000);
    }
  });
});

// Reserve the actual fixed-header height, including font loads and rotation.
const fixedMobileHeader = document.querySelector('.header');
if (fixedMobileHeader) {
  const measureMobileHeader = () => document.documentElement.style.setProperty('--mobile-header-height', `${Math.ceil(fixedMobileHeader.getBoundingClientRect().height)}px`);
  measureMobileHeader();
  if ('ResizeObserver' in window) new ResizeObserver(measureMobileHeader).observe(fixedMobileHeader);
  else window.addEventListener('resize', measureMobileHeader, { passive: true });
}

// Direct quantity edits can be saved with the check button or the keyboard.
document.querySelectorAll('.bag-quantity-input').forEach(input => {
  input.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      event.preventDefault();
      input.form.requestSubmit();
    }
  });
});

// Preserve the bag's position across its normal POST/redirect refreshes.
const bagScrollKey = 'wanwan:bag-scroll';
if (new URLSearchParams(location.search).get('route') === 'flowers/bag') {
  try {
    const saved = JSON.parse(sessionStorage.getItem(bagScrollKey) || 'null');
    sessionStorage.removeItem(bagScrollKey);
    if (saved && Number.isFinite(saved.y) && Date.now() - saved.at < 15000) {
      const restoreBagPosition = () => window.scrollTo({ top: saved.y, behavior: 'instant' });
      requestAnimationFrame(restoreBagPosition);
      if (document.readyState !== 'complete') window.addEventListener('load', restoreBagPosition, { once: true });
    }
  } catch { /* Storage may be disabled; forms still work normally. */ }
  document.querySelectorAll('.bag-row form').forEach(form => {
    form.addEventListener('submit', () => {
      try { sessionStorage.setItem(bagScrollKey, JSON.stringify({ y: window.scrollY, at: Date.now() })); } catch {}
    });
  });
}

// Inline checkout validation mirrors the server; it is never the authority.
const customerCheckout = document.querySelector('#flower-checkout-form');
if (customerCheckout) {
  const validateCheckoutField = input => {
    let message = '';
    const value = input.value.trim();
    if (input.required && !value) message = 'Please complete this field.';
    else if (input.name === 'email' && input.validity.typeMismatch) message = 'Enter a valid email address.';
    else if (input.name === 'phone' && !/^(?:\+63|0)9\d{9}$/.test(value.replace(/[\s()-]/g, ''))) message = 'Use 09XXXXXXXXX or +639XXXXXXXXX.';
    else if (input.name === 'postal_code' && !/^\d{4}$/.test(value)) message = 'Enter a four-digit postal code.';
    else if (!input.validity.valid) message = input.name === 'delivery_date' ? 'Choose a date within the available range.' : 'Please check this value.';
    input.setAttribute('aria-invalid', String(Boolean(message)));
    const error = document.querySelector(`#error-${input.name}`);
    if (error) error.textContent = message;
    return !message;
  };
  const fields = [...customerCheckout.querySelectorAll('input:not([type=hidden]),select')];
  fields.forEach(field => {
    field.addEventListener('blur', () => validateCheckoutField(field));
    field.addEventListener('input', () => { if (field.getAttribute('aria-invalid') === 'true') validateCheckoutField(field); });
    field.addEventListener('change', () => validateCheckoutField(field));
  });
  customerCheckout.addEventListener('submit', event => {
    const results = fields.map(validateCheckoutField);
    if (results.includes(false)) {
      event.preventDefault();
      fields[results.indexOf(false)].focus();
    } else {
      customerCheckout.querySelector('[type=submit]').disabled = true;
    }
  });
}
const placeForm = document.querySelector('#flower-place-form');
placeForm?.querySelectorAll('[name=payment_method]').forEach(input => input.addEventListener('change', () => {
  const qr = input.value === 'qrph';
  document.querySelector('#qr-fee-note').hidden = !qr;
  document.querySelector('#place-button-label').textContent = qr ? 'Place order & pay with QR Ph' : 'Place order · Cash on delivery';
}));
placeForm?.addEventListener('submit', () => { placeForm.querySelector('[type=submit]').disabled = true; });
window.addEventListener('pageshow', () => {
  customerCheckout?.querySelector('[type=submit]')?.removeAttribute('disabled');
  placeForm?.querySelector('[type=submit]')?.removeAttribute('disabled');
});

// Cascading PSGC choices use the same local snapshot as server validation.
document.querySelectorAll('[data-psgc-address]').forEach(group => {
  const levels = ['region', 'province', 'city', 'barangay'];
  const selects = levels.map(level => group.querySelector(`[data-address-level="${level}"]`));
  const status = group.querySelector('.psgc-address-status');
  const retry = group.querySelector('.psgc-retry');
  const cache = new Map();
  let revision = 0;
  let failedLevel = null;
  const clearFrom = index => {
    for (let i = index; i < selects.length; i++) {
      selects[i].replaceChildren(new Option(`Select ${levels[i] === 'city' ? 'city / municipality' : levels[i]}`, ''));
      selects[i].disabled = true;
    }
  };
  async function loadNext(index) {
    const currentRevision = ++revision;
    clearFrom(index);
    failedLevel = index;
    retry.hidden = true;
    if (!selects[index - 1].value) { status.textContent = ''; return; }
    const params = new URLSearchParams({ level: ['regions','provinces','cities','barangays'][index] });
    params.set('parent', index === 1 ? selects[0].value : index === 2 ? selects[0].value : selects[2].value);
    if (index === 2) params.set('province', selects[1].value);
    const url = `${group.dataset.endpoint}?${params}`;
    status.textContent = 'Loading address choices…';
    group.setAttribute('aria-busy', 'true');
    try {
      let options = cache.get(url);
      if (!options) {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Unavailable');
        options = await response.json();
        if (!Array.isArray(options)) throw new Error('Invalid address response');
        cache.set(url, options);
      }
      if (currentRevision !== revision) return;
      options.forEach(option => selects[index].add(new Option(option.name, option.code)));
      selects[index].disabled = false;
      status.textContent = options.length ? '' : 'No address choices found. Try another selection.';
      failedLevel = null;
      if (index === 1 && options.length === 1 && options[0].code === 'none') {
        selects[index].value = 'none';
        await loadNext(2);
      }
    } catch {
      if (currentRevision !== revision) return;
      status.textContent = 'Could not load address choices. Please retry.';
      retry.hidden = false;
    } finally {
      if (currentRevision === revision) group.removeAttribute('aria-busy');
    }
  }
  selects.forEach((select, index) => {
    if (index > 0 && !selects[index - 1].value) select.disabled = true;
    if (index < 3) select.addEventListener('change', () => loadNext(index + 1));
  });
  retry.addEventListener('click', () => { if (failedLevel !== null) loadNext(failedLevel); });
});

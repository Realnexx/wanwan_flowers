(() => {
  const app = document.querySelector('#online-orders-app');
  if (!app) return;
  const modal = document.querySelector('#order-detail-modal');
  const content = document.querySelector('#order-modal-content');
  const feedback = document.querySelector('#orders-feedback');
  const modalFeedback = document.querySelector('.order-modal-feedback');
  const board = document.querySelector('#order-board-region');
  let dragging = null, busy = false, openedTracking = '', lastTrigger = null, detailRevision = 0;
  const endpoint = app.dataset.endpoint;
  let selectedTab = 'live', suppressClickUntil = 0;
  function selectTab(name, focus = false) {
    selectedTab = name;
    board.querySelectorAll('[data-orders-tab]').forEach(tab => {
      const active = tab.dataset.ordersTab === name;
      tab.setAttribute('aria-selected', String(active));
      tab.tabIndex = active ? 0 : -1;
      if (active && focus) tab.focus();
    });
    board.querySelector('#orders-live-panel').hidden = name !== 'live';
    board.querySelector('#orders-archive-panel').hidden = name !== 'archive';
  }
  board.addEventListener('click', event => {
    const tab = event.target.closest('[data-orders-tab]');
    if (tab) selectTab(tab.dataset.ordersTab);
  });
  board.addEventListener('keydown', event => {
    const tab = event.target.closest('[data-orders-tab]');
    if (!tab || !['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
    event.preventDefault();
    selectTab(event.key === 'Home' ? 'live' : event.key === 'End' ? 'archive' : selectedTab === 'live' ? 'archive' : 'live', true);
  });
  const toastRegion = document.querySelector('#global-toast-region');
  function notify(text, error = false) {
    // A native dialog occupies the top layer, so keep its toast in that layer too.
    if (toastRegion) (modal.open ? modal : document.body).appendChild(toastRegion);
    window.showToast?.(text, error ? 'error' : 'success');
  }
  const message = (text, error = false, target = feedback) => { target.textContent = text; target.classList.toggle('is-error', error); };
  async function html(url) {
    const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    if (!response.ok) throw new Error('Could not load orders. Refresh the board or sign in again.');
    return response.text();
  }
  async function refresh() {
    const scroll = board.querySelector('.orders-kanban')?.scrollLeft || 0;
    board.innerHTML = await html(endpoint + '&board=1');
    selectTab(selectedTab);
    board.querySelector('.orders-kanban').scrollLeft = scroll;
  }
  async function openOrder(tracking, trigger = null) {
    const revision = ++detailRevision;
    lastTrigger = trigger || lastTrigger;
    openedTracking = tracking;
    message('', false, modalFeedback);
    content.innerHTML = '<p class="p-4" id="order-modal-title">Loading order details…</p>';
    if (!modal.open) modal.showModal();
    if (toastRegion) modal.appendChild(toastRegion);
    try {
      const result = await html(endpoint + '&detail=1&tracking=' + encodeURIComponent(tracking));
      if (revision === detailRevision) content.innerHTML = result;
    } catch (error) { if (revision === detailRevision) message(error.message, true, modalFeedback); }
  }
  document.querySelector('#order-modal-close').addEventListener('click', () => modal.close());
  modal.addEventListener('click', event => { if (event.target === modal) { const r = modal.getBoundingClientRect(); if(event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) modal.close(); } });
  modal.addEventListener('close', () => { detailRevision++; if (toastRegion) document.body.appendChild(toastRegion); lastTrigger?.focus(); });
  app.addEventListener('click', event => {
    if (Date.now() < suppressClickUntil) { event.preventDefault(); return; }
    const link = event.target.closest('.order-open');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;
    event.preventDefault();
    const tracking = new URL(link.href).searchParams.get('tracking');
    openOrder(tracking, link);
  });
  document.querySelector('#order-search').addEventListener('submit', event => { event.preventDefault(); const tracking = document.querySelector('#tracking-search').value.trim(); if (tracking) openOrder(tracking, event.submitter); });
  document.querySelector('#orders-refresh').addEventListener('click', async () => { try { await refresh(); message('Board refreshed.'); } catch(error) { message(error.message, true); } });
  async function save(data) {
    if (busy) return;
    busy = true;
    data.set('csrf_token', app.dataset.csrf);
    try {
      const response = await fetch(endpoint, { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'This update was not saved. Refresh and retry.');
      const status = data.get('status');
      const labels = { pending: 'received', preparing: 'preparing', ready: 'ready for pickup', completed: 'completed', cancelled: 'cancelled', paid: 'paid' };
      const confirmation = 'Order ' + result.tracking + (labels[status] ? ' marked as ' + labels[status] + '.' : ' updated successfully.');
      notify(confirmation);
      message(confirmation);
      try {
        await refresh();
        if (modal.open) await openOrder(result.tracking);
      } catch {
        notify('The update was saved. Refresh the board to see the latest details.', true);
      }
    } catch(error) {
      message(error.message || 'Could not update the order.', true, modal.open ? modalFeedback : feedback);
      notify(error.message || 'Could not update the order.', true);
      try { await refresh(); } catch {}
    } finally { busy = false; }
  }
  modal.addEventListener('submit', async event => {
    const form = event.target.closest('.order-action-form');
    if (!form) return;
    event.preventDefault();
    const data = new FormData(form);
    if (event.submitter?.name) data.set(event.submitter.name, event.submitter.value);
    form.querySelectorAll('button').forEach(b => b.disabled = true);
    await save(data);
    form.querySelectorAll('button').forEach(b => b.disabled = false);
  });
  function endDrag() {
    dragging?.classList.remove('is-dragging');
    app.querySelectorAll('.drop-allowed,.drop-active').forEach(el => el.classList.remove('drop-allowed','drop-active'));
    dragging = null;
  }
  function startDrag(card) {
    if (busy || !card || !JSON.parse(card.dataset.targets).length) return false;
    dragging = card; card.classList.add('is-dragging');
    app.querySelectorAll('[data-order-status]').forEach(lane => lane.classList.toggle('drop-allowed', JSON.parse(card.dataset.targets).includes(lane.dataset.orderStatus)));
    return true;
  }
  function drop(lane) {
    if (!dragging || !lane) { endDrag(); return; }
    const card = dragging, next = lane.dataset.orderStatus;
    if (!JSON.parse(card.dataset.targets).includes(next)) { message('Move this order to its next available status. Completion requires payment.', true); endDrag(); return; }
    const data = new FormData(); data.set('order_id', card.dataset.orderId); data.set('status', next); data.set('expected_status', card.dataset.currentStatus);
    endDrag(); save(data);
  }
  board.addEventListener('dragstart', event => {
    if (touch) { event.preventDefault(); return; }
    const card = event.target.closest('.orders-card');
    if (!startDrag(card)) { event.preventDefault(); return; }
    event.dataTransfer.setData('text/plain', card.dataset.orderId); event.dataTransfer.effectAllowed = 'move';
  });
  board.addEventListener('dragover', event => {
    const lane = event.target.closest('[data-order-status]');
    if (dragging && lane && JSON.parse(dragging.dataset.targets).includes(lane.dataset.orderStatus)) { event.preventDefault(); event.dataTransfer.dropEffect = 'move'; app.querySelectorAll('.drop-active').forEach(el => el.classList.remove('drop-active')); lane.classList.add('drop-active'); }
  });
  board.addEventListener('drop', event => { event.preventDefault(); drop(event.target.closest('[data-order-status]')); });
  board.addEventListener('dragend', endDrag);
  let touch = null, ghost = null;
  board.addEventListener('pointerdown', event => {
    const handle = event.target.closest('.order-drag-handle');
    if (!handle || busy || (event.pointerType === 'mouse' && event.button !== 0)) return;
    event.preventDefault();
    touch = { handle, card: handle.closest('.orders-card'), x: event.clientX, y: event.clientY, started: false, pointerId: event.pointerId };
    handle.setPointerCapture(event.pointerId);
  });
  board.addEventListener('pointermove', event => {
    if (!touch || touch.pointerId !== event.pointerId) return;
    if (!touch.started && Math.hypot(event.clientX-touch.x, event.clientY-touch.y)>8) {
      if (!startDrag(touch.card)) return;
      touch.started = true; ghost = touch.card.cloneNode(true); ghost.classList.remove('is-dragging'); ghost.classList.add('order-drag-ghost'); ghost.setAttribute('aria-hidden','true'); document.body.appendChild(ghost);
    }
    if (!touch.started) return;
    event.preventDefault(); ghost.style.left = event.clientX-100+'px'; ghost.style.top = event.clientY-30+'px';
    const lane = document.elementFromPoint(event.clientX,event.clientY)?.closest('[data-order-status]');
    app.querySelectorAll('.drop-active').forEach(el => el.classList.remove('drop-active'));
    if (lane && JSON.parse(touch.card.dataset.targets).includes(lane.dataset.orderStatus)) lane.classList.add('drop-active');
    const scroller = board.querySelector('.orders-kanban'), r = scroller.getBoundingClientRect();
    if(event.clientX>r.right-35) scroller.scrollLeft+=18; else if(event.clientX<r.left+35) scroller.scrollLeft-=18;
  });
  board.addEventListener('pointerup', event => { if (!touch || touch.pointerId !== event.pointerId) return; if (touch.started) { suppressClickUntil = Date.now() + 500; drop(document.elementFromPoint(event.clientX,event.clientY)?.closest('[data-order-status]')); } if (touch.handle.hasPointerCapture(event.pointerId)) touch.handle.releasePointerCapture(event.pointerId); ghost?.remove(); ghost=null; touch=null; endDrag(); });
  board.addEventListener('pointercancel', () => { ghost?.remove(); ghost=null; touch=null; endDrag(); });
  if (content.querySelector('#order-modal-title')) { modal.showModal(); if (toastRegion) modal.appendChild(toastRegion); }
})();

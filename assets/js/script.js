// ยืนยันก่อนลบสินค้าออกจากตะกร้า / ลบรายการ
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
  el.addEventListener('submit', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
});

const pageContent = document.querySelector('main.wrap');
if (pageContent) {
  let categoryRequest = 0;

  async function loadCategoryPage(url, addHistoryEntry) {
    const requestId = ++categoryRequest;

    try {
      const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok) throw new Error(`Category request failed: ${response.status}`);

      const html = await response.text();
      const nextPage = new DOMParser().parseFromString(html, 'text/html');
      const nextContent = nextPage.querySelector('main.wrap');
      if (!nextContent) throw new Error('Category response did not include page content');
      if (requestId !== categoryRequest) return;

      pageContent.innerHTML = nextContent.innerHTML;
      document.title = nextPage.title;
      if (addHistoryEntry) window.history.pushState({}, '', url);
      const resultsGrid = pageContent.querySelector('.grid');
      if (resultsGrid && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        resultsGrid.animate(
          [{ opacity: 0.85, transform: 'translateY(6px)' }, { opacity: 1, transform: 'translateY(0)' }],
          { duration: 180, easing: 'ease-out' }
        );
      }
    } catch (error) {
      if (requestId !== categoryRequest) return;
      console.error('Unable to load the selected product category.', error);
      window.location.assign(url);
    }
  }

  document.addEventListener('click', event => {
    if (!(event.target instanceof Element)) return;
    const categoryLink = event.target.closest('main.wrap .tabs a');
    if (!categoryLink || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const url = new URL(categoryLink.href, window.location.href);
    if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) return;

    event.preventDefault();
    loadCategoryPage(url.href, true);
  });

  window.addEventListener('popstate', () => loadCategoryPage(window.location.href, false));
}

const backgroundDemo = document.querySelector('.site-background-demo');
if (backgroundDemo) {
  let activeItem = null;
  let pressedItem = null;

  function connectBackgroundDemo() {
    const demoWindow = backgroundDemo.contentWindow;
    const demoDocument = backgroundDemo.contentDocument;
    if (!demoWindow || !demoDocument || !demoDocument.querySelector('.toy')) return;

    function eventIsOnBackground(event) {
      return event.target === document.body || event.target === document.querySelector('.wrap');
    }

    function backgroundItemAt(event) {
      if (!eventIsOnBackground(event)) return null;
      const rect = backgroundDemo.getBoundingClientRect();
      const x = (event.clientX - rect.left) * demoWindow.innerWidth / rect.width;
      const y = (event.clientY - rect.top) * demoWindow.innerHeight / rect.height;
      const target = demoDocument.elementFromPoint(x, y);
      return target && target.closest('.toy, .candy');
    }

    function demoPoint(event) {
      const rect = backgroundDemo.getBoundingClientRect();
      return {
        x: (event.clientX - rect.left) * demoWindow.innerWidth / rect.width,
        y: (event.clientY - rect.top) * demoWindow.innerHeight / rect.height
      };
    }

    function forward(type, item, event) {
      if (!item) return;
      const point = demoPoint(event);
      item.dispatchEvent(new demoWindow.PointerEvent(type, {
        bubbles: true,
        cancelable: true,
        clientX: point.x,
        clientY: point.y,
        pointerId: event.pointerId,
        pointerType: event.pointerType,
        isPrimary: event.isPrimary,
        button: event.button,
        buttons: event.buttons
      }));
    }

    document.addEventListener('pointermove', event => {
      const point = demoPoint(event);
      demoWindow.dispatchEvent(new demoWindow.PointerEvent('pointermove', {
        clientX: point.x,
        clientY: point.y,
        pointerId: event.pointerId,
        pointerType: event.pointerType,
        isPrimary: event.isPrimary,
        buttons: event.buttons
      }));

      const nextItem = backgroundItemAt(event);
      if (nextItem !== activeItem) {
        forward('pointerleave', activeItem, event);
        activeItem = nextItem;
        forward('pointerenter', activeItem, event);
      }
      forward('pointermove', activeItem, event);
    }, true);

    document.addEventListener('pointerdown', event => {
      const item = backgroundItemAt(event);
      if (!item) return;
      if (activeItem !== item) forward('pointerenter', item, event);
      activeItem = item;
      pressedItem = item;
      forward('pointerdown', item, event);
      event.preventDefault();
      event.stopPropagation();
    }, true);

    document.addEventListener('pointerup', event => {
      if (!pressedItem) return;
      forward('pointerup', pressedItem, event);
      pressedItem = null;
      event.preventDefault();
      event.stopPropagation();
    }, true);

    document.addEventListener('pointercancel', event => {
      if (!pressedItem) return;
      forward('pointerup', pressedItem, event);
      pressedItem = null;
      event.preventDefault();
      event.stopPropagation();
    }, true);

    document.addEventListener('click', event => {
      if (!backgroundItemAt(event)) return;
      event.preventDefault();
      event.stopPropagation();
    }, true);
  }

  backgroundDemo.addEventListener('load', connectBackgroundDemo);
  if (backgroundDemo.contentDocument?.readyState === 'complete') connectBackgroundDemo();
}

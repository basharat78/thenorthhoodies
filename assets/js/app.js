/**
 * ATLANTICS — Client Application
 * Handles category filtering, quick-order modal, color & size selection,
 * real-time total recalculation, and automated WhatsApp order link generation.
 */

document.addEventListener('DOMContentLoaded', () => {
  const storeDataEl = document.getElementById('store-json-data');
  if (!storeDataEl) return;

  const store = JSON.parse(storeDataEl.textContent);
  const hoodies = store.hoodies || [];

  // Active state for currently customized hoodie in modal
  let activeHoodie = null;
  let activeColor = null;
  let activeSize = null;
  let activeQty = 1;

  // DOM Elements
  const filterBtns = document.querySelectorAll('.filter-btn');
  const hoodieCards = document.querySelectorAll('.hoodie-card');
  const modal = document.getElementById('quickbuy-modal');
  const modalCloseBtn = document.getElementById('modal-close-btn');

  // Modal Fields
  const modalImg = document.getElementById('modal-hoodie-img');
  const modalBadge = document.getElementById('modal-hoodie-badge');
  const modalType = document.getElementById('modal-hoodie-type');
  const modalTitle = document.getElementById('modal-hoodie-title');
  const modalDesc = document.getElementById('modal-hoodie-desc');
  const modalPrice = document.getElementById('modal-hoodie-price');
  const modalStock = document.getElementById('modal-hoodie-stock');
  const modalSwatchesContainer = document.getElementById('modal-swatches-container');
  const modalSizesContainer = document.getElementById('modal-sizes-container');
  const modalQtyMinus = document.getElementById('modal-qty-minus');
  const modalQtyPlus = document.getElementById('modal-qty-plus');
  const modalQtyVal = document.getElementById('modal-qty-val');
  const modalTotal = document.getElementById('modal-calc-total');
  const modalOrderBtn = document.getElementById('modal-order-btn');

  // 1. FILTER HOODIES BY TYPE / CATEGORY
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filterRaw = (btn.dataset.filter || '').trim();
      const filter = filterRaw.toLowerCase().replace(/s$/, '');

      hoodieCards.forEach(card => {
        const cardType = (card.dataset.type || '').trim().toLowerCase().replace(/s$/, '');
        if (filterRaw === 'all' || cardType === filter || cardType.includes(filter) || filter.includes(cardType)) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  // 2. OPEN QUICK BUY MODAL
  function openQuickBuy(hoodieId) {
    const item = hoodies.find(h => h.id === hoodieId) || hoodies[0];
    if (!item) return;

    activeHoodie = item;
    activeColor = item.colors && item.colors[0] ? item.colors[0] : { name: 'Standard', image: item.image };
    activeSize = item.sizes && item.sizes[0] ? item.sizes[0].label : 'M';
    activeQty = 1;

    // Populate Modal Content
    if (modalTitle) modalTitle.textContent = item.title;
    if (modalType) modalType.textContent = item.type || 'Heavyweight Hoodie';
    if (modalBadge) {
      modalBadge.textContent = item.badge || 'LIMITED DROP';
      modalBadge.style.display = item.badge ? 'inline-block' : 'none';
    }
    if (modalDesc) modalDesc.textContent = item.short_description || '';
    if (modalImg) modalImg.src = activeColor.image || item.image;
    if (modalStock) modalStock.textContent = item.stock_text || 'Ready to Ship';

    const unitPrice = parseFloat(item.sale_price || item.original_price || 0);
    const curr = store.currency || '$';
    if (modalPrice) {
      modalPrice.innerHTML = `${curr}${unitPrice.toFixed(2)}`;
      if (item.original_price && item.original_price > item.sale_price) {
        modalPrice.innerHTML += ` <del style="font-size:14px; color:var(--text-muted); font-weight:500;">${curr}${parseFloat(item.original_price).toFixed(2)}</del>`;
      }
    }

    // Populate Color Swatches
    if (modalSwatchesContainer) {
      modalSwatchesContainer.innerHTML = '';
      (item.colors || []).forEach((c, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `modal-swatch-btn ${idx === 0 ? 'active' : ''}`;
        btn.innerHTML = `
          <span style="width:16px; height:16px; border-radius:50%; background-color:${c.hex}; display:inline-block; border:1px solid rgba(255,255,255,0.4);"></span>
          <span>${c.name}</span>
        `;
        btn.addEventListener('click', () => {
          modalSwatchesContainer.querySelectorAll('.modal-swatch-btn').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          activeColor = c;
          if (c.image && modalImg) {
            modalImg.style.opacity = '0.3';
            setTimeout(() => {
              modalImg.src = c.image;
              modalImg.style.opacity = '1';
            }, 100);
          }
        });
        modalSwatchesContainer.appendChild(btn);
      });
    }

    // Populate Size Pills
    if (modalSizesContainer) {
      modalSizesContainer.innerHTML = '';
      (item.sizes || []).forEach((s, sidx) => {
        const sBtn = document.createElement('button');
        sBtn.type = 'button';
        sBtn.className = `modal-size-btn ${sidx === 0 ? 'active' : ''}`;
        sBtn.textContent = s.label;
        if (!s.available) {
          sBtn.disabled = true;
          sBtn.style.opacity = '0.35';
        }
        sBtn.addEventListener('click', () => {
          if (sBtn.disabled) return;
          modalSizesContainer.querySelectorAll('.modal-size-btn').forEach(b => b.classList.remove('active'));
          sBtn.classList.add('active');
          activeSize = s.label;
        });
        modalSizesContainer.appendChild(sBtn);
      });
    }

    updateModalPricing();

    if (modal) {
      modal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function updateModalPricing() {
    if (!activeHoodie) return;
    const unitPrice = parseFloat(activeHoodie.sale_price || activeHoodie.original_price || 0);
    const total = unitPrice * activeQty;
    const curr = store.currency || '$';

    if (modalQtyVal) modalQtyVal.textContent = activeQty;
    if (modalTotal) modalTotal.textContent = `${curr}${total.toFixed(2)}`;
  }

  // Hook up Card Click triggers
  document.querySelectorAll('.open-hoodie-buy').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const hId = trigger.dataset.hoodieId;
      openQuickBuy(hId);
    });
  });

  // Quantity in modal
  if (modalQtyMinus && modalQtyPlus) {
    modalQtyMinus.addEventListener('click', () => {
      if (activeQty > 1) {
        activeQty--;
        updateModalPricing();
      }
    });
    modalQtyPlus.addEventListener('click', () => {
      if (activeQty < 20) {
        activeQty++;
        updateModalPricing();
      }
    });
  }

  // Close modal
  function closeModal() {
    if (modal) {
      modal.classList.remove('open');
      document.body.style.overflow = '';
    }
  }
  if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
  }
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });

  // 3. WHATSAPP CHECKOUT LINK DISPATCH
  if (modalOrderBtn) {
    modalOrderBtn.addEventListener('click', () => {
      if (!activeHoodie) return;

      let phone = (store.whatsapp_number || '').replace(/[^\d]/g, '') || '923418956864';
      if (phone.length === 11 && phone.startsWith('03')) {
        phone = '92' + phone.substring(1);
      }
      const template = store.whatsapp_message_template || "Hello! I would like to order {product_name} ({hoodie_type}) 🛍️\n\n• Selected Color: {color}\n• Selected Size: {size}\n• Quantity: {quantity}\n• Total Price: {total_price}\n\nPlease confirm availability and share payment & delivery details!";

      const unitPrice = parseFloat(activeHoodie.sale_price || activeHoodie.original_price || 0);
      const totalFormatted = (store.currency || '$') + (unitPrice * activeQty).toFixed(2);

      const msg = template
        .replace('{product_name}', activeHoodie.title)
        .replace('{hoodie_type}', activeHoodie.type || 'Hoodie')
        .replace('{color}', activeColor ? activeColor.name : 'Standard')
        .replace('{size}', activeSize || 'M')
        .replace('{quantity}', activeQty.toString())
        .replace('{total_price}', totalFormatted);

      const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
      window.open(url, '_blank', 'noopener,noreferrer');
    });
  }

  // 4. ACCORDIONS
  document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
      const item = header.parentElement;
      const isOpen = item.classList.contains('active');
      const body = item.querySelector('.accordion-body');

      document.querySelectorAll('.accordion-item').forEach(other => {
        if (other !== item) {
          other.classList.remove('active');
          const ob = other.querySelector('.accordion-body');
          if (ob) ob.style.maxHeight = null;
        }
      });

      if (!isOpen) {
        item.classList.add('active');
        body.style.maxHeight = body.scrollHeight + 'px';
      } else {
        item.classList.remove('active');
        body.style.maxHeight = null;
      }
    });
  });
});

/**
 * Admin Panel Interactive Logic
 */

document.addEventListener('DOMContentLoaded', () => {
  // Tab Switcher
  const tabs = document.querySelectorAll('.tab-btn');
  const panels = document.querySelectorAll('.tab-panel');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const targetId = tab.dataset.tab;
      
      tabs.forEach(t => t.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      
      tab.classList.add('active');
      const targetPanel = document.getElementById(targetId);
      if (targetPanel) {
        targetPanel.classList.add('active');
      }
    });
  });

  // Dynamic Add Color Row
  const addColorBtn = document.getElementById('btn-add-color');
  const colorsContainer = document.getElementById('colors-tbody');

  if (addColorBtn && colorsContainer) {
    addColorBtn.addEventListener('click', () => {
      const rowCount = colorsContainer.querySelectorAll('tr').length;
      // Get main image preview src if available to use as placeholder
      const mainPreview = document.getElementById('main-photo-preview');
      const defaultImgSrc = mainPreview ? mainPreview.src : '../assets/images/hoodie-black.jpg';

      const tr = document.createElement('tr');
      tr.className = 'color-row';
      tr.innerHTML = `
        <td>
          <input type="text" name="colors[${rowCount}][name]" class="form-input" placeholder="e.g. Orange, Vintage Olive" required>
        </td>
        <td>
          <div style="display:flex; align-items:center; gap:8px;">
            <input type="color" name="colors[${rowCount}][hex]" value="#f97316" style="width:40px; height:38px; border:none; border-radius:6px; cursor:pointer; background:none;">
            <input type="text" class="form-input color-hex-val" value="#f97316" style="width:90px;" onchange="this.previousElementSibling.value = this.value">
          </div>
        </td>
        <td>
          <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:40px; height:40px; border-radius:6px; overflow:hidden; background:#000; border:1px solid var(--border-color); flex-shrink:0;">
              <img class="color-thumb-preview" src="${defaultImgSrc}" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <div style="flex-grow:1;">
              <input type="file" name="color_images[${rowCount}]" accept="image/*" class="form-input color-file-input" style="padding:5px 8px; font-size:12px;">
              <input type="hidden" name="colors[${rowCount}][existing_image]" value="">
              <div class="form-hint" style="font-size:11px; margin-top:2px;">Leave empty to use main photo.</div>
            </div>
          </div>
        </td>
        <td style="text-align: right;">
          <button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button>
        </td>
      `;
      colorsContainer.appendChild(tr);
    });
  }

  // Dynamic Add Size Row
  const addSizeBtn = document.getElementById('btn-add-size');
  const sizesContainer = document.getElementById('sizes-tbody');

  if (addSizeBtn && sizesContainer) {
    addSizeBtn.addEventListener('click', () => {
      const rowCount = sizesContainer.querySelectorAll('tr').length;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>
          <input type="text" name="sizes[${rowCount}][label]" class="form-input" placeholder="e.g. XXL" required style="width:80px;">
        </td>
        <td>
          <input type="text" name="sizes[${rowCount}][chest]" class="form-input" placeholder="e.g. 56&quot;">
        </td>
        <td>
          <input type="text" name="sizes[${rowCount}][length]" class="form-input" placeholder="e.g. 31&quot;">
        </td>
        <td style="text-align: center;">
          <input type="checkbox" name="sizes[${rowCount}][available]" value="1" checked style="width:18px; height:18px; cursor:pointer;">
        </td>
        <td style="text-align: right;">
          <button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button>
        </td>
      `;
      sizesContainer.appendChild(tr);
    });
  }

  // Handle Dynamic Row Deletion
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-remove-row')) {
      const row = e.target.closest('tr');
      if (row) {
        row.remove();
      }
    }
  });

  // Live File Upload Previews
  function bindLiveImagePreview(inputId, imgId) {
    const input = document.getElementById(inputId);
    const img = document.getElementById(imgId);
    if (input && img) {
      input.addEventListener('change', () => {
        if (input.files && input.files[0]) {
          const reader = new FileReader();
          reader.onload = (e) => {
            img.src = e.target.result;
          };
          reader.readAsDataURL(input.files[0]);
        }
      });
    }
  }

  bindLiveImagePreview('hoodie-image-input', 'main-photo-preview');
  bindLiveImagePreview('secondary-image-input', 'secondary-photo-preview');

  // Live File Upload Preview for Color Rows (delegated)
  document.addEventListener('change', (e) => {
    if (e.target.classList.contains('color-file-input')) {
      const file = e.target.files && e.target.files[0];
      if (file) {
        const row = e.target.closest('tr');
        const img = row ? row.querySelector('.color-thumb-preview') : null;
        if (img) {
          const reader = new FileReader();
          reader.onload = (re) => {
            img.src = re.target.result;
          };
          reader.readAsDataURL(file);
        }
      }
    }
  });

  // Interactive WhatsApp Template Live Preview
  const waTemplateInput = document.getElementById('wa-template-input');
  const waPreviewBox = document.getElementById('wa-preview-box');

  function updateWaPreview() {
    if (!waTemplateInput || !waPreviewBox) return;
    const prodTitle = document.getElementById('product-title')?.value || 'ARCHIVE HOODIE';
    let text = waTemplateInput.value
      .replace('{product_name}', prodTitle)
      .replace('{color}', 'Washed Onyx')
      .replace('{size}', 'L')
      .replace('{quantity}', '1')
      .replace('{total_price}', '$79.00');

    waPreviewBox.textContent = text;
  }

  if (waTemplateInput) {
    waTemplateInput.addEventListener('input', updateWaPreview);
    updateWaPreview();
  }
});

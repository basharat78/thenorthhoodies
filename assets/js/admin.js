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
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>
          <input type="text" name="colors[${rowCount}][name]" class="form-input" placeholder="e.g. Vintage Olive" required>
        </td>
        <td>
          <div style="display:flex; align-items:center; gap:8px;">
            <input type="color" name="colors[${rowCount}][hex]" value="#4a5568" style="width:40px; height:38px; border:none; border-radius:6px; cursor:pointer; background:none;">
            <input type="text" class="form-input color-hex-val" value="#4a5568" style="width:100px;" onchange="this.previousElementSibling.value = this.value">
          </div>
        </td>
        <td>
          <input type="text" name="colors[${rowCount}][image]" class="form-input" placeholder="assets/images/... or image URL">
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

  // Image Upload Dropzone Helper
  const dropzone = document.getElementById('gallery-dropzone');
  const fileInput = document.getElementById('gallery-file-input');

  if (dropzone && fileInput) {
    dropzone.addEventListener('click', () => {
      fileInput.click();
    });

    fileInput.addEventListener('change', () => {
      if (fileInput.files && fileInput.files[0]) {
        dropzone.querySelector('.dropzone-text').textContent = 'Selected: ' + fileInput.files[0].name;
        dropzone.querySelector('.dropzone-sub').textContent = 'Click "Save Changes" below to upload and append to gallery.';
        dropzone.style.borderColor = '#25d366';
      }
    });

    ['dragenter', 'dragover'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropzone.style.borderColor = '#ffffff';
        dropzone.style.background = 'rgba(255,255,255,0.08)';
      });
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropzone.style.borderColor = '';
        dropzone.style.background = '';
      });
    });

    dropzone.addEventListener('drop', (e) => {
      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        fileInput.files = e.dataTransfer.files;
        dropzone.querySelector('.dropzone-text').textContent = 'Selected: ' + fileInput.files[0].name;
        dropzone.querySelector('.dropzone-sub').textContent = 'Click "Save Changes" below to upload and append to gallery.';
        dropzone.style.borderColor = '#25d366';
      }
    });
  }

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

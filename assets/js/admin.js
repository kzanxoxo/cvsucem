

document.addEventListener('DOMContentLoaded', () => {

  
  const sidebarToggle  = document.getElementById('sidebarToggle');
  const sidebar        = document.querySelector('.admin-sidebar');
  const sidebarOverlay = document.querySelector('.sidebar-overlay');

  function openSidebar() {
    sidebar?.classList.add('open');
    sidebarOverlay?.classList.add('open');
  }
  function closeSidebar() {
    sidebar?.classList.remove('open');
    sidebarOverlay?.classList.remove('open');
  }
  sidebarToggle?.addEventListener('click', openSidebar);
  sidebarOverlay?.addEventListener('click', closeSidebar);

  
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href && currentPath.endsWith(href.split('/').pop())) {
      link.classList.add('active');
    }
  });

  
  document.querySelectorAll('[data-reg-btn]').forEach(btn => {
    btn.addEventListener('click', () => {
      const url   = btn.dataset.regUrl;
      const title = btn.dataset.eventTitle || 'Event';
      const modal = document.getElementById('regModal');
      if (!modal) return;
      document.getElementById('regEventName').textContent = title + ' — Registration';
      const field = document.getElementById('regLinkField');
      field.value = url;
      const copyBtn = document.getElementById('copyRegLinkBtn');
      if (copyBtn) copyBtn.dataset.copy = url;
      modal.classList.add('open');
    });
  });

  
  document.querySelectorAll('[data-qr-btn]').forEach(btn => {
    btn.addEventListener('click', () => {
      const url    = btn.dataset.checkinUrl;
      const title  = btn.dataset.eventTitle || 'Event';
      const modal  = document.getElementById('qrModal');
      if (!modal) return;
      document.getElementById('qrEventName').textContent = title;
      document.getElementById('qrImage').src = `https:
      document.getElementById('qrCheckinUrl').textContent = url;
      document.getElementById('qrCheckinUrl').href = url;
      modal.classList.add('open');
    });
  });

  
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const msg = this.dataset.confirm || 'Are you sure?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.modal-overlay')?.classList.remove('open'));
  });
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
  });

  
  const imageInput   = document.getElementById('eventImage');
  const imagePreview = document.getElementById('imagePreview');
  const uploadArea   = document.getElementById('uploadArea');

  if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => {
          imagePreview.src = e.target.result;
          imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }
  if (uploadArea) {
    uploadArea.addEventListener('click', () => imageInput?.click());
    uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
    uploadArea.addEventListener('drop', e => {
      e.preventDefault();
      uploadArea.classList.remove('dragover');
      if (e.dataTransfer.files[0] && imageInput) {
        const dt = new DataTransfer();
        dt.items.add(e.dataTransfer.files[0]);
        imageInput.files = dt.files;
        imageInput.dispatchEvent(new Event('change'));
      }
    });
  }

  
  document.querySelectorAll('.attendance-check').forEach(cb => {
    cb.addEventListener('change', async function() {
      const participantId = this.dataset.id;
      const status        = this.checked ? 'attended' : 'registered';
      const row           = this.closest('tr');
      const statusCell    = row?.querySelector('.status-cell');

      try {
        const resp = await fetch('../api/update_attendance.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: participantId, status })
        });
        const data = await resp.json();
        if (data.success && statusCell) {
          statusCell.innerHTML = status === 'attended'
            ? '<span class="badge badge-ongoing">Attended</span>'
            : '<span class="badge badge-upcoming">Registered</span>';
        } else {
          this.checked = !this.checked; 
          alert('Failed to update. Try again.');
        }
      } catch {
        this.checked = !this.checked;
        alert('Network error. Try again.');
      }
    });
  });

  
  const tableSearch = document.getElementById('tableSearch');
  if (tableSearch) {
    tableSearch.addEventListener('input', function() {
      const q    = this.value.toLowerCase();
      const rows = document.querySelectorAll('tbody tr[data-search]');
      rows.forEach(row => {
        row.style.display = row.dataset.search.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 400);
    }, 5000);
  });

  
  const eventDateInput = document.getElementById('event_date');
  if (eventDateInput && !eventDateInput.value) {
    eventDateInput.min = new Date().toISOString().split('T')[0];
  }

  
  document.querySelectorAll('.capacity-bar-fill').forEach(bar => {
    const pct = parseFloat(bar.dataset.fill || 0);
    bar.style.width = '0%';
    requestAnimationFrame(() => {
      bar.style.transition = 'width 1s ease';
      bar.style.width = pct + '%';
    });
  });

  
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => {
      const text = btn.dataset.copy;
      navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => { btn.textContent = orig; }, 2000);
      });
    });
  });

  
  const titleInput = document.getElementById('title');
  const dateInput = document.getElementById('event_date');
  const startTimeInput = document.getElementById('start_time');
  const locationInput = document.getElementById('location');
  const capacityInput = document.getElementById('max_participants');

  function updateFormSummary() {
    const title = titleInput?.value || '—';
    const date = dateInput?.value ? new Date(dateInput.value).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
    const startTime = startTimeInput?.value || '—';
    const location = locationInput?.value || '—';
    const capacity = capacityInput?.value ? (capacityInput.value === '0' ? 'Unlimited' : capacityInput.value + ' people') : '—';

    const summaryTitle = document.getElementById('summaryTitle');
    const summaryDate = document.getElementById('summaryDate');
    const summaryTime = document.getElementById('summaryTime');
    const summaryLocation = document.getElementById('summaryLocation');
    const summaryCapacity = document.getElementById('summaryCapacity');

    if (summaryTitle) summaryTitle.textContent = title;
    if (summaryDate) summaryDate.textContent = date;
    if (summaryTime) summaryTime.textContent = startTime;
    if (summaryLocation) summaryLocation.textContent = location;
    if (summaryCapacity) summaryCapacity.textContent = capacity;
  }

  if (titleInput) {
    titleInput.addEventListener('input', updateFormSummary);
    dateInput?.addEventListener('change', updateFormSummary);
    startTimeInput?.addEventListener('change', updateFormSummary);
    locationInput?.addEventListener('input', updateFormSummary);
    capacityInput?.addEventListener('input', updateFormSummary);
    updateFormSummary();
  }

});

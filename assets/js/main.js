

document.addEventListener('DOMContentLoaded', () => {

  
  const hamburger = document.querySelector('.hamburger');
  const navMenu   = document.querySelector('.navbar-nav');
  if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
      navMenu.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', navMenu.classList.contains('open'));
    });
    
    document.addEventListener('click', (e) => {
      if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
        navMenu.classList.remove('open');
      }
    });
  }

  
  const fadeEls = document.querySelectorAll('.fade-up');
  if (fadeEls.length) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    fadeEls.forEach(el => observer.observe(el));
  }

  
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-8px)';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el    = entry.target;
          const end   = parseInt(el.dataset.count, 10);
          const dur   = 1600;
          const step  = 16;
          const steps = dur / step;
          const inc   = end / steps;
          let   cur   = 0;
          const timer = setInterval(() => {
            cur += inc;
            if (cur >= end) { cur = end; clearInterval(timer); }
            el.textContent = Math.floor(cur).toLocaleString();
          }, step);
          counterObserver.unobserve(el);
        }
      });
    }, { threshold: 0.3 });
    counters.forEach(c => counterObserver.observe(c));
  }

  
  const searchInput  = document.getElementById('eventSearch');
  const catFilter    = document.getElementById('categoryFilter');
  const statusFilter = document.getElementById('statusFilter');
  const eventCards   = document.querySelectorAll('.event-card[data-title]');

  function filterEvents() {
    const query  = searchInput  ? searchInput.value.toLowerCase()  : '';
    const cat    = catFilter    ? catFilter.value.toLowerCase()    : '';
    const status = statusFilter ? statusFilter.value.toLowerCase() : '';

    eventCards.forEach(card => {
      const title    = (card.dataset.title    || '').toLowerCase();
      const location = (card.dataset.location || '').toLowerCase();
      const cardCat  = (card.dataset.category || '').toLowerCase();
      const cardSt   = (card.dataset.status   || '').toLowerCase();

      const matchSearch = !query  || title.includes(query) || location.includes(query);
      const matchCat    = !cat    || cardCat === cat;
      const matchStatus = !status || cardSt  === status;

      card.style.display = (matchSearch && matchCat && matchStatus) ? '' : 'none';
    });

    
    const noResults = document.getElementById('noResults');
    if (noResults) {
      const visible = [...eventCards].filter(c => c.style.display !== 'none');
      noResults.style.display = visible.length === 0 ? 'block' : 'none';
    }
  }

  if (searchInput)  searchInput.addEventListener('input', filterEvents);
  if (catFilter)    catFilter.addEventListener('change', filterEvents);
  if (statusFilter) statusFilter.addEventListener('change', filterEvents);

  
  const regForm = document.getElementById('registrationForm');
  if (regForm) {
    regForm.addEventListener('submit', function(e) {
      const studentId = document.getElementById('student_id');
      const name      = document.getElementById('student_name');
      const email     = document.getElementById('student_email');
      let   valid     = true;

      [studentId, name, email].forEach(field => {
        if (field && !field.value.trim()) {
          field.classList.add('error');
          valid = false;
        } else if (field) {
          field.classList.remove('error');
        }
      });

      if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        email.classList.add('error');
        valid = false;
      }

      if (!valid) {
        e.preventDefault();
        const errMsg = document.getElementById('formError');
        if (errMsg) errMsg.style.display = 'block';
      } else {
        
        const submitBtn = regForm.querySelector('[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '⏳ Registering...';
        }
      }
    });
  }

  
  document.querySelectorAll('.capacity-bar-fill').forEach(bar => {
    const pct = parseFloat(bar.dataset.fill || 0);
    bar.style.width = '0%';
    setTimeout(() => { bar.style.width = pct + '%'; }, 300);
    if (pct >= 90) bar.classList.add('full');
    else if (pct >= 70) bar.classList.add('warning');
  });

  
  document.querySelectorAll('[data-qr-btn]').forEach(btn => {
    btn.addEventListener('click', () => {
      const checkinUrl = btn.dataset.checkinUrl;
      const eventName = btn.dataset.eventTitle || 'Event';
      const modal    = document.getElementById('qrModal');
      if (!modal) return;
      const qrImg  = modal.querySelector('#qrImage');
      const qrUrl  = modal.querySelector('#qrCheckinUrl');
      const qrTitle = modal.querySelector('#qrEventName');
      if (qrTitle) qrTitle.textContent = eventName;
      if (qrImg)   qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(checkinUrl)}`;
      if (qrUrl) {
        qrUrl.textContent = checkinUrl;
        qrUrl.href = checkinUrl;
      }
      modal.classList.add('open');
    });
  });

  
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.closest('.modal-overlay')?.classList.remove('open');
    });
  });
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', function(e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

});

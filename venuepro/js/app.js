/**
 * VenuePro Full-Stack Application JavaScript
 * Handles AJAX / Fetch communication with PHP APIs, authentication,
 * dynamic forms, booking workflows, caterer approvals, and live data.
 */

(function () {
  'use strict';

  // Base API paths (handles both root and subfolder execution)
  const isSubfolder = window.location.pathname.includes('/customer/') ||
                      window.location.pathname.includes('/caterer/') ||
                      window.location.pathname.includes('/staff/') ||
                      window.location.pathname.includes('/admin/');
  const API_BASE = isSubfolder ? '../api/' : 'api/';

  // Helper: Display Alert Banner
  function showAlert(formElement, message, isSuccess = false) {
    let alertBox = formElement.parentElement.querySelector('.vp-alert-banner');
    if (!alertBox) {
      alertBox = document.createElement('div');
      alertBox.className = 'vp-alert-banner';
      formElement.parentElement.insertBefore(alertBox, formElement);
    }
    alertBox.style.cssText = `
      padding: 12px 16px;
      margin-bottom: 16px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
      animation: fadeIn 0.2s ease;
      background: ${isSuccess ? '#dcfce7' : '#fee2e2'};
      color: ${isSuccess ? '#166534' : '#991b1b'};
      border: 1.5px solid ${isSuccess ? '#86efac' : '#fca5a5'};
    `;
    alertBox.innerHTML = `${isSuccess ? '✓' : '⚠️'} ${message}`;
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  // ============================================================
  // 1. AUTHENTICATION: LOGIN FORMS
  // ============================================================
  function initAuthForms() {
    // Detect login forms
    const loginForms = document.querySelectorAll('form[action*="login"], form#login-form, .auth-page form');
    loginForms.forEach(form => {
      // Determine if form is login or register
      const isRegister = form.action.includes('register') || form.action.includes('signup') || form.querySelector('input[type="password"][id*="confirm"]');
      if (isRegister) return; // Handled below

      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const origText = submitBtn ? submitBtn.innerHTML : 'Sign In';
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = 'Signing In...';
        }

        const emailInput = form.querySelector('input[type="email"], input[name="email"], input[placeholder*="email" i]');
        const passInput = form.querySelector('input[type="password"]');

        // Extract role if on role-specific page
        let role = '';
        const path = window.location.pathname.toLowerCase();
        if (path.includes('customer')) role = 'customer';
        else if (path.includes('caterer')) role = 'caterer';
        else if (path.includes('staff')) role = 'staff';
        else if (path.includes('admin')) role = 'admin';

        try {
          const res = await fetch(API_BASE + 'auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              email: emailInput ? emailInput.value.trim() : '',
              password: passInput ? passInput.value : '',
              role: role
            })
          });

          const data = await res.json();
          if (data.success) {
            showAlert(form, data.message || 'Login successful!', true);
            setTimeout(() => {
              window.location.href = data.data.redirect || (isSubfolder ? 'customer-dashboard.php' : 'customer/customer-dashboard.php');
            }, 500);
          } else {
            showAlert(form, data.message || 'Login failed. Please check credentials.');
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = origText;
            }
          }
        } catch (err) {
          console.error(err);
          showAlert(form, 'Connection error. Please ensure XAMPP is running.');
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;
          }
        }
      });
    });

    // Detect registration forms
    const registerForms = document.querySelectorAll('form[action*="register"], form[action*="signup"], form[action*="pending"], form#register-form');
    registerForms.forEach(form => {
      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const origText = submitBtn ? submitBtn.innerHTML : 'Create Account';
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = 'Registering...';
        }

        const nameInput = form.querySelector('input[name="name"], input[placeholder*="Name" i], input[placeholder*="Full" i]');
        const emailInput = form.querySelector('input[type="email"]');
        const accessCodeInput = form.querySelector('input[name="access_code"], input[placeholder*="Access Code" i], input[placeholder*="Token" i]');
        const passInputs = Array.from(form.querySelectorAll('input[type="password"]')).filter(p => p !== accessCodeInput);
        const pass = passInputs[0] ? passInputs[0].value : '';
        const confirmPass = passInputs[1] ? passInputs[1].value : pass;

        if (pass !== confirmPass) {
          showAlert(form, 'Passwords do not match.');
          if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
          return;
        }

        let role = 'customer';
        const path = window.location.pathname.toLowerCase();
        if (path.includes('caterer')) role = 'caterer';
        else if (path.includes('staff')) role = 'staff';
        else if (path.includes('admin')) role = 'admin';

        // Additional fields
        const bizNameInput = form.querySelector('input[placeholder*="Business" i]');
        const kitchenAddrInput = form.querySelector('input[placeholder*="Address" i], textarea[placeholder*="Address" i]');
        const staffIdInput = form.querySelector('input[placeholder*="Staff ID" i], input[placeholder*="Code" i]');

        try {
          const res = await fetch(API_BASE + 'auth.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              name: nameInput ? nameInput.value.trim() : 'User',
              email: emailInput ? emailInput.value.trim() : '',
              password: pass,
              role: role,
              business_name: bizNameInput ? bizNameInput.value.trim() : '',
              kitchen_address: kitchenAddrInput ? kitchenAddrInput.value.trim() : '',
              staff_id: staffIdInput ? staffIdInput.value.trim() : '',
              access_code: accessCodeInput ? accessCodeInput.value.trim() : ''
            })
          });

          const data = await res.json();
          if (data.success) {
            showAlert(form, data.message || 'Registration successful!', true);
            setTimeout(() => {
              window.location.href = data.data.redirect || (isSubfolder ? 'customer-dashboard.php' : 'customer/customer-dashboard.php');
            }, 600);
          } else {
            showAlert(form, data.message || 'Registration error.');
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
          }
        } catch (err) {
          console.error(err);
          showAlert(form, 'Connection error. Please try again.');
          if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
        }
      });
    });

    // Detect Logout links
    document.querySelectorAll('a[href*="login-role"], .btn-logout, a.nav-item[href*="login"]').forEach(link => {
      const text = link.textContent.toLowerCase();
      if (text.includes('log out') || text.includes('logout') || text.includes('sign out') || text.includes('exit') || link.classList.contains('btn-logout')) {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          link.style.opacity = '0.6';
          link.style.pointerEvents = 'none';
          
          // Clear client storage
          try { sessionStorage.clear(); } catch(err) {}

          const targetUrl = isSubfolder ? '../venues.php' : 'venues.php';
          const logoutUrl = API_BASE + 'auth.php?action=logout';

          let redirected = false;
          function doRedirect() {
            if (!redirected) {
              redirected = true;
              window.location.href = targetUrl;
            }
          }

          // Guaranteed fast transition within 350ms
          setTimeout(doRedirect, 350);

          fetch(logoutUrl, { method: 'POST', keepalive: true })
            .then(() => doRedirect())
            .catch(() => doRedirect());
        });
      }
    });
  }

  // ============================================================
  // 2. UNIVERSAL REAL-TIME SEARCH & FILTERS
  // ============================================================
  function initGlobalSearch() {
    const searchInputs = document.querySelectorAll(
      '.topbar-search input, input[type="search"], input[name="search"], input[placeholder*="search" i]'
    );
    if (!searchInputs || searchInputs.length === 0) return;

    let debounceTimer;

    function escapeHtml(str) {
      return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function performGlobalFilter(term, sourceInput) {
      const lower = term.toLowerCase();

      // 1. Sync other search inputs on the page without triggering loop
      searchInputs.forEach(input => {
        if (input !== sourceInput && input.value !== term) {
          input.value = term;
        }
      });

      // 2. Table rows filtering (Admin tables, Staff tables, Order tables, Recent activities, etc.)
      const tables = document.querySelectorAll('table');
      tables.forEach(table => {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr')).filter(tr => 
          !tr.classList.contains('no-filter') && 
          !tr.classList.contains('vp-search-empty-row')
        );
        if (rows.length === 0) return;

        let visibleCount = 0;
        rows.forEach(tr => {
          const matches = !lower || tr.textContent.toLowerCase().includes(lower);
          tr.style.display = matches ? '' : 'none';
          if (matches) visibleCount++;
        });

        // Manage empty state indicator row
        let emptyRow = tbody.querySelector('.vp-search-empty-row');
        if (visibleCount === 0 && lower) {
          if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'vp-search-empty-row';
            const colSpan = (table.querySelector('thead tr') || rows[0]).children.length || 7;
            emptyRow.innerHTML = `<td colspan="${colSpan}" style="text-align:center; padding:32px 16px; color:var(--gray-400);">
              <div style="font-size:1.5rem; margin-bottom:6px;">🔍</div>
              <div>No matching records found for "<strong>${escapeHtml(term)}</strong>"</div>
            </td>`;
            tbody.appendChild(emptyRow);
          }
        } else if (emptyRow) {
          emptyRow.remove();
        }
      });

      // 3. Venue cards filtering (venues.php, customer/venue-listings.php)
      const venueCards = document.querySelectorAll('.venue-card, .venue-item, .venue-card-item');
      if (venueCards.length > 0) {
        venueCards.forEach(card => {
          const matches = !lower || card.textContent.toLowerCase().includes(lower);
          card.style.display = matches ? '' : 'none';
        });
      }

      // 4. Catalog cards & grid items (Caterers, Packages, Staff, Notification center items)
      const catalogCards = document.querySelectorAll(
        '.grid-3 > .card, .grid-2 > .card, .order-select-card, .caterer-card, .staff-card, .notif-item'
      );
      catalogCards.forEach(card => {
        if (card.querySelector('form') || card.closest('#pkg-decision-card') || card.classList.contains('vp-no-search')) return;
        const matches = !lower || card.textContent.toLowerCase().includes(lower);
        card.style.display = matches ? '' : 'none';
      });
    }

    searchInputs.forEach(searchInput => {
      // Real-time input filtering with short debounce
      searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          performGlobalFilter(searchInput.value.trim(), searchInput);
        }, 150);
      });

      // Handle Enter keypress for submission if applicable
      searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          const term = searchInput.value.trim();
          const filterForm = document.getElementById('venue-filter-form') || document.getElementById('sort-form');
          if (filterForm && !searchInput.closest('form')) {
            const formSearch = filterForm.querySelector('input[name="search"]');
            if (formSearch) {
              formSearch.value = term;
              filterForm.submit();
            }
          }
        }
      });
    });
  }

  // ============================================================
  // 3. BOOKING FLOW (Payment & Singular Add-On Items submission)
  // ============================================================
  function initBookingPayment() {
    const paymentForm = document.getElementById('payment-form');
    if (!paymentForm) return;

    paymentForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const submitBtn = paymentForm.querySelector('button[type="submit"]');
      const origText = submitBtn ? submitBtn.innerHTML : 'Pay Now →';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Processing Secure Payment...';
      }

      // Collect singular items from page
      const singularItems = [];
      const itemInputs = document.querySelectorAll('.qty-input, input[id^="qty-"]');
      itemInputs.forEach(inp => {
        const qty = parseInt(inp.value) || 0;
        if (qty > 0) {
          const itemId = inp.getAttribute('data-item-id') || inp.id.replace('qty-', '');
          const itemName = inp.getAttribute('data-name') || inp.id;
          singularItems.push({
            id: isNaN(itemId) ? 1 : parseInt(itemId),
            name: itemName,
            qty: qty
          });
        }
      });

      // Saved booking data or defaults
      const venueId = sessionStorage.getItem('vp_venue_id') || 1;
      const packageId = sessionStorage.getItem('vp_package_id') || 2; // Default Platinum
      const eventDate = sessionStorage.getItem('vp_date') || '2026-12-14';
      const guests = sessionStorage.getItem('vp_guests') || 150;

      try {
        const res = await fetch(API_BASE + 'bookings.php?action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            venue_id: venueId,
            package_id: packageId,
            event_name: 'Corporate Evening Gala',
            event_date: eventDate,
            guest_count: guests,
            card_last4: '4242',
            singular_items: singularItems
          })
        });

        const data = await res.json();
        if (data.success) {
          showAlert(paymentForm, 'Booking and Payment Confirmed! Redirecting...', true);
          sessionStorage.removeItem('vp_venue_id');
          sessionStorage.removeItem('vp_package_id');
          setTimeout(() => {
            window.location.href = data.data.redirect || 'booking-success.php';
          }, 800);
        } else {
          showAlert(paymentForm, data.message || 'Payment processing error.');
          if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
        }
      } catch (err) {
        console.error(err);
        showAlert(paymentForm, 'Connection error. Please check server connection.');
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
      }
    });
  }

  // ============================================================
  // 4. CATERER MENU ITEMS (Add, Edit, Delete via API)
  // ============================================================
  function initCatererMenuItems() {
    const addModal = document.getElementById('modal-add-item');
    if (!addModal) return;

    // Save button in modal
    const saveBtn = addModal.querySelector('a[href*="caterer-menu-items"], button.btn-primary');
    if (saveBtn) {
      saveBtn.addEventListener('click', async function (e) {
        e.preventDefault();
        const nameInp = addModal.querySelector('input[placeholder*="Grilled Tiger Shrimp" i]');
        const priceInp = addModal.querySelector('input[type="number"][placeholder*="12.50" i]');
        const qtyInp = addModal.querySelector('input[type="number"][placeholder*="50" i]');
        const catSelect = addModal.querySelector('select');
        const descInp = addModal.querySelector('textarea');

        const name = nameInp ? nameInp.value.trim() : '';
        const price = priceInp ? parseFloat(priceInp.value) : 0;
        const qty = qtyInp ? parseInt(qtyInp.value) : 50;
        const cat = catSelect ? catSelect.value : 'Food';
        const desc = descInp ? descInp.value.trim() : '';

        if (!name || price <= 0) {
          alert('Please enter a valid item name and price.');
          return;
        }

        try {
          const res = await fetch(API_BASE + 'singular-items.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              name: name,
              price: price,
              quantity_available: qty,
              category: cat,
              description: desc
            })
          });

          const data = await res.json();
          if (data.success) {
            alert('✓ Item added to singular menu!');
            window.location.hash = '';
            window.location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        } catch (err) {
          console.error(err);
          window.location.hash = '';
        }
      });
    }
  }

  // ============================================================
  // 5. CATERER ORDER DETAILS (Accept/Reject sync with API)
  // ============================================================
  function initCatererOrderSync() {
    // Intercept package decision buttons if present
    const btnPkgAccept = document.getElementById('btn-pkg-accept');
    const btnPkgReject = document.getElementById('btn-pkg-reject');

    if (btnPkgAccept) {
      btnPkgAccept.addEventListener('click', function () {
        syncPackageDecision('Accepted');
      });
    }
    if (btnPkgReject) {
      btnPkgReject.addEventListener('click', function () {
        syncPackageDecision('Rejected');
      });
    }

    async function syncPackageDecision(decision) {
      try {
        await fetch(API_BASE + 'caterer-orders.php?action=decide_package', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            order_id: 1,
            decision: decision
          })
        });
      } catch (e) {
        console.log('Synced locally');
      }
    }
  }

  // ============================================================
  // 6. STAFF EVENT SETUP CHECKLIST PERSISTENCE
  // ============================================================
  function initStaffChecklist() {
    const checklistItems = document.querySelectorAll('.setup-checklist input[type="checkbox"], .checklist-item input[type="checkbox"]');
    if (checklistItems.length === 0) return;

    checklistItems.forEach(cb => {
      cb.addEventListener('change', async function () {
        const checkedList = [];
        checklistItems.forEach(item => {
          const label = item.parentElement.textContent.trim();
          checkedList.push(label + (item.checked ? ' ✓' : ' ○'));
        });

        try {
          await fetch(API_BASE + 'staff.php?action=update_checklist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              assignment_id: 1,
              checklist: checkedList,
              setup_status: 'in_progress'
            })
          });
        } catch (e) {}
      });
    });
  }

  // ============================================================
  // 7. CHAT MESSAGE SENDING (Customer & Staff Chat)
  // ============================================================
  function initChat() {
    const chatInput = document.querySelector('.chat-input input, .chat-input-bar input');
    const sendBtn = document.querySelector('.chat-input button, .chat-send-btn');
    const messagesContainer = document.querySelector('.chat-messages, .chat-conversation');

    if (!chatInput || !sendBtn || !messagesContainer) return;

    async function sendMessage() {
      const msg = chatInput.value.trim();
      if (!msg) return;

      // Append bubble immediately
      const bubble = document.createElement('div');
      bubble.className = 'chat-message sent';
      bubble.style.cssText = 'align-self:flex-end; background:var(--primary); color:#fff; padding:10px 16px; border-radius:12px; margin-bottom:8px; max-width:70%;';
      bubble.textContent = msg;
      messagesContainer.appendChild(bubble);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
      chatInput.value = '';

      // Determine receiver (default staff id 3)
      const receiverId = window.location.pathname.includes('staff') ? 1 : 3;

      try {
        await fetch(API_BASE + 'chat.php?action=send', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            receiver_id: receiverId,
            message: msg
          })
        });
      } catch (e) {}
    }

    sendBtn.addEventListener('click', (e) => { e.preventDefault(); sendMessage(); });
    chatInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); sendMessage(); }
    });
  }

  // ============================================================
  // 8. NOTIFICATIONS MARK AS READ
  // ============================================================
  function initNotifications() {
    const markAllBtn = document.querySelector('.btn-mark-all-read, a[href*="mark_all"]');
    if (markAllBtn) {
      markAllBtn.addEventListener('click', async function (e) {
        e.preventDefault();
        try {
          await fetch(API_BASE + 'notifications.php?action=mark_all_read');
          document.querySelectorAll('.unread, .notif-unread').forEach(el => {
            el.classList.remove('unread', 'notif-unread');
          });
          const badge = document.querySelector('.topbar-actions .badge');
          if (badge) badge.style.display = 'none';
        } catch (err) {}
      });
    }
  }

  // ============================================================
  // 9. PASSWORD SHOW / HIDE TOGGLE
  // ============================================================
  function initPasswordToggles() {
    document.querySelectorAll('input[type="password"]').forEach(input => {
      if (input.dataset.hasToggle) return;
      input.dataset.hasToggle = 'true';

      let container = input.parentElement;
      if (!container.classList.contains('input-wrap') && !container.classList.contains('password-wrap')) {
        const wrap = document.createElement('div');
        wrap.className = 'password-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        container = wrap;
      } else {
        container.style.position = 'relative';
      }

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'password-toggle-btn';
      btn.setAttribute('aria-label', 'Toggle password visibility');
      btn.title = 'Show/Hide Password';
      btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;

      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (input.type === 'password') {
          input.type = 'text';
          btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
        } else {
          input.type = 'password';
          btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
        }
      });

      container.appendChild(btn);
    });
  }

  // DOMContentLoaded Initializer
  document.addEventListener('DOMContentLoaded', function () {
    initAuthForms();
    initPasswordToggles();
    initGlobalSearch();
    initBookingPayment();
    initCatererMenuItems();
    initCatererOrderSync();
    initStaffChecklist();
    initChat();
    initNotifications();
  });
})();

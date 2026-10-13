/**
 * VenuePro Full-Stack Application JavaScript
 * Handles AJAX / Fetch communication with PHP APIs, authentication,
 * dynamic forms, booking workflows, caterer approvals, and live data.
 */

// Auto-load form-validation.js (F-1: required field red border/label on submit)
(function () {
  var s = document.createElement('script');
  var base = (window.location.pathname.includes('/customer/') ||
              window.location.pathname.includes('/caterer/') ||
              window.location.pathname.includes('/staff/') ||
              window.location.pathname.includes('/admin/'))
             ? '../js/form-validation.js' : 'js/form-validation.js';
  s.src = base;
  document.head.appendChild(s);
})();

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
    let alertBox = null;
    if (formElement && formElement.parentElement) {
      alertBox = formElement.parentElement.querySelector('#registerAlert, #signupAlert, .vp-alert-banner');
    }
    if (!alertBox) {
      alertBox = document.getElementById('registerAlert') || document.getElementById('signupAlert');
    }
    if (!alertBox && formElement && formElement.parentElement) {
      alertBox = document.createElement('div');
      alertBox.className = 'vp-alert-banner';
      formElement.parentElement.insertBefore(alertBox, formElement);
    }
    if (alertBox) {
      alertBox.style.display = 'block';
      alertBox.style.padding = '12px 16px';
      alertBox.style.marginBottom = '16px';
      alertBox.style.borderRadius = '6px';
      alertBox.style.fontSize = '0.85rem';
      alertBox.style.fontWeight = '600';
      alertBox.style.animation = 'fadeIn 0.2s ease';
      alertBox.style.background = isSuccess ? '#dcfce7' : '#fee2e2';
      alertBox.style.color = isSuccess ? '#166534' : '#991b1b';
      alertBox.style.border = `1.5px solid ${isSuccess ? '#86efac' : '#fca5a5'}`;
      alertBox.innerHTML = `${isSuccess ? '✓' : '⚠️'} ${message}`;
      alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  // ============================================================
  // 1. AUTHENTICATION: LOGIN FORMS
  // ============================================================
  function initAuthForms() {
    // Detect login forms
    const loginForms = document.querySelectorAll('form[action*="login"], form#login-form, .auth-page form');
    loginForms.forEach(form => {
      // Guard against multiple attachments
      if (form.dataset.vpAuthInit) return;

      // Determine if form is login or register
      const isRegister = form.id === 'register-form' ||
                         form.id === 'signup-form' ||
                         (form.getAttribute('action') && (form.getAttribute('action').includes('register') || form.getAttribute('action').includes('signup'))) ||
                         window.location.pathname.includes('register') ||
                         window.location.pathname.includes('signup') ||
                         form.querySelector('input[name*="confirm"]') ||
                         form.querySelector('input[name="access_code"]');
      if (isRegister) return; // Handled below

      form.dataset.vpAuthInit = 'true';

      form.addEventListener('submit', async function (e) {
        if (form.dataset.submitting === 'true') {
          e.preventDefault();
          return;
        }
        e.preventDefault();
        form.dataset.submitting = 'true';

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
        const roleInput = form.querySelector('input[name="role"]');
        if (roleInput && roleInput.value) {
          role = roleInput.value.toLowerCase();
        } else {
          const path = window.location.pathname.toLowerCase();
          if (path.includes('customer')) role = 'customer';
          else if (path.includes('caterer')) role = 'caterer';
          else if (path.includes('staff')) role = 'staff';
          else if (path.includes('admin')) role = 'admin';
        }

        try {
          const res = await fetch(API_BASE + 'auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
              email: emailInput ? emailInput.value.trim() : '',
              password: passInput ? passInput.value : '',
              role: role
            })
          });

          const data = await res.json();
          if (data.success) {
            showAlert(form, data.message || 'Login successful!', true);
            let dest = data.data && data.data.redirect ? data.data.redirect : '';
            if (isSubfolder && dest.startsWith('../')) {
              dest = dest.replace(/^\.\.\/[^\/]+\//, '');
            }
            if (!dest) {
              dest = isSubfolder ? (role ? `${role}-dashboard.php` : 'customer-dashboard.php') : `customer/customer-dashboard.php`;
            }
            setTimeout(() => {
              window.location.href = dest;
            }, 500);
          } else {
            delete form.dataset.submitting;
            showAlert(form, data.message || 'Login failed. Please check credentials.');
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = origText;
            }
          }
        } catch (err) {
          delete form.dataset.submitting;
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
    const registerForms = document.querySelectorAll('form[action*="register"], form[action*="signup"], form[action*="pending"], form#register-form, form#signup-form');
    registerForms.forEach(form => {
      if (form.dataset.vpAuthInit) return;
      form.dataset.vpAuthInit = 'true';

      form.addEventListener('submit', async function (e) {
        if (form.dataset.submitting === 'true') {
          e.preventDefault();
          return;
        }
        e.preventDefault();

        // Check terms checkbox if present
        const termsCheckbox = form.querySelector('input#terms, input[name="terms"]');
        if (termsCheckbox && !termsCheckbox.checked) {
          showAlert(form, 'Please agree to the Terms of Service & Privacy Policy.');
          return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const origText = submitBtn ? submitBtn.innerHTML : 'Create Account';

        const nameInput = form.querySelector('input[name="name"], input[placeholder*="Name" i], input[placeholder*="Full" i]');
        const emailInput = form.querySelector('input[type="email"], input[name="email"]');
        const phoneInput = form.querySelector('input[name="phone"], input[type="tel"]');
        const accessCodeInput = form.querySelector('input[name="access_code"], input[name="admin_code"], input[placeholder*="Access Code" i], input[placeholder*="Token" i]');
        
        // Passwords
        const passInput = form.querySelector('input[name="password"]');
        const confirmPassInput = form.querySelector('input[name="confirm_password"], input[name="password_confirm"], input[placeholder*="Confirm" i]');
        
        const pass = passInput ? passInput.value : '';
        const confirmPass = confirmPassInput ? confirmPassInput.value : pass;

        if (confirmPassInput && pass !== confirmPass) {
          showAlert(form, 'Passwords do not match.');
          return;
        }

        if (pass.length < 6) {
          showAlert(form, 'Password must be at least 6 characters.');
          return;
        }

        // Lock form submission immediately to prevent race conditions or double clicks
        form.dataset.submitting = 'true';
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = 'Creating account...';
        }

        // Extract role
        let role = 'customer';
        const roleInput = form.querySelector('input[name="role"]');
        if (roleInput && roleInput.value) {
          role = roleInput.value.toLowerCase();
        } else {
          const path = window.location.pathname.toLowerCase();
          if (path.includes('caterer')) role = 'caterer';
          else if (path.includes('staff')) role = 'staff';
          else if (path.includes('admin')) role = 'admin';
        }

        // Additional role-specific fields
        const bizNameInput = form.querySelector('input[name="business_name"], input[placeholder*="Business" i]');
        const kitchenAddrInput = form.querySelector('input[name="kitchen_address"], textarea[name="kitchen_address"], input[placeholder*="Address" i]');
        const staffIdInput = form.querySelector('input[name="staff_id"], input[placeholder*="Staff ID" i], input[placeholder*="Code" i]');
        const deptInput = form.querySelector('select[name="department"], input[name="department"]');
        const assignedVenueInput = form.querySelector('select[name="assigned_venue_id"], select[name="assigned_venues"]');

        const payload = {
          name: nameInput ? nameInput.value.trim() : 'User',
          email: emailInput ? emailInput.value.trim() : '',
          password: pass,
          role: role,
          phone: phoneInput ? phoneInput.value.trim() : '',
          business_name: bizNameInput ? bizNameInput.value.trim() : '',
          kitchen_address: kitchenAddrInput ? kitchenAddrInput.value.trim() : '',
          staff_id: staffIdInput ? staffIdInput.value.trim() : '',
          department: deptInput ? deptInput.value : '',
          assigned_venues: assignedVenueInput ? assignedVenueInput.value : '',
          access_code: accessCodeInput ? accessCodeInput.value.trim() : ''
        };

        try {
          const res = await fetch(API_BASE + 'auth.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
          });

          const data = await res.json();
          if (data.success) {
            showAlert(form, data.message || 'Registration successful! Welcome to VenuePro.', true);
            let dest = data.data && data.data.redirect ? data.data.redirect : '';
            if (isSubfolder && dest.startsWith('../')) {
              dest = dest.replace(/^\.\.\/[^\/]+\//, '');
            }
            if (!dest) {
              dest = isSubfolder ? `${role}-dashboard.php` : `${role}/${role}-dashboard.php`;
            }
            setTimeout(() => {
              window.location.href = dest;
            }, 700);
          } else {
            delete form.dataset.submitting;
            showAlert(form, data.message || 'Registration failed.');
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = origText;
            }
          }
        } catch (err) {
          delete form.dataset.submitting;
          console.error(err);
          showAlert(form, 'Connection error. Please try again.');
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;
          }
        }
      });
    });

    // Detect Logout links across all roles and pages
    document.querySelectorAll('a').forEach(link => {
      const text = (link.textContent || '').trim().toLowerCase();
      const href = (link.getAttribute('href') || '').toLowerCase();
      if (text === 'log out' || text === 'logout' || text.includes('log out') || text.includes('logout') || text.includes('switch role / logout') || link.classList.contains('btn-logout') || link.classList.contains('logout-link') || href.includes('logout.php') || href.includes('action=logout')) {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          link.style.opacity = '0.6';
          link.style.pointerEvents = 'none';
          
          // Clear client storage
          try { sessionStorage.clear(); localStorage.clear(); } catch(err) {}

          const logoutUrl = isSubfolder ? '../logout.php' : 'logout.php';
          window.location.href = logoutUrl;
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

      // Saved booking data or defaults from form/sessionStorage
      const isCustom = sessionStorage.getItem('vp_is_custom_package') === '1' || (new URLSearchParams(window.location.search).get('custom_package') === '1');
      let customDishes = [];
      let customCost = 0;
      if (isCustom) {
        try {
          customDishes = JSON.parse(sessionStorage.getItem('vp_custom_dishes') || '[]');
        } catch (e) {}
        customCost = parseFloat(sessionStorage.getItem('vp_custom_total') || '0');
      }

      const venueId = (paymentForm.querySelector('[name="venue_id"]') || {}).value || sessionStorage.getItem('vp_venue_id') || 1;
      const packageId = isCustom ? null : ((paymentForm.querySelector('[name="package_id"]') || {}).value || sessionStorage.getItem('vp_package_id') || 2);
      const eventDate = (paymentForm.querySelector('[name="event_date"]') || {}).value || sessionStorage.getItem('vp_event_date') || sessionStorage.getItem('vp_date') || '2026-10-14';
      const guests = parseInt((paymentForm.querySelector('[name="guest_count"]') || {}).value || (paymentForm.querySelector('[name="guests"]') || {}).value || sessionStorage.getItem('vp_guest_count') || sessionStorage.getItem('vp_guests') || 100) || 100;
      const startTime = (paymentForm.querySelector('[name="start_time"]') || {}).value || sessionStorage.getItem('vp_start_time') || '18:00:00';
      const endTime = (paymentForm.querySelector('[name="end_time"]') || {}).value || sessionStorage.getItem('vp_end_time') || '22:00:00';
      const eventName = (paymentForm.querySelector('[name="event_name"]') || {}).value || (isCustom ? 'Custom Banquet Event' : 'Executive Corporate Gala');
      const specialNotes = sessionStorage.getItem('vp_chef_notes') || '';

      try {
        const res = await fetch(API_BASE + 'bookings.php?action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            venue_id: venueId,
            package_id: packageId,
            is_custom_package: isCustom ? 1 : 0,
            custom_package_cost: isCustom ? customCost : null,
            custom_dishes: isCustom ? customDishes : [],
            event_name: eventName,
            event_date: eventDate,
            start_time: startTime,
            end_time: endTime,
            guest_count: guests,
            caterer_id: (paymentForm.querySelector('[name="caterer_id"]') || {}).value || sessionStorage.getItem('vp_caterer_id') || null,
            card_last4: '4242',
            special_notes: specialNotes,
            singular_items: singularItems
          })
        });

        const data = await res.json();
        if (data.success) {
          showAlert(paymentForm, 'Booking and Payment Confirmed! Redirecting...', true);
          sessionStorage.removeItem('vp_venue_id');
          sessionStorage.removeItem('vp_package_id');
          sessionStorage.removeItem('vp_caterer_id');
          sessionStorage.removeItem('vp_event_date');
          sessionStorage.removeItem('vp_date');
          sessionStorage.removeItem('vp_is_custom_package');
          sessionStorage.removeItem('vp_custom_dishes');
          sessionStorage.removeItem('vp_custom_total');
          sessionStorage.removeItem('vp_custom_rate');
          sessionStorage.removeItem('vp_chef_notes');
          setTimeout(() => {
            window.location.href = data.data.redirect || 'booking-success.php';
          }, 800);
        } else {
          if (data.data && data.data.conflict) {
            const conflictUrl = `booking-date-conflict.php?venue_id=${encodeURIComponent(venueId)}&date=${encodeURIComponent(eventDate)}`;
            showAlert(paymentForm, `⚠️ ${data.message || 'Date conflict detected!'} <div style="margin-top:8px;"><a href="${conflictUrl}" class="btn btn-sm btn-outline" style="text-decoration:none;">View Conflict Alert &amp; Alternative Dates →</a></div>`);
          } else {
            showAlert(paymentForm, data.message || 'Payment processing error.');
          }
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

    if (window.location.pathname.includes('customer-chat.php') || window.location.pathname.includes('staff-chat.php')) {
      return; // Dedicated real-time chat with live polling handles this
    }

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
  // 8. NOTIFICATIONS MARK AS READ & DYNAMIC UNREAD BADGE SYNC
  // ============================================================
  function initNotifications() {
    async function syncNotificationBadge() {
      try {
        const res = await fetch(API_BASE + 'notifications.php?action=unread_count');
        const data = await res.json();
        const count = (data && data.success && data.data) ? (parseInt(data.data.count, 10) || 0) : 0;
        const badges = document.querySelectorAll('.topbar-actions .badge, .topbar-icon-btn .badge, #notif-badge');
        badges.forEach(b => {
          b.dataset.count = count;
          if (count > 0) {
            b.textContent = count;
            b.style.display = 'flex';
          } else {
            b.textContent = '';
            b.style.display = 'none';
          }
        });
      } catch (err) {}
    }

    // Always sync on page load to ensure fresh accounts immediately show 0/hidden badge
    syncNotificationBadge();

    const markAllBtn = document.querySelector('.btn-mark-all-read, #admin-mark-all-read, .btn-ghost.btn-sm, a[href*="mark_all"]');
    if (markAllBtn) {
      markAllBtn.addEventListener('click', async function (e) {
        e.preventDefault();
        try {
          await fetch(API_BASE + 'notifications.php?action=mark_all_read');
          document.querySelectorAll('.unread, .notif-unread').forEach(el => {
            el.classList.remove('unread', 'notif-unread');
          });
          document.querySelectorAll('.notif-dot').forEach(el => {
            el.style.display = 'none';
          });
          const badges = document.querySelectorAll('.topbar-actions .badge, .topbar-icon-btn .badge, #notif-badge');
          badges.forEach(b => {
            b.dataset.count = 0;
            b.textContent = '';
            b.style.display = 'none';
          });
        } catch (err) {}
      });
    }

    // Individual notification items click to mark as read
    document.querySelectorAll('.notif-item[data-notif-id]').forEach(item => {
      item.addEventListener('click', async function () {
        const id = this.dataset.notifId;
        if (this.classList.contains('unread')) {
          try {
            await fetch(API_BASE + 'notifications.php?action=mark_read&id=' + id);
            this.classList.remove('unread');
            syncNotificationBadge();
          } catch(e) {}
        }
      });
    });
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

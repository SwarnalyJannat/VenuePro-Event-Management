<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();

$pendingCount = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – User Governance</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .filter-tabs { display:flex; gap:8px; border-bottom:1px solid var(--gray-200); padding-bottom:12px; margin-bottom:20px; }
    .filter-tab {
      padding:8px 16px; border-radius:6px; font-size:0.85rem; font-weight:600;
      cursor:pointer; border:1px solid transparent; background:transparent; color:var(--gray-600);
      transition:all 0.15s;
    }
    .filter-tab.active { background:#eff6ff; color:var(--primary); border-color:#bfdbfe; }
    .filter-tab:hover:not(.active) { background:var(--gray-100); }
    .action-btn-group { display:flex; gap:6px; align-items:center; }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div class="sidebar-logo-text">VenuePro</div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Governance</div>
        <a href="admin-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="admin-pending-bookings.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Bookings Approvals
        </a>
        <a href="venue-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Venue Catalog
        </a>
        <a href="admin-catering-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg> Catering Oversight
        </a>
        <a href="admin-staff-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Staff Directory
        </a>
        <a href="admin-user-management.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> User Governance
        </a>
        <a href="admin-reports.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Reports &amp; Analytics
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../logout.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Log out
        </a>
      </div>
    </aside>

    <div class="main-content">
      <header class="topbar">
        <label for="sidebar-toggle" class="sidebar-toggle-btn" title="Toggle Sidebar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </label>
        <div class="topbar-search">
          <span class="topbar-search-icon">🔍</span>
          <input type="text" id="topbarSearchInput" placeholder="e.g. Search bookings, venues, staff, caterers..." oninput="handleSearch(this.value)">
        </div>
        <div class="topbar-actions">
          <a href="notification-center.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge"><?= $pendingCount ?></span>
            🔔
          </a>
          <a href="admin-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="View & Edit My Profile">
            <div class="user-avatar" style="background:<?= e($currentUser['avatar_bg'] ?: '#0f172a') ?>;"><?= e($currentUser['avatar_text'] ?: 'AD') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name']) ?></div>
              <div class="user-role">Administrator</div>
            </div>
          </a>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb">
          <a href="admin-dashboard.php">Governance</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">User Management &amp; Access Controls</span>
        </div>

        <div class="flex-between mb-24" style="flex-wrap:wrap; gap:16px;">
          <div>
            <h1>User Governance &amp; Account Controls</h1>
            <p>Administer customer accounts and administrator privileges, with audit-safe removal and suspension.</p>
          </div>
          <div class="flex gap-12">
            <a href="admin-signup.php" class="btn btn-primary btn-sm">+ Provision Administrator</a>
          </div>
        </div>

        <div id="actionAlert" style="display:none; padding:12px 16px; border-radius:6px; margin-bottom:20px; font-size:0.9rem;"></div>

        <div class="card mb-24">
          <div class="flex-between mb-16" style="flex-wrap:wrap; gap:12px;">
            <div class="filter-tabs" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
              <button type="button" class="filter-tab active" onclick="setRoleFilter('', this)">All Accounts (<span id="count-all">0</span>)</button>
              <button type="button" class="filter-tab" onclick="setRoleFilter('customer', this)">Customers (<span id="count-customer">0</span>)</button>
              <button type="button" class="filter-tab" onclick="setRoleFilter('admin', this)">Administrators (<span id="count-admin">0</span>)</button>
            </div>
            <div style="min-width:240px;">
              <input type="text" id="userFilterInput" class="form-control" style="font-size:0.85rem; padding:6px 12px;" placeholder="e.g. Search by name, email, phone..." oninput="handleSearch(this.value)">
            </div>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>USER / IDENTITY</th>
                  <th>ROLE</th>
                  <th>CONTACT INFO</th>
                  <th>RESERVATIONS</th>
                  <th>JOINED DATE</th>
                  <th>STATUS</th>
                  <th style="text-align:right;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="userTableBody">
                <tr><td colspan="7" style="text-align:center; padding:32px; color:var(--gray-400);">Loading users directory from database...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>

      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>

  <!-- Confirmation Modal for Safe Deletion -->
  <div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="card" style="max-width:480px; width:100%; margin:0; box-shadow:var(--shadow-lg);">
      <div class="flex-between mb-16" style="border-bottom:1px solid var(--gray-200); padding-bottom:12px;">
        <h3 style="margin:0; color:#dc2626;">Confirm Account Removal</h3>
        <button type="button" onclick="closeDeleteModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--gray-500); line-height:1;">&times;</button>
      </div>
      <p style="margin-bottom:12px; font-size:0.95rem;">Are you sure you want to permanently remove <strong id="deleteTargetName"></strong>?</p>
      <div id="deleteWarningNote" style="background:#fee2e2; border:1px solid #f87171; color:#991b1b; padding:10px 14px; border-radius:6px; font-size:0.85rem; margin-bottom:16px;">
        This action permanently removes the user and all linked records. This action cannot be undone.
      </div>
      <div class="flex gap-12">
        <button type="button" class="btn btn-ghost" style="flex:1;" onclick="closeDeleteModal()">Cancel</button>
        <button type="button" id="btnConfirmDelete" class="btn btn-outline" style="flex:1; border-color:#dc2626; color:#dc2626;" onclick="executeDelete()">Remove User</button>
      </div>
    </div>
  </div>

  <script>
    var currentAdminId = <?= (int)$currentUser['id'] ?>;
    var currentRoleFilter = '';
    var currentSearch = '';
    var deleteCandidateId = null;
    var allUsersCache = [];

    async function loadUsers() {
      try {
        var url = '../api/users.php?action=list';
        if (currentRoleFilter) url += '&role=' + encodeURIComponent(currentRoleFilter);
        if (currentSearch) url += '&search=' + encodeURIComponent(currentSearch);

        var res = await fetch(url, { credentials: 'include' });
        var data = await res.json();
        if (data.success && Array.isArray(data.data.users)) {
          allUsersCache = data.data.users;
          renderUsers(data.data.users);
          updateCounts();
        } else {
          document.getElementById('userTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#dc2626;">Failed to load user directory: ' + (data.message || 'Server error') + '</td></tr>';
        }
      } catch (err) {
        console.error(err);
        document.getElementById('userTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#dc2626;">Network communication error loading user directory.</td></tr>';
      }
    }

    function updateCounts() {
      var allCount = allUsersCache.length;
      var custCount = allUsersCache.filter(u => u.role === 'customer').length;
      var adminCount = allUsersCache.filter(u => u.role === 'admin').length;
      document.getElementById('count-all').textContent = allCount;
      document.getElementById('count-customer').textContent = custCount;
      document.getElementById('count-admin').textContent = adminCount;
    }

    function renderUsers(users) {
      var tbody = document.getElementById('userTableBody');
      if (!users || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:32px; color:var(--gray-400);">No user records matching criteria.</td></tr>';
        return;
      }

      var html = '';
      users.forEach(function(u) {
        var isSelf = (parseInt(u.id) === currentAdminId);
        var rolePill = '';
        if (u.role === 'admin') {
          rolePill = '<span class="pill pill-confirmed" style="font-size:0.75rem;">ADMIN</span>';
        } else if (u.role === 'customer') {
          rolePill = '<span class="pill pill-in-progress" style="font-size:0.75rem;">CUSTOMER</span>';
        } else {
          rolePill = '<span class="pill" style="font-size:0.75rem;">' + u.role.toUpperCase() + '</span>';
        }

        var statusClass = (u.status === 'active') ? 'confirmed' : 'cancelled';
        var statusLabel = (u.status === 'active') ? 'ACTIVE' : 'SUSPENDED';

        var reservationText = '-';
        if (u.role === 'customer') {
          var tot = parseInt(u.total_bookings) || 0;
          var act = parseInt(u.active_bookings) || 0;
          reservationText = '<strong>' + tot + '</strong> total' + (act > 0 ? ' (' + act + ' active)' : '');
        }

        var actions = '<div class="action-btn-group" style="justify-content:flex-end;">';
        if (isSelf) {
          actions += '<span class="text-xs text-muted font-bold" style="padding:4px 8px; background:var(--gray-100); border-radius:4px;">You (Active Session)</span>';
          actions += '<a href="admin-profile.php" class="btn btn-outline btn-sm">Edit</a>';
        } else {
          // Toggle status button
          var nextStatus = (u.status === 'active') ? 'suspended' : 'active';
          var toggleBtnText = (u.status === 'active') ? 'Suspend' : 'Activate';
          var toggleBtnClass = (u.status === 'active') ? 'btn-ghost' : 'btn-outline';
          actions += '<button type="button" class="btn ' + toggleBtnClass + ' btn-sm" onclick="toggleUserStatus(' + u.id + ', \'' + nextStatus + '\')">' + toggleBtnText + '</button>';

          // Safe Delete button
          actions += '<button type="button" class="btn btn-outline btn-sm" style="color:#dc2626; border-color:#fca5a5;" onclick="openDeleteModal(' + u.id + ', \'' + escapeHtml(u.name) + '\', \'' + u.role + '\', ' + (parseInt(u.active_bookings) || 0) + ')">Remove</button>';
        }
        actions += '</div>';

        html += '<tr>' +
          '<td>' +
            '<div class="flex-center gap-10" style="justify-content:flex-start;">' +
              '<div class="user-avatar" style="width:34px; height:34px; font-size:0.75rem; background:' + (u.avatar_bg || '#2563eb') + '; color:#fff;">' + (u.avatar_text || 'U') + '</div>' +
              '<div>' +
                '<div class="font-bold">' + escapeHtml(u.name) + '</div>' +
                '<div class="text-xs text-muted">ID: #' + u.id + '</div>' +
              '</div>' +
            '</div>' +
          '</td>' +
          '<td>' + rolePill + '</td>' +
          '<td>' +
            '<div class="text-sm font-semibold">' + escapeHtml(u.email) + '</div>' +
            '<div class="text-xs text-muted">' + (u.phone ? escapeHtml(u.phone) : 'No phone listed') + '</div>' +
          '</td>' +
          '<td class="text-sm">' + reservationText + '</td>' +
          '<td class="text-sm text-muted">' + (u.created_at ? u.created_at.substring(0, 10) : '-') + '</td>' +
          '<td><span class="pill pill-' + statusClass + '">' + statusLabel + '</span></td>' +
          '<td>' + actions + '</td>' +
        '</tr>';
      });

      tbody.innerHTML = html;
    }

    function setRoleFilter(role, btn) {
      currentRoleFilter = role;
      document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
      btn.classList.add('active');
      loadUsers();
    }

    var searchTimer = null;
    function handleSearch(val) {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function() {
        currentSearch = val.trim();
        loadUsers();
      }, 300);
    }

    async function toggleUserStatus(userId, newStatus) {
      try {
        var res = await fetch('../api/users.php?action=toggle_status', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          credentials: 'include',
          body: JSON.stringify({ id: userId, status: newStatus })
        });
        var data = await res.json();
        showNotification(data.message, data.success);
        if (data.success) {
          loadUsers();
        }
      } catch (err) {
        showNotification('Network communication error updating user status.', false);
      }
    }

    function openDeleteModal(userId, name, role, activeBk) {
      deleteCandidateId = userId;
      document.getElementById('deleteTargetName').textContent = name + ' (' + role.toUpperCase() + ')';
      var note = document.getElementById('deleteWarningNote');
      if (activeBk > 0) {
        note.innerHTML = '<strong>Warning:</strong> This customer has <strong>' + activeBk + ' active/confirmed reservation(s)</strong>. The system will prevent removal until those reservations are completed or cancelled. You can suspend the account instead.';
      } else {
        note.textContent = 'This action permanently removes the user and cascades their historical records safely.';
      }
      document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
      deleteCandidateId = null;
    }

    async function executeDelete() {
      if (!deleteCandidateId) return;
      var btn = document.getElementById('btnConfirmDelete');
      var origText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Removing...';

      try {
        var res = await fetch('../api/users.php?action=delete', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          credentials: 'include',
          body: JSON.stringify({ id: deleteCandidateId })
        });
        var data = await res.json();
        closeDeleteModal();
        showNotification(data.message, data.success);
        if (data.success) {
          loadUsers();
        }
      } catch (err) {
        showNotification('Network error while attempting user deletion.', false);
      } finally {
        btn.disabled = false;
        btn.textContent = origText;
      }
    }

    function showNotification(msg, isSuccess) {
      var box = document.getElementById('actionAlert');
      box.style.display = 'block';
      box.style.background = isSuccess ? '#dcfce7' : '#fee2e2';
      box.style.border = isSuccess ? '1px solid #86efac' : '1px solid #f87171';
      box.style.color = isSuccess ? '#166534' : '#991b1b';
      box.innerHTML = (isSuccess ? '✓ ' : '⚠️ ') + msg;
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function escapeHtml(text) {
      if (!text) return '';
      var div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function() {
      loadUsers();
    });
  </script>
</body>
</html>

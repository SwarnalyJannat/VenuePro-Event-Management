<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('staff', 'staff-login.php');
$db = getDBConnection();

$staffUser = $currentUser;
$bookingId = (int)($_GET['booking_id'] ?? 0);
$assignmentIdParam = (int)($_GET['assignment_id'] ?? 0);

// Base list of standard 10 setup tasks
$baseTasks = [
    "Floor layout and banquet table arrangement verified",
    "Bose audio rig assembly and wireless mic array test",
    "Catering prep station staging and bar glassware stocked",
    "Valet parking cones and directional signage positioned",
    "Security detail radio distribution and perimeter briefing",
    "Guest registration desk setup (badges, lanyards, packets)",
    "LED video wall and main projector presentation feed tested",
    "Floral and decor delivery receipt sign-off with dock staff",
    "ADA accessibility ramp and elevator clearances inspected",
    "Emergency exits verified clear and fire safety sweep complete"
];

// Helper to parse checklist_json in various formats into standardized array
function parseAssignmentTasks($checklistJson, $baseTasks) {
    $decoded = !empty($checklistJson) ? json_decode($checklistJson, true) : null;
    $tasks = [];
    foreach ($baseTasks as $i => $text) {
        $isChecked = false;
        if (is_array($decoded)) {
            if (isset($decoded['task-' . $i])) {
                $isChecked = (bool)$decoded['task-' . $i];
            } else if (isset($decoded['c' . ($i + 1)])) {
                $isChecked = (bool)$decoded['c' . ($i + 1)];
            } else if (isset($decoded[$i]) && is_bool($decoded[$i])) {
                $isChecked = (bool)$decoded[$i];
            } else if (isset($decoded[$i]) && is_array($decoded[$i]) && isset($decoded[$i]['done'])) {
                $isChecked = (bool)$decoded[$i]['done'];
            } else if (isset($decoded[$i]) && is_array($decoded[$i]) && isset($decoded[$i]['checked'])) {
                $isChecked = (bool)$decoded[$i]['checked'];
            } else {
                foreach ($decoded as $k => $v) {
                    if (is_string($v) && (strpos($v, $text) !== false || strpos($text, substr($v, 0, 15)) !== false)) {
                        $isChecked = (strpos($v, '✓') !== false);
                        break;
                    }
                }
            }
        }
        $tasks[] = [
            'id' => 'task-' . $i,
            'text' => $text,
            'checked' => $isChecked
        ];
    }
    return $tasks;
}

// Fetch staff profile and admin-assigned venues
$staffId = (int)$staffUser['id'];
$stmtProf = $db->prepare("SELECT sp.assigned_venues, sp.staff_code, sp.department FROM staff_profiles sp WHERE sp.user_id = ? LIMIT 1");
$stmtProf->execute([$staffId]);
$staffProfile = $stmtProf->fetch();

$assignedVenueIds = [];
if (!empty($staffProfile['assigned_venues'])) {
    foreach (explode(',', $staffProfile['assigned_venues']) as $vId) {
        $vId = (int)trim($vId);
        if ($vId > 0) {
            $assignedVenueIds[] = $vId;
        }
    }
    $assignedVenueIds = array_values(array_unique($assignedVenueIds));
}

$assignedVenues = [];
$staffAssignmentsList = [];

if (!empty($assignedVenueIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($assignedVenueIds), '?'));

    // Fetch assigned venues metadata
    $stmtVenues = $db->prepare("SELECT id, name, address, capacity, district FROM venues WHERE id IN ($inPlaceholders) AND status = 'active' ORDER BY name ASC");
    $stmtVenues->execute($assignedVenueIds);
    $assignedVenues = $stmtVenues->fetchAll(PDO::FETCH_ASSOC);

    // Fetch confirmed/completed bookings strictly for assigned venues
    $stmtMyAll = $db->prepare(
        "SELECT 
            b.id AS booking_id, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count, b.special_notes, b.booking_status,
            v.id AS venue_id, v.name AS venue_name, v.address AS venue_address,
            u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
            sa.id AS id, sa.staff_id, sa.setup_status, sa.completion_pct, sa.checklist_json, sa.completed_at, sa.reported_staff
         FROM bookings b
         JOIN venues v ON b.venue_id = v.id
         JOIN users u ON b.customer_id = u.id
         LEFT JOIN staff_assignments sa ON sa.booking_id = b.id
         WHERE b.venue_id IN ($inPlaceholders)
           AND b.booking_status IN ('confirmed', 'completed')
         ORDER BY b.event_date ASC, b.id ASC"
    );
    $stmtMyAll->execute($assignedVenueIds);
    $staffAssignmentsList = $stmtMyAll->fetchAll(PDO::FETCH_ASSOC);

    // Auto-create assignment record if a confirmed booking doesn't have one yet
    foreach ($staffAssignmentsList as &$asgn) {
        if (empty($asgn['id'])) {
            $shiftTime = (!empty($asgn['start_time']) ? substr($asgn['start_time'], 0, 5) : '10:00') . ' - ' . (!empty($asgn['end_time']) ? substr($asgn['end_time'], 0, 5) : '18:00');
            $ins = $db->prepare("INSERT INTO staff_assignments (booking_id, staff_id, shift_time, setup_status, completion_pct, checklist_json) VALUES (?, ?, ?, 'pending', 0, '{}')");
            $ins->execute([$asgn['booking_id'], $staffId, $shiftTime]);
            $asgn['id'] = (int)$db->lastInsertId();
            $asgn['staff_id'] = $staffId;
            $asgn['setup_status'] = 'pending';
            $asgn['completion_pct'] = 0;
            $asgn['checklist_json'] = '{}';
            $asgn['completed_at'] = null;
            $asgn['reported_staff'] = $staffUser['name'];
        }
    }
    unset($asgn);
}

// Determine active assignment
$activeAssignment = null;
if ($bookingId > 0) {
    foreach ($staffAssignmentsList as $asgn) {
        if ((int)$asgn['booking_id'] === $bookingId) {
            $activeAssignment = $asgn;
            break;
        }
    }
}
if (!$activeAssignment && $assignmentIdParam > 0) {
    foreach ($staffAssignmentsList as $asgn) {
        if ((int)$asgn['id'] === $assignmentIdParam) {
            $activeAssignment = $asgn;
            break;
        }
    }
}
if (!$activeAssignment && !empty($staffAssignmentsList)) {
    $activeAssignment = $staffAssignmentsList[0];
}

// Build JSON data object for JavaScript
$jsVenueData = [];
foreach ($staffAssignmentsList as $asgn) {
    $tasks = parseAssignmentTasks($asgn['checklist_json'] ?? '', $baseTasks);
    $totalTasks = count($tasks);
    $completedTasks = count(array_filter($tasks, fn($t) => $t['checked']));
    $savedPct = !empty($asgn['completion_pct']) ? (int)$asgn['completion_pct'] : 0;
    $computedPct = $totalTasks > 0 ? (int)round(($completedTasks / $totalTasks) * 100) : 0;
    $pct = max($savedPct, $computedPct);
    $isComplete = ($asgn['setup_status'] === 'completed' || $pct >= 100);
    if ($isComplete) {
        $pct = 100;
        foreach ($tasks as &$t) {
            $t['checked'] = true;
        }
        unset($t);
    }

    $jsVenueData[$asgn['id']] = [
        'assignment_id'   => (int)$asgn['id'],
        'booking_id'      => (int)$asgn['booking_id'],
        'venue_name'      => $asgn['venue_name'],
        'event_name'      => $asgn['event_name'],
        'event_date'      => date('M j, Y', strtotime($asgn['event_date'])),
        'guest_count'     => (int)$asgn['guest_count'],
        'call_time'       => substr($asgn['start_time'], 0, 5),
        'setup_status'    => $isComplete ? 'completed' : ($asgn['setup_status'] ?: 'in_progress'),
        'status_label'    => $isComplete ? 'READY / COMPLETED' : ($asgn['setup_status'] === 'pending' ? 'UPCOMING' : 'IN SETUP'),
        'pill_class'      => $isComplete ? 'pill-confirmed' : ($asgn['setup_status'] === 'pending' ? 'pill-pending' : 'pill-in-progress'),
        'completion_pct'  => $pct,
        'completed_at'    => $asgn['completed_at'] ? date('M j, Y g:i A', strtotime($asgn['completed_at'])) : null,
        'reported_staff'  => $asgn['reported_staff'] ?: $staffUser['name'],
        'tasks'           => $tasks
    ];
}

$initialAssignmentId = $activeAssignment ? (int)$activeAssignment['id'] : (array_key_first($jsVenueData) ?? 0);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>VenuePro Staff – Event Setup Detail</title>
    <link rel="stylesheet" href="../css/style.css" />
    <style>
      .venue-card-btn {
        border: 2px solid var(--gray-200);
        background: #fff;
        border-radius: var(--radius);
        padding: 16px 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: left;
      }
      .venue-card-btn:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-sm);
      }
      .venue-card-btn.active {
        border-color: var(--primary);
        background: #eff6ff;
      }
      .task-item {
        display: flex;
        align-items: center;
        padding: 12px 14px;
        border-radius: var(--radius-sm);
        margin-bottom: 6px;
        background: #fff;
        border: 1px solid var(--gray-100);
        transition: background 0.15s;
      }
      .task-item:hover {
        background: var(--gray-50);
      }
      .task-item input[type="checkbox"] {
        display: none;
      }
      .task-item label {
        display: flex;
        align-items: center;
        width: 100%;
        cursor: pointer;
        gap: 12px;
        font-size: 0.9rem;
        color: var(--navy-900);
      }
      .cb-box {
        width: 20px;
        height: 20px;
        border: 2px solid var(--gray-400);
        border-radius: 4px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
      }
      .task-item input[type="checkbox"]:checked + label .cb-box {
        background: #16a34a;
        border-color: #16a34a;
      }
      .task-item input[type="checkbox"]:checked + label .cb-box::after {
        content: "✓";
        color: #fff;
        font-size: 13px;
        font-weight: bold;
      }
      .task-item input[type="checkbox"]:checked + label .task-text {
        text-decoration: line-through;
        color: var(--gray-400);
      }
    </style>
  </head>
  <body>
    <input type="checkbox" id="sidebar-toggle" />
    <div class="app-shell">
      <aside class="sidebar" id="main-sidebar">
        <div class="sidebar-logo">
          <img
            src="../assets/logo.png"
            alt="VenuePro"
            class="sidebar-logo-img"
          />
          <div class="sidebar-logo-text">VenuePro</div>
        </div>
        <nav class="sidebar-nav">
          <div class="nav-label">Coordination</div>
          <a href="staff-dashboard.php" class="nav-item">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <rect x="3" y="3" width="7" height="7" />
              <rect x="14" y="3" width="7" height="7" />
              <rect x="14" y="14" width="7" height="7" />
              <rect x="3" y="14" width="7" height="7" />
            </svg>
            Staff Dashboard
          </a>
          <a href="staff-event-setup.php" class="nav-item active">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
              />
              <polyline points="14 2 14 8 20 8" />
              <line x1="9" y1="13" x2="15" y2="13" />
              <line x1="9" y1="17" x2="15" y2="17" />
            </svg>
            Event Setup Detail
          </a>
        </nav>
        <div class="sidebar-footer">
          <a
            href="../venues.php"
            class="nav-item"
            style="color: var(--gray-400)"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
              <polyline points="16 17 21 12 16 7" />
              <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            Log out
          </a>
        </div>
      </aside>

      <div class="main-content">
        <header class="topbar">
          <label
            for="sidebar-toggle"
            class="sidebar-toggle-btn"
            title="Toggle Sidebar"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <line x1="3" y1="6" x2="21" y2="6" />
              <line x1="3" y1="12" x2="21" y2="12" />
              <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
          </label>
          <div class="topbar-search">
            <span class="topbar-search-icon">🔍</span>
            <input
              type="text"
              placeholder="Search assigned tasks, venues, clients..."
            />
          </div>
          <div class="topbar-actions">
            <a
              href="staff-notifications.php"
              class="topbar-icon-btn"
              title="Notifications"
            >
              <?= renderNotifBadge() ?>
              🔔
            </a>
            <a
              href="staff-chat.php"
              class="topbar-icon-btn"
              title="Customer Chat"
            >
              💬
            </a>
            <div class="topbar-user">
              <div class="user-avatar" style="background: #7c3aed"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
              <div class="user-info">
                <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
                <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'staff')) ?></div>
              </div>
            </div>
          </div>
        </header>

        <main class="page-body">
          <div class="breadcrumb">
            <a href="staff-dashboard.php">Coordination Hub</a>
            <span class="breadcrumb-sep">›</span>
            <span class="breadcrumb-current">Event Setup Details</span>
          </div>

          <div class="flex-between mb-24">
            <div>
              <h1>Assigned Venues &amp; Setup Checklists</h1>
              <p>
                Select an assigned property below to load and manage its live
                setup task list.
              </p>
            </div>
            <div class="flex gap-8">
              <a href="staff-dashboard.php" class="btn btn-ghost btn-sm"
                >← Back to Dashboard</a
              >
            </div>
          </div>

          <!-- Assigned Venues Info Strip -->
          <div class="card mb-24" style="padding: 16px 20px; background: linear-gradient(to right, #f8fafc, #eff6ff); border: 1.5px solid #bfdbfe;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
              <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:1.6rem;">🏛️</span>
                <div>
                  <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:700; color:var(--primary);">
                    Your Admin-Assigned Operational Properties (<?= count($assignedVenues) ?>)
                  </div>
                  <div style="font-size:0.875rem; color:var(--navy-900); font-weight:600; margin-top:2px;">
                    <?php if (empty($assignedVenues)): ?>
                      <span style="color:#dc2626; font-weight:500;">No operational properties assigned by administrator yet.</span>
                    <?php else: ?>
                      <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:4px;">
                        <?php foreach ($assignedVenues as $av): ?>
                          <span class="badge" style="background:#fff; border:1px solid #93c5fd; color:#1e40af; font-size:0.8rem; padding:4px 10px; border-radius:14px; font-weight:600;">
                            📍 <?= e($av['name']) ?><?= !empty($av['district']) ? ' ('.e($av['district']).')' : '' ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="badge" style="background:var(--primary); color:#fff; font-size:12px; padding:6px 14px; border-radius:16px;">
                <?= count($staffAssignmentsList) ?> Active Event Shifts
              </div>
            </div>
          </div>

          <?php if (empty($assignedVenueIds)): ?>
          <div class="card mb-24" style="background:#fffbeb; border:1.5px solid #fde68a; padding:32px 24px; text-align:center; border-radius:var(--radius);">
            <div style="font-size:2.5rem; margin-bottom:12px;">⚠️</div>
            <h2 style="color:#b45309; margin-bottom:8px;">No Operational Venues Assigned</h2>
            <p style="color:#92400e; max-width:600px; margin:0 auto 16px;">
              An administrator has not assigned any operational venues to your staff profile yet. Your tasklists and setup checklists are strictly scoped to your assigned properties.
            </p>
            <p class="text-xs text-muted">Please contact your VenuePro system administrator to assign properties in the Staff Directory.</p>
          </div>
          <?php elseif (empty($staffAssignmentsList)): ?>
          <div class="card mb-24" style="background:#f8fafc; border:1.5px solid var(--gray-200); padding:36px 24px; text-align:center; border-radius:var(--radius);">
            <div style="font-size:2.5rem; margin-bottom:12px;">📋</div>
            <h2 style="color:var(--navy-900); margin-bottom:8px;">No Active Event Shifts Scheduled</h2>
            <p style="color:var(--gray-600); max-width:600px; margin:0 auto 16px;">
              There are currently no confirmed event reservations scheduled for your assigned properties (<strong><?= implode(', ', array_map('e', array_column($assignedVenues, 'name'))) ?></strong>).
            </p>
            <div class="text-xs text-muted" style="background:#f1f5f9; display:inline-block; padding:8px 16px; border-radius:8px;">
              All facilities at your assigned venues are currently in standby readiness. When a booking is confirmed by an administrator, its interactive 10-task setup checklist and client details will appear here automatically.
            </div>
          </div>
          <?php else: ?>
          <!-- Assigned Venues Selection Bar (Dynamic from Database) -->
          <div class="card mb-24" style="padding: 20px">
            <div
              class="text-xs font-bold text-muted mb-12 flex-between"
              style="letter-spacing: 0.05em; text-transform: uppercase"
            >
              <span>Your Assigned Venues &amp; Scheduled Shifts (Click to view tasks):</span>
              <span class="badge" style="background:var(--primary); color:#fff; font-size:11px;"><?= count($staffAssignmentsList) ?> Scheduled</span>
            </div>
            <div
              style="
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 16px;
              "
            >
              <?php foreach ($staffAssignmentsList as $asgn): 
                $aid = (int)$asgn['id'];
                $isInitActive = ($aid === $initialAssignmentId);
                $isComp = ($asgn['setup_status'] === 'completed' || (int)$asgn['completion_pct'] >= 100);
                $pillCls = $isComp ? 'pill-confirmed' : (($asgn['setup_status'] === 'pending') ? 'pill-pending' : 'pill-in-progress');
                $pillLbl = $isComp ? 'READY' : (($asgn['setup_status'] === 'pending') ? 'UPCOMING' : 'IN SETUP');
              ?>
              <div
                id="vcard-<?= $aid ?>"
                class="venue-card-btn <?= $isInitActive ? 'active' : '' ?>"
                onclick="selectVenue(<?= $aid ?>)"
              >
                <div class="flex-between mb-4">
                  <span class="font-bold" style="color: var(--navy-900)"><?= e($asgn['venue_name']) ?></span>
                  <span id="vcard-pill-<?= $aid ?>" class="pill <?= $pillCls ?>" style="font-size: 0.68rem"><?= $pillLbl ?></span>
                </div>
                <div class="text-sm font-semibold text-primary mb-2">
                  <?= e($asgn['event_name']) ?>
                </div>
                <div class="text-xs text-muted">
                  <?= date('M j, Y', strtotime($asgn['event_date'])) ?> · <?= (int)$asgn['guest_count'] ?> Guests · Call Time: <?= substr($asgn['start_time'], 0, 5) ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Active Venue Setup Details & Task Checklist Card -->
          <div class="card mb-24">
            <div
              class="flex-between mb-16"
              style="
                padding-bottom: 16px;
                border-bottom: 1px solid var(--gray-200);
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
              "
            >
              <div>
                <div
                  class="flex gap-8"
                  style="align-items: center; margin-bottom: 4px"
                >
                  <h2 id="venue-title" style="margin: 0; font-size: 1.3rem">
                    <?= e($activeAssignment['venue_name'] ?? 'Venue') ?>
                  </h2>
                  <span id="venue-pill" class="pill pill-in-progress"
                    >IN SETUP</span
                  >
                </div>
                <p
                  id="venue-event-meta"
                  class="text-xs text-muted"
                  style="margin: 0"
                >
                  Loading event setup metadata...
                </p>
              </div>
              <div>
                <button
                  type="button"
                  id="mark-all-btn"
                  class="btn btn-outline btn-sm"
                  onclick="toggleMarkAll()"
                >
                  ✓ Mark All Tasks Complete
                </button>
              </div>
            </div>

            <!-- Task Items Container -->
            <div id="task-list-container">
              <!-- Dynamically rendered by JavaScript -->
            </div>

            <!-- Progress summary -->
            <div
              style="
                margin-top: 20px;
                padding: 14px 18px;
                background: var(--gray-50);
                border-radius: var(--radius-sm);
              "
            >
              <div class="flex-between text-sm mb-6">
                <span class="text-muted font-medium">Tasks Completed</span>
                <span class="font-bold" id="task-progress-text">0 / 0 (0%)</span>
              </div>
              <div class="progress-bar" style="height: 10px">
                <div
                  id="task-progress-bar"
                  class="progress-fill"
                  style="width: 0%"
                ></div>
              </div>
            </div>
          </div>

          <!-- Mark Event Complete Panel -->
          <div class="card complete-form">
            <div class="card-header">
              <div class="card-title">Event Completion Sign-Off &amp; Report Submission</div>
            </div>
            <div class="confirm-panel">
              <div
                id="completion-notice-card"
                class="card mb-12"
                style="
                  background: #f0fdf4;
                  border: 1px solid #86efac;
                  padding: 16px;
                  margin-top: 16px;
                "
              >
                <div
                  id="completion-notice-title"
                  class="font-bold text-sm"
                  style="color: #16a34a; margin-bottom: 4px"
                >
                  ⚠️ Confirm Event Completion
                </div>
                <p id="completion-notice-desc" class="text-xs" style="color: #166534">
                  By confirming, you verify that all setup requirements and
                  teardown checklists are fulfilled for this assigned space.
                </p>
              </div>
              
              <div class="flex gap-8 mt-8">
                <button type="button" id="cancelReportBtn"
                  class="btn btn-ghost" style="flex:1;">Cancel / Reset</button>
                <button type="button" id="submitReportBtn"
                  class="btn btn-primary"
                  style="flex:2; background:#16a34a; border-color:#16a34a;">
                  Confirm completion &amp; Submit Report →
                </button>
              </div>
              <div id="reportAlert" style="display:none; margin-top:10px; padding:12px; border-radius:6px; font-size:0.85rem; font-weight:600;"></div>
            </div>
          </div>
          <?php endif; ?>
        </main>

        <footer class="page-footer">
          <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
        </footer>
      </div>
    </div>

    <script>
      // Live database data loaded seamlessly from PHP
      const venueData = <?= json_encode($jsVenueData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      let currentAssignmentId = <?= (int)$initialAssignmentId ?>;

      function selectVenue(assignmentId) {
        currentAssignmentId = assignmentId;
        const data = venueData[assignmentId];
        if (!data) return;

        // Update cards active states
        Object.keys(venueData).forEach(function(aid) {
          const card = document.getElementById("vcard-" + aid);
          if (card) {
            if (parseInt(aid) === assignmentId) {
              card.classList.add("active");
            } else {
              card.classList.remove("active");
            }
          }
        });

        // Update header details
        document.getElementById("venue-title").innerText = data.venue_name;
        const pill = document.getElementById("venue-pill");
        pill.innerText = data.status_label;
        pill.className = "pill " + data.pill_class;

        document.getElementById("venue-event-meta").innerHTML = 
          `Event: <strong>${escapeHtml(data.event_name)}</strong> · ${data.event_date} · ${data.guest_count} Guests · Call Time: ${data.call_time}`;

        // Update completion notice card
        const noticeTitle = document.getElementById("completion-notice-title");
        const noticeDesc = document.getElementById("completion-notice-desc");
        const submitBtn = document.getElementById("submitReportBtn");

        if (data.setup_status === 'completed') {
          noticeTitle.innerText = "✓ Event Setup Verified & Signed Off";
          noticeTitle.style.color = "#15803d";
          noticeDesc.innerHTML = `Completed and certified by <strong>${escapeHtml(data.reported_staff)}</strong> ${data.completed_at ? 'on ' + data.completed_at : ''}. All logistics permanently recorded in system.`;
          if (submitBtn) {
            submitBtn.innerHTML = "✓ Re-verify &amp; Update Setup Report →";
          }
        } else {
          noticeTitle.innerText = "⚠️ Confirm Event Completion";
          noticeTitle.style.color = "#16a34a";
          noticeDesc.innerText = "By confirming, you verify that all setup requirements and teardown checklists are fulfilled for this assigned space.";
          if (submitBtn) {
            submitBtn.innerHTML = "Confirm completion &amp; Submit Report →";
          }
        }

        renderTasks();
      }

      function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
      }

      function renderTasks() {
        const data = venueData[currentAssignmentId];
        if (!data) return;
        const container = document.getElementById("task-list-container");
        container.innerHTML = "";

        data.tasks.forEach((task, index) => {
          const item = document.createElement("div");
          item.className = "task-item";
          item.innerHTML = `
            <input type="checkbox" id="task-${index}" ${task.checked ? "checked" : ""} onchange="toggleTask(${index}, this.checked)">
            <label for="task-${index}">
              <span class="cb-box"></span>
              <span class="task-text">${escapeHtml(task.text)}</span>
            </label>
          `;
          container.appendChild(item);
        });

        updateProgress();
      }

      async function toggleTask(index, isChecked) {
        const data = venueData[currentAssignmentId];
        if (!data) return;
        data.tasks[index].checked = isChecked;
        updateProgress();

        // Auto-save checkbox states to database in real time
        const total = data.tasks.length;
        const completed = data.tasks.filter(t => t.checked).length;
        const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
        const checklistObj = data.tasks.reduce(function(acc, t, i) { acc['task-' + i] = t.checked; return acc; }, {});

        try {
          await fetch('../api/staff.php?action=update_checklist', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              assignment_id: data.assignment_id,
              checklist: checklistObj,
              completion_pct: pct,
              setup_status: (pct === 100 ? 'completed' : (pct > 0 ? 'in_progress' : 'pending'))
            })
          });
        } catch (e) {
          console.warn('Auto-save error:', e);
        }
      }

      function toggleMarkAll() {
        const data = venueData[currentAssignmentId];
        if (!data) return;
        const tasks = data.tasks;
        const allChecked = tasks.every((t) => t.checked);
        const targetState = !allChecked;

        tasks.forEach((t) => (t.checked = targetState));
        renderTasks();

        // Save batch mark
        const total = tasks.length;
        const completed = tasks.filter(t => t.checked).length;
        const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
        const checklistObj = tasks.reduce(function(acc, t, i) { acc['task-' + i] = t.checked; return acc; }, {});

        fetch('../api/staff.php?action=update_checklist', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            assignment_id: data.assignment_id,
            checklist: checklistObj,
            completion_pct: pct,
            setup_status: (pct === 100 ? 'completed' : 'in_progress')
          })
        }).catch(err => console.warn(err));
      }

      function updateProgress() {
        const data = venueData[currentAssignmentId];
        if (!data) return;
        const tasks = data.tasks;
        const total = tasks.length;
        const completed = tasks.filter((t) => t.checked).length;
        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
        data.completion_pct = percent;

        document.getElementById("task-progress-text").innerText =
          `${completed} / ${total} (${percent}%)`;
        document.getElementById("task-progress-bar").style.width =
          `${percent}%`;

        const markBtn = document.getElementById("mark-all-btn");
        if (completed === total) {
          markBtn.innerText = "↺ Unmark All Tasks";
        } else {
          markBtn.innerText = "✓ Mark All Tasks Complete";
        }
      }

      // Initialize on load
      document.addEventListener("DOMContentLoaded", () => {
        if (currentAssignmentId > 0 && typeof venueData !== 'undefined' && venueData[currentAssignmentId]) {
          selectVenue(currentAssignmentId);
        }
      });
    </script>
    <script src="../js/app.js"></script>
    <script>
      // ----- Submit Report -----
      const submitBtn = document.getElementById('submitReportBtn');
      const cancelBtn = document.getElementById('cancelReportBtn');
      const reportAlert = document.getElementById('reportAlert');

      if (submitBtn) {
        submitBtn.addEventListener('click', async function() {
          const data = venueData[currentAssignmentId];
          if (!data) return;

          // Mark all tasks checked upon confirming completion
          data.tasks.forEach(t => t.checked = true);
          renderTasks();
          updateProgress();

          submitBtn.disabled = true;
          const origText = submitBtn.textContent;
          submitBtn.textContent = 'Submitting & Finalizing...';
          reportAlert.style.display = 'none';

          const checklistObj = data.tasks.reduce(function(acc, t, i) { acc['task-' + i] = true; return acc; }, {});

          const payload = {
            assignment_id: data.assignment_id,
            completion_pct: 100,
            venue_name: data.venue_name,
            staff_name: '<?= addslashes(e($currentUser['name'] ?? 'Staff Coordinator')) ?>',
            checklist: checklistObj
          };

          try {
            const res = await fetch('../api/staff.php?action=submit_report', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify(payload)
            });
            const d = await res.json();
            if (d && d.success) {
              // Update local state permanently
              data.setup_status = 'completed';
              data.status_label = 'READY / COMPLETED';
              data.pill_class = 'pill-confirmed';
              data.completion_pct = 100;
              data.reported_staff = '<?= addslashes(e($currentUser['name'] ?? 'Staff Coordinator')) ?>';
              data.completed_at = 'Just now';

              // Update top selector badge
              const topPill = document.getElementById("vcard-pill-" + data.assignment_id);
              if (topPill) {
                topPill.className = "pill pill-confirmed";
                topPill.innerText = "READY";
              }

              // Update main card pill
              const mainPill = document.getElementById("venue-pill");
              if (mainPill) {
                mainPill.className = "pill pill-confirmed";
                mainPill.innerText = "READY / COMPLETED";
              }

              // Update completion notice card
              const noticeTitle = document.getElementById("completion-notice-title");
              const noticeDesc = document.getElementById("completion-notice-desc");
              if (noticeTitle) {
                noticeTitle.innerText = "✓ Event Setup Verified & Signed Off";
                noticeTitle.style.color = "#15803d";
              }
              if (noticeDesc) {
                noticeDesc.innerHTML = `Completed and certified by <strong>${escapeHtml(data.reported_staff)}</strong> Just now. All logistics permanently recorded in system.`;
              }

              reportAlert.style.display = 'block';
              reportAlert.style.background = '#dcfce7';
              reportAlert.style.color = '#166534';
              reportAlert.innerHTML = `✓ Event Setup Report confirmed &amp; submitted successfully!<br>Status permanently updated to <strong>READY / COMPLETED (100%)</strong> across project. Redirecting to Staff Dashboard...`;
              
              setTimeout(function() { 
                window.location.href = 'staff-dashboard.php'; 
              }, 1200);
            } else {
              reportAlert.style.display = 'block';
              reportAlert.style.background = '#fee2e2';
              reportAlert.style.color = '#991b1b';
              reportAlert.textContent = '⚠️ ' + (d.message || 'Could not submit report.');
              submitBtn.disabled = false;
              submitBtn.textContent = origText;
            }
          } catch(e) {
            console.error(e);
            reportAlert.style.display = 'block';
            reportAlert.style.background = '#fee2e2';
            reportAlert.style.color = '#991b1b';
            reportAlert.textContent = '⚠️ Connection error. Please try again.';
            submitBtn.disabled = false;
            submitBtn.textContent = origText;
          }
        });
      }

      // ----- Cancel / Reset all checkboxes -----
      if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
          const data = venueData[currentAssignmentId];
          if (!data) return;
          if (!confirm('Reset all checkboxes for ' + data.venue_name + '?')) return;
          data.tasks.forEach(function(t) { t.checked = false; });
          renderTasks();

          // Save reset to DB
          const checklistObj = data.tasks.reduce(function(acc, t, i) { acc['task-' + i] = false; return acc; }, {});
          fetch('../api/staff.php?action=update_checklist', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              assignment_id: data.assignment_id,
              checklist: checklistObj,
              completion_pct: 0,
              setup_status: 'in_progress'
            })
          }).catch(err => console.warn(err));
        });
      }
    </script>
  </body>
</html>

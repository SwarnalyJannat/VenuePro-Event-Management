<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('staff', 'staff-login.php');
$db = getDBConnection();
?>
<?php
$staffUser    = $currentUser;
$bookingId    = (int)($_GET['booking_id'] ?? 0);
$setupAssignment = null;
$checklistItems  = [];

if ($bookingId > 0) {
    $stmtSA = $db->prepare(
        "SELECT sa.*, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count, b.special_notes,
         v.name AS venue_name, v.address AS venue_address,
         u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
         FROM staff_assignments sa
         JOIN bookings b ON sa.booking_id = b.id
         JOIN venues v ON b.venue_id = v.id
         JOIN users u ON b.customer_id = u.id
         WHERE sa.booking_id = ? AND sa.staff_id = ?
         LIMIT 1"
    );
    $stmtSA->execute([$bookingId, $staffUser['id']]);
    $setupAssignment = $stmtSA->fetch();
}

// Fallback: load latest assignment for this staff member
if (!$setupAssignment) {
    $stmtFB = $db->prepare(
        "SELECT sa.*, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count, b.special_notes,
         v.name AS venue_name, v.address AS venue_address,
         u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
         FROM staff_assignments sa
         JOIN bookings b ON sa.booking_id = b.id
         JOIN venues v ON b.venue_id = v.id
         JOIN users u ON b.customer_id = u.id
         WHERE sa.staff_id = ?
         ORDER BY b.event_date ASC LIMIT 1"
    );
    $stmtFB->execute([$staffUser['id']]);
    $setupAssignment = $stmtFB->fetch();
}

// Default checklist (stored as JSON in checklist_json column if available)
$defaultChecklist = [
    ['id'=>'c1', 'label'=>'Venue walkthrough and area inspection',         'done'=>false],
    ['id'=>'c2', 'label'=>'Tables, chairs, and linens arranged',           'done'=>false],
    ['id'=>'c3', 'label'=>'AV equipment tested (mic, projector, lighting)','done'=>false],
    ['id'=>'c4', 'label'=>'Catering team briefed on timeline',             'done'=>false],
    ['id'=>'c5', 'label'=>'Security personnel briefed and positioned',     'done'=>false],
    ['id'=>'c6', 'label'=>'Emergency exits clearly marked and accessible', 'done'=>false],
    ['id'=>'c7', 'label'=>'Guest registration table set up at entrance',   'done'=>false],
    ['id'=>'c8', 'label'=>'VIP/reserved seating clearly labeled',         'done'=>false],
    ['id'=>'c9', 'label'=>'First aid kit and fire safety checked',         'done'=>false],
    ['id'=>'c10','label'=>'Final sweep and event coordinator sign-off',    'done'=>false],
];

// If checklist_json stored in assignment, use it
if ($setupAssignment && !empty($setupAssignment['checklist_json'])) {
    $saved = json_decode($setupAssignment['checklist_json'], true);
    if (is_array($saved)) $defaultChecklist = $saved;
}
$checklistItems = $defaultChecklist;
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
        background: var(--primary);
        border-color: var(--primary);
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
            href="../login-role.php"
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
              <span class="badge">4</span>
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

          <!-- Assigned Venues Selection Bar -->
          <div class="card mb-24" style="padding: 20px">
            <div
              class="text-xs font-bold text-muted mb-12"
              style="letter-spacing: 0.05em; text-transform: uppercase"
            >
              Your Assigned Venues (Click to view tasks):
            </div>
            <div
              style="
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 16px;
              "
            >
              <!-- Venue 1 -->
              <div
                id="vcard-1"
                class="venue-card-btn active"
                onclick="selectVenue(1)"
              >
                <div class="flex-between mb-4">
                  <span class="font-bold" style="color: var(--navy-900)"
                    ><?= $setupAssignment ? e($setupAssignment['venue_name']) : 'Venue' ?></span
                  >
                  <span class="pill pill-in-progress" style="font-size: 0.68rem"
                    >IN SETUP</span
                  >
                </div>
                <div class="text-sm font-semibold text-primary mb-2">
                  <?= $setupAssignment ? e($setupAssignment['event_name']) : 'Event Setup' ?>
                </div>
                <div class="text-xs text-muted">
                  Oct 14, 2026 · 150 Guests · Call Time: 14:00
                </div>
              </div>

              <!-- Venue 2 -->
              <div id="vcard-2" class="venue-card-btn" onclick="selectVenue(2)">
                <div class="flex-between mb-4">
                  <span class="font-bold" style="color: var(--navy-900)"
                    >Skyline Vista Lounge</span
                  >
                  <span class="pill pill-pending" style="font-size: 0.68rem"
                    >UPCOMING</span
                  >
                </div>
                <div class="text-sm font-semibold text-primary mb-2">
                  Tech Summit Keynote
                </div>
                <div class="text-xs text-muted">
                  Oct 15, 2026 · 200 Guests · Call Time: 17:30
                </div>
              </div>

              <!-- Venue 3 -->
              <div id="vcard-3" class="venue-card-btn" onclick="selectVenue(3)">
                <div class="flex-between mb-4">
                  <span class="font-bold" style="color: var(--navy-900)"
                    >Brick &amp; Steel Gallery</span
                  >
                  <span class="pill pill-pending" style="font-size: 0.68rem"
                    >UPCOMING</span
                  >
                </div>
                <div class="text-sm font-semibold text-primary mb-2">
                  Art Gala Reception
                </div>
                <div class="text-xs text-muted">
                  Oct 16, 2026 · 120 Guests · Call Time: 19:00
                </div>
              </div>
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
                    <?= $setupAssignment ? e($setupAssignment['venue_name']) : 'Venue' ?>
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
                  Event: <strong><?= $setupAssignment ? e($setupAssignment['event_name']) : 'Event Setup' ?></strong> · Oct 14,
                  2026 · 150 Guests · Call Time: 14:00
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
              <!-- Injected via JavaScript based on selected venue -->
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
                <span class="font-bold" id="task-progress-text">0 / 0</span>
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
              <div class="card-title"> Event Completion Sign-Off </div>
            </div>
            <!-- <label
              for="confirm-check"
              class="btn btn-primary btn-full"
              style="
                background: #16a34a;
                border-color: #16a34a;
                cursor: pointer;
                margin-bottom: 0;
              "
            >
              ✓ Mark Event as Completed
            </label>
             <input type="checkbox" id="confirm-check" />  -->
            <div class="confirm-panel">
              <div
                class="card mb-12"
                style="
                  background: #f0fdf4;
                  border: 1px solid #86efac;
                  padding: 16px;
                  margin-top: 16px;
                "
              >
                <div
                  class="font-bold text-sm"
                  style="color: #16a34a; margin-bottom: 4px"
                >
                  ⚠️ Confirm Event Completion
                </div>
                <p class="text-xs" style="color: #166534">
                  By confirming, you verify that all setup requirements and
                  teardown checklists are fulfilled for this assigned space.
                </p>
              </div>
<!--               
              <div class="form-group">
                <label class="form-label"
                  >Post-event operational notes (optional)</label
                > 

                 <textarea
                  class="form-control"
                  rows="2"
                  placeholder="e.g. Venue setup verified, all AV lines checked and functioning..."
                ></textarea>
              </div> -->
              
              
              <div class="flex gap-8 mt-8">
                <label
                  for="confirm-check"
                  class="btn btn-ghost"
                  style="flex: 1; cursor: pointer"
                  >Cancel</label
                >
                <a
                  href="staff-dashboard.php"
                  class="btn btn-primary"
                  style="flex: 2; background: #16a34a; border-color: #16a34a"
                  >Confirm completion &amp; Submit Report →</a
                >
              </div>
            </div>
          </div>
        </main>

              <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
      </footer>
      </div>
    </div>

    <script>
      const venueData = {
        1: {
          name: "<?= $setupAssignment ? e($setupAssignment['venue_name']) : 'Venue' ?>",
          status: "IN SETUP",
          pillClass: "pill-in-progress",
          event: "<?= $setupAssignment ? e($setupAssignment['event_name']) : 'Event Setup' ?>",
          meta: "Event: <strong><?= $setupAssignment ? e($setupAssignment['event_name']) : 'Event Setup' ?></strong> · Oct 14, 2026 · 150 Guests · Call Time: 14:00",
          tasks: [
            {
              text: "Floor layout — 15 round tables of 10 guests each",
              checked: true,
            },
            {
              text: "Bose audio rig assembly and wireless mic array test (6 channels)",
              checked: true,
            },
            {
              text: "Catering prep station staging and bar glassware stocking (Artisan Catering)",
              checked: false,
            },
            {
              text: "Valet parking cone placement and signage at all three entrance points",
              checked: false,
            },
            {
              text: "Security detail radio distribution and perimeter briefing (4 officers)",
              checked: false,
            },
            {
              text: "Guest registration desk setup — name badges, lanyards, welcome packets",
              checked: false,
            },
            {
              text: "LED video wall and projector feed test from main AV control room",
              checked: false,
            },
            {
              text: "Floral and décor delivery receipt sign-off with venue dock staff",
              checked: false,
            },
            {
              text: "ADA elevator and accessibility ramp inspection at main entrance",
              checked: false,
            },
            {
              text: "Emergency exits verified clear and fire marshal walk-through complete",
              checked: false,
            },
          ],
        },
        2: {
          name: "Skyline Vista Lounge",
          status: "UPCOMING",
          pillClass: "pill-pending",
          event: "Tech Summit Keynote",
          meta: "Event: <strong>Tech Summit Keynote</strong> · Oct 15, 2026 · 200 Guests · Call Time: 17:30",
          tasks: [
            {
              text: "Stage riser assembly and keynote speaker podium placement",
              checked: false,
            },
            {
              text: "Dual 4K laser projector alignment and confidence monitor setup",
              checked: false,
            },
            {
              text: "High-density WiFi access point testing for 200 concurrent devices",
              checked: false,
            },
            {
              text: "Livestream broadcast encoder feed and audio sync test with production van",
              checked: false,
            },
            {
              text: "Executive VIP lounge staging and premium beverage refreshment station",
              checked: false,
            },
            {
              text: "Sponsor exhibition booth partition layout and AC power routing",
              checked: false,
            },
            {
              text: "Q&A handheld audience microphones battery check and RF frequency scan",
              checked: false,
            },
            {
              text: "Digital registration badge scanners configured, tested, and docked",
              checked: false,
            },
          ],
        },
        3: {
          name: "Brick & Steel Gallery",
          status: "UPCOMING",
          pillClass: "pill-pending",
          event: "Art Gala Reception",
          meta: "Event: <strong>Art Gala Reception</strong> · Oct 16, 2026 · 120 Guests · Call Time: 19:00",
          tasks: [
            {
              text: "Gallery track lighting dimming profile and sculpture spotlight positioning",
              checked: false,
            },
            {
              text: "High-top timber cocktail table distribution and black linen draping",
              checked: false,
            },
            {
              text: "Champagne bar mobile chilling bins and glassware staging",
              checked: false,
            },
            {
              text: "Acoustic fabric baffles deployment along north brick wall for speech clarity",
              checked: false,
            },
            {
              text: "Attended coat check ticketing station and garment rail setup",
              checked: false,
            },
            {
              text: "Background ambient sound system playlist levels balanced",
              checked: false,
            },
            {
              text: "Curbside drop-off stanchions and velvet rope placement",
              checked: false,
            },
            {
              text: "Restroom amenity check and concierge hospitality basket stocked",
              checked: false,
            },
          ],
        },
      };

      let currentVenueId = 1;

      function selectVenue(id) {
        currentVenueId = id;
        // Update cards
        for (let i = 1; i <= 3; i++) {
          const card = document.getElementById("vcard-" + i);
          if (card) {
            if (i === id) {
              card.classList.add("active");
            } else {
              card.classList.remove("active");
            }
          }
        }

        const data = venueData[id];
        document.getElementById("venue-title").innerText = data.name;
        const pill = document.getElementById("venue-pill");
        pill.innerText = data.status;
        pill.className = "pill " + data.pillClass;
        document.getElementById("venue-event-meta").innerHTML = data.meta;

        renderTasks();
      }

      function renderTasks() {
        const data = venueData[currentVenueId];
        const container = document.getElementById("task-list-container");
        container.innerHTML = "";

        data.tasks.forEach((task, index) => {
          const item = document.createElement("div");
          item.className = "task-item";
          item.innerHTML = `
          <input type="checkbox" id="task-${index}" ${task.checked ? "checked" : ""} onchange="toggleTask(${index}, this.checked)">
          <label for="task-${index}">
            <span class="cb-box"></span>
            <span class="task-text">${task.text}</span>
          </label>
        `;
          container.appendChild(item);
        });

        updateProgress();
      }

      function toggleTask(index, isChecked) {
        venueData[currentVenueId].tasks[index].checked = isChecked;
        updateProgress();
      }

      function toggleMarkAll() {
        const tasks = venueData[currentVenueId].tasks;
        const allChecked = tasks.every((t) => t.checked);
        const targetState = !allChecked;

        tasks.forEach((t) => (t.checked = targetState));
        renderTasks();
      }

      function updateProgress() {
        const tasks = venueData[currentVenueId].tasks;
        const total = tasks.length;
        const completed = tasks.filter((t) => t.checked).length;
        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

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
        selectVenue(1);
      });
    </script>
  <script src="../js/app.js"></script>
<script>
// Restore checked state from localStorage (key by assignment)
var setupKey = 'vp_setup_<?= $setupAssignment ? $setupAssignment["id"] : "0" ?>';
var saved = JSON.parse(localStorage.getItem(setupKey) || '{}');
document.querySelectorAll('.task-item input[type="checkbox"]').forEach(function(cb) {
  if (saved[cb.id]) { cb.checked = true; var lbl = document.querySelector('label[for="'+cb.id+'"]'); if(lbl) lbl.style.opacity='0.55'; }
  cb.addEventListener('change', function() {
    saved[this.id] = this.checked;
    localStorage.setItem(setupKey, JSON.stringify(saved));
  });
});

// "Mark Setup Complete" button
var completeBtn = document.querySelector('.btn-complete-setup, button.btn-primary');
if (completeBtn && completeBtn.textContent.toLowerCase().includes('complete')) {
  completeBtn.addEventListener('click', async function(e) {
    e.preventDefault();
    var done = {};
    document.querySelectorAll('.task-item input[type="checkbox"]').forEach(function(cb){ done[cb.id]=cb.checked; });
    var allChecked = Object.values(done).every(Boolean);
    if (!allChecked && !confirm('Not all tasks completed. Mark setup complete anyway?')) return;
    var res = await fetch('../api/staff.php?action=complete_setup', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({assignment_id: <?= $setupAssignment ? $setupAssignment['id'] : 0 ?>, checklist: done})
    });
    var d = await res.json();
    if (d.success) { alert('Setup marked complete!'); window.location.href='staff-dashboard.php'; }
    else alert(d.message || 'Could not update setup status.');
  });
}
</script>
</body>
</html>

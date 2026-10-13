<?php
/**
 * VenuePro General Helpers & Utilities
 */

/**
 * Output JSON response and terminate script.
 */
function jsonResponse(bool $success, string $message = '', $data = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Escape string for safe HTML output (XSS protection).
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize clean string input.
 */
function sanitize(?string $input): string {
    return trim($input ?? '');
}

/**
 * Format currency.
 */
function formatMoney(float $amount): string {
    return '$' . number_format($amount, 2);
}

/**
 * Format date nicely.
 */
function formatDate(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return '';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : $date;
}

/**
 * Read JSON input body from POST request.
 */
function getJsonInput(): array {
    if (!empty($GLOBALS['TEST_INPUT'])) {
        $testData = is_array($GLOBALS['TEST_INPUT']) ? $GLOBALS['TEST_INPUT'] : json_decode($GLOBALS['TEST_INPUT'], true);
        if (is_array($testData)) return $testData;
    }
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return !empty($_POST) ? $_POST : [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : (!empty($_POST) ? $_POST : []);
}

/**
 * Compute the 6 real-time progression steps for a booking.
 * 
 * Step 1: Booking Submitted (Always true once booking exists)
 * Step 2: Under Review (Admin Review: ticks true as soon as admin approves/confirms)
 * Step 3: Awaiting Caterer Confirmation (Ticks true as soon as caterer accepts, or auto-true if venue only)
 * Step 4: Approved & Confirmed (Ticks true as soon as BOTH admin and caterer have accepted)
 * Step 5: Event Day Preparation (Staff staging: ticks true as soon as staff completes setup / >= 100%)
 * Step 6: Event Complete (Ticks true as soon as staff submits completion report & marks event completed)
 */
function getBookingProgressTimeline(array $booking): array {
    $bookingStatus = strtolower($booking['booking_status'] ?? 'pending');
    $catererStatus = strtolower($booking['caterer_status'] ?? 'pending');
    $setupStatus   = strtolower($booking['setup_status'] ?? 'pending');
    $completionPct = (int)($booking['completion_pct'] ?? 0);
    $packageId     = (int)($booking['package_id'] ?? 0);
    $hasCatering   = (!empty($packageId) || !empty($booking['package_title']) || (float)($booking['package_cost'] ?? 0) > 0 || !empty($booking['is_custom_package']));

    // Handle cancellation
    if ($bookingStatus === 'cancelled' || $bookingStatus === 'canceled') {
        $cancelReason = !empty($booking['special_notes']) ? trim($booking['special_notes']) : 'Cancelled by customer';
        return [
            [
                'step'    => 1,
                'label'   => 'Booking Submitted',
                'done'    => true,
                'status'  => 'completed',
                'subtext' => 'Reservation received & payment secured on ' . date('M j, Y g:i A', strtotime($booking['created_at'] ?? 'now')),
                'badge'   => 'RECEIVED'
            ],
            [
                'step'    => 2,
                'label'   => 'Cancelled',
                'done'    => true,
                'status'  => 'cancelled',
                'cancelled' => true,
                'subtext' => $cancelReason,
                'badge'   => 'CANCELLED'
            ]
        ];
    }

    // 1. Admin Acceptance Check
    $adminAccepted = in_array($bookingStatus, ['confirmed', 'completed']);
    $adminRejected = ($bookingStatus === 'rejected');

    // 2. Caterer Acceptance Check
    $catererAccepted = (!$hasCatering || $catererStatus === 'accepted');
    $catererRejected = ($hasCatering && $catererStatus === 'rejected');

    // 3. Both Confirmed Check
    $bothConfirmed = ($adminAccepted && $catererAccepted);

    // 4. Setup & Event Done Checks
    $isSetupDone   = ($setupStatus === 'completed' || $completionPct >= 100 || $bookingStatus === 'completed');
    $isSetupInPrep = ($setupStatus === 'in_progress' || $completionPct > 0);
    $isEventDone   = ($bookingStatus === 'completed');

    // Step 1: Booking Submitted
    $createdAtFormatted = !empty($booking['created_at']) ? date('M j, Y g:i A', strtotime($booking['created_at'])) : date('M j, Y');
    $step1 = [
        'step'    => 1,
        'label'   => 'Booking Submitted',
        'done'    => true,
        'status'  => 'completed',
        'subtext' => 'Reservation received & payment secured on ' . $createdAtFormatted,
        'badge'   => 'RECEIVED'
    ];

    // Step 2: Under Review (Admin Acceptance)
    $step2 = [
        'step'    => 2,
        'label'   => 'Under Review',
        'done'    => $adminAccepted,
        'status'  => $adminAccepted ? 'completed' : ($adminRejected ? 'rejected' : 'in_progress'),
        'subtext' => $adminAccepted 
            ? 'Administrative verification approved by Venue Administration' 
            : ($adminRejected ? 'Application rejected by administration: ' . ($booking['special_notes'] ?? 'Capacity/scheduling conflict') : 'Venue administration is currently reviewing scheduling & capacity'),
        'badge'   => $adminAccepted ? 'APPROVED' : ($adminRejected ? 'REJECTED' : 'IN REVIEW')
    ];

    // Step 3: Awaiting Caterer Confirmation (Caterer Acceptance)
    $step3 = [
        'step'    => 3,
        'label'   => 'Awaiting Caterer Confirmation',
        'done'    => $catererAccepted,
        'status'  => $catererAccepted ? 'completed' : ($catererRejected ? 'rejected' : ($adminAccepted ? 'in_progress' : 'waiting')),
        'subtext' => !$hasCatering 
            ? 'No catering package requested (Venue only reservation)' 
            : ($catererAccepted 
                ? 'Executive chef & banquet kitchen verified menu and ingredients' 
                : ($catererRejected 
                    ? 'Caterer declined order: ' . ($booking['caterer_rejection_reason'] ?? 'Kitchen capacity reached') 
                    : 'Catering culinary team is reviewing course menus and dietary accommodations')),
        'badge'   => !$hasCatering ? 'N/A (VENUE ONLY)' : ($catererAccepted ? 'CONFIRMED' : ($catererRejected ? 'DECLINED' : 'AWAITING CATERER'))
    ];

    // Step 4: Approved & Confirmed (Both Admin & Caterer Accepted)
    $step4 = [
        'step'    => 4,
        'label'   => 'Approved & Confirmed',
        'done'    => $bothConfirmed,
        'status'  => $bothConfirmed ? 'completed' : (($adminRejected || $catererRejected) ? 'rejected' : 'waiting'),
        'subtext' => $bothConfirmed 
            ? 'Event reservation & catering are locked and officially confirmed' 
            : (($adminRejected || $catererRejected) ? 'Reservation could not be confirmed' : 'Awaiting completion of all administrative and catering authorizations'),
        'badge'   => $bothConfirmed ? 'CONFIRMED' : 'PENDING'
    ];

    // Step 5: Event Day Preparation (Staff Setup)
    $eventDateFormatted = !empty($booking['event_date']) ? date('M j, Y', strtotime($booking['event_date'])) : 'Event Day';
    $step5 = [
        'step'    => 5,
        'label'   => 'Event Day Preparation',
        'done'    => $isSetupDone,
        'status'  => $isSetupDone ? 'completed' : ($isSetupInPrep ? 'in_progress' : 'waiting'),
        'subtext' => $isSetupDone 
            ? 'All 10 checklist protocols verified & certified by ' . (!empty($booking['reported_staff']) ? $booking['reported_staff'] : 'Venue Operations Staff') . (!empty($booking['completed_at']) ? ' on ' . date('M j, Y g:i A', strtotime($booking['completed_at'])) : '') 
            : ($isSetupInPrep ? 'Operations team staging venue (' . $completionPct . '% completed)' : 'Venue setup and A/V staging scheduled for ' . $eventDateFormatted),
        'badge'   => $isSetupDone ? '100% READY' : ($isSetupInPrep ? $completionPct . '% IN PROGRESS' : 'SCHEDULED')
    ];

    // Step 6: Event Complete (Staff Event Sign-Off)
    $step6 = [
        'step'    => 6,
        'label'   => 'Event Complete',
        'done'    => $isEventDone,
        'status'  => $isEventDone ? 'completed' : 'waiting',
        'subtext' => $isEventDone 
            ? 'Event successfully concluded. Final operations sign-off completed and archived.' 
            : 'Post-event sign-off and wrap-up certification will trigger upon event conclusion',
        'badge'   => $isEventDone ? 'COMPLETED' : 'UPCOMING'
    ];

    return [$step1, $step2, $step3, $step4, $step5, $step6];
}

/**
 * Get unread notification count for a user.
 */
function getUnreadNotificationCount(?int $userId = null): int {
    if (!$userId) {
        if (function_exists('getCurrentUser')) {
            $user = getCurrentUser();
            $userId = $user['id'] ?? null;
        } elseif (isset($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
        }
    }
    if (!$userId) return 0;
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([(int)$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Render the topbar notification badge HTML.
 * Renders hidden with data-count="0" if count is 0.
 */
function renderNotifBadge(?int $count = null): string {
    if ($count === null) {
        $count = getUnreadNotificationCount();
    }
    $style = ($count > 0) ? '' : 'style="display:none;"';
    $text = ($count > 0) ? (string)$count : '';
    return '<span class="badge" id="notif-badge" ' . $style . ' data-count="' . (int)$count . '">' . $text . '</span>';
}


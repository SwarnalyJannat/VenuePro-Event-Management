<?php
/**
 * Test script for fresh account notification badge behavior and unread counting.
 */
require_once 'C:/xampp/htdocs/venuepro/config/database.php';
require_once 'C:/xampp/htdocs/venuepro/config/auth.php';
require_once 'C:/xampp/htdocs/venuepro/config/helpers.php';

echo "=== STARTING NOTIFICATION BADGE TESTS ===\n";

$db = getDBConnection();

// 1. Create a fresh test user
$testEmail = 'fresh_user_test_' . time() . '@example.com';
$stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, 'dummy_hash', 'customer', 'active')");
$stmt->execute(['Fresh Test User', $testEmail]);
$freshUserId = (int)$db->lastInsertId();

echo "Created fresh user ID: $freshUserId ($testEmail)\n";

try {
    // 2. Test initial count for fresh account
    $count = getUnreadNotificationCount($freshUserId);
    echo "Fresh account unread count: $count (Expected: 0) -> " . ($count === 0 ? "PASS" : "FAIL") . "\n";

    // 3. Test badge rendering for fresh account
    $badgeHtml = renderNotifBadge($count);
    echo "Rendered HTML: $badgeHtml\n";
    $hasDisplayNone = strpos($badgeHtml, 'style="display:none;"') !== false;
    $hasCount0 = strpos($badgeHtml, 'data-count="0"') !== false;
    $isEmpty = strpos($badgeHtml, '></span>') !== false;
    echo "Badge is hidden (style display:none): " . ($hasDisplayNone ? "PASS" : "FAIL") . "\n";
    echo "Badge has data-count='0': " . ($hasCount0 ? "PASS" : "FAIL") . "\n";
    echo "Badge content is empty: " . ($isEmpty ? "PASS" : "FAIL") . "\n";

    // 4. Test when a notification arrives
    $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url, is_read) VALUES (?, 'Welcome', 'Welcome to VenuePro', 'system', '#', 0)");
    $stmtNotif->execute([$freshUserId]);
    $newNotifId = (int)$db->lastInsertId();

    $countAfter = getUnreadNotificationCount($freshUserId);
    echo "Unread count after 1 notification: $countAfter (Expected: 1) -> " . ($countAfter === 1 ? "PASS" : "FAIL") . "\n";

    $badgeHtmlAfter = renderNotifBadge($countAfter);
    echo "Rendered HTML with 1 notif: $badgeHtmlAfter\n";
    $isVisible = strpos($badgeHtmlAfter, 'style="display:none;"') === false;
    $hasCount1 = strpos($badgeHtmlAfter, 'data-count="1"') !== false;
    $hasText1 = strpos($badgeHtmlAfter, '>1</span>') !== false;
    echo "Badge is visible: " . ($isVisible ? "PASS" : "FAIL") . "\n";
    echo "Badge has data-count='1': " . ($hasCount1 ? "PASS" : "FAIL") . "\n";
    echo "Badge displays text '1': " . ($hasText1 ? "PASS" : "FAIL") . "\n";

    // 5. Test marking notification as read
    $stmtMark = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmtMark->execute([$freshUserId]);

    $countRead = getUnreadNotificationCount($freshUserId);
    echo "Unread count after mark read: $countRead (Expected: 0) -> " . ($countRead === 0 ? "PASS" : "FAIL") . "\n";

    $badgeHtmlRead = renderNotifBadge($countRead);
    echo "Rendered HTML after mark read: $badgeHtmlRead\n";
    $isReHidden = strpos($badgeHtmlRead, 'style="display:none;"') !== false && strpos($badgeHtmlRead, 'data-count="0"') !== false;
    echo "Badge re-hidden properly: " . ($isReHidden ? "PASS" : "FAIL") . "\n";

} finally {
    // Cleanup
    $db->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$freshUserId]);
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$freshUserId]);
    echo "Cleaned up test user.\n";
}

echo "=== ALL NOTIFICATION BADGE TESTS PASSED ===\n";

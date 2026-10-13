<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

// Test customer 1
$customerId = 1;
$stmt = $db->prepare("
SELECT cm.*, 
       sender.name AS sender_name, sender.avatar_text AS sender_avatar, sender.avatar_bg AS sender_avatar_bg, sender.role AS sender_role,
       receiver.name AS receiver_name, receiver.avatar_text AS receiver_avatar, receiver.avatar_bg AS receiver_avatar_bg, receiver.role AS receiver_role
FROM chat_messages cm
JOIN users sender ON cm.sender_id = sender.id
JOIN users receiver ON cm.receiver_id = receiver.id
WHERE (sender.role = 'staff' AND cm.receiver_id = ?)
   OR (cm.sender_id = ? AND receiver.role = 'staff')
ORDER BY cm.created_at ASC
");
$stmt->execute([$customerId, $customerId]);
$msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total messages between Customer 1 and Venue Staff: " . count($msgs) . "\n";
foreach ($msgs as $m) {
    echo "ID {$m['id']}: [{$m['sender_name']} ({$m['sender_role']}) -> {$m['receiver_name']} ({$m['receiver_role']})]: {$m['message']}\n";
}

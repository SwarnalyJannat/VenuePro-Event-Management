<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$users = $db->query("SELECT email, password_hash FROM users WHERE role='staff'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo $u['email'] . ":\n";
    foreach (['admin123', 'password123', 'staff123', 'password', '123456'] as $pass) {
        if (password_verify($pass, $u['password_hash'])) {
            echo "  Matched: $pass\n";
        }
    }
}

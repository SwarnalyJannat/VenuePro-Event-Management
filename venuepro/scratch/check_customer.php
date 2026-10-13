<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$customerUser = $db->query("SELECT * FROM users WHERE role = 'customer' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($customerUser);

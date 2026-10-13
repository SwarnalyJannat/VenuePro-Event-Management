<?php
$db = new PDO("mysql:host=localhost;dbname=venuepro", "root", "");
$vs = $db->query("SELECT id, name FROM venues")->fetchAll(PDO::FETCH_ASSOC);
print_r($vs);
$staff = $db->query("SELECT sp.*, u.name, u.email FROM staff_profiles sp JOIN users u ON sp.user_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
print_r($staff);

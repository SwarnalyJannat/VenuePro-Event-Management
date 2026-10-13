<?php
$db = new PDO("mysql:host=localhost;dbname=venuepro", "root", "");
$staff = $db->query("SELECT sp.*, u.name, u.email FROM staff_profiles sp JOIN users u ON sp.user_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
echo "Staff Profiles:\n";
print_r($staff);
$caterers = $db->query("SELECT cp.*, u.name, u.email FROM caterer_profiles cp JOIN users u ON cp.user_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
echo "Caterer Profiles:\n";
print_r($caterers);

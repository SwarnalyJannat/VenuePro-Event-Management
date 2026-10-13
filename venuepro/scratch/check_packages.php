<?php
$db = new PDO("mysql:host=localhost;dbname=venuepro", "root", "");
$pkgs = $db->query("SELECT id, caterer_id, title, tier, price_per_event FROM catering_packages")->fetchAll(PDO::FETCH_ASSOC);
print_r($pkgs);

<?php
$db = new PDO("mysql:host=localhost;dbname=venuepro", "root", "");
$cols = $db->query("DESCRIBE singular_menu_items")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c["Field"] . " (" . $c["Type"] . ")\n";
$items = $db->query("SELECT id, caterer_id, item_name, unit_price FROM singular_menu_items LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($items);

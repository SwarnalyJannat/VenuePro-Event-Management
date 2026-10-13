<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$db->exec("ALTER TABLE package_menu_items MODIFY COLUMN course VARCHAR(50) DEFAULT 'Course 1'");
echo "package_menu_items.course altered to VARCHAR(50) successfully\n";

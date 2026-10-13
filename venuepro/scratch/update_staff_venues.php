<?php
$db = new PDO("mysql:host=localhost;dbname=venuepro", "root", "");
$db->exec("UPDATE staff_profiles SET assigned_venues = '1,2,7' WHERE user_id = 3");
$db->exec("UPDATE staff_profiles SET assigned_venues = '3,4' WHERE user_id = 5");
$db->exec("UPDATE staff_profiles SET assigned_venues = '5,6' WHERE user_id = 6");
echo "Staff profiles assigned venues updated successfully.\n";

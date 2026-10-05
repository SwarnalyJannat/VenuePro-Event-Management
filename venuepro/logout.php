<?php
/**
 * VenuePro Central Logout Controller
 * Destroys current session, clears authentication cookies, and redirects to venues.php
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

logoutUser();

// Clean redirect to venues catalog
header("Location: venues.php");
exit;

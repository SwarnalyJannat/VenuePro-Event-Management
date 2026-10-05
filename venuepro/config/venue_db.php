<?php
/**
 * VenuePro Multi-Database Per-Venue Storage Engine
 * Manages dedicated MySQL databases for each venue and secures photo assets as LONGBLOBs.
 */
require_once __DIR__ . '/database.php';

/**
 * Returns a PDO connection to the dedicated database for a specific venue.
 * Automatically provisions the database and photos schema if not already existing.
 *
 * @param int $venueId
 * @return PDO
 */
function getVenueDBConnection(int $venueId): PDO {
    if ($venueId <= 0) {
        throw new InvalidArgumentException("Valid venue_id is required.");
    }

    $dbName = "venuepro_venue_" . $venueId;
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';

    // Step 1: Connect to MySQL root and ensure the dedicated venue database exists
    $rootPdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // Step 2: Connect to the venue's dedicated database
    $venuePdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Step 3: Ensure the secure photos table exists inside this venue's database
    $tableSql = "
    CREATE TABLE IF NOT EXISTS photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venue_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size INT NOT NULL,
        photo_data LONGBLOB NOT NULL,
        caption VARCHAR(255) DEFAULT '',
        is_cover TINYINT(1) DEFAULT 0,
        sort_order INT DEFAULT 0,
        uploaded_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_venue_sort (sort_order),
        INDEX idx_cover (is_cover)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $venuePdo->exec($tableSql);

    return $venuePdo;
}

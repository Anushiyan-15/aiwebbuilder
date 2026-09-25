<?php
// ═══════════════════════════════════════════════════════════════
//  includes/db.php — Safe PDO Database Helper
// ═══════════════════════════════════════════════════════════════

require_once dirname(__DIR__) . '/config/database.php';

function getDb(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Return null to allow graceful fallback to file storage
        error_log('Database Connection Error: ' . $e->getMessage());
        return null;
    }
}

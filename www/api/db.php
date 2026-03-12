<?php
// Simple DB helper for caulong app
$DB_HOST = getenv('DB_HOST') ?: 'mysql';
$DB_NAME = getenv('DB_NAME') ?: 'appdb';
$DB_USER = getenv('DB_USER') ?: 'appuser';
$DB_PASS = getenv('DB_PASS') ?: 'secret';
$DB_CHARSET = 'utf8mb4';

function caulong_pdo() {
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // Try connect; if DB missing, create then reconnect
    $dsnWithDb = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
    try {
        $pdo = new PDO($dsnWithDb, $DB_USER, $DB_PASS, $options);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown database') !== false) {
            // Connect without DB and create it
            $dsnNoDb = "mysql:host={$DB_HOST};charset={$DB_CHARSET}";
            $tmp = new PDO($dsnNoDb, $DB_USER, $DB_PASS, $options);
            $tmp->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET {$DB_CHARSET} COLLATE {$DB_CHARSET}_unicode_ci");
            $pdo = new PDO($dsnWithDb, $DB_USER, $DB_PASS, $options);
        } else {
            throw $e;
        }
    }

    // Ensure table exists
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS caulong_results (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            group_key VARCHAR(16) NOT NULL,
            match_code VARCHAR(32) NOT NULL,
            team1 VARCHAR(128) NOT NULL,
            team2 VARCHAR(128) NOT NULL,
            set1_team1 INT NOT NULL DEFAULT 0,
            set1_team2 INT NOT NULL DEFAULT 0,
            set2_team1 INT NOT NULL DEFAULT 0,
            set2_team2 INT NOT NULL DEFAULT 0,
            score1 INT NOT NULL DEFAULT 0,
            score2 INT NOT NULL DEFAULT 0,
            winner VARCHAR(128) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_match (group_key, match_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    // Create config table for tournament setup
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS caulong_config (
            `key` VARCHAR(32) PRIMARY KEY,
            `value` JSON NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    // Ensure columns exist (for older tables)
    $columns = ['set1_team1', 'set1_team2', 'set2_team1', 'set2_team2'];
    foreach ($columns as $col) {
        $stmt = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'caulong_results' AND COLUMN_NAME = :col");
        $stmt->execute([':db' => $DB_NAME, ':col' => $col]);
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE caulong_results ADD COLUMN {$col} INT NOT NULL DEFAULT 0");
        }
    }

    return $pdo;
}

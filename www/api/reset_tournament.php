<?php
// CORS for remote frontends
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

try {
    $pdo = caulong_pdo();
    // Use TRUNCATE to clear all data and reset auto-increment if any (though match_code seems to be string/key)
    $stmt = $pdo->prepare("TRUNCATE TABLE caulong_results");
    $stmt->execute();

    $stmt = $pdo->prepare("DELETE FROM caulong_config WHERE `key` = 'tournament_v1'");
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Tournament data reset successfully.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'db.php';

try {
    $pdo = caulong_pdo();

    // Fetch config
    $stmt = $pdo->prepare("SELECT `value` FROM caulong_config WHERE `key` = :key");
    $stmt->execute([':key' => 'tournament_v1']);
    $val = $stmt->fetchColumn();

    if ($val) {
        $groups = json_decode($val, true);

        // Fetch results as well if needed, but keeping this focused on config
        echo json_encode(['success' => true, 'data' => $groups]);
    } else {
        // Return null or success: false to trigger setup
        echo json_encode(['success' => true, 'data' => null]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

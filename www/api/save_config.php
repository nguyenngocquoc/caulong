<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'db.php';

try {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!$data || !isset($data['groupA']) || !isset($data['groupB']) || !isset($data['groupC'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Invalid data format']));
    }

    $pdo = caulong_pdo();

    // Reset old config if needed, but here we just upsert
    // Since we only have one tournament config, we use a fixed key 'tournament_v1'
    $jsonVal = json_encode($data);
    $stmt = $pdo->prepare("INSERT INTO caulong_config (`key`, `value`) VALUES (:key, :val1) ON DUPLICATE KEY UPDATE `value` = :val2");
    $stmt->execute([
        ':key' => 'tournament_v1',
        ':val1' => $jsonVal,
        ':val2' => $jsonVal
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
// CORS for remote frontends (e.g., github.io)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

try {
    $pdo = caulong_pdo();
    $stmt = $pdo->query("SELECT group_key, match_code, team1, team2, set1_team1, set1_team2, set2_team1, set2_team2, score1, score2, winner, updated_at FROM caulong_results ORDER BY group_key, match_code");
    $rows = $stmt->fetchAll();

    $data = ['groupA' => [], 'groupB' => []];
    foreach ($rows as $row) {
        $entry = [
            'match_code' => $row['match_code'],
            'team1' => $row['team1'],
            'team2' => $row['team2'],
            'set1_team1' => (int)$row['set1_team1'],
            'set1_team2' => (int)$row['set1_team2'],
            'set2_team1' => (int)$row['set2_team1'],
            'set2_team2' => (int)$row['set2_team2'],
            'score1' => (int)$row['score1'],
            'score2' => (int)$row['score2'],
            'winner' => $row['winner'],
            'updated_at' => $row['updated_at'],
        ];
        if ($row['group_key'] === 'groupA') {
            $data['groupA'][] = $entry;
        } elseif ($row['group_key'] === 'groupB') {
            $data['groupB'][] = $entry;
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}


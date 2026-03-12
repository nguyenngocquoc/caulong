<?php
// CORS for remote frontends (e.g., github.io)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

function json_error($msg, $code = 400)
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    json_error('Invalid JSON');
}

$groupKey = $input['group_key'] ?? '';
$matchCode = $input['match_code'] ?? '';
$team1 = trim($input['team1'] ?? '');
$team2 = trim($input['team2'] ?? '');
$set1_team1 = isset($input['set1_team1']) ? (int) $input['set1_team1'] : null;
$set1_team2 = isset($input['set1_team2']) ? (int) $input['set1_team2'] : null;
$set2_team1 = isset($input['set2_team1']) ? (int) $input['set2_team1'] : null;
$set2_team2 = isset($input['set2_team2']) ? (int) $input['set2_team2'] : null;
$winner = $input['winner'] ?? null;

if (empty($groupKey)) {
    json_error('group_key invalid');
}
if ($matchCode === '' || $team1 === '' || $team2 === '' || $set1_team1 === null || $set1_team2 === null || $set2_team1 === null || $set2_team2 === null) {
    json_error('Missing fields');
}

$score1 = $set1_team1 + $set2_team1;
$score2 = $set1_team2 + $set2_team2;

try {
    $pdo = caulong_pdo();
    $stmt = $pdo->prepare(
        "INSERT INTO caulong_results (group_key, match_code, team1, team2, set1_team1, set1_team2, set2_team1, set2_team2, score1, score2, winner)
         VALUES (:group_key, :match_code, :team1, :team2, :set1_team1, :set1_team2, :set2_team1, :set2_team2, :score1, :score2, :winner)
         ON DUPLICATE KEY UPDATE
            team1 = VALUES(team1),
            team2 = VALUES(team2),
            set1_team1 = VALUES(set1_team1),
            set1_team2 = VALUES(set1_team2),
            set2_team1 = VALUES(set2_team1),
            set2_team2 = VALUES(set2_team2),
            score1 = VALUES(score1),
            score2 = VALUES(score2),
            winner = VALUES(winner),
            updated_at = CURRENT_TIMESTAMP"
    );
    $stmt->execute([
        ':group_key' => $groupKey,
        ':match_code' => $matchCode,
        ':team1' => $team1,
        ':team2' => $team2,
        ':set1_team1' => $set1_team1,
        ':set1_team2' => $set1_team2,
        ':set2_team1' => $set2_team1,
        ':set2_team2' => $set2_team2,
        ':score1' => $score1,
        ':score2' => $score2,
        ':winner' => $winner,
    ]);

    // Return latest data
    $stmt = $pdo->query("SELECT group_key, match_code, team1, team2, set1_team1, set1_team2, set2_team1, set2_team2, score1, score2, winner, updated_at FROM caulong_results ORDER BY group_key, match_code");
    $rows = $stmt->fetchAll();
    $data = [];
    foreach ($rows as $row) {
        $entry = [
            'match_code' => $row['match_code'],
            'team1' => $row['team1'],
            'team2' => $row['team2'],
            'set1_team1' => (int) $row['set1_team1'],
            'set1_team2' => (int) $row['set1_team2'],
            'set2_team1' => (int) $row['set2_team1'],
            'set2_team2' => (int) $row['set2_team2'],
            'score1' => (int) $row['score1'],
            'score2' => (int) $row['score2'],
            'winner' => $row['winner'],
            'updated_at' => $row['updated_at'],
        ];

        $g = $row['group_key'];
        if (!isset($data[$g])) {
            $data[$g] = [];
        }
        $data[$g][] = $entry;
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}


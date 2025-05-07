<?php
// api/start_combat.php

declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require __DIR__ . '/../db.php';
session_start();

// 1) Auth
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    exit(json_encode(['error'=>'Not logged in']));
}

// 2) Decode & validate input
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$party    = $input['party']    ?? [];
$monsters = $input['monsters'] ?? [];

if (!is_array($party) || !is_array($monsters)) {
    http_response_code(400);
    exit(json_encode(['error'=>'Invalid payload']));
}
if (count($party) > 4) {
    http_response_code(400);
    exit(json_encode(['error'=>'Party cannot exceed 4 members']));
}
if (count($monsters) > 6) {
    http_response_code(400);
    exit(json_encode(['error'=>'No more than 6 monsters allowed']));
}

// 3) Load PCs from DB
//    We'll query only the characters belonging to this user
$placeholders = implode(',', array_fill(0, count($party), '?'));
$stmt = $pdo->prepare("
    SELECT 
      id, name, speed, endurance AS max_endurance, current_endurance,
      level, battle_disposition, social_disposition,
      wellspring, focus_type, focus,
      luck, current_luck,
      golden_dollars, adventure_tokens_left
    FROM characters
    WHERE user_id = ? AND id IN ({$placeholders})
");
$stmt->execute(array_merge([$userId], $party));
$pcs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($pcs) !== count($party)) {
    http_response_code(404);
    exit(json_encode(['error'=>'One or more party members not found']));
}

// 4) Prepare monster entries
foreach ($monsters as &$m) {
    if (!isset($m['name'], $m['speed'], $m['endurance'])) {
        http_response_code(400);
        exit(json_encode(['error'=>'Each monster needs name, speed, endurance']));
    }
    $m['max_endurance']     = (int)$m['endurance'];
    $m['current_endurance'] = (int)$m['endurance'];
    $m['speed']             = (int)$m['speed'];
    $m['type']              = 'monster';
}
unset($m);

// 5) Compute initiative tie‑breakers
function dieSize(int $level): int {
    if ($level >= 9) return 12;
    if ($level >= 7) return 10;
    if ($level >= 5) return 8;
    if ($level >= 3) return 6;
    return 4;
}

// Attach tie_breaker rolls to each combatant
$roster = [];
foreach ($pcs as $pc) {
    $tb = random_int(1, dieSize((int)$pc['level']));
    $roster[] = array_merge($pc, ['type'=>'player','tie_breaker'=>$tb]);
}
foreach ($monsters as $m) {
    // use monster level = 1 for tie breaker
    $tb = random_int(1, dieSize(1));
    $roster[] = array_merge($m, ['tie_breaker'=>$tb]);
}

// Sort by speed desc, then tie_breaker desc
usort($roster, function($a,$b){
    if ($a['speed'] !== $b['speed']) {
        return $b['speed'] - $a['speed'];
    }
    return $b['tie_breaker'] - $a['tie_breaker'];
});

// Assign initiative_order
foreach ($roster as $i => &$c) {
    $c['initiative_order'] = $i + 1;
}
unset($c);

// 6) Insert combat + combatants
$pdo->beginTransaction();
try {
    // 6a) combat record
    $cStmt = $pdo->prepare("
        INSERT INTO combats 
          (user_id, can_choose_range) 
        VALUES (?, ?)
    ");
    // party wins if any PC speed > any monster speed
    $maxPcSpeed      = max(array_column($pcs,'speed'));
    $maxMonsterSpeed = max(array_column($monsters,'speed'));
    $canChoose = $maxPcSpeed > $maxMonsterSpeed ? 1 : 0;

    $cStmt->execute([$userId, $canChoose]);
    $combatId = (int)$pdo->lastInsertId();

    // 6b) each combatant
    $cbStmt = $pdo->prepare("
        INSERT INTO combatants
          (combat_id, character_id, type,
           speed, max_endurance, current_endurance,
           bm, am,
           battle_disposition, social_disposition,
           wellspring, focus_type, focus,
           luck, current_luck,
           golden_dollars, adventure_tokens_left,
           initiative_order, tie_breaker)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($roster as $c) {
        $isPlayer = $c['type']==='player';
        $cbStmt->execute([
            $combatId,
            $isPlayer ? $c['id'] : null,
            $c['type'],
            $c['speed'],
            $c['max_endurance'],
            $c['current_endurance'],
            $isPlayer ? ($c['level'] + 1) : 0,   // bm
            $isPlayer ? ($c['level'] + 1) : 0,   // am
            $c['battle_disposition']    ?? null,
            $c['social_disposition']    ?? null,
            $c['wellspring']            ?? null,
            $c['focus_type']            ?? null,
            $c['focus']                 ?? null,
            $c['luck']                  ?? 0,
            $c['current_luck']          ?? 0,
            $c['golden_dollars']        ?? 0,
            $c['adventure_tokens_left'] ?? 0,
            $c['initiative_order'],
            $c['tie_breaker']
        ]);
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    exit(json_encode(['error'=>'Failed to start combat','details'=>$e->getMessage()]));
}

// 7) Return the combat state
echo json_encode([
    'combat_id'        => $combatId,
    'can_choose_range' => (bool)$canChoose,
    'combatants'       => $roster
]);

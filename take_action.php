<?php
// api/take_action.php

declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require __DIR__ . '/../db.php';
session_start();

// 1) Authenticate
$userId = $_SESSION['user_id'] ?? null;
if (! $userId) {
    http_response_code(401);
    exit(json_encode(['error'=>'Not logged in']));
}

// 2) Decode & validate input
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$combatId     = $input['combat_id']     ?? null;
$combatantId  = $input['combatant_id']  ?? null;
$actionType   = $input['action_type']   ?? null;    // "free", "bm", "ability", "move"
$actionName   = $input['action_name']   ?? null;    // e.g. "Strike", or range name
$abilityId    = $input['ability_id']    ?? null;    // when action_type=="ability"
$targetId     = $input['target_id']     ?? null;    // combatant id of target
$luckSpent    = isset($input['luck_spent']) 
                ? (int)$input['luck_spent'] : 0;

if (! $combatId || ! $combatantId || ! $actionType) {
    http_response_code(400);
    exit(json_encode(['error'=>'Missing required fields']));
}

// 3) Load acting combatant (and verify ownership for players)
$stmt = $pdo->prepare("
  SELECT cmt.*, ch.user_id, ch.current_luck
    FROM combatants cmt
    LEFT JOIN characters ch ON cmt.character_id = ch.id
   WHERE cmt.id = ? AND cmt.combat_id = ?
");
$stmt->execute([$combatantId, $combatId]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);

if (! $actor) {
    http_response_code(404);
    exit(json_encode(['error'=>'Combatant not found']));
}
// If this is a player, ensure it belongs to the session user
if ($actor['type']==='player' && $actor['user_id'] != $userId) {
    http_response_code(403);
    exit(json_encode(['error'=>'Not your character']));
}

// 4) Deduct luck if requested
if ($luckSpent > 0) {
    if ($luckSpent > $actor['current_luck']) {
        http_response_code(400);
        exit(json_encode(['error'=>'Not enough Luck']));
    }
    $actor['current_luck'] -= $luckSpent;
    $pdo->prepare("UPDATE characters 
                     SET current_luck = ? 
                   WHERE id = ?")
        ->execute([$actor['current_luck'], $actor['character_id']]);
}

// 5) Handle the action
switch ($actionType) {
    case 'free':
        // free actions cost no BM
        // TODO: implement disposition action logic, e.g. damage or buff
        $log = "Executed free action '{$actionName}'";
        break;

    case 'bm':
        // a second stance action or basic attack
        if ($actor['bm'] < 1) {
            http_response_code(400);
            exit(json_encode(['error'=>'Not enough Battle Momentum']));
        }
        $actor['bm'] -= 1;
        $pdo->prepare("UPDATE combatants SET bm = ? WHERE id = ?")
            ->execute([$actor['bm'], $actorId]);
        // TODO: implement effect of $actionName
        $log = "Spent 1 BM for '{$actionName}'";
        break;

    case 'ability':
        // use a named ability from ability_definitions
        if (! $abilityId) {
            http_response_code(400);
            exit(json_encode(['error'=>'Missing ability_id']));
        }
        // Load ability
        $aStmt = $pdo->prepare("SELECT * FROM ability_definitions WHERE id = ?");
        $aStmt->execute([$abilityId]);
        $ability = $aStmt->fetch(PDO::FETCH_ASSOC);
        if (! $ability) {
            http_response_code(404);
            exit(json_encode(['error'=>'Ability not found']));
        }
        $cost = (int)$ability['cost_bm'];
        if ($actor['bm'] < $cost) {
            http_response_code(400);
            exit(json_encode(['error'=>'Not enough BM for ability']));
        }
        // Deduct BM
        $actor['bm'] -= $cost;
        $pdo->prepare("UPDATE combatants SET bm = ? WHERE id = ?")
            ->execute([$actor['bm'], $combatantId]);
        // Apply effect to target
        if ($targetId) {
            // Load target
            $tStmt = $pdo->prepare("
                SELECT id, current_endurance 
                  FROM combatants 
                 WHERE id = ? AND combat_id = ?");
            $tStmt->execute([$targetId, $combatId]);
            $target = $tStmt->fetch(PDO::FETCH_ASSOC);
            if ($target) {
                $params = json_decode($ability['effect_params'], true);
                switch ($ability['effect_type']) {
                    case 'damage':
                        $dmg = $params['damage'] ?? 0;
                        $target['current_endurance'] = max(0, $target['current_endurance'] - $dmg);
                        $pdo->prepare("
                            UPDATE combatants 
                               SET current_endurance = ?
                             WHERE id = ?
                        ")->execute([$target['current_endurance'], $targetId]);
                        $log = "{$actor['type']} used {$ability['label']} on target #{$targetId}, dealt {$dmg} dmg";
                        break;
                    // TODO: handle other effect types (heal, buff, move, etc.)
                    default:
                        $log = "Used {$ability['label']} (effect_type={$ability['effect_type']})";
                }
            } else {
                $log = "Ability used but target not found";
            }
        } else {
            $log = "Used {$ability['label']} with no target";
        }
        break;

    case 'move':
        // change range (e.g. "Close", "Midfield", "Backline")
        $range = $actionName;
        $valid = ['Close','Midfield','Backline'];
        if (! in_array($range, $valid, true)) {
            http_response_code(400);
            exit(json_encode(['error'=>'Invalid range']));
        }
        if ($actor['bm'] < 1) {
            http_response_code(400);
            exit(json_encode(['error'=>'Not enough BM to move']));
        }
        $actor['bm'] -= 1;
        $pdo->prepare("
            UPDATE combatants 
               SET bm = ?, current_range = ?
             WHERE id = ?
        ")->execute([$actor['bm'], $range, $combatantId]);
        $log = "Moved to {$range}, spent 1 BM";
        break;

    default:
        http_response_code(400);
        exit(json_encode(['error'=>'Unknown action type']));
}

// 6) Fetch updated combatant state
$uStmt = $pdo->prepare("
    SELECT * 
      FROM combatants 
     WHERE id = ? AND combat_id = ?
");
$uStmt->execute([$combatantId, $combatId]);
$updated = $uStmt->fetch(PDO::FETCH_ASSOC);

// 7) Return result
echo json_encode([
    'log'        => $log,
    'actor'      => $updated,
    'luck_left'  => $actor['current_luck'] ?? 0,
]);

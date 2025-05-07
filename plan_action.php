<?php
// api/plan_action.php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
require __DIR__ . '/../db.php';
require __DIR__ . '/../lib/spend_resources.php';
session_start();

// 1) Authentication
$userId = $_SESSION['user_id'] ?? null;
if (! $userId) {
    http_response_code(401);
    exit(json_encode(['error' => 'Not logged in']));
}

// 2) Decode & validate JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (
    ! isset($input['combat_id'], $input['combatant_id'], $input['free_action'])
    || ! is_int($input['combat_id'])
    || ! is_int($input['combatant_id'])
    || ! is_string($input['free_action'])
    || ! isset($input['bm_actions'], $input['bm_spent'])
    || ! is_array($input['bm_actions'])
    || ! is_int($input['bm_spent'])
    || ! isset($input['luck_spent'])
    || ! is_int($input['luck_spent'])
) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid input']));
}

$combatId     = $input['combat_id'];
$combatantId  = $input['combatant_id'];
$freeAction   = trim($input['free_action']);
$bmActions    = $input['bm_actions'];
$bmSpent      = $input['bm_spent'];
$luckSpent    = $input['luck_spent'];

if ($bmSpent < 0 || $luckSpent < 0) {
    http_response_code(400);
    exit(json_encode(['error' => 'Cannot spend negative resources']));
}

// 3) Load the combatant and verify ownership
$stmt = $pdo->prepare("
    SELECT cmt.bm, cmt.luck, ch.user_id
      FROM combatants AS cmt
      LEFT JOIN characters AS ch ON cmt.character_id = ch.id
     WHERE cmt.id = :cmt_id
       AND cmt.combat_id = :combat_id
");
$stmt->execute([
    ':cmt_id'     => $combatantId,
    ':combat_id'  => $combatId
]);
$combatant = $stmt->fetch(PDO::FETCH_ASSOC);

if (! $combatant) {
    http_response_code(404);
    exit(json_encode(['error' => 'Combatant not found']));
}

if ($combatant['user_id'] !== null && (int)$combatant['user_id'] !== $userId) {
    http_response_code(403);
    exit(json_encode(['error' => 'Not your character']));
}

// 4) Spend resources
try {
    spend_resources($pdo, $combatantId, [
        'bm'   => $bmSpent,
        'luck' => $luckSpent,
    ]);
} catch (Exception $e) {
    http_response_code(400);
    exit(json_encode(['error' => $e->getMessage()]));
}

// 5) Insert or update the planned action
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO planned_actions
          (combat_id, combatant_id, free_action, bm_actions, bm_spent, luck_spent)
        VALUES
          (:combat_id, :combatant_id, :free_action, :bm_actions, :bm_spent, :luck_spent)
        ON DUPLICATE KEY UPDATE
          free_action = VALUES(free_action),
          bm_actions  = VALUES(bm_actions),
          bm_spent    = VALUES(bm_spent),
          luck_spent  = VALUES(luck_spent),
          committed_at = NOW()
    ");

    $stmt->execute([
        ':combat_id'     => $combatId,
        ':combatant_id'  => $combatantId,
        ':free_action'   => $freeAction,
        ':bm_actions'    => json_encode($bmActions),
        ':bm_spent'      => $bmSpent,
        ':luck_spent'    => $luckSpent,
    ]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to plan action',
        'details' => $e->getMessage()
    ]);
}

<?php
// lib/spend_resources.php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/validate_resources.php';

/**
 * Spend resources from a combatant and update the database.
 *
 * @param PDO $pdo
 * @param int $combatantId
 * @param array $costs Example: ['bm' => 2, 'luck' => 1]
 * @return bool True on success, throws exception on failure
 */
function spend_resources(PDO $pdo, int $combatantId, array $costs): bool
{
    // Load current resources
    $stmt = $pdo->prepare("SELECT bm, luck FROM combatants WHERE id = :id");
    $stmt->execute([':id' => $combatantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (! $row) {
        throw new RuntimeException("Combatant not found");
    }

    // Validate before deducting
    validate_resources([
        'bm'   => (int)($row['bm'] ?? 0),
        'luck' => (int)($row['luck'] ?? 0)
    ], $costs);

    // Prepare dynamic SQL
    $setClauses = [];
    $params = [':id' => $combatantId];

    foreach ($costs as $resource => $amount) {
        if ($amount > 0) {
            $setClauses[] = "$resource = $resource - :$resource";
            $params[":$resource"] = $amount;
        }
    }

    if (empty($setClauses)) {
        return true; // Nothing to deduct
    }

    $sql = "UPDATE combatants SET " . implode(", ", $setClauses) . " WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

<?php
// api/reset_tokens.php

declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require __DIR__ . '/../db.php';

// Simple shared‐secret auth to prevent public abuse.
// Replace this with a secure key or integrate with your admin auth.
$secret = $_GET['key'] ?? '';
if ($secret !== 'YOUR_RESET_SECRET') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Reset every character’s remaining tokens back to their max
    $rows = $pdo->exec("
        UPDATE characters
           SET adventure_tokens_left = adventure_tokens_max
    ");

    echo json_encode([
        'message'       => 'Adventure tokens reset for all characters.',
        'rows_updated'  => $rows
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to reset tokens',
        'details' => $e->getMessage()
    ]);
}

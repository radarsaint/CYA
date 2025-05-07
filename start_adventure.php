<?php
// api/start_adventure.php

declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require __DIR__ . '/../db.php';
session_start();

// 1) Authenticate
$userId = $_SESSION['user_id'] ?? null;
if (! $userId) {
    http_response_code(401);
    exit(json_encode(['error' => 'Not logged in']));
}

// 2) Load remaining tokens
$stmt = $pdo->prepare("
    SELECT adventure_tokens_left
      FROM characters
     WHERE user_id = ?
");
$stmt->execute([$userId]);
$tokensLeft = $stmt->fetchColumn();

if ($tokensLeft === false) {
    http_response_code(404);
    exit(json_encode(['error' => 'Character not found']));
}

if ((int)$tokensLeft < 1) {
    http_response_code(400);
    exit(json_encode(['error' => 'No adventure tokens left']));
}

// 3) Decrement token
$update = $pdo->prepare("
    UPDATE characters
       SET adventure_tokens_left = adventure_tokens_left - 1
     WHERE user_id = ?
");
$update->execute([$userId]);

// 4) Return new token count
echo json_encode([
    'adventure_tokens_left' => (int)$tokensLeft - 1
]);

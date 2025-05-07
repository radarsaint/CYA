<?php
// api/config.php

declare(strict_types=1);

// Always return JSON
header('Content-Type: application/json; charset=UTF-8');

// Whitelist every config “type” your front‑end may request
$allowed = [
    'races',
    'wellsprings',
    'focusTypes',
    'corporea',
    'essentia',
    'socialdisposition',
    'battledisposition',
    'awakeningstory',
    'saga',
    'masks',
    'mainstats',
    'npc_tags',
    'corePrinciples',     // newly added
    'tokens',             // newly added
];

$type = $_GET['type'] ?? '';

if (! in_array($type, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid config type']);
    exit;
}

$file = __DIR__ . "/data/{$type}.json";

if (! is_file($file) || ! is_readable($file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Config not found']);
    exit;
}

echo file_get_contents($file);

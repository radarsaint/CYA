<?php
// api/config.php

// Always return JSON
header('Content-Type: application/json; charset=UTF-8');

// Which endpoint was requested? e.g. /api/config.php?type=races
$type = $_GET['type'] ?? null;

// Whitelist of allowed config types
$allowed = ['races','wellsprings','focusTypes','foci','dispositions'];

if (!in_array($type, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid config type']);
  exit;
}

// Map type → JSON file in api/data/
$file = __DIR__ . "/data/{$type}.json";
if (!file_exists($file)) {
  http_response_code(404);
  echo json_encode(['error' => 'Not found']);
  exit;
}

// Serve the JSON
echo file_get_contents($file);

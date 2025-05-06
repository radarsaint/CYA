<?php
// api/config.php

// Always return JSON
header('Content-Type: application/json; charset=UTF-8');

// Grab the `type` query‑param (e.g. ?type=races)
$type = $_GET['type'] ?? '';

// Whitelist every config “type” your front end may request
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
  'npc_tags'
];

if (! in_array($type, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid config type']);
  exit;
}

// Build the path to data/{type}.json
$file = __DIR__ . "/data/{$type}.json";

if (! file_exists($file)) {
  http_response_code(404);
  echo json_encode(['error' => 'Not found']);
  exit;
}

// Return the raw JSON
echo file_get_contents($file);

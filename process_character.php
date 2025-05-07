<?php
// process_character.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
session_start();

// 1) Must be logged in
if (empty($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// 2) Collect & validate POST
$required = [
    'name','race','wellspring','focus_type','focus',
    'social_disposition','battle_disposition','awakening_story',
    'luck','speed','endurance',
    'saga','core_principle','mask'
];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        die("Error: missing “{$field}”");
    }
}

// sanitize / cast
$name               = trim($_POST['name']);
$race               = trim($_POST['race']);
$wellspring         = trim($_POST['wellspring']);
$focus_type         = trim($_POST['focus_type']);
$focus              = trim($_POST['focus']);
$social_disposition = trim($_POST['social_disposition']);
$battle_disposition = trim($_POST['battle_disposition']);
$awakening_story    = trim($_POST['awakening_story']);
$luck               = (int) $_POST['luck'];
$speed              = (int) $_POST['speed'];
$endurance          = (int) $_POST['endurance'];
$saga               = trim($_POST['saga']);
$core_principle     = trim($_POST['core_principle']);
$mask               = trim($_POST['mask']);

// 3) Prevent duplicate
$stmt = $pdo->prepare("SELECT COUNT(*) FROM characters WHERE user_id = ?");
$stmt->execute([ $_SESSION['user_id'] ]);
if ($stmt->fetchColumn() > 0) {
    die("You already have a character.");
}

// 4) Insert into DB
$sql = "
INSERT INTO characters (
    user_id,
    name, race, wellspring,
    focus_type, focus,
    social_disposition, battle_disposition,
    awakening_story,
    luck, current_luck,
    speed,
    endurance, current_endurance,
    level,
    golden_dollars,
    adventure_tokens_max,
    adventure_tokens_left,
    saga, core_principle, mask
) VALUES (
    :user_id,
    :name, :race, :wellspring,
    :focus_type, :focus,
    :social_disposition, :battle_disposition,
    :awakening_story,
    :luck, :current_luck,
    :speed,
    :endurance, :current_endurance,
    :level,
    :golden_dollars,
    :adventure_tokens_max,
    :adventure_tokens_left,
    :saga, :core_principle, :mask
)
";

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        ':user_id'                => $_SESSION['user_id'],
        ':name'                   => $name,
        ':race'                   => $race,
        ':wellspring'             => $wellspring,
        ':focus_type'             => $focus_type,
        ':focus'                  => $focus,
        ':social_disposition'     => $social_disposition,
        ':battle_disposition'     => $battle_disposition,
        ':awakening_story'        => $awakening_story,
        ':luck'                   => $luck,
        ':current_luck'           => $luck,
        ':speed'                  => $speed,
        ':endurance'              => $endurance,
        ':current_endurance'      => $endurance,
        ':level'                  => 1,
        ':golden_dollars'         => 0,
        ':adventure_tokens_max'   => 3,
        ':adventure_tokens_left'  => 3,
        ':saga'                   => $saga,
        ':core_principle'         => $core_principle,
        ':mask'                   => $mask,
    ]);
} catch (Exception $e) {
    // In production, log $e->getMessage()
    die("Database error: " . $e->getMessage());
}

// 5) Success → dashboard
header('Location: dashboard.php');
exit;

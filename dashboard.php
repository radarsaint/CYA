<?php
// dashboard.php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require 'db.php';
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// fetch their character
$stmt = $pdo->prepare("
  SELECT * FROM characters
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([ $_SESSION['user_id'] ]);
$char = $stmt->fetch(PDO::FETCH_ASSOC);

if (! $char) {
    // no character yet — send them back to creation wizard
    header('Location: character.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Your Legend</title>
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
  <h1>Welcome, <?= htmlspecialchars($char['name']) ?></h1>
  <table>
    <tr><th>Race</th><td><?= htmlspecialchars($char['race']) ?></td></tr>
    <tr><th>Wellspring</th><td><?= htmlspecialchars($char['wellspring']) ?></td></tr>
    <tr><th>Focus</th>
      <td>
        <?= htmlspecialchars($char['focus_type']) ?> →
        <?= htmlspecialchars($char['focus']) ?>
      </td>
    </tr>
    <tr><th>Social Disposition</th>
        <td><?= htmlspecialchars($char['social_disposition']) ?></td></tr>
    <tr><th>Battle Disposition</th>
        <td><?= htmlspecialchars($char['battle_disposition']) ?></td></tr>
    <tr><th>Awakening Story</th>
        <td><?= htmlspecialchars($char['awakening_story']) ?></td></tr>
    <tr><th>Stats</th>
      <td>
        Luck: <?= $char['luck'] ?>,
        Speed: <?= $char['speed'] ?>,
        Endurance: <?= $char['endurance'] ?>
      </td>
    </tr>
    <tr><th>Saga</th><td><?= htmlspecialchars($char['saga']) ?></td></tr>
    <tr><th>Mask</th><td><?= htmlspecialchars($char['mask']) ?></td></tr>
  </table>

  <p>
    <a href="start_adventure.php">Begin Your First Adventure →</a>
  </p>
</body>
</html>

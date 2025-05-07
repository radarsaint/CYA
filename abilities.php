<?php
// filepath: c:\xampp\htdocs\cya\api\abilities.php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
require __DIR__ . '/../db.php';

// 1) Gather & validate inputs
$well  = $_GET['source_name'] ?? '';
$focus = $_GET['focus_name']  ?? '';
$level = max(1, (int)($_GET['level'] ?? 1));

// Valid names from your config.json files
$validWells  = ['Arcane','Divine','Valor','Eldritch Shadow'];
$validFoci   = ['Guts','Stride','Atlas','Maul','Icarus','Virtuoso',
                'Resonance','Logic','Rhetoric','Nobility','Heartflame','Philosophy'];

if (! in_array($well, $validWells, true) ||
    ! in_array($focus, $validFoci,  true)) {
    http_response_code(400);
    exit(json_encode(['error'=>'Invalid wellspring or focus name']));
}

// 2) Base SQL template
$sql = "
  SELECT
    id,
    label,
    level_required,
    cost_bm,
    effect_type,
    effect_params,
    description
  FROM ability_definitions
  WHERE source_type   = :src_type
    AND source_name   = :src_name
    AND level_required <= :level
    AND MOD(level_required, :mod) = 0
  ORDER BY level_required
";

// 3) Fetch Wellspring (odd levels → mod=1)
$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':src_type'  => 'wellspring',
  ':src_name'  => $well,
  ':level'     => $level,
  ':mod'       => 1
]);
$wellspringAbilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Fetch Focus (even levels → mod=0)
$stmt->execute([
  ':src_type'  => 'focus',
  ':src_name'  => $focus,
  ':level'     => $level,
  ':mod'       => 0
]);
$focusAbilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5) Output JSON
echo json_encode([
  'wellspring' => $wellspringAbilities,
  'focus'      => $focusAbilities
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

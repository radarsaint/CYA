#!/usr/bin/env bash
set -e

DB_USER="root"
DB_NAME="arcania"
DB_HOST="127.0.0.1"

echo "1) Applying SQL migration…"
mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME" <<SQL
ALTER TABLE planned_actions
  ADD COLUMN luck_spent TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER bm_spent;
SQL
echo "→ Migration applied."

echo "2) Patching api/plan_action.php…"
patch -p0 <<'PATCH'
*** plan_action.php.old	2024-05-06 12:00:00.000000000 +0000
--- plan_action.php	2024-05-06 12:00:00.000000000 +0000
@@
-if (!isset($data['combat_id'], $data['combatant_id'], $data['free_action'], $data['bm_actions'], $data['bm_spent'])) {
+if (!isset($data['combat_id'], $data['combatant_id'], $data['free_action'], $data['bm_actions'], $data['bm_spent'], $data['luck_spent'])) {
     echo json_encode(['error' => 'Invalid input']);
     exit;
 }
PATCH
echo "→ PHP patch applied."

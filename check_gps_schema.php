<?php
require 'config.php';
echo "--- project_stage_updates ---\n";
$res = $pdo->query('DESCRIBE project_stage_updates');
while($row = $res->fetch()) echo $row['Field'] . "\n";

echo "\n--- site_reports ---\n";
$res = $pdo->query('DESCRIBE site_reports');
while($row = $res->fetch()) echo $row['Field'] . "\n";
?>

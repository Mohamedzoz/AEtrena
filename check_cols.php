<?php
require_once 'config.php';
$stmt = $pdo->query("DESCRIBE project_stage_updates");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

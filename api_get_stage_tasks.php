<?php
require_once 'config.php';
requireLogin();

$stage = $_GET['stage'] ?? 1;

$stmt = $pdo->prepare("SELECT task_name FROM stage_reference_tasks WHERE stage = ?");
$stmt->execute([$stage]);
$tasks = $stmt->fetchAll(PDO::FETCH_COLUMN);

header('Content-Type: application/json');
echo json_encode($tasks);

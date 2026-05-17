<?php
require_once 'config.php';
header('Content-Type: text/plain');

try {
    $stmt = $pdo->query("SELECT 1");
    echo "Connection Successful\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    
    if (in_array('notifications', $tables)) {
        $cols = $pdo->query("DESCRIBE notifications")->fetchAll(PDO::FETCH_COLUMN);
        echo "Notifications Columns: " . implode(', ', $cols) . "\n";
    } else {
        echo "Notifications table NOT found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

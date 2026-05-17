<?php
require 'config.php';

function addColumnIfNotExists($pdo, $table, $column, $definition) {
    try {
        $pdo->query("SELECT $column FROM $table LIMIT 1");
    } catch (PDOException $e) {
        $pdo->query("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "Added $column to $table\n";
    }
}

addColumnIfNotExists($pdo, 'project_stage_updates', 'latitude', 'varchar(50)');
addColumnIfNotExists($pdo, 'project_stage_updates', 'longitude', 'varchar(50)');
addColumnIfNotExists($pdo, 'site_reports', 'latitude', 'varchar(50)');
addColumnIfNotExists($pdo, 'site_reports', 'longitude', 'varchar(50)');

echo "Migration finished.\n";
?>

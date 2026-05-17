<?php
require_once 'config.php';
requireLogin();

// Admin only
if (!isAdmin()) {
    header("Location: settings.php");
    exit;
}

// ── Helpers ────────────────────────────────────────────────

/**
 * Recursively collect all files under $dir, skipping $skip dirs.
 */
function collectFiles(string $dir, array $skip = []): array {
    $files = [];
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        if ($item->isFile()) {
            $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($dir) + 1));
            // Skip backup ZIPs and temp files
            foreach ($skip as $s) {
                if (strpos($rel, $s) === 0) continue 2;
            }
            $files[] = $item->getPathname();
        }
    }
    return $files;
}

/**
 * Generate a full SQL dump (CREATE TABLE + INSERT) using PDO.
 */
function generateSqlDump(PDO $pdo): string {
    $sql  = "-- Aeterna ERP — Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- ----------------------------------------\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // DROP + CREATE
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $createSql = array_values($create)[1]; // second column
        $sql .= "-- Table: `$table`\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $createSql . ";\n\n";

        // Data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
            $sql .= "INSERT INTO `$table` ($cols) VALUES\n";
            $valueLines = [];
            foreach ($rows as $row) {
                $vals = array_map(function($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    return $pdo->quote($v);
                }, array_values($row));
                $valueLines[] = '(' . implode(', ', $vals) . ')';
            }
            $sql .= implode(",\n", $valueLines) . ";\n\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

// ── Build ZIP ─────────────────────────────────────────────

if (!class_exists('ZipArchive')) {
    die('<div style="font-family:sans-serif;direction:rtl;padding:40px;color:red;text-align:center;">
        <h2>⚠️ خطأ في النظام</h2>
        <p>الـ ZipArchive extension غير مفعّل في PHP. يرجى تفعيله من php.ini</p>
        <a href="settings.php" style="color:#555">← العودة للإعدادات</a>
    </div>');
}

$projectRoot = realpath(__DIR__);
$timestamp   = date('Y-m-d_H-i-s');
$zipName     = "aeterna_backup_{$timestamp}.zip";
$zipTmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

$zip = new ZipArchive();
if ($zip->open($zipTmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die('فشل في إنشاء ملف النسخة الاحتياطية.');
}

// Add all project files (skip backup ZIPs in root if any)
$allFiles = collectFiles($projectRoot, ['aeterna_backup_']);

foreach ($allFiles as $filePath) {
    $relativePath = 'files/' . str_replace('\\', '/', substr($filePath, strlen($projectRoot) + 1));
    $zip->addFile($filePath, $relativePath);
}

// Add SQL dump
$sqlDump = generateSqlDump($pdo);
$zip->addFromString('database/aeterna_backup_' . $timestamp . '.sql', $sqlDump);

// Add a README
$readme  = "Aeterna ERP — Full Backup\n";
$readme .= "========================\n";
$readme .= "Date    : " . date('Y-m-d H:i:s') . "\n";
$readme .= "Files   : " . count($allFiles) . " files backed up\n\n";
$readme .= "Contents:\n";
$readme .= "  /files/     → All PHP, SQL, CSS, JS, and upload files\n";
$readme .= "  /database/  → MySQL dump (SQL file)\n\n";
$readme .= "To restore:\n";
$readme .= "  1. Upload the /files/ contents to your server root.\n";
$readme .= "  2. Import the SQL file via phpMyAdmin or mysql CLI.\n";
$zip->addFromString('README.txt', $readme);

$zip->close();

// ── Log the action ────────────────────────────────────────
logActivity('نسخة احتياطية', "تم إنشاء نسخة احتياطية كاملة: $zipName (" . count($allFiles) . " ملف)");

// ── Stream ZIP as download ────────────────────────────────
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($zipTmpPath));
header('Pragma: no-cache');
header('Expires: 0');

readfile($zipTmpPath);
@unlink($zipTmpPath); // Clean up temp file
exit;

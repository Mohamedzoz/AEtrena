<?php
// Aeterna DB Sync - Master Version
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "Aeterna Master Auth Sync - Starting...\n";
echo "====================================\n\n";

$roles_data = json_decode('[{"id":1,"name":"Admin","description":null,"created_at":"2026-05-11 01:58:29"},{"id":2,"name":"Manager","description":null,"created_at":"2026-05-11 01:58:29"},{"id":3,"name":"Accountant","description":null,"created_at":"2026-05-11 01:58:29"},{"id":4,"name":"Secretary","description":null,"created_at":"2026-05-11 01:58:29"},{"id":5,"name":"Senior Design","description":null,"created_at":"2026-05-11 01:58:29"},{"id":6,"name":"Junior Design","description":null,"created_at":"2026-05-11 01:58:29"},{"id":7,"name":"Site Manager","description":null,"created_at":"2026-05-11 01:58:29"},{"id":8,"name":"Site Engineer","description":null,"created_at":"2026-05-11 01:58:29"}]', true);
$perms_data = json_decode('[{"id":1,"name":"Finance View","slug":"view_finance","module":"Finance","created_at":"2026-05-11 01:58:28"},{"id":2,"name":"Manage Revenues","slug":"manage_revenues","module":"Finance","created_at":"2026-05-11 01:58:28"},{"id":3,"name":"Manage Expenses","slug":"manage_expenses","module":"Finance","created_at":"2026-05-11 01:58:28"},{"id":4,"name":"Manage Categories","slug":"manage_categories","module":"Finance","created_at":"2026-05-11 01:58:28"},{"id":5,"name":"View HR Admin","slug":"view_hr_admin","module":"HR","created_at":"2026-05-11 01:58:28"},{"id":6,"name":"Manage Attendance","slug":"manage_attendance","module":"HR","created_at":"2026-05-11 01:58:28"},{"id":7,"name":"Manage Leaves","slug":"manage_leaves","module":"HR","created_at":"2026-05-11 01:58:28"},{"id":8,"name":"View Reports","slug":"view_reports","module":"Reports","created_at":"2026-05-11 01:58:28"},{"id":9,"name":"Manage Projects","slug":"manage_projects","module":"Projects","created_at":"2026-05-11 01:58:28"},{"id":10,"name":"Create Project","slug":"create_project","module":"Projects","created_at":"2026-05-11 01:58:28"},{"id":11,"name":"Delete Project","slug":"delete_project","module":"Projects","created_at":"2026-05-11 01:58:29"},{"id":12,"name":"Manage Users","slug":"manage_users","module":"Administration","created_at":"2026-05-11 01:58:29"},{"id":13,"name":"View Logs","slug":"view_logs","module":"Administration","created_at":"2026-05-11 01:58:29"},{"id":14,"name":"View Trash","slug":"view_trash","module":"Administration","created_at":"2026-05-11 01:58:29"},{"id":43,"name":"رؤية الميزانية المرصودة","slug":"view_budgets","module":"المشاريع","created_at":"2026-05-11 04:31:24"}]', true);
$matrix_data = json_decode('[{"role_name":"Accountant","permission_slug":"view_finance"},{"role_name":"Accountant","permission_slug":"manage_revenues"},{"role_name":"Accountant","permission_slug":"manage_expenses"},{"role_name":"Accountant","permission_slug":"manage_categories"},{"role_name":"Accountant","permission_slug":"view_budgets"},{"role_name":"Admin","permission_slug":"view_finance"},{"role_name":"Admin","permission_slug":"manage_revenues"},{"role_name":"Admin","permission_slug":"manage_expenses"},{"role_name":"Admin","permission_slug":"manage_categories"},{"role_name":"Admin","permission_slug":"view_hr_admin"},{"role_name":"Admin","permission_slug":"manage_attendance"},{"role_name":"Admin","permission_slug":"manage_leaves"},{"role_name":"Admin","permission_slug":"view_reports"},{"role_name":"Admin","permission_slug":"manage_projects"},{"role_name":"Admin","permission_slug":"create_project"},{"role_name":"Admin","permission_slug":"delete_project"},{"role_name":"Admin","permission_slug":"manage_users"},{"role_name":"Admin","permission_slug":"view_logs"},{"role_name":"Admin","permission_slug":"view_trash"},{"role_name":"Admin","permission_slug":"view_budgets"},{"role_name":"Junior Design","permission_slug":"manage_projects"},{"role_name":"Manager","permission_slug":"view_finance"},{"role_name":"Manager","permission_slug":"manage_revenues"},{"role_name":"Manager","permission_slug":"manage_expenses"},{"role_name":"Manager","permission_slug":"manage_categories"},{"role_name":"Manager","permission_slug":"view_hr_admin"},{"role_name":"Manager","permission_slug":"manage_attendance"},{"role_name":"Manager","permission_slug":"manage_leaves"},{"role_name":"Manager","permission_slug":"view_reports"},{"role_name":"Manager","permission_slug":"manage_projects"},{"role_name":"Manager","permission_slug":"create_project"},{"role_name":"Manager","permission_slug":"delete_project"},{"role_name":"Manager","permission_slug":"manage_users"},{"role_name":"Manager","permission_slug":"view_logs"},{"role_name":"Manager","permission_slug":"view_trash"},{"role_name":"Manager","permission_slug":"view_budgets"},{"role_name":"Secretary","permission_slug":"manage_revenues"},{"role_name":"Secretary","permission_slug":"view_hr_admin"},{"role_name":"Secretary","permission_slug":"manage_attendance"},{"role_name":"Secretary","permission_slug":"manage_leaves"},{"role_name":"Secretary","permission_slug":"manage_projects"},{"role_name":"Secretary","permission_slug":"create_project"},{"role_name":"Senior Design","permission_slug":"manage_projects"},{"role_name":"Site Engineer","permission_slug":"view_reports"},{"role_name":"Site Engineer","permission_slug":"manage_projects"},{"role_name":"Site Manager","permission_slug":"manage_attendance"},{"role_name":"Site Manager","permission_slug":"view_reports"},{"role_name":"Site Manager","permission_slug":"manage_projects"}]', true);

try {
    // 1. Sync Table Schema
    echo "1. Syncing Schema...\n";
    
    $cols_tasks = $pdo->query("DESCRIBE project_tasks")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('assigned_to', $cols_tasks)) {
        $pdo->exec("ALTER TABLE project_tasks ADD COLUMN assigned_to INT NULL");
        echo "[SUCCESS] Added assigned_to to project_tasks\n";
    }

    $cols_cust = $pdo->query("DESCRIBE customers")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $cols_cust)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN email VARCHAR(255) NULL");
        echo "[SUCCESS] Added email to customers\n";
    }
    if (!in_array('social_media', $cols_cust)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN social_media TEXT NULL");
        echo "[SUCCESS] Added social_media to customers\n";
    }
    if (!in_array('username', $cols_cust)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN username VARCHAR(50) UNIQUE NULL");
        echo "[SUCCESS] Added username to customers\n";
    }
    if (!in_array('password_hash', $cols_cust)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN password_hash VARCHAR(255) NULL");
        echo "[SUCCESS] Added password_hash to customers\n";
    }

    $cols_users = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('last_notif_read_at', $cols_users)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_notif_read_at TIMESTAMP NULL");
        echo "[SUCCESS] Added last_notif_read_at to users\n";
    }

    // New: Notifications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT NULL,
        type VARCHAR(50) DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "[SUCCESS] Notifications table synchronized\n";

    // New: Project Stage History Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_stage_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        stage_num INT NOT NULL,
        deadline_at DATE DEFAULT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "[SUCCESS] Project Stage History table synchronized\n";

    // Ensure projects table has stage_deadline
    $cols_proj = $pdo->query("DESCRIBE projects")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('stage_deadline', $cols_proj)) {
        $pdo->exec("ALTER TABLE projects ADD COLUMN stage_deadline DATE NULL AFTER stage");
        echo "[SUCCESS] Added stage_deadline to projects\n";
    }

    // New: Project Stage Updates Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_stage_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        stage INT NOT NULL,
        notes TEXT DEFAULT NULL,
        file_path VARCHAR(255) DEFAULT NULL,
        latitude DECIMAL(10,8) DEFAULT NULL,
        longitude DECIMAL(11,8) DEFAULT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "[SUCCESS] Project Stage Updates table synchronized\n";
    
    // New: Project Site Modifications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_site_modifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        description TEXT NOT NULL,
        assigned_to INT DEFAULT NULL,
        designer_id INT DEFAULT NULL,
        engineer_id INT DEFAULT NULL,
        engineer_report TEXT DEFAULT NULL,
        status ENUM('Pending', 'Completed') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "[SUCCESS] Project Site Modifications table synchronized\n";

    $cols_psu = $pdo->query("DESCRIBE project_stage_updates")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('latitude', $cols_psu)) {
        $pdo->exec("ALTER TABLE project_stage_updates ADD COLUMN latitude DECIMAL(10,8) NULL AFTER file_path");
        $pdo->exec("ALTER TABLE project_stage_updates ADD COLUMN longitude DECIMAL(11,8) NULL AFTER latitude");
        echo "[SUCCESS] Added GPS columns to project_stage_updates\n";
    }

    $cols_notif = $pdo->query("DESCRIBE notifications")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('is_toast_shown', $cols_notif)) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN is_toast_shown TINYINT(1) DEFAULT 0 AFTER is_read");
        echo "[SUCCESS] Added is_toast_shown to notifications\n";
    }

    // 2. Sync Roles
    echo "\n2. Syncing Roles...\n";
    foreach ($roles_data as $role) {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$role['name']]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
            $stmt->execute([$role['name'], $role['description'] ?? '']);
            echo "[SUCCESS] Added Role: {$role['name']}\n";
        }
    }

    // 3. Sync Permissions
    echo "\n3. Syncing Permissions...\n";
    foreach ($perms_data as $perm) {
        $stmt = $pdo->prepare("SELECT id FROM permissions WHERE slug = ?");
        $stmt->execute([$perm['slug']]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO permissions (module, slug, name) VALUES (?, ?, ?)");
            $stmt->execute([$perm['module'], $perm['slug'], $perm['name']]);
            echo "[SUCCESS] Added Permission: {$perm['slug']}\n";
        }
    }

    // 4. Sync Role-Permission Matrix (The magic part)
    echo "\n4. Synchronizing Authorization Matrix...\n";
    // We clear and rebuild to ensure it matches EXACTLY
    $pdo->exec("DELETE FROM role_permissions");
    foreach ($matrix_data as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO role_permissions (role_id, permission_id)
            SELECT r.id, p.id
            FROM roles r, permissions p
            WHERE r.name = ? AND p.slug = ?
        ");
        $stmt->execute([$item['role_name'], $item['permission_slug']]);
    }
    echo "[SUCCESS] Matrix synchronized (" . count($matrix_data) . " entries)\n";

    // 5. Add Performance Indexes
    echo "\n5. Adding Performance Indexes...\n";
    $indexes = [
        ['projects', 'customer_id'],
        ['payments', 'project_id'],
        ['project_tasks', 'project_id'],
        ['system_settings', 'setting_key']
    ];
    foreach ($indexes as $idx) {
        try {
            $pdo->exec("CREATE INDEX idx_{$idx[0]}_{$idx[1]} ON {$idx[0]}({$idx[1]})");
            echo "[SUCCESS] Indexed {$idx[0]}.{$idx[1]}\n";
        } catch (Exception $e) { /* Probably exists */ }
    }

    echo "\nSync Completed Successfully! Please delete this file now.";

} catch (Exception $e) {
    echo "\n[CRITICAL ERROR] " . $e->getMessage();
}
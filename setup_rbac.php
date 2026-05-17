<?php
require_once 'config.php';

// Only admins can run this
if (!isAdmin()) {
    die("Access Denied. Only Administrators can run this setup script.");
}

try {
// Removed transaction because DDL statements in MySQL cause implicit commits
// $pdo->beginTransaction();

    // 1. Create Roles Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 2. Create Permissions Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(50) NOT NULL UNIQUE,
        module VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 3. Create Role-Permissions Pivot Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        PRIMARY KEY (role_id, permission_id),
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 4. Update Users Table (Add role_id)
    $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role_id'");
    if (!$checkColumn->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role_id INT DEFAULT NULL AFTER role");
        $pdo->exec("ALTER TABLE users ADD FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL");
    }

    // 5. Insert Default Permissions
    $permissions = [
        ['Finance View', 'view_finance', 'Finance'],
        ['Manage Revenues', 'manage_revenues', 'Finance'],
        ['Manage Expenses', 'manage_expenses', 'Finance'],
        ['Manage Categories', 'manage_categories', 'Finance'],
        ['View HR Admin', 'view_hr_admin', 'HR'],
        ['Manage Attendance', 'manage_attendance', 'HR'],
        ['Manage Leaves', 'manage_leaves', 'HR'],
        ['View Reports', 'view_reports', 'Reports'],
        ['Manage Projects', 'manage_projects', 'Projects'],
        ['Create Project', 'create_project', 'Projects'],
        ['Delete Project', 'delete_project', 'Projects'],
        ['Manage Users', 'manage_users', 'Administration'],
        ['View Logs', 'view_logs', 'Administration'],
        ['View Trash', 'view_trash', 'Administration'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (name, slug, module) VALUES (?, ?, ?)");
    foreach ($permissions as $p) {
        $stmt->execute($p);
    }

    // 6. Insert Default Roles & Link Permissions
    // Based on config.php matrix
    $roles_matrix = [
        'Admin' => ['*'],
        'Manager' => ['view_finance', 'manage_revenues', 'manage_expenses', 'manage_categories', 'view_hr_admin', 'manage_attendance', 'manage_leaves', 'view_reports', 'manage_projects', 'create_project', 'delete_project', 'manage_users', 'view_logs', 'view_trash'],
        'Accountant' => ['view_finance', 'manage_revenues', 'manage_expenses', 'manage_categories'],
        'Secretary' => ['manage_projects', 'create_project', 'manage_revenues', 'view_hr_admin', 'manage_attendance', 'manage_leaves'],
        'Senior Design' => ['manage_projects'],
        'Junior Design' => ['manage_projects'],
        'Site Manager' => ['manage_projects', 'view_reports', 'view_hr_admin', 'manage_attendance'],
        'Site Engineer' => ['manage_projects', 'view_reports']
    ];

    $all_perms = $pdo->query("SELECT id, slug FROM permissions")->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($roles_matrix as $role_name => $perms_slugs) {
        // Insert role
        $stmt = $pdo->prepare("INSERT IGNORE INTO roles (name) VALUES (?)");
        $stmt->execute([$role_name]);
        
        // Get role ID
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$role_name]);
        $role_id = $stmt->fetchColumn();

        // Link permissions
        if ($perms_slugs[0] === '*') {
            // Admin gets everything
            $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($all_perms as $p_id => $slug) {
                $stmt->execute([$role_id, $p_id]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($perms_slugs as $slug) {
                if ($p_id = array_search($slug, $all_perms)) {
                    $stmt->execute([$role_id, $p_id]);
                }
            }
        }

        // 7. Update existing users to this role_id
        $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE role = ?");
        $stmt->execute([$role_id, $role_name]);
    }

    // $pdo->commit();
    echo "<div style='font-family: sans-serif; padding: 20px; color: green;'>
            <h2>✅ RBAC Setup Completed Successfully!</h2>
            <p>1. Tables created.</p>
            <p>2. Default permissions inserted.</p>
            <p>3. Default roles created and linked.</p>
            <p>4. Existing users mapped to new roles.</p>
            <a href='dashboard.php'>Go to Dashboard</a>
          </div>";

} catch (Exception $e) {
    echo "<div style='font-family: sans-serif; padding: 20px; color: red;'>
            <h2>❌ Error during setup</h2>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p>File: " . $e->getFile() . " on line " . $e->getLine() . "</p>
            <a href='setup_rbac.php'>Try Again</a>
          </div>";
    die();
}

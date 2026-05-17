<?php
require_once 'config.php';
requireLogin();

// Only Admins can manage roles
if (!isAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$message = '';

// ─── Handle Post Actions ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'save_role') {
                $role_id = $_POST['role_id'] ?? null;
                $role_name = $_POST['role_name'];
                $permissions = $_POST['perms'] ?? [];

                if ($role_id) {
                    // Update existing role
                    $stmt = $pdo->prepare("UPDATE roles SET name = ? WHERE id = ?");
                    $stmt->execute([$role_name, $role_id]);
                } else {
                    // Create new role
                    $stmt = $pdo->prepare("INSERT INTO roles (name) VALUES (?)");
                    $stmt->execute([$role_name]);
                    $role_id = $pdo->lastInsertId();
                }

                // Update permissions
                $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$role_id]);
                $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissions as $p_id) {
                    $stmt->execute([$role_id, $p_id]);
                }

                $message = "تم حفظ الدور والصلاحيات بنجاح!";
                logActivity("تحديث الأدوار", "قام بتعديل صلاحيات الدور: $role_name");
            } elseif ($_POST['action'] === 'delete_role') {
                $role_id = $_POST['role_id'];
                // Check if role is used by users
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
                $stmt->execute([$role_id]);
                if ($stmt->fetchColumn() > 0) {
                    $message = "Error: لا يمكن حذف دور مرتبط بموظفين نشطين.";
                } else {
                    $pdo->prepare("DELETE FROM roles WHERE id = ?")->execute([$role_id]);
                    $message = "تم حذف الدور بنجاح.";
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// ─── Fetch Data ─────────────────────────────────────
$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
$permissions = $pdo->query("SELECT * FROM permissions ORDER BY module, name")->fetchAll();

// Group permissions by module
$grouped_perms = [];
foreach ($permissions as $p) {
    $grouped_perms[$p['module']][] = $p;
}

// Get permissions for current selected role (if editing)
$edit_role = null;
$role_perms = [];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_role = $stmt->fetch();
    if ($edit_role) {
        $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$edit_role['id']]);
        $role_perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

include 'layout/header.php';
?>

<div class="mb-10 animate__animated animate__fadeIn">
    <h2 class="text-3xl font-black text-charcoal tracking-tight">إدارة الأدوار والصلاحيات 🔐</h2>
    <p class="text-sm text-gray-400 mt-1">قم بتخصيص مستويات الوصول لكل مسمى وظيفي في النظام.</p>
</div>

<?php if ($message): ?>
    <div class="p-4 mb-6 rounded-2xl <?php echo strpos($message, 'Error') !== false ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'; ?> font-bold animate__animated animate__shakeX">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Roles List -->
    <div class="lg:col-span-1">
        <div class="aeterna-card p-0 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-black text-charcoal">الأدوار المتاحة</h3>
                <a href="settings_roles.php" class="text-xs bg-taupe text-white px-3 py-1.5 rounded-xl font-bold hover:opacity-90 transition-all">إضافة جديد</a>
            </div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($roles as $r): ?>
                    <div class="p-4 hover:bg-gray-50 transition-all flex justify-between items-center group <?php echo ($edit_role && $edit_role['id'] == $r['id']) ? 'bg-taupe/5 border-r-4 border-taupe' : ''; ?>">
                        <div>
                            <p class="font-black text-charcoal"><?php echo htmlspecialchars($r['name']); ?></p>
                            <p class="text-[10px] text-gray-400">معرف الدور: #<?php echo $r['id']; ?></p>
                        </div>
                        <div class="flex gap-2">
                            <a href="?edit=<?php echo $r['id']; ?>" class="w-8 h-8 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center hover:bg-taupe hover:text-white transition-all">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($r['name'] !== 'Admin'): ?>
                                <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟');" class="inline">
                                    <input type="hidden" name="action" value="delete_role">
                                    <input type="hidden" name="role_id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="w-8 h-8 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Edit/Add Form -->
    <div class="lg:col-span-2">
        <div class="aeterna-card">
            <h3 class="font-black text-charcoal mb-6">
                <?php echo $edit_role ? 'تعديل صلاحيات: ' . htmlspecialchars($edit_role['name']) : 'إضافة دور جديد'; ?>
            </h3>

            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="save_role">
                <?php if ($edit_role): ?>
                    <input type="hidden" name="role_id" value="<?php echo $edit_role['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">اسم الدور (الوظيفة)</label>
                    <input type="text" name="role_name" value="<?php echo $edit_role ? htmlspecialchars($edit_role['name']) : ''; ?>" required
                           class="w-full bg-gray-50 border-none rounded-2xl px-5 py-4 text-charcoal font-bold focus:ring-2 focus:ring-taupe/20 transition-all"
                           placeholder="مثلاً: مراجع مالي، مديرة مكتب...">
                </div>

                <div class="space-y-6">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest">تخصيص الصلاحيات</label>
                    
                    <?php foreach ($grouped_perms as $module => $perms): ?>
                        <div class="p-6 bg-gray-50 rounded-3xl border border-gray-100">
                            <h4 class="font-black text-taupe text-sm mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-taupe"></span>
                                <?php echo $module; ?>
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($perms as $p): ?>
                                    <label class="flex items-center gap-3 p-3 bg-white rounded-2xl border border-gray-100 cursor-pointer hover:border-taupe transition-all group">
                                        <div class="relative inline-flex items-center">
                                            <input type="checkbox" name="perms[]" value="<?php echo $p['id']; ?>" 
                                                   <?php echo in_array($p['id'], $role_perms) ? 'checked' : ''; ?>
                                                   class="peer appearance-none w-5 h-5 border-2 border-gray-200 rounded-lg checked:bg-taupe checked:border-taupe transition-all">
                                            <i class="fas fa-check absolute text-[10px] text-white opacity-0 peer-checked:opacity-100 left-1.5 transition-opacity"></i>
                                        </div>
                                        <span class="text-xs font-bold text-charcoal"><?php echo htmlspecialchars($p['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full md:w-auto bg-charcoal text-white px-10 py-4 rounded-2xl font-black shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                        حفظ التغييرات
                    </button>
                    <?php if ($edit_role): ?>
                        <a href="settings_roles.php" class="inline-block mt-4 md:mt-0 md:mr-4 text-xs font-bold text-gray-400 hover:text-charcoal transition-all">إلغاء التعديل</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

</div>

<?php include 'layout/footer.php'; ?>

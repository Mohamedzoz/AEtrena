<?php
require_once 'config.php';
requireLogin();

// Role Check: manage_categories capability
if (!can('manage_categories')) {
    header("Location: dashboard.php");
    exit;
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// إضافة قسم مصروفات جديد
if ($action === 'add_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $error = 'اسم القسم مطلوب.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO expense_categories (name) VALUES (?)");
        if ($stmt->execute([$name])) {
            $success = "تم إضافة القسم بنجاح.";
            logActivity('إضافة قسم مالي', "تم إضافة قسم مصروفات جديد: $name");
        } else {
            $error = 'حدث خطأ أثناء إضافة القسم.';
        }
    }
}

// حذف قسم (اختياري - فقط لو مفيش مصروفات مرتبطة بيه)
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE category_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $error = "لا يمكن حذف القسم لأنه يحتوي على مصروفات مسجلة بالفعل.";
    } else {
        $pdo->prepare("DELETE FROM expense_categories WHERE id = ?")->execute([$id]);
        $success = "تم حذف القسم بنجاح.";
    }
}

include 'layout/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-xl font-bold text-charcoal flex items-center gap-2">
            <i class="fas fa-tags text-taupe"></i> إدارة أقسام المصروفات
        </h2>
        <p class="text-xs text-gray-400 mt-1">تصنيف وترتيب بنود الصرف المالي في النظام.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded shadow-sm mb-6 animate__animated animate__fadeIn">
        <i class="fas fa-check-circle ml-1"></i> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-50 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded shadow-sm mb-6 animate__animated animate__fadeIn">
        <i class="fas fa-exclamation-circle ml-1"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Form -->
    <div class="lg:col-span-1">
        <div class="aeterna-card sticky top-24">
            <h3 class="font-bold text-lg mb-4 text-charcoal">إضافة قسم جديد</h3>
            <form method="POST" action="finance_categories.php?action=add_category">
                <div class="mb-6">
                    <label class="block text-gray-700 text-xs font-bold mb-2">اسم القسم</label>
                    <input type="text" name="name" required placeholder="مثال: رواتب، إيجارات، انتقالات..." class="w-full py-3 px-4 border border-gray-200 rounded-xl bg-gray-50 focus:ring-2 focus:ring-taupe outline-none text-sm">
                </div>
                <button type="submit" class="btn-primary w-full py-3.5 rounded-xl font-bold shadow-lg">
                    حفظ القسم الجديد <i class="fas fa-save ml-1"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="lg:col-span-2">
        <div class="aeterna-card">
            <h3 class="font-bold text-lg mb-6 text-charcoal">الأقسام المسجلة</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php
                $stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM expenses WHERE category_id = c.id) as expense_count FROM expense_categories c ORDER BY c.id DESC");
                while($cat = $stmt->fetch()):
                ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 border border-gray-100 rounded-2xl hover:bg-white hover:shadow-md transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-taupe shadow-sm border border-gray-50 group-hover:bg-taupe group-hover:text-white transition-colors">
                                <i class="fas fa-tag text-xs"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-charcoal text-sm"><?php echo htmlspecialchars($cat['name']); ?></h4>
                                <p class="text-[9px] text-gray-400 font-bold"><?php echo $cat['expense_count']; ?> مصروفات مسجلة</p>
                            </div>
                        </div>
                        <?php if($cat['expense_count'] == 0): ?>
                            <a href="finance_categories.php?action=delete&id=<?php echo $cat['id']; ?>" onclick="return confirm('هل أنت متأكد من حذف هذا القسم؟');" class="text-red-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

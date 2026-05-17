<?php
require_once 'config.php';
requireLogin();

// Role Check: manage_expenses capability
if (!can('manage_expenses')) {
    header("Location: dashboard.php");
    exit;
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';
$current_user_id = $_SESSION['user_id'] ?? null; 

// التقاط رقم القسم للفلترة
$filter_category = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// تسجيل مصروف جديد
if ($action === 'add_expense' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $expense_date = parseDate($_POST['expense_date'] ?? date('Y-m-d'));
    $project_id = !empty($_POST['project_id']) ? $_POST['project_id'] : null;
    $stage = !empty($_POST['stage']) ? $_POST['stage'] : null;
    $notes = $_POST['notes'] ?? '';
    
    if (empty($category_id) || empty($amount)) {
        $error = 'القسم والمبلغ مطلوبان.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO expenses (category_id, project_id, stage, amount, description, notes, expense_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$category_id, $project_id, $stage, $amount, $description, $notes, $expense_date, $current_user_id])) {
            $success = "تم تسجيل المصروف بنجاح.";
            logActivity('تسجيل مصروف', "تم تسجيل مصروف بقيمة $amount في قسم #$category_id");
            header("Location: finance_expenses.php?success=1");
            exit;
        } else {
            $error = 'حدث خطأ أثناء تسجيل المصروف.';
        }
    }
}

// جلب قائمة المصروفات مع الفلترة
$where = $filter_category ? "WHERE e.category_id = $filter_category" : "";
$expenses = $pdo->query("SELECT e.*, c.name as category_name, u.name as creator_name 
                         FROM expenses e 
                         JOIN expense_categories c ON e.category_id = c.id 
                         LEFT JOIN users u ON e.created_by = u.id
                         $where
                         ORDER BY e.id DESC LIMIT 200")->fetchAll();

include 'layout/header.php';
?>

<div class="mb-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 stagger">
    <div>
        <h2 class="text-3xl font-black text-charcoal tracking-tight flex items-center gap-3">
            <i class="fas fa-arrow-up text-red-500"></i> المصروفات (Expenses)
        </h2>
        <p class="text-base text-gray-500 font-bold mt-2">توثيق ومراقبة كافة المصاريف التشغيلية والنثريات.</p>
    </div>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full lg:w-auto">
        <a href="finance_categories.php" class="bg-white border border-gray-100 text-charcoal hover:bg-charcoal hover:text-white font-black py-4 px-6 rounded-2xl shadow-premium text-sm transition-all flex items-center justify-center gap-2 active:scale-95">
            <i class="fas fa-tags"></i> إدارة الأقسام
        </a>
        <button onclick="document.getElementById('addExpenseModal').classList.remove('hidden')" 
                class="bg-red-600 text-white font-black py-4 px-8 rounded-2xl shadow-premium flex items-center justify-center gap-3 active:scale-95 hover:bg-red-700 transition-all">
            <i class="fas fa-minus-circle"></i>
            <span>تسجيل مصروف</span>
        </button>
    </div>
</div>

<?php if ($success || isset($_GET['success'])): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded shadow-sm mb-6 animate__animated animate__fadeIn">
        <i class="fas fa-check-circle ml-1"></i> تم تسجيل المصروف بنجاح.
    </div>
<?php endif; ?>

<!-- Summary Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
    <?php
    $cat_stmt = $pdo->query("SELECT c.name, COALESCE(SUM(e.amount), 0) as total FROM expense_categories c LEFT JOIN expenses e ON c.id = e.category_id GROUP BY c.id ORDER BY total DESC LIMIT 4");
    while($cat_summary = $cat_stmt->fetch()):
        $total_val = $cat_summary['total'];
    ?>
        <div class="aeterna-card mb-0 p-5 border-b-4 border-red-500/30">
            <p class="text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest"><?php echo htmlspecialchars($cat_summary['name']); ?></p>
            <p class="text-xl font-black text-charcoal" dir="ltr"><?php echo number_format($total_val, 0); ?> <span class="text-[10px] text-gray-400">EGP</span></p>
        </div>
    <?php endwhile; ?>
</div>

<!-- Category Filters -->
<div class="mb-4">
    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
        <a href="finance_expenses.php" class="px-5 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all <?php echo ($filter_category == 0) ? 'bg-charcoal text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'; ?>">
            الكل
        </a>
        <?php
        $filter_cats = $pdo->query("SELECT * FROM expense_categories ORDER BY name")->fetchAll();
        foreach($filter_cats as $cat):
            $isActive = ($filter_category == $cat['id']);
            $btnClass = $isActive ? 'bg-charcoal text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50';
        ?>
            <a href="finance_expenses.php?category_id=<?php echo $cat['id']; ?>" class="px-5 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all <?php echo $btnClass; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Unified View: Grid on Mobile, Table on Desktop -->
<div class="mb-12">
    <!-- Mobile Grid View -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
        <?php
        foreach ($expenses as $expense):
        ?>
        <div class="aeterna-card flex flex-col justify-between p-6 border-r-4 border-r-red-500 group">
            <div class="flex items-start justify-between mb-4">
                <div class="flex flex-col">
                    <h4 class="font-black text-charcoal text-lg leading-tight group-hover:text-taupe transition-colors"><?php echo htmlspecialchars($expense['description'] ?: 'بدون بيان'); ?></h4>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="bg-red-50 text-red-600 text-[10px] font-black px-2 py-1 rounded border border-red-100">
                            <?php echo htmlspecialchars($expense['category_name']); ?>
                        </span>
                        <?php if($expense['stage']): ?>
                            <span class="bg-gray-100 text-gray-500 text-[10px] font-black px-2 py-1 rounded border border-gray-200">
                                م: <?php echo $expense['stage']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-left">
                    <div class="font-black text-red-600 text-lg" dir="ltr">- <?php echo number_format($expense['amount'], 0); ?></div>
                    <span class="text-[9px] font-black text-gray-400 uppercase">EGP</span>
                </div>
            </div>

            <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-[11px] font-black text-gray-400 border border-gray-100">
                        <?php echo mb_substr($expense['creator_name'] ?? 'U', 0, 1, 'UTF-8'); ?>
                    </div>
                    <span class="text-[10px] text-gray-500 font-bold"><?php echo htmlspecialchars($expense['creator_name'] ?? '-'); ?></span>
                </div>
                <div class="text-[10px] text-gray-400 font-black" dir="ltr">
                    <i class="fas fa-calendar-alt ml-1"></i> <?php echo formatDate($expense['expense_date']); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($expenses)): ?>
            <div class="col-span-full py-20 text-center">
                <i class="fas fa-receipt text-4xl text-gray-200 mb-4 block"></i>
                <p class="text-gray-400 font-bold">لا توجد مصروفات مسجلة.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden lg:block bg-white rounded-[2.5rem] shadow-premium border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 text-gray-400 text-[10px] font-black uppercase tracking-[0.2em] border-b border-gray-100 sticky top-0 z-10">
                        <th class="p-6">البيان / التفاصيل</th>
                        <th class="p-6">القسم</th>
                        <th class="p-6">المبلغ</th>
                        <th class="p-6">التاريخ</th>
                        <th class="p-6">المسؤول</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($expenses as $expense): ?>
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="p-6">
                            <div class="font-black text-charcoal text-base"><?php echo htmlspecialchars($expense['description'] ?: 'بدون بيان'); ?></div>
                        </td>
                        <td class="p-6">
                            <span class="bg-red-50 text-red-600 text-[10px] font-black px-2 py-1 rounded border border-red-100">
                                <?php echo htmlspecialchars($expense['category_name']); ?>
                            </span>
                            <?php if($expense['stage']): ?>
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-black px-2 py-1 rounded border border-gray-200 mr-1">
                                    م: <?php echo $expense['stage']; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-6">
                            <div class="font-black text-red-600 text-lg" dir="ltr">
                                - <?php echo number_format($expense['amount'], 2); ?>
                            </div>
                        </td>
                        <td class="p-6 text-[10px] text-gray-400 font-bold" dir="ltr">
                            <?php echo formatDate($expense['expense_date']); ?>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-xs font-black text-gray-400 border border-gray-100">
                                    <?php echo mb_substr($expense['creator_name'] ?? 'U', 0, 1, 'UTF-8'); ?>
                                </div>
                                <span class="text-sm text-charcoal font-bold"><?php echo htmlspecialchars($expense['creator_name'] ?? '-'); ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Expense -->
<div id="addExpenseModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-8 relative text-right animate__animated animate__zoomIn animate__faster">
        <button onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="absolute top-6 left-6 text-gray-400 hover:text-charcoal transition-colors"><i class="fas fa-times text-xl"></i></button>
        <h3 class="text-2xl font-black mb-2 text-charcoal">تسجيل مصروف جديد</h3>
        <p class="text-sm text-gray-400 mb-6">يرجى توثيق قيمة المصروف والبيان بدقة.</p>
        
        <form method="POST" action="finance_expenses.php?action=add_expense">
            <div class="mb-4">
                <label class="block text-gray-700 text-xs font-bold mb-2">قسم المصروف *</label>
                <select name="category_id" required class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 bg-gray-50 text-sm">
                    <option value="">اختر القسم...</option>
                    <?php
                    $form_cats_stmt = $pdo->query("SELECT * FROM expense_categories ORDER BY name");
                    while($row = $form_cats_stmt->fetch()):
                    ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-xs font-bold mb-2">المبلغ (EGP) *</label>
                    <input type="number" step="0.01" name="amount" required dir="ltr" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 bg-gray-50 text-left font-bold">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-bold mb-2">تاريخ المصروف</label>
                    <input type="text" name="expense_date" value="<?php echo date('d-m-Y'); ?>" placeholder="DD-MM-YYYY" class="flatpickr w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 bg-gray-50 text-sm text-left">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-xs font-bold mb-2">مشروع مرتبط (اختياري)</label>
                    <select name="project_id" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 bg-gray-50 text-sm">
                        <option value="">لا يوجد</option>
                        <?php
                        $projs = $pdo->query("SELECT id, title FROM projects ORDER BY id DESC");
                        while($p = $projs->fetch()) echo "<option value='{$p['id']}'>#{$p['id']} - {$p['title']}</option>";
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-bold mb-2">المرحلة المرتبطة</label>
                    <select name="stage" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 bg-gray-50 text-sm">
                        <option value="">غير محدد</option>
                        <option value="1">1. معاينة</option>
                        <option value="2">2. تصميم</option>
                        <option value="3">3. تعاقد</option>
                        <option value="4">4. تنفيذية</option>
                        <option value="5">5. تصنيع</option>
                        <option value="6">6. توريد</option>
                        <option value="7">7. تركيب</option>
                        <option value="8">8. فيدباك</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-xs font-bold mb-2">البيان / التفاصيل</label>
                <input type="text" name="description" placeholder="مثال: فاتورة كهرباء المعرض" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 bg-gray-50 text-sm">
            </div>

            <div class="mb-8">
                <label class="block text-gray-700 text-xs font-bold mb-2">ملاحظات إضافية</label>
                <textarea name="notes" rows="2" placeholder="اكتب أي ملاحظات هنا..." class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 bg-gray-50 text-sm outline-none"></textarea>
            </div>

            <button type="submit" class="w-full bg-red-600 text-white font-black py-4 rounded-2xl hover:bg-red-700 transition-all shadow-xl hover:-translate-y-1">
                حفظ المصروف <i class="fas fa-save mr-2"></i>
            </button>
        </form>
    </div>
</div>

<style>
/* لإخفاء الـ Scrollbar في متصفحات Webkit عشان شكل الفلاتر يبقى أنضف */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<?php include 'layout/footer.php'; ?>
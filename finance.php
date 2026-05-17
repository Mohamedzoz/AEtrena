<?php
require_once 'config.php';
requireLogin();

// Role Check: Only those with view_finance capability
if (!can('view_finance')) {
    header("Location: dashboard.php");
    exit;
}

// جلب الإحصائيات الشاملة
$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as total_collected, 
    SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as total_expected
    FROM payments");
$revenue_stats = $stmt->fetch();

$stmt = $pdo->query("SELECT SUM(amount) as total_expenses FROM expenses");
$expense_stats = $stmt->fetch();

$total_collected = $revenue_stats['total_collected'] ?? 0;
$total_expected = $revenue_stats['total_expected'] ?? 0;
$total_expenses = $expense_stats['total_expenses'] ?? 0;
$net_balance = $total_collected - $total_expenses;

include 'layout/header.php';
?>

<div class="mb-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 stagger">
    <div>
        <h2 class="text-3xl font-black text-charcoal tracking-tight">الخزنة والتحليلات المالية</h2>
        <p class="text-base text-gray-500 font-bold mt-2">نظرة شاملة على التدفقات النقدية والمقبوضات والمصروفات.</p>
    </div>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full lg:w-auto">
        <a href="finance_expenses.php" class="bg-red-50 text-red-600 border border-red-100 hover:bg-red-600 hover:text-white font-black py-4 px-6 rounded-2xl shadow-premium text-sm transition-all flex items-center justify-center gap-2 active:scale-95">
            <i class="fas fa-minus-circle"></i> تسجيل مصروف
        </a>
        <a href="finance_revenues.php" class="btn-primary py-4 px-8 text-base flex items-center justify-center gap-3 shadow-premium active:scale-95 transition-all">
            <i class="fas fa-plus-circle"></i> تسجيل إيراد
        </a>
    </div>
</div>

<!-- Key Metrics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-charcoal to-graphite text-white rounded-3xl p-8 shadow-premium relative overflow-hidden group">
        <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/5 rounded-full group-hover:scale-125 transition-transform"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-vault text-taupe"></i>
                </div>
                <h3 class="font-bold text-taupe">صافي رصيد الخزنة</h3>
            </div>
            <p class="text-4xl font-black mb-2" dir="ltr">EGP <?php echo number_format($net_balance, 2); ?></p>
            <p class="text-xs opacity-60">إجمالي المقبوضات الفعلية ناقص المصروفات</p>
        </div>
    </div>

    <!-- Revenues Summary -->
    <div class="aeterna-card mb-0 border-t-4 border-t-green-500">
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center"><i class="fas fa-hand-holding-usd"></i></div>
            <span class="text-[10px] font-black text-green-600 bg-green-50 px-2 py-1 rounded">إيجابي</span>
        </div>
        <h3 class="text-xs font-bold text-gray-400 mb-1">المحصل الفعلي (مشاريع)</h3>
        <p class="text-2xl font-black text-charcoal" dir="ltr">EGP <?php echo number_format($total_collected, 2); ?></p>
        <div class="mt-4 pt-4 border-t border-gray-50">
            <p class="text-[10px] text-gray-400">تحصيلات منتظرة: <span class="text-orange-500 font-bold" dir="ltr"><?php echo number_format($total_expected, 2); ?> EGP</span></p>
        </div>
    </div>

    <!-- Expenses Summary -->
    <div class="aeterna-card mb-0 border-t-4 border-t-red-500">
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center"><i class="fas fa-file-invoice-dollar"></i></div>
            <span class="text-[10px] font-black text-red-600 bg-red-50 px-2 py-1 rounded">سلبي</span>
        </div>
        <h3 class="text-xs font-bold text-gray-400 mb-1">إجمالي المصروفات</h3>
        <p class="text-2xl font-black text-charcoal" dir="ltr">EGP <?php echo number_format($total_expenses, 2); ?></p>
        <div class="mt-4 pt-4 border-t border-gray-50">
            <a href="finance_expenses.php" class="text-[10px] text-taupe font-bold hover:underline">عرض تفاصيل المصروفات <i class="fas fa-chevron-left mr-1"></i></a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Quick Links / Actions -->
    <div>
        <h3 class="font-bold text-lg text-charcoal mb-6 flex items-center gap-2">
            <i class="fas fa-th-large text-taupe text-sm"></i> الأقسام المالية
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="finance_revenues.php" class="p-6 bg-white border border-gray-100 rounded-3xl hover:shadow-xl transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center text-xl mb-4 group-hover:bg-green-500 group-hover:text-white transition-all">
                    <i class="fas fa-coins"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-1">المقبوضات</h4>
                <p class="text-[10px] text-gray-400">إدارة دفعات المشاريع وإصدار الفواتير.</p>
            </a>

            <a href="finance_expenses.php" class="p-6 bg-white border border-gray-100 rounded-3xl hover:shadow-xl transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-xl mb-4 group-hover:bg-red-600 group-hover:text-white transition-all">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-1">المصروفات</h4>
                <p class="text-[10px] text-gray-400">تسجيل وتصنيف كافة المصاريف والنثريات.</p>
            </a>

            <a href="finance_categories.php" class="p-6 bg-white border border-gray-100 rounded-3xl hover:shadow-xl transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-gray-50 text-taupe flex items-center justify-center text-xl mb-4 group-hover:bg-taupe group-hover:text-white transition-all">
                    <i class="fas fa-tags"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-1">الأقسام</h4>
                <p class="text-[10px] text-gray-400">إعداد وتعديل تصنيفات الصرف المالي.</p>
            </a>

            <a href="invoice.php" class="p-6 bg-white border border-gray-100 rounded-3xl hover:shadow-xl transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl mb-4 group-hover:bg-amber-500 group-hover:text-white transition-all">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <h4 class="font-bold text-charcoal mb-1">الفواتير</h4>
                <p class="text-[10px] text-gray-400">إصدار عروض الأسعار وفواتير العملاء.</p>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div>
        <h3 class="font-black text-xl text-charcoal mb-6 flex items-center gap-3">
            <i class="fas fa-history text-taupe"></i> آخر الحركات المالية
        </h3>
        <div class="aeterna-card p-0 overflow-hidden shadow-premium">
            <div class="divide-y divide-gray-100">
                <?php
                // Get latest 8 finance movements (revenues or expenses)
                $stmt = $pdo->query("(SELECT 'revenue' as move_type, amount, created_at, 'دفعة مشروع' as title FROM payments) 
                                     UNION ALL 
                                     (SELECT 'expense' as move_type, amount, created_at, description as title FROM expenses)
                                     ORDER BY created_at DESC LIMIT 8");
                while($move = $stmt->fetch()):
                    $isRev = $move['move_type'] == 'revenue';
                ?>
                    <div class="p-5 flex items-center justify-between hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl <?php echo $isRev ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'; ?> flex items-center justify-center text-sm shadow-sm">
                                <i class="fas <?php echo $isRev ? 'fa-plus' : 'fa-minus'; ?>"></i>
                            </div>
                            <div>
                                <p class="text-sm font-black text-charcoal leading-tight"><?php echo htmlspecialchars($move['title'] ?: 'بدون بيان'); ?></p>
                                <p class="text-[10px] text-gray-400 font-bold mt-1" dir="ltr"><?php echo formatDate($move['created_at']); ?></p>
                            </div>
                        </div>
                        <div class="text-base font-black <?php echo $isRev ? 'text-green-600' : 'text-red-600'; ?>" dir="ltr">
                            <?php echo $isRev ? '+' : '-'; ?> <?php echo number_format($move['amount'], 0); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="bg-gray-50/50 p-4 text-center border-t border-gray-100">
                <a href="finance_revenues.php" class="text-xs font-black text-taupe hover:text-charcoal transition-colors">عرض السجلات الكاملة <i class="fas fa-external-link-alt mr-1"></i></a>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
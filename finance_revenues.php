<?php
require_once 'config.php';
requireLogin();

// Role Check: manage_revenues capability
if (!can('manage_revenues')) {
    header("Location: dashboard.php");
    exit;
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';
$current_user_id = $_SESSION['user_id'] ?? null; 

// تسجيل إيراد جديد
if ($action === 'add_revenue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $type = $_POST['type'] ?? 'Milestone';
    $is_vat = isset($_POST['is_vat_included']) ? 1 : 0;
    $due_date = !empty($_POST['due_date']) ? parseDate($_POST['due_date']) : null;
    $status = $_POST['status'] ?? 'Paid';
    $stage = !empty($_POST['stage']) ? $_POST['stage'] : null;
    $notes = $_POST['notes'] ?? '';
    
    if (empty($project_id) || empty($amount)) {
        $error = 'المشروع والمبلغ مطلوبان.';
    } else {
        $vat_amount = $is_vat ? ($amount * 0.14) : 0;
        $stmt = $pdo->prepare("INSERT INTO payments (project_id, amount, is_vat_included, vat_amount, type, stage, notes, status, due_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$project_id, $amount, $is_vat, $vat_amount, $type, $stage, $notes, $status, $due_date, $current_user_id])) {
            $success = "تم تسجيل الإيراد بنجاح.";
            logActivity('تسجيل إيراد', "تم تسجيل دفعة جديدة بمبلغ $amount لمشروع #$project_id");
            header("Location: finance_revenues.php?success=1");
            exit;
        } else {
            $error = 'حدث خطأ أثناء تسجيل الإيراد.';
        }
    }
}

// تحصيل دفعة قيد الانتظار
if ($action === 'mark_paid' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE payments SET status = 'Paid', paid_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $success = "تم تحصيل الدفعة بنجاح.";
    logActivity('تحصيل دفعة', "تم تأكيد تحصيل الدفعة رقم #" . $_GET['id']);
}

include 'layout/header.php';
?>

<div class="mb-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 stagger">
    <div>
        <h2 class="text-3xl font-black text-charcoal tracking-tight flex items-center gap-3">
            <i class="fas fa-arrow-down text-green-500"></i> المقبوضات (Revenue)
        </h2>
        <p class="text-base text-gray-500 font-bold mt-2">تتبع دفعات المشاريع والتحصيلات المالية الجارية.</p>
    </div>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full lg:w-auto">
        <a href="invoice.php" class="bg-white border border-gray-100 text-charcoal hover:bg-charcoal hover:text-white font-black py-4 px-6 rounded-2xl shadow-premium text-sm transition-all flex items-center justify-center gap-2 active:scale-95">
            <i class="fas fa-file-invoice"></i> إصدار فاتورة
        </a>
        <button onclick="document.getElementById('addRevenueModal').classList.remove('hidden')" 
                class="btn-primary py-4 px-8 text-base flex items-center justify-center gap-3 shadow-premium active:scale-95 transition-all">
            <i class="fas fa-plus-circle"></i>
            <span>تسجيل دفعة</span>
        </button>
    </div>
</div>

<?php if ($success || isset($_GET['success'])): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded shadow-sm mb-6 animate__animated animate__fadeIn">
        <i class="fas fa-check-circle ml-1"></i> تم تسجيل العملية بنجاح.
    </div>
<?php endif; ?>

<!-- Unified View: Grid on Mobile, Table on Desktop -->
<div class="mb-12">
    <!-- Mobile Grid View -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
        <?php
        $stmt = $pdo->query("SELECT py.*, p.title as project_title, c.name as customer_name, u.name as creator_name 
                             FROM payments py 
                             JOIN projects p ON py.project_id = p.id 
                             JOIN customers c ON p.customer_id = c.id 
                             LEFT JOIN users u ON py.created_by = u.id
                             ORDER BY py.id DESC LIMIT 100");
        $payments = $stmt->fetchAll();
        foreach ($payments as $payment):
            $isPending = $payment['status'] == 'Pending';
            $isLate = $isPending && $payment['due_date'] && strtotime($payment['due_date']) < time();
        ?>
        <div class="aeterna-card flex flex-col justify-between p-6 border-r-4 <?php echo $isPending ? 'border-r-amber-400' : 'border-r-green-500'; ?> group">
            <div class="flex items-start justify-between mb-4">
                <div class="flex flex-col">
                    <h4 class="font-black text-charcoal text-lg leading-tight group-hover:text-taupe transition-colors"><?php echo htmlspecialchars($payment['project_title']); ?></h4>
                    <span class="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-widest"><i class="fas fa-user ml-1 text-taupe"></i> <?php echo htmlspecialchars($payment['customer_name']); ?></span>
                </div>
                <div class="text-left">
                    <div class="font-black text-charcoal text-lg" dir="ltr"><?php echo number_format($payment['amount'], 0); ?></div>
                    <span class="text-[9px] font-black text-gray-400 uppercase">EGP</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-6">
                <span class="bg-gray-50 text-gray-500 text-[10px] font-black px-2.5 py-1 rounded-lg border border-gray-100">
                    <?php echo htmlspecialchars($payment['type']); ?>
                </span>
                <?php if($isPending): ?>
                    <span class="px-3 py-1 rounded-lg text-[10px] font-black <?php echo $isLate ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-amber-50 text-amber-600 border border-amber-100'; ?>">
                        <?php echo $isLate ? 'متأخرة' : 'قيد الانتظار'; ?>
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-lg text-[10px] font-black bg-green-50 text-green-600 border border-green-100">
                        تم التحصيل
                    </span>
                <?php endif; ?>
            </div>

            <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-50">
                <div class="text-[10px] text-gray-400 font-bold">
                    <i class="fas fa-calendar-alt ml-1"></i> <?php echo formatDate($payment['created_at']); ?>
                </div>
                <?php if($isPending): ?>
                    <a href="finance_revenues.php?action=mark_paid&id=<?php echo $payment['id']; ?>" class="bg-charcoal text-white px-6 py-3 rounded-xl text-xs font-black shadow-lg active:scale-95 transition-all">
                        تأكيد التحصيل
                    </a>
                <?php else: ?>
                    <a href="invoice.php?receipt_id=<?php echo $payment['id']; ?>" class="w-12 h-12 rounded-xl bg-gray-50 text-taupe flex items-center justify-center hover:bg-taupe hover:text-white transition-all shadow-sm border border-gray-100">
                        <i class="fas fa-receipt text-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($payments)): ?>
            <div class="col-span-full py-20 text-center">
                <i class="fas fa-box-open text-4xl text-gray-200 mb-4 block"></i>
                <p class="text-gray-400 font-bold">لا توجد مقبوضات مسجلة.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden lg:block bg-white rounded-[2.5rem] shadow-premium border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 text-gray-400 text-[10px] font-black uppercase tracking-[0.2em] border-b border-gray-100">
                        <th class="p-6">المشروع / العميل</th>
                        <th class="p-6">نوع الدفعة</th>
                        <th class="p-6">المبلغ (EGP)</th>
                        <th class="p-6">الحالة</th>
                        <th class="p-6">التاريخ</th>
                        <th class="p-6 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($payments as $payment):
                        $isPending = $payment['status'] == 'Pending';
                        $isLate = $isPending && $payment['due_date'] && strtotime($payment['due_date']) < time();
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="p-6">
                            <div class="font-black text-charcoal text-base"><?php echo htmlspecialchars($payment['project_title']); ?></div>
                            <div class="text-[11px] text-gray-500 mt-0.5 font-bold"><i class="fas fa-user ml-1 text-taupe"></i> <?php echo htmlspecialchars($payment['customer_name']); ?></div>
                        </td>
                        <td class="p-6">
                            <span class="bg-gray-100 text-gray-600 text-[10px] font-black px-2 py-1 rounded">
                                <?php echo htmlspecialchars($payment['type']); ?>
                            </span>
                            <?php if($payment['stage']): ?>
                                <span class="bg-blue-50 text-blue-600 text-[10px] font-black px-2 py-1 rounded mr-1">
                                    المرحلة: <?php echo $payment['stage']; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-6">
                            <div class="font-black text-charcoal" dir="ltr">
                                <?php echo number_format($payment['amount'], 2); ?>
                            </div>
                            <?php if($payment['is_vat_included']): ?>
                                <div class="text-[9px] text-red-400">شامل ضريبة (14%)</div>
                            <?php endif; ?>
                        </td>
                        <td class="p-6">
                            <?php if($isPending): ?>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black <?php echo $isLate ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-amber-50 text-amber-600 border border-amber-100'; ?>">
                                    <?php echo $isLate ? 'متأخرة' : 'قيد الانتظار'; ?>
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black bg-green-50 text-green-600 border border-green-100">
                                    تم التحصيل
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-6 text-[10px] text-gray-400 font-bold" dir="ltr">
                            <?php echo formatDate($payment['created_at']); ?>
                        </td>
                        <td class="p-6">
                            <div class="flex justify-center gap-2">
                                <?php if($isPending): ?>
                                    <a href="finance_revenues.php?action=mark_paid&id=<?php echo $payment['id']; ?>" class="bg-charcoal text-white hover:bg-graphite px-4 py-2 rounded-xl text-[10px] font-black shadow-lg transition-all active:scale-95">
                                        تحصيل
                                    </a>
                                <?php else: ?>
                                    <a href="invoice.php?receipt_id=<?php echo $payment['id']; ?>" class="w-10 h-10 rounded-xl bg-gray-50 text-taupe flex items-center justify-center hover:bg-taupe hover:text-white transition-all shadow-sm border border-gray-100" title="إيصال استلام">
                                        <i class="fas fa-receipt"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Revenue -->
<div id="addRevenueModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-8 relative text-right animate__animated animate__zoomIn animate__faster">
        <button onclick="document.getElementById('addRevenueModal').classList.add('hidden')" class="absolute top-6 left-6 text-gray-400 hover:text-charcoal transition-colors"><i class="fas fa-times text-xl"></i></button>
        <h3 class="text-2xl font-black mb-2 text-charcoal">تسجيل دفعة جديدة</h3>
        <p class="text-sm text-gray-400 mb-6">قم بإدخال بيانات التحصيل المالي للمشروع.</p>
        
        <form method="POST" action="finance_revenues.php?action=add_revenue">
            <div class="mb-4">
                <label class="block text-gray-700 text-xs font-bold mb-2">المشروع المرتبط *</label>
                <select name="project_id" required class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-sm">
                    <option value="">اختر المشروع...</option>
                    <?php
                    $stmt = $pdo->query("SELECT p.id, p.title, c.name FROM projects p JOIN customers c ON p.customer_id = c.id ORDER BY p.id DESC");
                    while($row = $stmt->fetch()):
                    ?>
                        <option value="<?php echo $row['id']; ?>">
                            <?php echo htmlspecialchars($row['title'] . ' - ' . $row['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-xs font-bold mb-2">المبلغ (EGP) *</label>
                    <input type="number" step="0.01" name="amount" id="amountInput" required dir="ltr" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left font-bold">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-bold mb-2">حالة الدفعة</label>
                    <select name="status" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-sm">
                        <option value="Paid">تم التحصيل (Paid)</option>
                        <option value="Pending">قيد الانتظار (Pending)</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-gray-700 text-xs font-bold mb-2">نوع الدفعة</label>
                    <select name="type" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-sm">
                        <option value="Survey & Design">مصاريف معيانة وتصميم</option>
                        <option value="Down Payment">دفعة مقدمة</option>
                        <option value="Milestone">دفعة مرحلية</option>
                        <option value="Final">دفعة نهائية (تسليم)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-bold mb-2">المرحلة المرتبطة</label>
                    <select name="stage" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-sm">
                        <option value="">غير محدد</option>
                        <?php 
                        $stages = [1=>'معاينة', 2=>'تصميم', 3=>'تعاقد', 4=>'تنفيذية', 5=>'تصنيع', 6=>'توريد', 7=>'تركيب', 8=>'فيدباك'];
                        foreach($stages as $num=>$name) echo "<option value='$num'>$num. $name</option>"; 
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-xs font-bold mb-2">ملاحظات الدفعة (تظهر للعميل)</label>
                <textarea name="notes" rows="2" placeholder="اكتب تفاصيل الدفعة هنا..." class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-sm outline-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">

            <div class="mb-8 p-4 rounded-2xl bg-blue-50 border border-blue-100">
                <label class="flex items-center gap-3 text-charcoal text-xs font-bold cursor-pointer">
                    <input type="checkbox" name="is_vat_included" id="vatCheckbox" value="1" class="h-5 w-5 text-taupe focus:ring-taupe border-gray-300 rounded cursor-pointer" onchange="calculateVat()">
                    <span>إضافة ضريبة القيمة المضافة (VAT 14%)</span>
                </label>
                <div id="vatPreview" class="text-[10px] font-black text-red-500 mt-2 hidden">
                    قيمة الضريبة: <span id="vatValue" dir="ltr">0.00</span> EGP
                </div>
            </div>

            <button type="submit" class="w-full bg-charcoal text-white font-black py-4 rounded-2xl hover:bg-graphite transition-all shadow-xl hover:-translate-y-1">
                حفظ وتحصيل الدفعة <i class="fas fa-save mr-2"></i>
            </button>
        </form>
    </div>
</div>

<script>
    function calculateVat() {
        const amount = document.getElementById('amountInput').value;
        const checkbox = document.getElementById('vatCheckbox');
        const preview = document.getElementById('vatPreview');
        const value = document.getElementById('vatValue');
        
        if (checkbox.checked && amount > 0) {
            const vat = amount * 0.14;
            value.innerText = vat.toLocaleString(undefined, {minimumFractionDigits: 2});
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
        }
    }
    document.getElementById('amountInput').addEventListener('input', calculateVat);
</script>

<?php include 'layout/footer.php'; ?>

<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Leave Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_type'])) {
    $type = $_POST['leave_type'];
    $reason = $_POST['reason'] ?? '';
    $start = !empty($_POST['start_date']) ? parseDate($_POST['start_date']) : null;
    $end = !empty($_POST['end_date']) ? parseDate($_POST['end_date']) : null;
    
    $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, type, reason, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $type, $reason, $start, $end])) {
        $success = "تم إرسال طلبك بنجاح وهو قيد المراجعة حالياً.";
        logActivity('طلب إجازة', "قام " . $_SESSION['user_name'] . " بتقديم طلب إجازة جديد ($type)");
        
        // Notify Admins
        notifyAdmins("طلب إجازة جديد", "قام الموظف [{$_SESSION['user_name']}] بتقديم طلب [{$type}] جديد.", "admin_leaves.php", 'warning');
    } else {
        $error = "حدث خطأ أثناء إرسال الطلب.";
    }
}

include 'layout/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-xl font-bold text-charcoal flex items-center gap-2">
            <i class="fas fa-calendar-check text-taupe"></i> طلبات الإجازات والأذونات
        </h2>
        <p class="text-xs text-gray-400 mt-1">يمكنك متابعة حالة طلباتك الحالية وتقديم طلبات جديدة.</p>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Form -->
    <div class="lg:col-span-1">
        <div class="aeterna-card">
            <h3 class="font-bold text-lg mb-4 text-charcoal">تقديم طلب جديد</h3>
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-xs font-bold mb-2">نوع الطلب</label>
                    <select name="leave_type" required class="w-full py-3 px-4 border border-gray-200 rounded-xl bg-gray-50 text-sm focus:ring-2 focus:ring-taupe outline-none">
                        <option value="Leave">إجازة اعتيادية</option>
                        <option value="Permission">إذن خروج / تأخير</option>
                        <option value="Occasion">مناسبة عائلية</option>
                        <option value="Sick">إجازة مرضية</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-2">من تاريخ</label>
                        <input type="text" name="start_date" required placeholder="DD-MM-YYYY" class="flatpickr w-full py-3 px-4 border border-gray-200 rounded-xl bg-gray-50 text-sm focus:ring-2 focus:ring-taupe outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-2">إلى تاريخ</label>
                        <input type="text" name="end_date" placeholder="DD-MM-YYYY" class="flatpickr w-full py-3 px-4 border border-gray-200 rounded-xl bg-gray-50 text-sm focus:ring-2 focus:ring-taupe outline-none">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-xs font-bold mb-2">السبب / الملاحظات</label>
                    <textarea name="reason" rows="3" placeholder="اكتب سبباً مختصراً..." class="w-full py-3 px-4 border border-gray-200 rounded-xl bg-gray-50 text-sm focus:ring-2 focus:ring-taupe outline-none"></textarea>
                </div>
                
                <button type="submit" class="btn-primary w-full py-3.5 rounded-xl text-sm font-bold shadow-lg">
                    إرسال الطلب الآن <i class="fas fa-paper-plane ml-1"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- History -->
    <div class="lg:col-span-2">
        <div class="aeterna-card">
            <h3 class="font-bold text-lg mb-4 text-charcoal">سجل طلباتي</h3>
            <div class="space-y-3">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY id DESC");
                $stmt->execute([$user_id]);
                $leaves = $stmt->fetchAll();
                
                if (count($leaves) > 0):
                    foreach ($leaves as $leave):
                        $status_styles = [
                            'Pending'  => 'bg-amber-50 text-amber-700 border-amber-100',
                            'Approved' => 'bg-green-50 text-green-700 border-green-100',
                            'Rejected' => 'bg-red-50 text-red-700 border-red-100',
                        ];
                        $status_ar = ['Pending' => 'انتظار', 'Approved' => 'مقبول', 'Rejected' => 'مرفوض'];
                        $type_ar = ['Leave' => 'إجازة', 'Permission' => 'إذن', 'Occasion' => 'مناسبة', 'Sick' => 'مرضي'];
                ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 border border-gray-100 rounded-2xl hover:bg-white hover:shadow-md transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-taupe shadow-sm border border-gray-100">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-charcoal"><?php echo $type_ar[$leave['type']] ?? $leave['type']; ?></h4>
                                <p class="text-[10px] text-gray-400 font-bold" dir="ltr">
                                    <?php echo formatDate($leave['start_date']); ?><?php echo $leave['end_date'] ? ' → ' . formatDate($leave['end_date']) : ''; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black border <?php echo $status_styles[$leave['status']] ?? ''; ?>">
                                <?php echo $status_ar[$leave['status']] ?? $leave['status']; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-calendar-times text-3xl text-gray-200 mb-2"></i>
                        <p class="text-gray-400 text-sm">لا يوجد طلبات سابقة.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

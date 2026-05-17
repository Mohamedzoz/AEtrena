<?php
require_once 'config.php';
requireLogin();

if (!can('manage_leaves')) {
    header("Location: dashboard.php");
    exit;
}

// Handle Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_action'])) {
    $leave_id = $_POST['leave_id'];
    $new_status = $_POST['leave_action'] === 'approve' ? 'Approved' : 'Rejected';
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $leave_id]);
    logActivity("تحديث طلب إجازة", "تم تغيير حالة الطلب #$leave_id إلى $new_status");
    header("Location: admin_leaves.php?success=1");
    exit;
}

$filter_user = $_GET['filter_user'] ?? '';
$all_users = $pdo->query("SELECT id, name, role FROM users ORDER BY name")->fetchAll();

include 'layout/header.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-charcoal flex items-center gap-2">
            <i class="fas fa-calendar-check text-taupe"></i> إدارة طلبات الإجازات
        </h2>
        <p class="text-xs text-gray-400 mt-1">مراجعة واعتماد طلبات الإجازات والأذونات المقدمة من الموظفين.</p>
    </div>
</div>

<div class="aeterna-card mb-8 py-4">
    <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[250px]">
            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest">تصفية حسب الموظف</label>
            <select name="filter_user" class="w-full py-2.5 px-4 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:ring-2 focus:ring-taupe outline-none">
                <option value="">كل الموظفين</option>
                <?php foreach ($all_users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary px-8 py-2.5 rounded-xl text-sm shadow-lg">
            <i class="fas fa-filter ml-1"></i> تصفية
        </button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php
    $where = $filter_user ? "WHERE l.user_id = $filter_user" : "";
    $leaves = $pdo->query("SELECT l.*, u.name as user_name, u.role as user_role FROM leave_requests l LEFT JOIN users u ON l.user_id = u.id $where ORDER BY l.id DESC LIMIT 100")->fetchAll();
    
    if (count($leaves) > 0): foreach ($leaves as $leave): 
        $statusClass = $leave['status'] == 'Approved' ? 'bg-green-50 text-green-700 border-green-100' : ($leave['status'] == 'Rejected' ? 'bg-red-50 text-red-700 border-red-100' : 'bg-amber-50 text-amber-700 border-amber-100');
    ?>
        <div class="aeterna-card mb-0 flex flex-col justify-between border-t-4 <?php echo $leave['status'] == 'Pending' ? 'border-t-amber-400' : ($leave['status'] == 'Approved' ? 'border-t-green-500' : 'border-t-red-500'); ?>">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-stone text-white flex items-center justify-center font-bold text-sm">
                            <?= mb_substr($leave['user_name'] ?? 'U', 0, 1, 'UTF-8') ?>
                        </div>
                        <div>
                            <h4 class="font-bold text-charcoal text-sm leading-tight"><?= htmlspecialchars($leave['user_name'] ?? '-') ?></h4>
                            <p class="text-[10px] text-gray-400"><?= htmlspecialchars($leave['user_role'] ?? '') ?></p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black border <?= $statusClass ?>">
                        <?= $leave['status'] == 'Pending' ? 'انتظار' : ($leave['status'] == 'Approved' ? 'تم الموافقة' : 'تم الرفض') ?>
                    </span>
                </div>

                <div class="bg-gray-50 p-3 rounded-xl mb-4 border border-gray-100">
                    <p class="text-[10px] font-black text-taupe mb-1 uppercase tracking-widest">نوع الطلب</p>
                    <p class="text-sm font-bold text-charcoal">
                        <?php 
                        $types = ['Leave' => 'إجازة اعتيادية', 'Permission' => 'إذن خروج', 'Occasion' => 'مناسبة عائلية'];
                        echo $types[$leave['type']] ?? $leave['type']; 
                        ?>
                    </p>
                </div>

                <div class="space-y-2 mb-6">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <i class="fas fa-calendar-day text-gray-300 w-4"></i>
                        <span>البداية: <b><?= $leave['start_date'] ?></b></span>
                    </div>
                    <?php if($leave['end_date']): ?>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <i class="fas fa-calendar-check text-gray-300 w-4"></i>
                        <span>النهاية: <b><?= $leave['end_date'] ?></b></span>
                    </div>
                    <?php endif; ?>
                    <?php if($leave['reason']): ?>
                    <div class="mt-3 p-3 bg-white border border-dashed border-gray-200 rounded-xl text-[11px] text-gray-500 leading-relaxed italic">
                        " <?= htmlspecialchars($leave['reason']) ?> "
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($leave['status'] === 'Pending'): ?>
                <form method="POST" action="" class="flex gap-2 pt-4 border-t border-gray-50">
                    <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                    <button type="submit" name="leave_action" value="reject" class="flex-1 py-2.5 rounded-xl bg-red-50 text-red-600 font-bold text-xs hover:bg-red-600 hover:text-white transition-all">
                        <i class="fas fa-times ml-1"></i> رفض
                    </button>
                    <button type="submit" name="leave_action" value="approve" class="flex-1 py-2.5 rounded-xl bg-green-500 text-white font-bold text-xs hover:bg-green-600 transition-all shadow-md">
                        <i class="fas fa-check ml-1"></i> موافقة
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; else: ?>
        <div class="col-span-full aeterna-card text-center py-16">
            <i class="fas fa-calendar-times text-5xl text-gray-100 mb-4 block"></i>
            <p class="text-gray-400 font-bold">لا توجد طلبات إجازات مسجلة.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'layout/footer.php'; ?>

<?php
require_once 'config.php';
requireLogin();

if (!can('manage_attendance')) {
    header("Location: dashboard.php");
    exit;
}

$filter_user = $_GET['filter_user'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

// Handle Delete (Admin only)
if (isAdmin() && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $att_id = (int)$_GET['id'];
    moveToTrash('attendance', $att_id);
    $pdo->prepare("DELETE FROM attendance WHERE id = ?")->execute([$att_id]);
    logActivity('حذف سجل حضور', "تم حذف سجل حضور #$att_id ونقله لسلة المهملات.");
    header("Location: admin_attendance.php?success=1");
    exit;
}

$all_users = $pdo->query("SELECT id, name, role FROM users ORDER BY name")->fetchAll();

include 'layout/header.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-charcoal flex items-center gap-2">
            <i class="fas fa-fingerprint text-taupe"></i> سجل حضور وانصراف الموظفين
        </h2>
        <p class="text-xs text-gray-400 mt-1">متابعة دقيقة لمواعيد ومواقع تسجيل الحضور الجغرافي.</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="aeterna-card mb-6 py-4">
    <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase">تصفية بالموظف</label>
            <select name="filter_user" class="w-full py-2.5 px-4 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:ring-2 focus:ring-taupe outline-none">
                <option value="">كل الموظفين</option>
                <?php foreach ($all_users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-black text-gray-400 mb-2 uppercase">تصفية بالتاريخ</label>
            <input type="date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>" class="w-full py-2.5 px-4 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:ring-2 focus:ring-taupe outline-none">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm">
                <i class="fas fa-filter ml-1"></i> تصفية
            </button>
            <?php if($filter_user || $filter_date): ?>
                <a href="admin_attendance.php" class="bg-white border border-gray-200 text-gray-400 hover:text-red-500 px-4 py-2.5 rounded-xl text-sm transition-all flex items-center">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Unified View: Grid on Mobile, Table on Desktop -->
<div class="mb-12">
    <!-- Mobile Grid View -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
        <?php
        $where = [];
        $params = [];
        if ($filter_user) { $where[] = "a.user_id = ?"; $params[] = $filter_user; }
        if ($filter_date) { $where[] = "DATE(a.created_at) = ?"; $params[] = $filter_date; }
        $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
        
        $stmt = $pdo->prepare("SELECT a.*, u.name as user_name, u.role as user_role FROM attendance a LEFT JOIN users u ON a.user_id = u.id $where_sql ORDER BY a.id DESC LIMIT 300");
        $stmt->execute($params);
        $attendances = $stmt->fetchAll();

        foreach ($attendances as $att):
            $isSignIn = $att['action'] == 'sign_in';
        ?>
        <div class="aeterna-card flex flex-col justify-between p-6 border-r-4 <?php echo $isSignIn ? 'border-r-green-500' : 'border-r-red-500'; ?> group">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gray-50 text-charcoal flex items-center justify-center font-black shadow-sm group-hover:bg-charcoal group-hover:text-white transition-all">
                        <?php echo mb_substr($att['user_name'] ?? 'U', 0, 1, 'UTF-8'); ?>
                    </div>
                    <div>
                        <h4 class="font-black text-charcoal text-base leading-tight"><?php echo htmlspecialchars($att['user_name'] ?? '-'); ?></h4>
                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5"><?php echo htmlspecialchars($att['user_role'] ?? ''); ?></span>
                    </div>
                </div>
                <div class="text-left">
                    <span class="px-3 py-1 rounded-lg text-[10px] font-black <?php echo $isSignIn ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'; ?>">
                        <?php echo $isSignIn ? 'حضور' : 'انصراف'; ?>
                    </span>
                </div>
            </div>

            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-50 mb-4 space-y-2">
                <div class="flex items-center justify-between text-[10px] font-bold">
                    <span class="text-gray-400">التوقيت:</span>
                    <span class="text-charcoal" dir="ltr"><?php echo date('h:i A', strtotime($att['created_at'])); ?></span>
                </div>
                <div class="flex items-center justify-between text-[10px] font-bold">
                    <span class="text-gray-400">الموقع:</span>
                    <?php if ($att['is_inside_location'] == 1): ?>
                        <span class="text-green-600">داخل النطاق <i class="fas fa-check-circle ml-1"></i></span>
                    <?php else: ?>
                        <span class="text-red-600">خارج النطاق <i class="fas fa-times-circle ml-1"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center justify-between mt-auto pt-4">
                <div class="text-[10px] text-gray-400 font-bold" dir="ltr">
                    <i class="fas fa-calendar-alt ml-1"></i> <?php echo formatDate($att['created_at']); ?>
                </div>
                <div class="flex items-center gap-2">
                    <a href="https://maps.google.com/?q=<?php echo $att['latitude'].','.$att['longitude']; ?>" target="_blank" class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center border border-blue-100 hover:bg-blue-500 hover:text-white transition-all">
                        <i class="fas fa-map-marker-alt"></i>
                    </a>
                    <?php if(isAdmin()): ?>
                        <a href="admin_attendance.php?action=delete&id=<?php echo $att['id']; ?>" onclick="return confirm('حذف؟');" class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center border border-red-100 hover:bg-red-500 hover:text-white transition-all">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($attendances)): ?>
            <div class="col-span-full py-20 text-center">
                <i class="fas fa-fingerprint text-4xl text-gray-200 mb-4 block"></i>
                <p class="text-gray-400 font-bold">لا توجد سجلات حضور.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden lg:block aeterna-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-[10px] text-gray-500 font-black uppercase">
                        <th class="p-5">الموظف</th>
                        <th class="p-5">التاريخ والوقت</th>
                        <th class="p-5">نوع الحركة</th>
                        <th class="p-5">حالة الموقع</th>
                        <th class="p-5">إحداثيات GPS</th>
                        <th class="p-5 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php
                    foreach ($attendances as $att): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center font-black text-gray-400 border border-gray-100">
                                    <?php echo mb_substr($att['user_name'] ?? 'U', 0, 1, 'UTF-8'); ?>
                                </div>
                                <div>
                                    <div class="font-black text-charcoal text-sm"><?= htmlspecialchars($att['user_name'] ?? '-') ?></div>
                                    <div class="text-[9px] text-gray-400 font-black uppercase tracking-widest mt-0.5"><?= htmlspecialchars($att['user_role'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-5">
                            <div class="text-[10px] text-gray-400 font-bold mb-1" dir="ltr"><?= formatDate($att['created_at']) ?></div>
                            <div class="font-black text-charcoal text-sm" dir="ltr"><?= date('h:i A', strtotime($att['created_at'])) ?></div>
                        </td>
                        <td class="p-5">
                            <?php if ($att['action'] == 'sign_in'): ?>
                                <span class="bg-green-50 text-green-700 px-3 py-1 rounded-lg text-[10px] font-black border border-green-100">حضور</span>
                            <?php else: ?>
                                <span class="bg-red-50 text-red-700 px-3 py-1 rounded-lg text-[10px] font-black border border-red-100">انصراف</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-5">
                            <?php if ($att['is_inside_location'] == 1): ?>
                                <span class="bg-green-50 text-green-700 px-3 py-1 rounded-full text-[9px] font-black border border-green-200">من المقر</span>
                            <?php elseif ($att['is_inside_location'] == 0): ?>
                                <span class="bg-red-50 text-red-700 px-3 py-1 rounded-full text-[9px] font-black border border-red-200">خارج المقر</span>
                            <?php else: ?>
                                <span class="text-gray-400 text-[9px]">غير محدد</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-5 text-[10px] text-gray-400" dir="ltr">
                            <a href="https://maps.google.com/?q=<?= $att['latitude'].','.$att['longitude'] ?>" target="_blank" class="flex items-center gap-2 hover:text-taupe transition-colors">
                                 <i class="fas fa-map-marker-alt text-red-500"></i> GPS الموقع
                            </a>
                        </td>
                        <td class="p-5">
                            <div class="flex justify-center">
                                <?php if(isAdmin()): ?>
                                <a href="admin_attendance.php?action=delete&id=<?= $att['id'] ?>" onclick="return confirm('حذف هذا السجل؟');" class="w-10 h-10 rounded-xl bg-gray-50 text-gray-300 hover:bg-red-50 hover:text-red-500 flex items-center justify-center transition-all border border-gray-100">
                                    <i class="fas fa-trash-alt text-xs"></i>
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

<?php include 'layout/footer.php'; ?>

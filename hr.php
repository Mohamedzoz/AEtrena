<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get some stats for the dashboard
$today = date('Y-m-d');
$stmt_att = $pdo->prepare("SELECT action, created_at FROM attendance WHERE user_id = ? AND DATE(created_at) = ? ORDER BY id DESC LIMIT 1");
$stmt_att->execute([$user_id, $today]);
$last_att = $stmt_att->fetch();

$stmt_leaves = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE user_id = ? AND status = 'Pending'");
$stmt_leaves->execute([$user_id]);
$pending_leaves = $stmt_leaves->fetchColumn();

$stmt_reports = $pdo->prepare("SELECT COUNT(*) FROM site_reports WHERE user_id = ? AND DATE(created_at) = ?");
$stmt_reports->execute([$user_id, $today]);
$today_reports = $stmt_reports->fetchColumn();

include 'layout/header.php';
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-charcoal">مرحباً، <?php echo explode(' ', $_SESSION['user_name'])[0]; ?> 👋</h2>
    <p class="text-gray-400 mt-1">إليك ملخص سريع لنشاطك في شؤون الموظفين اليوم.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
    <!-- Attendance Card -->
    <div class="aeterna-card mb-0 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-green-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center text-xl shadow-sm">
                <i class="fas fa-fingerprint"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400">حالة الحضور اليوم</p>
                <h3 class="text-lg font-black text-charcoal">
                    <?php 
                    if (!$last_att) echo "لم يتم التسجيل";
                    else echo ($last_att['action'] == 'sign_in' ? 'تم الحضور' : 'تم الانصراف');
                    ?>
                </h3>
                <?php if($last_att): ?>
                    <p class="text-[10px] text-gray-400" dir="ltr"><?php echo date('h:i A', strtotime($last_att['created_at'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Leaves Card -->
    <div class="aeterna-card mb-0 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-sm">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400">طلبات انتظار</p>
                <h3 class="text-lg font-black text-charcoal"><?php echo $pending_leaves; ?> طلبات</h3>
                <p class="text-[10px] text-gray-400">قيد المراجعة حالياً</p>
            </div>
        </div>
    </div>

    <!-- Reports Card -->
    <div class="aeterna-card mb-0 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-sm">
                <i class="fas fa-camera"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400">تقارير اليوم</p>
                <h3 class="text-lg font-black text-charcoal"><?php echo $today_reports; ?> تقارير</h3>
                <p class="text-[10px] text-gray-400">تم رفعها اليوم</p>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Links (Big Cards) -->
<h3 class="font-bold text-lg text-charcoal mb-6 flex items-center gap-2">
    <i class="fas fa-th-large text-taupe text-sm"></i> الأقسام والخدمات
</h3>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <a href="hr_attendance.php" class="aeterna-card mb-0 hover:-translate-y-2 hover:shadow-xl transition-all group border-b-4 border-b-green-500">
        <div class="w-14 h-14 rounded-2xl bg-gray-50 text-green-600 flex items-center justify-center text-2xl mb-4 group-hover:bg-green-500 group-hover:text-white transition-colors shadow-sm">
            <i class="fas fa-fingerprint"></i>
        </div>
        <h4 class="font-bold text-charcoal mb-1">تسجيل الحضور</h4>
        <p class="text-xs text-gray-400 leading-relaxed">تبصيم الموقع الجغرافي (GPS) للحضور والانصراف.</p>
    </a>

    <a href="hr_reports.php" class="aeterna-card mb-0 hover:-translate-y-2 hover:shadow-xl transition-all group border-b-4 border-b-blue-500">
        <div class="w-14 h-14 rounded-2xl bg-gray-50 text-blue-600 flex items-center justify-center text-2xl mb-4 group-hover:bg-blue-500 group-hover:text-white transition-colors shadow-sm">
            <i class="fas fa-camera"></i>
        </div>
        <h4 class="font-bold text-charcoal mb-1">تقارير المواقع</h4>
        <p class="text-xs text-gray-400 leading-relaxed">رفع تقارير المواقع اليومية مع الصور والموقع.</p>
    </a>

    <a href="hr_leaves.php" class="aeterna-card mb-0 hover:-translate-y-2 hover:shadow-xl transition-all group border-b-4 border-b-amber-500">
        <div class="w-14 h-14 rounded-2xl bg-gray-50 text-amber-600 flex items-center justify-center text-2xl mb-4 group-hover:bg-amber-500 group-hover:text-white transition-colors shadow-sm">
            <i class="fas fa-calendar-check"></i>
        </div>
        <h4 class="font-bold text-charcoal mb-1">طلبات الإجازات</h4>
        <p class="text-xs text-gray-400 leading-relaxed">تقديم طلبات إجازة أو إذن ومتابعة حالتها.</p>
    </a>

    <a href="hr_history.php" class="aeterna-card mb-0 hover:-translate-y-2 hover:shadow-xl transition-all group border-b-4 border-b-charcoal">
        <div class="w-14 h-14 rounded-2xl bg-gray-50 text-charcoal flex items-center justify-center text-2xl mb-4 group-hover:bg-charcoal group-hover:text-white transition-colors shadow-sm">
            <i class="fas fa-history"></i>
        </div>
        <h4 class="font-bold text-charcoal mb-1">سجل الحركات</h4>
        <p class="text-xs text-gray-400 leading-relaxed">استعراض سجل حضورك وانصرافك الكامل.</p>
    </a>
</div>

<?php include 'layout/footer.php'; ?>
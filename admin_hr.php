<?php
require_once 'config.php';
requireLogin();

if (!can('view_hr_admin')) {
    header("Location: dashboard.php");
    exit;
}

// Stats for the HR Dashboard
$today = date('Y-m-d');
$stmt_att = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE action='sign_in' AND DATE(created_at) = '$today'");
$today_present = $stmt_att->fetchColumn();

$stmt_leaves = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'");
$pending_leaves = $stmt_leaves->fetchColumn();

$stmt_reports = $pdo->query("SELECT COUNT(*) FROM site_reports WHERE DATE(created_at) = '$today'");
$today_reports = $stmt_reports->fetchColumn();

$stmt_users = $pdo->query("SELECT COUNT(*) FROM users");
$total_staff = $stmt_users->fetchColumn();

include 'layout/header.php';
?>

<div class="mb-10 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-black text-charcoal flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-charcoal text-taupe flex items-center justify-center shadow-lg">
                <i class="fas fa-users-cog"></i>
            </div>
            الإدارة المركزية لشؤون الموظفين
        </h2>
        <p class="text-sm text-gray-400 mt-2">مراقبة الأداء، الحضور، الإجازات، والتقارير الميدانية للمؤسسة.</p>
    </div>
</div>

<!-- Key Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <!-- Attendance -->
    <div class="aeterna-card mb-0 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-green-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
        <p class="text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest">الحضور اليوم</p>
        <div class="flex items-end gap-2">
            <span class="text-3xl font-black text-charcoal"><?php echo $today_present; ?></span>
            <span class="text-xs text-green-500 font-bold mb-1">موظف حاضر</span>
        </div>
        <div class="mt-4 flex items-center gap-1">
            <div class="flex-1 h-1 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-green-500" style="width: <?php echo ($total_staff > 0) ? ($today_present/$total_staff)*100 : 0; ?>%"></div>
            </div>
            <span class="text-[9px] text-gray-400 font-bold"><?php echo ($total_staff > 0) ? round(($today_present/$total_staff)*100) : 0; ?>%</span>
        </div>
    </div>

    <!-- Pending Leaves -->
    <div class="aeterna-card mb-0 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-amber-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
        <p class="text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest">طلبات انتظار</p>
        <div class="flex items-end gap-2">
            <span class="text-3xl font-black text-charcoal"><?php echo $pending_leaves; ?></span>
            <span class="text-xs text-amber-500 font-bold mb-1">بانتظار القرار</span>
        </div>
        <div class="mt-4">
            <a href="admin_leaves.php" class="text-[10px] font-bold text-taupe hover:underline">مراجعة الطلبات <i class="fas fa-arrow-left mr-1"></i></a>
        </div>
    </div>

    <!-- Site Reports -->
    <div class="aeterna-card mb-0 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-blue-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
        <p class="text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest">تقارير اليوم</p>
        <div class="flex items-end gap-2">
            <span class="text-3xl font-black text-charcoal"><?php echo $today_reports; ?></span>
            <span class="text-xs text-blue-500 font-bold mb-1">تقرير ميداني</span>
        </div>
        <div class="mt-4">
            <a href="admin_reports.php" class="text-[10px] font-bold text-taupe hover:underline">عرض التقارير <i class="fas fa-arrow-left mr-1"></i></a>
        </div>
    </div>

    <!-- Total Staff -->
    <div class="aeterna-card mb-0 relative overflow-hidden group">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-charcoal/5 rounded-full group-hover:scale-110 transition-transform"></div>
        <p class="text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest">إجمالي القوى العاملة</p>
        <div class="flex items-end gap-2">
            <span class="text-3xl font-black text-charcoal"><?php echo $total_staff; ?></span>
            <span class="text-xs text-gray-400 font-bold mb-1">عضو فريق</span>
        </div>
        <div class="mt-4">
            <a href="users.php" class="text-[10px] font-bold text-taupe hover:underline">إدارة المستخدمين <i class="fas fa-arrow-left mr-1"></i></a>
        </div>
    </div>
</div>

<!-- Navigation to Sub-Modules -->
<h3 class="font-black text-lg text-charcoal mb-6 flex items-center gap-2">
    <i class="fas fa-th-large text-taupe text-sm"></i> الأقسام الإدارية
</h3>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
    <a href="admin_attendance.php" class="aeterna-card mb-0 hover:-translate-y-2 hover:shadow-2xl transition-all group">
        <div class="w-14 h-14 rounded-2xl bg-gray-50 text-taupe flex items-center justify-center text-2xl mb-5 group-hover:bg-charcoal group-hover:text-white transition-all shadow-sm">
            <i class="fas fa-fingerprint"></i>
        </div>
        <h4 class="font-black text-charcoal text-lg mb-2">سجلات الحضور</h4>
        <p class="text-xs text-gray-400 leading-relaxed">استعراض وتحليل مواعيد حضور وانصراف جميع الموظفين جغرافياً.</p>
    </a>

    <a href="admin_leaves.php" class="aeterna-card mb-0 hover:-translate-y-2 hover:shadow-2xl transition-all group">
        <div class="w-14 h-14 rounded-2xl bg-gray-50 text-taupe flex items-center justify-center text-2xl mb-5 group-hover:bg-charcoal group-hover:text-white transition-all shadow-sm">
            <i class="fas fa-calendar-check"></i>
        </div>
        <h4 class="font-black text-charcoal text-lg mb-2">إدارة الإجازات</h4>
        <p class="text-xs text-gray-400 leading-relaxed">بوابة مركزية للموافقة أو الرفض على طلبات الإجازات والأذونات.</p>
    </a>

    <a href="admin_reports.php" class="aeterna-card mb-0 hover:-translate-y-2 hover:shadow-2xl transition-all group">
        <div class="w-14 h-14 rounded-2xl bg-gray-50 text-taupe flex items-center justify-center text-2xl mb-5 group-hover:bg-charcoal group-hover:text-white transition-all shadow-sm">
            <i class="fas fa-camera"></i>
        </div>
        <h4 class="font-black text-charcoal text-lg mb-2">تقارير المواقع</h4>
        <p class="text-xs text-gray-400 leading-relaxed">متابعة الأرشيف البصري والتقارير اليومية للمهندسين من المواقع.</p>
    </a>
</div>

<?php include 'layout/footer.php'; ?>

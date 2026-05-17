<?php
require_once 'config.php';
requireLogin();

$role = $_SESSION['user_role'];
$name = $_SESSION['user_name'];

// ─── Fetch Enhanced Stats ─────────────────────────────
$stats = [
    'active_projects' => 0,
    'late_projects'   => 0,
    'total_revenue'   => 0,
    'total_expenses'  => 0,
    'total_customers' => 0,
    'pending_leaves'  => 0,
    'today_attendance' => 0
];

try {
    // Projects
    $stats['active_projects'] = $pdo->query("SELECT COUNT(*) FROM projects WHERE stage < 8")->fetchColumn();
    $stats['late_projects']   = $pdo->query("SELECT COUNT(*) FROM projects WHERE stage < 8 AND stage_deadline < CURDATE() AND stage_deadline IS NOT NULL")->fetchColumn();
    
    // Finance (Manager/Accountant only)
    if (can('view_finance')) {
        $stats['total_revenue']  = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'Paid'")->fetchColumn() ?: 0;
        $stats['total_expenses'] = $pdo->query("SELECT SUM(amount) FROM expenses")->fetchColumn() ?: 0;
    }

    // CRM
    $stats['total_customers'] = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

    // HR (Admin/Manager only)
    if (can('view_hr_admin')) {
        $stats['pending_leaves']  = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();
        $stats['today_attendance'] = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE action='sign_in' AND DATE(created_at) = CURDATE()")->fetchColumn();
    }
} catch (Exception $e) {}

include 'layout/header.php';
?>

<!-- Executive Welcome -->
<div class="mb-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
    <div>
        <h2 class="text-3xl font-black text-charcoal tracking-tight">مرحباً، <?php echo htmlspecialchars($name); ?> 👋</h2>
        <p class="text-sm text-gray-400 mt-1">نظرة عامة على مؤشرات الأداء الحالية لمؤسسة Aeterna.</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="px-4 py-2 bg-white border border-gray-100 rounded-2xl text-xs font-black text-taupe shadow-sm" dir="ltr">
            <?php echo formatDate(date('Y-m-d')); ?>
        </span>
    </div>
</div>

<!-- Key Performance Indicators (KPIs) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    
    <!-- Projects KPI -->
    <div class="aeterna-card mb-0 group hover:border-taupe transition-all cursor-pointer overflow-hidden relative" onclick="window.location.href='projects.php'">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-taupe/5 rounded-full transition-transform"></div>
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-gray-50 text-charcoal flex items-center justify-center text-lg"><i class="fas fa-project-diagram"></i></div>
            <?php if($stats['late_projects'] > 0): ?>
                <span class="bg-red-50 text-red-600 text-[9px] font-black px-2 py-1 rounded-lg"><?php echo $stats['late_projects']; ?> متأخر</span>
            <?php endif; ?>
        </div>
        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">المشاريع النشطة</h3>
        <p class="text-3xl font-black text-charcoal"><?php echo $stats['active_projects']; ?></p>
        <div class="mt-4 flex items-center gap-1">
            <div class="flex-1 h-1 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-taupe" style="width: 75%"></div>
            </div>
            <span class="text-[9px] text-gray-400 font-bold">In Progress</span>
        </div>
    </div>

    <!-- Finance KPI (Net Cash) -->
    <?php if(can('view_finance')): ?>
    <div class="aeterna-card mb-0 group hover:border-green-500 transition-all cursor-pointer overflow-hidden relative" onclick="window.location.href='finance.php'">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-green-500/5 rounded-full group-hover:scale-150 transition-transform"></div>
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center text-lg"><i class="fas fa-vault"></i></div>
        </div>
        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">صافي التحصيل</h3>
        <p class="text-2xl font-black text-charcoal" dir="ltr">EGP <?php echo number_format($stats['total_revenue'] - $stats['total_expenses']); ?></p>
        <p class="text-[9px] text-green-500 font-bold mt-4"><i class="fas fa-caret-up"></i> إجمالي المقبوضات الفعلية</p>
    </div>
    <?php endif; ?>

    <!-- CRM KPI -->
    <div class="aeterna-card mb-0 group hover:border-blue-500 transition-all cursor-pointer overflow-hidden relative" onclick="window.location.href='customers.php'">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-blue-500/5 rounded-full group-hover:scale-150 transition-transform"></div>
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg"><i class="fas fa-user-tie"></i></div>
        </div>
        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">إجمالي العملاء</h3>
        <p class="text-3xl font-black text-charcoal"><?php echo $stats['total_customers']; ?></p>
        <p class="text-[9px] text-gray-400 font-bold mt-4">سجل الـ CRM المتكامل</p>
    </div>

    <!-- HR KPI -->
    <?php if(can('view_hr_admin')): ?>
    <div class="aeterna-card mb-0 group hover:border-amber-500 transition-all cursor-pointer overflow-hidden relative" onclick="window.location.href='admin_hr.php'">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-amber-500/5 rounded-full group-hover:scale-150 transition-transform"></div>
        <div class="flex justify-between items-start mb-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg"><i class="fas fa-users-cog"></i></div>
            <?php if($stats['pending_leaves'] > 0): ?>
                <span class="bg-amber-100 text-amber-800 text-[9px] font-black px-2 py-1 rounded-lg"><?php echo $stats['pending_leaves']; ?> إجازة</span>
            <?php endif; ?>
        </div>
        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">حضور الموظفين اليوم</h3>
        <p class="text-3xl font-black text-charcoal"><?php echo $stats['today_attendance']; ?></p>
        <p class="text-[9px] text-gray-400 font-bold mt-4">إجمالي فريق العمل</p>
    </div>
    <?php endif; ?>

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
    
    <!-- Admin Alerts / Important Notifications -->
    <div class="<?php echo isAdmin() ? 'lg:col-span-2' : 'lg:col-span-3'; ?>">
        <h3 class="font-black text-lg text-charcoal mb-6 flex items-center gap-2">
            <i class="fas fa-bell text-taupe text-sm"></i> تنبيهات النظام العاجلة
        </h3>
        
        <div class="space-y-4">
            <?php
            $alerts = [];
            
            // Late Projects Alert
            $stmt = $pdo->query("SELECT id, title, stage_deadline FROM projects WHERE stage < 8 AND stage_deadline < CURDATE() AND stage_deadline IS NOT NULL LIMIT 3");
            while($p = $stmt->fetch()) {
                $alerts[] = ['type' => 'danger', 'title' => 'تأخر في تسليم مشروع', 'msg' => "المشروع #{$p['id']} - {$p['title']} تجاوز الموعد المحدد.", 'link' => "project_view.php?id={$p['id']}"];
            }

            // Pending Leaves Alert
            if(can('manage_leaves')) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();
                if($stmt > 0) {
                    $alerts[] = ['type' => 'warning', 'title' => 'طلبات إجازة معلقة', 'msg' => "يوجد $stmt طلبات إجازة بانتظار قرارك الإداري.", 'link' => "admin_leaves.php"];
                }
            }

            if(count($alerts) > 0):
                foreach($alerts as $alert):
                    $color = $alert['type'] == 'danger' ? 'red' : 'amber';
            ?>
                <a href="<?php echo $alert['link']; ?>" class="flex items-center gap-4 p-5 bg-white border border-<?php echo $color; ?>-100 rounded-3xl hover:shadow-xl transition-all group">
                    <div class="w-12 h-12 rounded-2xl bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-600 flex items-center justify-center text-xl shrink-0 group-hover:bg-<?php echo $color; ?>-600 group-hover:text-white transition-all">
                        <i class="fas <?php echo $alert['type'] == 'danger' ? 'fa-exclamation-triangle' : 'fa-clock'; ?>"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-black text-charcoal text-sm"><?php echo $alert['title']; ?></h4>
                        <p class="text-xs text-gray-400 mt-0.5"><?php echo $alert['msg']; ?></p>
                    </div>
                    <i class="fas fa-chevron-left text-gray-200 group-hover:text-<?php echo $color; ?>-600 transition-colors"></i>
                </a>
            <?php endforeach; else: ?>
                <div class="p-10 bg-white border border-dashed border-gray-200 rounded-3xl text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                        <i class="fas fa-check text-green-500 text-2xl"></i>
                    </div>
                    <p class="text-gray-400 text-sm font-bold">لا توجد تنبيهات عاجلة حالياً. كل شيء تحت السيطرة!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity Feed (Admin Only) -->
    <?php if(isAdmin()): ?>
    <div class="lg:col-span-1">
        <h3 class="font-black text-lg text-charcoal mb-6 flex items-center gap-2">
            <i class="fas fa-history text-taupe text-sm"></i> آخر الأنشطة
        </h3>
        
        <div class="aeterna-card p-0 overflow-hidden">
            <div class="divide-y divide-gray-100">
                <?php
                $stmt = $pdo->query("SELECT l.*, u.name as user_name FROM system_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT 6");
                while($log = $stmt->fetch()):
                ?>
                    <div class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 text-charcoal flex items-center justify-center text-[10px] font-black shrink-0">
                                <?php echo mb_substr($log['user_name'] ?? 'SYS', 0, 1, 'UTF-8'); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-charcoal leading-tight"><?php echo htmlspecialchars($log['action']); ?></p>
                                <p class="text-[9px] text-gray-400 mt-1 truncate"><?php echo htmlspecialchars($log['details']); ?></p>
                                <p class="text-[8px] text-taupe font-black mt-1 uppercase" dir="ltr"><?php echo date('H:i', strtotime($log['created_at'])); ?> - <?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="bg-gray-50 p-3 text-center border-t border-gray-100">
                <a href="logs.php" class="text-[10px] font-bold text-gray-400 hover:text-charcoal transition-colors">سجل الأنشطة الكامل <i class="fas fa-external-link-alt mr-1"></i></a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Quick Link Icons Grid -->
<div class="mb-10">
    <h3 class="font-black text-lg text-charcoal mb-6 flex items-center gap-2">
        <i class="fas fa-th text-taupe text-sm"></i> الاختصارات السريعة
    </h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
        <?php
        $links = [
            ['icon' => 'fa-user-plus', 'label' => 'إضافة عميل', 'url' => 'customers.php?action=add', 'can' => 'manage_revenues'],
            ['icon' => 'fa-folder-plus', 'label' => 'مشروع جديد', 'url' => 'project_add.php', 'can' => 'create_project'],
            ['icon' => 'fa-money-bill-wave', 'label' => 'تسجيل إيراد', 'url' => 'finance_revenues.php', 'can' => 'manage_revenues'],
            ['icon' => 'fa-shopping-basket', 'label' => 'تسجيل مصروف', 'url' => 'finance_expenses.php', 'can' => 'manage_expenses'],
            ['icon' => 'fa-camera', 'label' => 'تقرير موقع', 'url' => 'hr_reports.php', 'can' => 'view_reports'],
            ['icon' => 'fa-calendar-plus', 'label' => 'طلب إجازة', 'url' => 'hr_leaves.php', 'can' => 'isLoggedIn'],
        ];

        foreach($links as $link):
            if($link['can'] == 'isLoggedIn' || can($link['can'])):
        ?>
            <a href="<?php echo $link['url']; ?>" class="p-4 bg-white border border-gray-100 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all text-center group">
                <div class="w-10 h-10 rounded-2xl bg-gray-50 text-taupe flex items-center justify-center mx-auto mb-3 group-hover:bg-charcoal group-hover:text-white transition-all shadow-sm">
                    <i class="fas <?php echo $link['icon']; ?>"></i>
                </div>
                <span class="text-[10px] font-black text-charcoal block truncate"><?php echo $link['label']; ?></span>
            </a>
        <?php endif; endforeach; ?>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
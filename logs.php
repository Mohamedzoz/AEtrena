<?php
require_once 'config.php';
requireLogin();

if (!isManagerOrAdmin()) {
    header("Location: dashboard.php");
    exit;
}

// ── Admin-Only Log Actions ─────────────────────────────────
if (isAdmin()) {

    // Delete a single log
    if (isset($_GET['action']) && $_GET['action'] === 'delete_log' && isset($_GET['id'])) {
        $log_id = (int)$_GET['id'];
        $pdo->prepare("DELETE FROM system_logs WHERE id = ?")->execute([$log_id]);
        logActivity('حذف سجل نشاط', "تم حذف سجل رقم #$log_id من السجلات.");
        header("Location: logs.php?success=deleted");
        exit;
    }

    // Clear ALL logs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all_logs'])) {
        $pdo->exec("DELETE FROM system_logs");
        // Re-log the clear action
        logActivity('مسح كامل للسجلات', 'قام الأدمن بمسح جميع سجلات النشاط.');
        header("Location: logs.php?success=cleared");
        exit;
    }

    // Manually add a log
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_action'])) {
        $m_action  = trim($_POST['manual_action']);
        $m_details = trim($_POST['manual_details'] ?? '');
        if ($m_action) {
            logActivity($m_action, $m_details);
        }
        header("Location: logs.php?success=added");
        exit;
    }
}


// ── KPI Stats ──────────────────────────────────────────────
$today = date('Y-m-d');

$total_logs     = $pdo->query("SELECT COUNT(*) FROM system_logs")->fetchColumn();
$today_logs     = $pdo->query("SELECT COUNT(*) FROM system_logs WHERE DATE(created_at) = '$today'")->fetchColumn();
$unique_users   = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM system_logs WHERE user_id IS NOT NULL")->fetchColumn();
$unique_ips     = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM system_logs")->fetchColumn();

// ── Top 7 Active Users ─────────────────────────────────────
$top_users = $pdo->query("
    SELECT u.name, u.role, COUNT(l.id) as total, 
           MAX(l.created_at) as last_seen
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE l.user_id IS NOT NULL
    GROUP BY l.user_id, u.name, u.role
    ORDER BY total DESC
    LIMIT 7
")->fetchAll();

// ── Top Actions ────────────────────────────────────────────
$top_actions = $pdo->query("
    SELECT action, COUNT(*) as cnt
    FROM system_logs
    GROUP BY action
    ORDER BY cnt DESC
    LIMIT 8
")->fetchAll();

// ── Top IPs ────────────────────────────────────────────────
$top_ips = $pdo->query("
    SELECT ip_address, COUNT(*) as cnt, MAX(created_at) as last_seen
    FROM system_logs
    GROUP BY ip_address
    ORDER BY cnt DESC
    LIMIT 6
")->fetchAll();

// ── Last 14 Days Activity ──────────────────────────────────
$daily_activity = $pdo->query("
    SELECT DATE(created_at) as day, COUNT(*) as cnt
    FROM system_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll();

// ── Hourly Distribution (busiest hours) ───────────────────
$hourly = $pdo->query("
    SELECT HOUR(created_at) as hr, COUNT(*) as cnt
    FROM system_logs
    GROUP BY HOUR(created_at)
    ORDER BY hr ASC
")->fetchAll();

// ── Recent Logs ────────────────────────────────────────────
$filter_user   = $_GET['filter_user'] ?? '';
$filter_action = $_GET['filter_action'] ?? '';
$filter_date   = $_GET['filter_date'] ?? '';

$where = []; $params = [];
if ($filter_user)   { $where[] = "l.user_id = ?";               $params[] = $filter_user; }
if ($filter_action) { $where[] = "l.action LIKE ?";             $params[] = "%$filter_action%"; }
if ($filter_date)   { $where[] = "DATE(l.created_at) = ?";      $params[] = $filter_date; }
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt_logs = $pdo->prepare("
    SELECT l.*, u.name as user_name, u.role as user_role
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.id
    $where_sql
    ORDER BY l.id DESC LIMIT 100
");
$stmt_logs->execute($params);
$logs = $stmt_logs->fetchAll();

$all_users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll();

// ── Prepare chart data ─────────────────────────────────────
$days_labels = []; $days_data = [];
foreach ($daily_activity as $d) { $days_labels[] = $d['day']; $days_data[] = $d['cnt']; }

$hours_labels = array_fill(0, 24, 0);
foreach ($hourly as $h) { $hours_labels[(int)$h['hr']] = (int)$h['cnt']; }

$action_labels = array_column($top_actions, 'action');
$action_data   = array_column($top_actions, 'cnt');

include 'layout/header.php';
?>

<style>
.kpi-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); padding: 1.25rem 1.5rem; border: 1px solid rgba(0,0,0,0.03); position: relative; overflow: hidden; }
.kpi-card .kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.kpi-glow::after { content: ''; position: absolute; top: -30px; left: -30px; width: 100px; height: 100px; border-radius: 50%; opacity: 0.07; background: currentColor; }
.chart-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); padding: 1.25rem 1.5rem; border: 1px solid rgba(0,0,0,0.03); }
.rank-badge { width: 24px; height: 24px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 900; flex-shrink: 0; }
</style>

<!-- Page Header -->
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold text-charcoal flex items-center gap-2">
            <div class="w-9 h-9 rounded-xl bg-charcoal flex items-center justify-center text-taupe shadow">
                <i class="fas fa-chart-bar text-base"></i>
            </div>
            تحليلات الأنشطة
        </h2>
        <p class="text-sm text-gray-400 mt-1 mr-12">إحصائيات شاملة لكل تفاعلات النظام</p>
    </div>
    <div class="text-xs text-gray-400 bg-white px-4 py-2 rounded-xl border border-gray-100 shadow-sm">
        <i class="fas fa-sync-alt ml-1 text-taupe"></i> آخر تحديث: <?= formatDateTime(date('Y-m-d H:i:s')) ?>
    </div>
</div>

<!-- Flash Messages -->
<?php if (isset($_GET['success'])): ?>
<?php
$flash_msgs = [
    'deleted' => ['green', 'fa-trash-alt', 'تم حذف السجل بنجاح.'],
    'cleared' => ['red',   'fa-broom',     'تم مسح جميع السجلات.'],
    'added'   => ['blue',  'fa-plus',       'تم إضافة السجل يدوياً.'],
];
$fm = $flash_msgs[$_GET['success']] ?? ['green', 'fa-check', 'تم تنفيذ الإجراء.'];
?>
<div class="bg-<?= $fm[0] ?>-50 border-r-4 border-<?= $fm[0] ?>-500 text-<?= $fm[0] ?>-700 px-4 py-3 rounded-xl shadow-sm mb-5 animate__animated animate__fadeIn flex items-center gap-2">
    <i class="fas <?= $fm[1] ?>"></i> <?= $fm[2] ?>
</div>
<?php endif; ?>

<!-- Admin Control Panel -->
<?php if (isAdmin()): ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

    <!-- Add Log Manually -->
    <div class="aeterna-card mb-0 border-2 border-dashed border-blue-200 bg-blue-50/30">
        <h3 class="font-bold text-sm text-charcoal mb-3 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs"><i class="fas fa-plus"></i></div>
            إضافة سجل يدوي
        </h3>
        <form method="POST" action="logs.php" class="space-y-2">
            <input type="text" name="manual_action" required placeholder="اسم الإجراء (مثال: مراجعة دورية)" 
                   class="w-full py-2 px-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-300">
            <input type="text" name="manual_details" placeholder="التفاصيل (اختياري)"
                   class="w-full py-2 px-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-300">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-xl text-sm transition-colors">
                <i class="fas fa-plus ml-1"></i> إضافة للسجل
            </button>
        </form>
    </div>

    <!-- Danger Zone: Clear All -->
    <div class="aeterna-card mb-0 border-2 border-dashed border-red-200 bg-red-50/30">
        <h3 class="font-bold text-sm text-charcoal mb-3 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-red-100 text-red-600 flex items-center justify-center text-xs"><i class="fas fa-exclamation-triangle"></i></div>
            منطقة الخطر
        </h3>
        <p class="text-xs text-gray-500 mb-3">مسح جميع سجلات النشاط نهائياً. لا يمكن التراجع عن هذه العملية.</p>
        <form method="POST" action="logs.php" onsubmit="return confirm('⚠️ تحذير: سيتم مسح كل السجلات نهائياً. هل أنت متأكد؟');">
            <button type="submit" name="clear_all_logs" value="1"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-xl text-sm transition-colors">
                <i class="fas fa-broom ml-1"></i> مسح كل السجلات
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ── KPI Row ── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="kpi-card kpi-glow flex items-center gap-3">
        <div class="kpi-icon bg-charcoal text-taupe"><i class="fas fa-list-alt"></i></div>
        <div>
            <div class="text-2xl font-black text-charcoal"><?= number_format($total_logs) ?></div>
            <div class="text-xs text-gray-400">إجمالي السجلات</div>
        </div>
    </div>
    <div class="kpi-card kpi-glow flex items-center gap-3">
        <div class="kpi-icon bg-blue-50 text-blue-600"><i class="fas fa-calendar-day"></i></div>
        <div>
            <div class="text-2xl font-black text-blue-600"><?= number_format($today_logs) ?></div>
            <div class="text-xs text-gray-400">نشاط اليوم</div>
        </div>
    </div>
    <div class="kpi-card kpi-glow flex items-center gap-3">
        <div class="kpi-icon bg-green-50 text-green-600"><i class="fas fa-users"></i></div>
        <div>
            <div class="text-2xl font-black text-green-600"><?= $unique_users ?></div>
            <div class="text-xs text-gray-400">مستخدمين نشطين</div>
        </div>
    </div>
    <div class="kpi-card kpi-glow flex items-center gap-3">
        <div class="kpi-icon bg-purple-50 text-purple-600"><i class="fas fa-network-wired"></i></div>
        <div>
            <div class="text-2xl font-black text-purple-600"><?= $unique_ips ?></div>
            <div class="text-xs text-gray-400">عناوين IP فريدة</div>
        </div>
    </div>
</div>

<!-- ── Charts Row ── -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    <!-- Daily Activity (2/3 width) -->
    <div class="chart-card lg:col-span-2">
        <h3 class="font-bold text-charcoal text-sm mb-4 flex items-center gap-2">
            <i class="fas fa-chart-area text-taupe"></i> النشاط اليومي (آخر 14 يوم)
        </h3>
        <canvas id="dailyChart" height="120"></canvas>
    </div>

    <!-- Actions Doughnut (1/3 width) -->
    <div class="chart-card">
        <h3 class="font-bold text-charcoal text-sm mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-taupe"></i> أكثر الإجراءات تكراراً
        </h3>
        <canvas id="actionsChart" height="160"></canvas>
    </div>
</div>

<!-- ── Hourly Heatmap ── -->
<div class="chart-card mb-6">
    <h3 class="font-bold text-charcoal text-sm mb-4 flex items-center gap-2">
        <i class="fas fa-clock text-taupe"></i> توزيع النشاط على مدار اليوم
    </h3>
    <canvas id="hourlyChart" height="60"></canvas>
</div>

<!-- ── Rankings Row ── -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">

    <!-- Top Users -->
    <div class="chart-card">
        <h3 class="font-bold text-charcoal text-sm mb-4 flex items-center gap-2">
            <i class="fas fa-trophy text-amber-400"></i> أكثر المستخدمين نشاطاً
        </h3>
        <?php if ($top_users): ?>
        <?php
        $max_count = $top_users[0]['total'] ?: 1;
        $rank_colors = ['bg-amber-400 text-white','bg-gray-300 text-white','bg-amber-700 text-white'];
        foreach ($top_users as $i => $u):
            $pct = round(($u['total'] / $max_count) * 100);
        ?>
        <div class="mb-3">
            <div class="flex items-center gap-3 mb-1">
                <div class="rank-badge <?= $rank_colors[$i] ?? 'bg-gray-100 text-gray-500' ?>"><?= $i+1 ?></div>
                <div class="flex-1 min-w-0">
                    <span class="font-bold text-charcoal text-sm"><?= htmlspecialchars($u['name']) ?></span>
                    <span class="text-[10px] text-gray-400 mr-1"><?= htmlspecialchars($u['role']) ?></span>
                </div>
                <span class="font-black text-charcoal text-sm"><?= number_format($u['total']) ?></span>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-l from-charcoal to-stone transition-all duration-700"
                     style="width:<?= $pct ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="text-gray-400 text-sm text-center py-6">لا توجد بيانات.</p>
        <?php endif; ?>
    </div>

    <!-- Top IPs -->
    <div class="chart-card">
        <h3 class="font-bold text-charcoal text-sm mb-4 flex items-center gap-2">
            <i class="fas fa-globe text-blue-400"></i> أكثر العناوين زيارةً (IP)
        </h3>
        <?php if ($top_ips): ?>
        <div class="space-y-3">
        <?php
        $max_ip = $top_ips[0]['cnt'] ?: 1;
        foreach ($top_ips as $j => $ip):
            $pct = round(($ip['cnt'] / $max_ip) * 100);
        ?>
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-5 h-5 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] font-black flex-shrink-0"><?= $j+1 ?></span>
                <span class="font-mono text-xs text-gray-600 flex-1" dir="ltr"><?= htmlspecialchars($ip['ip_address']) ?></span>
                <span class="font-bold text-sm text-charcoal"><?= number_format($ip['cnt']) ?></span>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-l from-blue-500 to-blue-300"
                     style="width:<?= $pct ?>%"></div>
            </div>
            <div class="text-[10px] text-gray-400 mt-0.5 text-left" dir="ltr">
                آخر ظهور: <?= formatDateTime($ip['last_seen']) ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-gray-400 text-sm text-center py-6">لا توجد بيانات.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ── Recent Logs Table ── -->
<div class="chart-card">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h3 class="font-bold text-charcoal text-sm flex items-center gap-2">
            <i class="fas fa-history text-taupe"></i> سجل الأنشطة التفصيلي
        </h3>
        <span class="text-xs text-gray-400"><?= count($logs) ?> سجل</span>
    </div>

    <!-- Filters -->
    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <select name="filter_user" class="py-2 px-3 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-taupe">
            <option value="">كل المستخدمين</option>
            <?php foreach ($all_users as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="filter_action" value="<?= htmlspecialchars($filter_action) ?>"
               placeholder="بحث في الإجراء..." class="py-2 px-3 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-taupe min-w-[160px]">
        <input type="date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>"
               class="py-2 px-3 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-taupe">
        <button type="submit" class="btn-primary text-xs px-4 py-2 rounded-xl">
            <i class="fas fa-filter ml-1"></i> تصفية
        </button>
        <?php if ($filter_user || $filter_action || $filter_date): ?>
        <a href="logs.php" class="text-xs text-gray-400 hover:text-red-500 px-3 py-2 rounded-xl border border-gray-200 transition">
            <i class="fas fa-times ml-1"></i> إلغاء
        </a>
        <?php endif; ?>
    </form>

    <div class="mb-12">
        <!-- Mobile Grid View -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
            <?php if (count($logs) > 0): foreach ($logs as $log): 
                $action = $log['action'];
                $colorClass = 'border-r-blue-500';
                if (str_contains($action, 'حذف') || str_contains($action, 'delete')) $colorClass = 'border-r-red-500';
                elseif (str_contains($action, 'إضافة') || str_contains($action, 'إنشاء') || str_contains($action, 'add')) $colorClass = 'border-r-green-500';
                elseif (str_contains($action, 'تعديل') || str_contains($action, 'تحديث') || str_contains($action, 'edit')) $colorClass = 'border-r-amber-500';
            ?>
            <div class="aeterna-card flex flex-col justify-between p-5 border-r-4 <?php echo $colorClass; ?> group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-charcoal text-white flex items-center justify-center font-black shadow-sm">
                            <?php echo $log['user_name'] ? mb_substr($log['user_name'], 0, 1, 'UTF-8') : '<i class="fas fa-robot text-[10px]"></i>'; ?>
                        </div>
                        <div>
                            <h4 class="font-black text-charcoal text-sm leading-tight"><?php echo htmlspecialchars($log['user_name'] ?? 'النظام'); ?></h4>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5"><?php echo htmlspecialchars($log['user_role'] ?? 'System'); ?></span>
                        </div>
                    </div>
                    <?php if(isAdmin()): ?>
                        <a href="logs.php?action=delete_log&id=<?= $log['id'] ?>" onclick="return confirm('حذف؟');" class="text-red-300 hover:text-red-500 transition-colors"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>

                <div class="bg-gray-50/50 p-3 rounded-xl border border-gray-50 mb-4">
                    <div class="text-[11px] font-black text-charcoal mb-1"><?php echo htmlspecialchars($log['action']); ?></div>
                    <div class="text-[10px] text-gray-500 leading-relaxed"><?php echo htmlspecialchars($log['details']); ?></div>
                </div>

                <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-50">
                    <div class="text-[9px] text-gray-400 font-bold" dir="ltr">
                        <i class="fas fa-clock ml-1"></i> <?php echo formatDateTime($log['created_at']); ?>
                    </div>
                    <div class="text-[9px] text-gray-300 font-mono" dir="ltr"><?php echo htmlspecialchars($log['ip_address']); ?></div>
                </div>
            </div>
            <?php endforeach; else: ?>
                <div class="col-span-full py-10 text-center text-gray-400">لا توجد سجلات.</div>
            <?php endif; ?>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-right border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500">
                        <th class="p-3 font-bold">التاريخ والوقت</th>
                        <th class="p-3 font-bold">المستخدم</th>
                        <th class="p-3 font-bold">الإجراء</th>
                        <th class="p-3 font-bold">التفاصيل</th>
                        <th class="p-3 font-bold" dir="ltr">IP</th>
                        <?php if(isAdmin()): ?><th class="p-3"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (count($logs) > 0): foreach ($logs as $log): ?>
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="p-3 text-gray-400 text-[11px] whitespace-nowrap" dir="ltr">
                            <?= formatDateTime($log['created_at']) ?>
                        </td>
                        <td class="p-3">
                            <?php if ($log['user_name']): ?>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-charcoal text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                    <?= mb_substr($log['user_name'], 0, 1, 'UTF-8') ?>
                                </div>
                                <div>
                                    <div class="font-bold text-charcoal text-xs"><?= htmlspecialchars($log['user_name']) ?></div>
                                    <div class="text-[10px] text-gray-400"><?= htmlspecialchars($log['user_role'] ?? '') ?></div>
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-gray-400 text-xs"><i class="fas fa-robot ml-1"></i>نظام</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <?php
                            $action = $log['action'];
                            $color = 'bg-blue-50 text-blue-700 border-blue-100';
                            if (str_contains($action, 'حذف') || str_contains($action, 'delete')) $color = 'bg-red-50 text-red-700 border-red-100';
                            elseif (str_contains($action, 'إضافة') || str_contains($action, 'إنشاء') || str_contains($action, 'add')) $color = 'bg-green-50 text-green-700 border-green-100';
                            elseif (str_contains($action, 'تعديل') || str_contains($action, 'تحديث') || str_contains($action, 'edit')) $color = 'bg-amber-50 text-amber-700 border-amber-100';
                            elseif (str_contains($action, 'تسجيل') || str_contains($action, 'login')) $color = 'bg-purple-50 text-purple-700 border-purple-100';
                            ?>
                            <span class="px-2 py-1 rounded-lg text-[11px] font-bold border <?= $color ?>">
                                <?= htmlspecialchars($action) ?>
                            </span>
                        </td>
                        <td class="p-3 text-gray-500 text-xs max-w-xs truncate">
                            <?= htmlspecialchars($log['details']) ?>
                        </td>
                        <td class="p-3 text-gray-400 text-[10px] font-mono" dir="ltr">
                            <?= htmlspecialchars($log['ip_address']) ?>
                        </td>
                        <?php if(isAdmin()): ?>
                        <td class="p-3">
                            <a href="logs.php?action=delete_log&id=<?= $log['id'] ?>"
                            onclick="return confirm('حذف هذا السجل نهائياً؟');"
                            class="w-7 h-7 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors text-xs" title="حذف السجل">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" class="p-10 text-center text-gray-400">
                            <i class="fas fa-search text-3xl mb-2 text-gray-200 block"></i>
                            لا توجد سجلات تطابق البحث.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const taupe = '#BEB7A9', charcoal = '#1F1E1C', stone = '#5A5551';

// ── Daily Activity Chart ──
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($days_labels) ?>,
        datasets: [{
            label: 'عدد الأحداث',
            data: <?= json_encode($days_data) ?>,
            fill: true,
            tension: 0.4,
            borderColor: charcoal,
            backgroundColor: 'rgba(31,30,28,0.07)',
            pointBackgroundColor: taupe,
            pointRadius: 5,
            pointHoverRadius: 7,
            borderWidth: 2.5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { grid: { color: '#f0f0f0' }, ticks: { stepSize: 1, font: { size: 11 } }, beginAtZero: true }
        }
    }
});

// ── Actions Doughnut ──
new Chart(document.getElementById('actionsChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($action_labels) ?>,
        datasets: [{
            data: <?= json_encode($action_data) ?>,
            backgroundColor: ['#1F1E1C','#3A3734','#5A5551','#7A7470','#A59F9A','#BEB7A9','#D4D0CA','#E8E5E0'],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        cutout: '60%',
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } }
        }
    }
});

// ── Hourly Bar Chart ──
const hours = Array.from({length: 24}, (_, i) => i + ':00');
const hourData = <?= json_encode(array_values($hours_labels)) ?>;
const maxHour = Math.max(...hourData, 1);
new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: hours,
        datasets: [{
            label: 'نشاط',
            data: hourData,
            backgroundColor: hourData.map(v => {
                const opacity = 0.2 + (v / maxHour) * 0.8;
                return `rgba(31,30,28,${opacity.toFixed(2)})`;
            }),
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: { grid: { color: '#f5f5f5' }, ticks: { stepSize: 1, font: { size: 10 } }, beginAtZero: true }
        }
    }
});
</script>

<?php include 'layout/footer.php'; ?>

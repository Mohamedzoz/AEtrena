<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'mark_read') {
        $pdo->prepare("UPDATE users SET last_notif_read_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$_SESSION['user_id']]);
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    if ($_GET['action'] === 'mark_all_read') {
        $pdo->prepare("UPDATE users SET last_notif_read_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$_SESSION['user_id']]);
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    if ($_GET['action'] === 'get') {
        header('Content-Type: application/json');
        $stmt = $pdo->prepare("SELECT id, title, message, link, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$_SESSION['user_id']]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['created_at'] = date('d/m H:i', strtotime($r['created_at']));
        }
        echo json_encode($rows);
        exit;
    }

    if ($_GET['action'] === 'check_new') {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_toast_shown = 0 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $new_notif = $stmt->fetch();
        
        header('Content-Type: application/json');
        if ($new_notif) {
            $pdo->prepare("UPDATE notifications SET is_toast_shown = 1 WHERE id = ?")->execute([$new_notif['id']]);
            echo json_encode(['success' => true, 'notif' => $new_notif]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }
}

// Default: return the notification dropdown HTML
$notifCount = 0;
$lastRead = $pdo->prepare("SELECT last_notif_read_at FROM users WHERE id = ?");
$lastRead->execute([$_SESSION['user_id']]);
$last_read_at = $lastRead->fetchColumn() ?: '1970-01-01 00:00:00';

$notifs = [];

if(can('manage_leaves')) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending' AND created_at > ?");
    $stmt->execute([$last_read_at]);
    $leaves = $stmt->fetchColumn();
    if ($leaves > 0) {
        $notifs[] = [
            'type' => 'leaves',
            'count' => $leaves,
            'title' => "يوجد $leaves طلب إجازة معلق",
            'subtitle' => 'بانتظار المراجعة والاعتماد',
            'link' => 'admin_leaves.php',
            'icon' => 'fas fa-calendar-check',
            'bg' => 'bg-amber-50',
            'text' => 'text-amber-600'
        ];
        $notifCount += $leaves;
    }
}

if(can('view_reports')) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM site_reports WHERE created_at > ?");
    $stmt->execute([$last_read_at]);
    $reps = $stmt->fetchColumn();
    if ($reps > 0) {
        $notifs[] = [
            'type' => 'reports',
            'count' => $reps,
            'title' => "يوجد $reps تقرير موقع جديد",
            'subtitle' => 'تم رفعها منذ آخر مراجعة لك',
            'link' => 'admin_reports.php',
            'icon' => 'fas fa-camera',
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-600'
        ];
        $notifCount += $reps;
    }
}

// Add items from notifications table (e.g. Tasks)
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$personalNotifs = $stmt->fetchAll();
foreach ($personalNotifs as $pn) {
    $notifs[] = [
        'type' => 'personal',
        'count' => 1,
        'title' => $pn['title'],
        'subtitle' => $pn['message'],
        'link' => $pn['link'] ?? '#',
        'icon' => ($pn['type'] == 'task' ? 'fas fa-tasks' : 'fas fa-info-circle'),
        'bg' => ($pn['is_read'] ? 'bg-gray-50' : 'bg-red-50'),
        'text' => ($pn['is_read'] ? 'text-gray-400' : 'text-red-600')
    ];
    if (!$pn['is_read']) $notifCount++;
}
?>

<div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
    <div class="flex flex-col">
        <span class="text-xs font-black text-charcoal">التنبيهات الإدارية</span>
        <span class="text-[8px] font-bold text-gray-400"><?php echo $notifCount; ?> تنبيه جديد</span>
    </div>
    <?php if($notifCount > 0): ?>
        <button onclick="markAllRead(event)" class="text-[9px] font-black text-taupe hover:text-charcoal transition-colors flex items-center gap-1">
            <i class="fas fa-check-double"></i> Read All
        </button>
    <?php endif; ?>
</div>
<div class="max-h-64 overflow-y-auto">
    <?php if($notifCount == 0): ?>
        <div class="p-10 text-center">
            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-bell-slash text-gray-200"></i>
            </div>
            <p class="text-[10px] font-bold text-gray-400">لا توجد تنبيهات جديدة حالياً</p>
        </div>
    <?php else: ?>
        <?php foreach($notifs as $n): ?>
            <a href="<?php echo $n['link']; ?>" class="flex items-center gap-3 p-4 hover:bg-gray-50 border-b border-gray-50 transition-colors">
                <div class="w-8 h-8 rounded-lg <?php echo $n['bg']; ?> <?php echo $n['text']; ?> flex items-center justify-center text-xs shadow-sm"><i class="<?php echo $n['icon']; ?>"></i></div>
                <div class="flex-1">
                    <p class="text-[10px] font-black text-charcoal"><?php echo $n['title']; ?></p>
                    <p class="text-[8px] text-gray-400 mt-0.5"><?php echo $n['subtitle']; ?></p>
                </div>
                <i class="fas fa-chevron-left text-[8px] text-gray-200"></i>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>
    // Update the counter on the bell icon if it exists in parent
    if (window.parentDocument) {
        // This script runs when HTML is injected, so we can update the outer UI
    }
    // We can also return the count as a data attribute or header
</script>

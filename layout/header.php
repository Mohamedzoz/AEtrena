<?php
$sysName    = getSetting('system_name', 'Aeterna ERP');
$fontFamily = getSetting('primary_font', 'Cairo');
$favIcon    = getSetting('favicon_path', '');
$logoPath   = getSetting('logo_path', '');
$currentPage = basename($_SERVER['PHP_SELF']);

// Unread notifications count
$unread_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_count = (int)$stmt->fetchColumn();
} catch(Exception $e){}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#1F1E1C">
    <link rel="manifest" href="manifest.json?v=2">
    <title><?php echo htmlspecialchars($sysName); ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset($favIcon) ?: 'uploads/assets/icon-192.png'; ?>">
    <link rel="apple-touch-icon" href="uploads/assets/icon-192.png">

    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator && !window.Capacitor) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(() => console.log('Aeterna PWA Ready'))
                    .catch(e => console.warn('PWA SW Error:', e));
            });
        }
    </script>

    <!-- ═══ Capacitor Core (native bridge) ══════════════════════ -->
    <!-- يتحمل تلقائياً لو التطبيق شغال جوه APK -->
    <script>
        // تحديد بيئة التشغيل
        window.isCapacitorApp = !!(window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor.isNativePlatform());
    </script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($fontFamily); ?>:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- Lenis Smooth Scroll -->
    <link rel="stylesheet" href="https://unpkg.com/lenis@1.0.45/dist/lenis.css"/>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        taupe: '#BEB7A9',
                        charcoal: '#1F1E1C',
                        graphite: '#3A3734',
                        stone: '#5A5551',
                    },
                    fontFamily: { sans: ['<?php echo $fontFamily; ?>', 'sans-serif'] }
                }
            }
        }
    </script>

    <style>
        :root { --safe-top: env(safe-area-inset-top, 0px); }
        * { -webkit-tap-highlight-color: transparent; }
        body {
            font-family: '<?php echo $fontFamily; ?>', sans-serif;
            background: #f8f8f7;
            overflow-x: hidden;
            overscroll-behavior: none;
        }
        /* Sidebar */
        #sidebar {
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            will-change: transform;
        }
        #sidebar-overlay {
            transition: opacity 0.3s ease;
        }
        /* Cards */
        .aeterna-card {
            background: #fff;
            border: 1px solid #f0efed;
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            margin-bottom: 1.5rem;
        }
        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1cec9; border-radius: 2px; }
        /* Active Nav */
        .nav-item.active { background: #1F1E1C; color: #fff !important; }
        .nav-item.active i { color: #BEB7A9 !important; }
        .nav-item { border-radius: 0.875rem; transition: all 0.15s; }
        .nav-item:hover:not(.active) { background: #f0efed; }
        /* Capacitor safe area */
        .capacitor-safe-top { padding-top: var(--safe-top); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php
// ─── Capacitor GPS Bridge (بعد body مباشرة) ──────────────────────
// نحقن الـ bridge JS للـ native GPS لو التطبيق شغال جوه APK
?>
<script>
// سيتم load الـ Capacitor core تلقائياً من native APK
// هنا نحقن الـ bridge بعد ما يتحمل
document.addEventListener('DOMContentLoaded', function() {
    if (!window.Capacitor) return;
    var s = document.createElement('script');
    s.src = 'www/capacitor-bridge.js';
    s.onerror = function() {
        // fallback: الـ bridge مضمن في الـ APK
    };
    document.head.appendChild(s);
});
</script>

<!-- ═══ Sidebar Overlay ════════════════════════════════════════════ -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-40 hidden opacity-0 md:hidden" onclick="closeSidebar()"></div>

<!-- ═══ SIDEBAR ════════════════════════════════════════════════════ -->
<aside id="sidebar" class="fixed top-0 right-0 h-full w-72 bg-charcoal z-50 flex flex-col translate-x-full md:translate-x-0 md:z-30 capacitor-safe-top" style="padding-top: max(env(safe-area-inset-top,0px), 0px)">

    <!-- Logo -->
    <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between shrink-0">
        <div>
            <?php if($logoPath && file_exists($logoPath)): ?>
                <img src="<?php echo asset($logoPath); ?>" alt="Logo" class="h-8 object-contain">
            <?php else: ?>
                <div class="text-white font-black tracking-widest text-lg">AETERNA</div>
                <div class="text-taupe text-[9px] font-bold tracking-[0.3em] mt-0.5">KITCHEN & DRESSING</div>
            <?php endif; ?>
        </div>
        <button onclick="closeSidebar()" class="md:hidden text-white/50 hover:text-white text-lg">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- User Info -->
    <div class="px-5 py-4 border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-taupe/20 text-taupe flex items-center justify-center font-black text-sm shrink-0">
                <?php echo mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8'); ?>
            </div>
            <div class="min-w-0">
                <p class="text-white text-xs font-black truncate"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                <p class="text-taupe text-[10px] font-bold"><?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?></p>
            </div>
            <?php if($unread_count > 0): ?>
                <span class="mr-auto bg-red-500 text-white text-[9px] font-black rounded-full w-5 h-5 flex items-center justify-center shrink-0"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <?php
        $nav = [
            ['icon'=>'fa-tachometer-alt','label'=>'لوحة التحكم','url'=>'dashboard.php','cap'=>null],
            ['icon'=>'fa-users','label'=>'العملاء','url'=>'customers.php','cap'=>'view_customers'],
            ['icon'=>'fa-project-diagram','label'=>'المشاريع','url'=>'projects.php','cap'=>'view_projects'],
            ['icon'=>'fa-chart-line','label'=>'المالية','url'=>'finance.php','cap'=>'view_finance'],
            ['icon'=>'fa-user-clock','label'=>'الحضور','url'=>'hr_attendance.php','cap'=>null],
            ['icon'=>'fa-calendar-alt','label'=>'الإجازات','url'=>'hr_leaves.php','cap'=>null],
            ['icon'=>'fa-camera','label'=>'تقارير الموقع','url'=>'hr_reports.php','cap'=>'view_reports'],
            ['icon'=>'fa-history','label'=>'سجل حضوري','url'=>'hr_history.php','cap'=>null],
        ];
        if(isManagerOrAdmin()) {
            $nav[] = ['icon'=>'fa-chart-bar','label'=>'تقارير الإدارة','url'=>'admin_reports.php','cap'=>'view_hr_admin'];
            $nav[] = ['icon'=>'fa-user-check','label'=>'إدارة الحضور','url'=>'admin_attendance.php','cap'=>'view_hr_admin'];
            $nav[] = ['icon'=>'fa-archive','label'=>'الأرشيف','url'=>'archive.php','cap'=>'view_reports'];
        }
        if(isAdmin()) {
            $nav[] = ['icon'=>'fa-list-alt','label'=>'سجل الأنشطة','url'=>'logs.php','cap'=>null];
            $nav[] = ['icon'=>'fa-trash','label'=>'المهملات','url'=>'trash.php','cap'=>null];
            $nav[] = ['icon'=>'fa-cog','label'=>'الإعدادات','url'=>'settings.php','cap'=>null];
            $nav[] = ['icon'=>'fa-users-cog','label'=>'الصلاحيات','url'=>'settings_roles.php','cap'=>null];
        }
        foreach($nav as $item):
            if($item['cap'] && !can($item['cap'])) continue;
            $active = ($currentPage === $item['url']) ? 'active text-white' : 'text-white/70';
        ?>
        <a href="<?php echo $item['url']; ?>" class="nav-item <?php echo $active; ?> flex items-center gap-3 px-3 py-2.5 text-sm font-bold">
            <i class="fas <?php echo $item['icon']; ?> w-4 text-center text-taupe text-sm"></i>
            <span><?php echo $item['label']; ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Logout -->
    <div class="px-4 py-4 border-t border-white/10 shrink-0">
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/60 hover:text-red-400 hover:bg-red-400/10 transition-all text-sm font-bold">
            <i class="fas fa-sign-out-alt w-4 text-center"></i>
            <span>تسجيل الخروج</span>
        </a>
    </div>
</aside>

<!-- ═══ MAIN LAYOUT ════════════════════════════════════════════════ -->
<div class="md:mr-72 min-h-screen flex flex-col">

    <!-- Top Bar -->
    <header class="sticky top-0 z-20 bg-white/90 backdrop-blur-md border-b border-gray-100 px-4 md:px-8 py-3 flex items-center justify-between" style="padding-top: max(env(safe-area-inset-top,0px), 12px)">
        <button onclick="openSidebar()" class="md:hidden w-9 h-9 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-charcoal">
            <i class="fas fa-bars text-sm"></i>
        </button>

        <div class="md:hidden font-black text-charcoal tracking-widest text-sm"><?php echo htmlspecialchars($sysName); ?></div>
        <div class="hidden md:block text-sm font-black text-gray-400"><?php echo date('l, d M Y'); ?></div>

        <div class="flex items-center gap-2">
            <!-- AI Button (إذا كانت موجودة) -->
            <?php if(isManagerOrAdmin()): ?>
            <a href="#" onclick="toggleAI()" class="w-9 h-9 rounded-xl bg-charcoal text-taupe flex items-center justify-center text-sm hover:bg-graphite transition-all" title="مساعد AI">
                <i class="fas fa-robot"></i>
            </a>
            <?php endif; ?>

            <!-- Notifications -->
            <a href="#" onclick="toggleNotifications()" class="relative w-9 h-9 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-charcoal">
                <i class="fas fa-bell text-sm"></i>
                <?php if($unread_count > 0): ?>
                    <span class="absolute -top-1 -left-1 w-4 h-4 bg-red-500 text-white text-[9px] font-black rounded-full flex items-center justify-center"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <!-- Page Content -->
    <main class="flex-1 px-4 md:px-8 py-6">

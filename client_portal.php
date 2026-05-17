<?php
require_once 'config.php';

// Simple Client Authentication (by Phone Number)
$client_id = $_SESSION['client_portal_id'] ?? null;

if (isset($_GET['logout'])) {
    unset($_SESSION['client_portal_id']);
    header("Location: client_portal.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_phone'])) {
    $phone = trim($_POST['customer_phone']);
    $stmt = $pdo->prepare("SELECT id, name FROM customers WHERE phone = ?");
    $stmt->execute([$phone]);
    $client = $stmt->fetch();
    
    if ($client) {
        $_SESSION['client_portal_id'] = $client['id'];
        $_SESSION['client_portal_name'] = $client['name'];
        header("Location: client_portal.php");
        exit;
    } else {
        $login_error = "لم نتمكن من العثور على حساب بهذا الرقم. يرجى التأكد والمحاولة مرة أخرى.";
    }
}

// If not logged in, show simple login
if (!$client_id) {
    header("Location: index.php");
    exit;
}

// ---------------------------------------------------------------------
// Client is Logged In
// ---------------------------------------------------------------------

$client_name = $_SESSION['client_portal_name'];

// Fetch Client Data
$stages = [
    1 => 'معاينة', 2 => 'تصميم مبدئي', 3 => 'تعاقد', 4 => 'رسومات تنفيذية',
    5 => 'تصنيع', 6 => 'توريد', 7 => 'تركيب وتسليم', 8 => 'فيدباك'
];

$stmt = $pdo->prepare("SELECT * FROM projects WHERE customer_id = ? ORDER BY id DESC");
$stmt->execute([$client_id]);
$projects = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT p.*, pr.title FROM payments p JOIN projects pr ON p.project_id = pr.id WHERE pr.customer_id = ? ORDER BY p.id DESC");
$stmt->execute([$client_id]);
$payments = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM customer_files WHERE customer_id = ? ORDER BY id DESC");
$stmt->execute([$client_id]);
$files = $stmt->fetchAll();

$total_projects = count($projects);
$total_paid = 0;
$total_paid = 0;
foreach($payments as $p) { if($p['status'] === 'Paid') $total_paid += $p['amount']; }

// Helper for type translation
function getPaymentTypeName($type) {
    $map = [
        'Survey & Design' => 'مصاريف معاينة وتصميم',
        'Down Payment' => 'دفعة مقدمة',
        'Milestone' => 'دفعة مرحلية',
        'Final' => 'دفعة نهائية'
    ];
    return $map[$type] ?? $type;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json?v=3">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Aeterna">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then(reg => {
                    console.log('Aeterna PWA Ready');
                }).catch(err => console.log('PWA Error: ', err));
            });
        }
    </script>
    <title>بوابتي الشاملة | Aeterna</title>
    <?php $favIcon = getSetting('favicon_path', ''); ?>
    <?php if($favIcon): ?>
        <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($favIcon); ?>">
        <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($favIcon); ?>">
    <?php else: ?>
        <link rel="icon" type="image/png" href="uploads/assets/icon-192.png">
        <link rel="apple-touch-icon" href="uploads/assets/icon-192.png">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://unpkg.com/lenis@1.0.45/dist/lenis.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/lenis@1.0.45/dist/lenis.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { taupe: '#BEB7A9', charcoal: '#1F1E1C', graphite: '#3A3734', stone: '#5A5551', lightbg: '#F4F5F7' }, fontFamily: { sans: ['Cairo', 'sans-serif'] } }
            }
        }
    </script>
    <style>
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: #F4F5F7; 
            background-image: linear-gradient(rgba(244, 245, 247, 0.94), rgba(244, 245, 247, 0.94)), url('uploads/assets/marble.png');
            background-size: 300px;
            overscroll-behavior-y: none; /* Disable pull-to-refresh & bounce */
        }
        .aeterna-card { 
            background: #fff; 
            background-image: linear-gradient(rgba(255, 255, 255, 0.96), rgba(255, 255, 255, 0.96)), url('uploads/assets/marble.png');
            background-size: 400px;
            border-radius: 20px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
            padding: 1.5rem; 
            border: 1px solid rgba(0,0,0,0.02); 
            transition: all 0.3s ease; 
        }
        .aeterna-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        
        /* Circular Progress styling based on the test file */
        .progress-circle-wrap { position: relative; width: 80px; height: 80px; }
        .progress-circle-wrap svg { transform: rotate(-90deg); width: 100%; height: 100%; }
        .progress-circle-bg { fill: none; stroke: #f3f4f6; stroke-width: 6; }
        .progress-circle-fill { fill: none; stroke: #BEB7A9; stroke-width: 6; stroke-linecap: round; transition: stroke-dashoffset 1s ease; }
        .progress-label { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; color: #1F1E1C; }
    </style>
</head>
<body class="pb-10">

    <!-- Top Navbar -->
    <nav class="bg-charcoal text-white sticky top-0 z-50 shadow-lg border-b-4 border-taupe">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-2">
                    <div class="bg-gradient-to-tr from-taupe to-[#D3CCBE] w-10 h-10 rounded-xl flex items-center justify-center text-charcoal font-black text-xl shadow-inner">
                        A
                    </div>
                    <div>
                        <h1 class="font-bold tracking-widest text-sm">AETERNA</h1>
                        <p class="text-[10px] text-taupe font-semibold">بوابتي الشاملة</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <!-- PWA Install Button -->
                    <button id="clientInstallBtn" class="hidden bg-taupe text-charcoal px-4 py-1.5 rounded-xl text-xs font-black shadow-lg active:scale-95 transition-all flex items-center gap-2">
                        <i class="fas fa-download"></i> تثبيت التطبيق
                    </button>
                    
                    <div class="hidden sm:flex items-center gap-2 bg-white/10 px-4 py-1.5 rounded-full border border-white/20">
                        <i class="fas fa-user-circle text-taupe"></i>
                        <span class="text-sm font-bold"><?php echo htmlspecialchars($client_name); ?></span>
                    </div>
                    <a href="client_portal.php?logout=1" class="text-red-400 hover:text-red-300 bg-red-400/10 hover:bg-red-400/20 px-3 py-1.5 rounded-lg text-sm font-bold transition-colors">
                        <i class="fas fa-sign-out-alt"></i> خروج
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-l from-charcoal to-graphite rounded-3xl p-8 sm:p-10 text-white mb-8 relative overflow-hidden shadow-2xl border border-white/10 animate__animated animate__fadeInDown">
            <div class="relative z-10">
                <p class="text-taupe font-bold mb-1"><i class="fas fa-hand-sparkles"></i> مرحباً بك في بوابتك الخاصة</p>
                <h2 class="text-3xl sm:text-4xl font-black mb-2"><?php echo htmlspecialchars($client_name); ?></h2>
                <p class="text-gray-300 text-sm max-w-lg leading-relaxed">من هنا يمكنك متابعة تطورات مطبخك/مشروعك لحظة بلحظة، مراجعة دفعاتك المالية، وتحميل تصميمات الـ 3D الخاصة بك بكل شفافية ووضوح.</p>
            </div>
            <i class="fas fa-home absolute -bottom-10 left-10 text-9xl opacity-5 transform -rotate-12"></i>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-xl"><i class="fas fa-project-diagram"></i></div>
                <div>
                    <p class="text-xs text-gray-500 font-bold mb-1">المشاريع</p>
                    <p class="text-xl font-black text-charcoal"><?php echo $total_projects; ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xl shadow-inner"><i class="fas fa-wallet"></i></div>
                <div>
                    <p class="text-xs text-gray-600 font-bold mb-1">المدفوعات</p>
                    <p class="text-xl font-black text-green-700" dir="ltr"><?php echo number_format($total_paid); ?> <span class="text-[10px]">EGP</span></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center text-xl"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <p class="text-xs text-gray-500 font-bold mb-1">الفواتير</p>
                    <p class="text-xl font-black text-charcoal"><?php echo count($payments); ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center text-xl"><i class="fas fa-cloud-download-alt"></i></div>
                <div>
                    <p class="text-xs text-gray-500 font-bold mb-1">المستندات</p>
                    <p class="text-xl font-black text-charcoal"><?php echo count($files); ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Column 1: Projects (Stages) -->
            <div class="lg:col-span-2 space-y-6">
                <h3 class="font-black text-xl text-charcoal flex items-center gap-2">
                    <i class="fas fa-hammer text-taupe"></i> مشاريعي ومراحل التنفيذ
                </h3>
                
                <?php if(empty($projects)): ?>
                    <div class="aeterna-card text-center py-10">
                        <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500 font-bold">لا توجد مشاريع مسجلة حالياً.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($projects as $proj): 
                        $pct = round(($proj['stage'] / 8) * 100);
                        $r = 36; $circ = 2 * pi() * $r;
                        $offset = $circ * (1 - $pct/100);
                    ?>
                    <div class="aeterna-card relative overflow-hidden group animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                        <!-- Status indicator line -->
                        <div class="absolute right-0 top-0 bottom-0 w-1.5 <?php echo $pct >= 100 ? 'bg-green-500' : 'bg-taupe'; ?>"></div>
                        
                        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-6">
                            <div>
                                <h4 class="text-xl font-black text-charcoal mb-1"><?php echo htmlspecialchars($proj['title']); ?></h4>
                                <p class="text-sm text-gray-500 mb-4"><i class="fas fa-calendar-alt ml-1"></i> تاريخ البدء: <?php echo formatDate($proj['created_at']); ?></p>
                                
                                <div class="bg-gray-50 rounded-xl p-3 inline-flex items-center gap-3 border border-gray-100">
                                    <span class="w-8 h-8 rounded-full bg-charcoal text-taupe font-bold flex items-center justify-center text-sm shadow-inner">
                                        <?php echo $proj['stage']; ?>
                                    </span>
                                    <div>
                                        <p class="text-[10px] text-gray-500 font-bold">المرحلة الحالية</p>
                                        <p class="text-sm font-black text-charcoal"><?php echo $stages[$proj['stage']]; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col items-center">
                                <div class="progress-circle-wrap mb-2">
                                    <svg viewBox="0 0 80 80">
                                        <circle class="progress-circle-bg" cx="40" cy="40" r="<?php echo $r; ?>"/>
                                        <circle class="progress-circle-fill" cx="40" cy="40" r="<?php echo $r; ?>" stroke-dasharray="<?php echo $circ; ?>" stroke-dashoffset="<?php echo $offset; ?>"/>
                                    </svg>
                                    <div class="progress-label"><?php echo $pct; ?>%</div>
                                </div>
                                <p class="text-xs font-bold text-gray-400">نسبة الإنجاز</p>
                            </div>
                        </div>
                        
                        <!-- Mini visual timeline -->
                        <div class="mt-6 pt-5 border-t border-gray-100">
                            <div class="flex justify-between items-center relative">
                                <div class="absolute top-1/2 left-0 right-0 h-1 bg-gray-100 -z-10 transform -translate-y-1/2 rounded-full"></div>
                                <!-- active line -->
                                <div class="absolute top-1/2 right-0 h-1 bg-taupe -z-10 transform -translate-y-1/2 rounded-full transition-all duration-1000" style="width: <?php echo $pct; ?>%;"></div>
                                
                                <?php for($i=1; $i<=8; $i++): 
                                    $is_done = $i <= $proj['stage'];
                                    $is_current = $i == $proj['stage'];
                                ?>
                                    <div class="flex flex-col items-center gap-1 group/step">
                                        <div class="w-4 h-4 rounded-full border-2 transition-all duration-300 <?php echo $is_current ? 'border-charcoal bg-taupe scale-125 shadow-md' : ($is_done ? 'border-taupe bg-taupe' : 'border-gray-300 bg-white'); ?>"></div>
                                        <span class="text-[9px] font-bold absolute -bottom-5 opacity-0 group-hover/step:opacity-100 transition-opacity whitespace-nowrap bg-charcoal text-white px-2 py-1 rounded shadow-lg"><?php echo $stages[$i]; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Stage Updates for Client -->
                        <div class="mt-10 pt-6 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-black text-charcoal flex items-center gap-2">
                                    <i class="fas fa-camera-retro text-taupe"></i> توثيق مراحل التنفيذ (صور وتقارير)
                                </h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php
                                $stmt_up = $pdo->prepare("SELECT up.*, users.name as creator_name 
                                                         FROM project_stage_updates up 
                                                         LEFT JOIN users ON up.created_by = users.id 
                                                         WHERE up.project_id = ? 
                                                         ORDER BY up.id DESC LIMIT 4");
                                $stmt_up->execute([$proj['id']]);
                                $updates = $stmt_up->fetchAll();
                                if($updates):
                                    foreach($updates as $up):
                                ?>
                                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 hover:border-taupe/20 transition-all">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-[10px] font-black bg-charcoal text-taupe px-2 py-0.5 rounded">مرحلة: <?php echo $stages[$up['stage']]; ?></span>
                                            <span class="text-[9px] text-gray-400 font-bold"><?php echo htmlspecialchars($up['creator_name'] ?? 'نظام'); ?> - <?php echo date('d-m-Y', strtotime($up['created_at'])); ?></span>
                                        </div>
                                        <?php if($up['notes']): ?>
                                            <p class="text-xs font-bold text-gray-700 mb-3 leading-relaxed"><?php echo nl2br(htmlspecialchars($up['notes'])); ?></p>
                                        <?php endif; ?>
                                        <?php if($up['file_path']): 
                                            $ext = strtolower(pathinfo($up['file_path'], PATHINFO_EXTENSION));
                                            $is_img = in_array($ext, ['jpg','jpeg','png','webp','gif']);
                                            $icon = 'fa-file';
                                            $color = 'text-gray-400';
                                            if($ext == 'pdf') { $icon = 'fa-file-pdf'; $color = 'text-red-500'; }
                                            elseif(in_array($ext, ['doc','docx'])) { $icon = 'fa-file-word'; $color = 'text-blue-500'; }
                                            elseif(in_array($ext, ['xls','xlsx'])) { $icon = 'fa-file-excel'; $color = 'text-green-500'; }
                                        ?>
                                            <div class="rounded-xl overflow-hidden shadow-sm border border-gray-200 bg-white">
                                                <?php if($is_img): ?>
                                                    <img src="<?php echo $up['file_path']; ?>" class="w-full h-48 object-cover cursor-pointer hover:scale-105 transition-transform duration-500" onclick="window.open(this.src, '_blank')">
                                                <?php else: ?>
                                                    <div class="p-3 flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <i class="fas <?php echo $icon; ?> <?php echo $color; ?> text-lg"></i>
                                                            <span class="text-[10px] font-bold text-gray-600 truncate max-w-[100px]"><?php echo basename($up['file_path']); ?></span>
                                                        </div>
                                                        <a href="<?php echo $up['file_path']; ?>" download class="w-6 h-6 rounded bg-gray-50 flex items-center justify-center text-gray-400 hover:text-taupe transition-colors">
                                                            <i class="fas fa-download text-[10px]"></i>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; else: ?>
                                    <p class="text-[11px] text-gray-400 font-bold col-span-full text-center py-4 bg-gray-50/50 rounded-xl border border-dashed">سيتم إضافة صور وتقارير التنفيذ قريباً.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Column 2: Finances & Docs -->
            <div class="space-y-6">
                <!-- Financials -->
                <h3 class="font-black text-xl text-charcoal flex items-center gap-2">
                    <i class="fas fa-receipt text-taupe"></i> سجل المدفوعات
                </h3>
                
                <div class="aeterna-card p-0 overflow-hidden animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                    <?php if(empty($payments)): ?>
                        <p class="text-gray-500 text-sm text-center py-6">لا توجد معاملات مالية.</p>
                    <?php else: ?>
                        <div class="divide-y divide-gray-100">
                            <?php foreach($payments as $pay): ?>
                            <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $pay['status'] === 'Paid' ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600'; ?>">
                                        <i class="fas <?php echo $pay['status'] === 'Paid' ? 'fa-check' : 'fa-clock'; ?>"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-charcoal flex items-center gap-2">
                                            <?php echo getPaymentTypeName($pay['type']); ?>
                                            <?php if($pay['stage']): ?>
                                                <span class="text-[10px] bg-charcoal text-taupe px-2 py-1 rounded font-black">المرحلة: <?php echo $stages[$pay['stage']]; ?></span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-[10px] text-gray-600 font-bold"><?php echo formatDate($pay['status'] === 'Paid' ? $pay['paid_at'] : $pay['due_date']); ?></p>
                                        <?php if(!empty($pay['notes'])): ?>
                                            <p class="text-[10px] text-gray-800 mt-1.5 font-bold leading-tight bg-gray-50 p-1.5 rounded-lg border border-gray-100"><?php echo htmlspecialchars($pay['notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-left flex flex-col items-end gap-2">
                                    <div class="text-right">
                                        <p class="font-black text-charcoal text-base" dir="ltr"><?php echo number_format($pay['amount']); ?> EGP</p>
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded <?php echo $pay['status'] === 'Paid' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'; ?> border <?php echo $pay['status'] === 'Paid' ? 'border-green-200' : 'border-orange-200'; ?>"><?php echo $pay['status'] === 'Paid' ? 'محصلة' : 'مستحقة'; ?></span>
                                    </div>
                                    <?php if($pay['status'] === 'Paid'): ?>
                                        <a href="invoice.php?receipt_id=<?php echo $pay['id']; ?>" target="_blank" class="text-[10px] font-black text-taupe hover:text-charcoal flex items-center gap-1 transition-colors">
                                            <i class="fas fa-file-invoice"></i> عرض الفاتورة
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Documents -->
                <h3 class="font-black text-xl text-charcoal flex items-center gap-2 pt-2">
                    <i class="fas fa-folder-open text-taupe"></i> المستندات والتصميمات
                </h3>
                
                <div class="aeterna-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                    <?php if(empty($files)): ?>
                        <p class="text-gray-500 text-sm text-center py-4">لم يتم إرفاق أي ملفات من قبل الإدارة بعد.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach($files as $file): ?>
                            <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="flex items-center justify-between p-3 border border-gray-100 rounded-xl hover:border-taupe hover:shadow-md transition-all group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center group-hover:bg-taupe group-hover:text-white transition-colors">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-charcoal truncate w-40" dir="ltr"><?php echo htmlspecialchars($file['file_name']); ?></p>
                                        <p class="text-[10px] text-gray-400"><?php echo formatDate($file['uploaded_at']); ?></p>
                                    </div>
                                </div>
                                <i class="fas fa-download text-gray-300 group-hover:text-taupe transition-colors"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
        <!-- Support Section -->
        <div class="mt-8 bg-white border border-gray-200 rounded-3xl p-6 flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center text-charcoal text-2xl border border-gray-100">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <h4 class="font-black text-charcoal">تحتاج إلى مساعدة أو لديك استفسار؟</h4>
                    <p class="text-sm text-gray-500">فريق خدمة العملاء لدينا مستعد للرد على جميع استفساراتك.</p>
                </div>
            </div>
            <a href="https://wa.me/201234567890" target="_blank" class="bg-gradient-to-r from-green-600 to-green-500 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all flex items-center gap-2 whitespace-nowrap">
                تواصل عبر واتساب <i class="fab fa-whatsapp text-xl"></i>
            </a>
        </div>
        
    </div>

    <script>
        // Lenis Smooth Scroll
        const lenis = new Lenis()
        function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
        requestAnimationFrame(raf);

        // GSAP Reveal
        gsap.registerPlugin(ScrollTrigger);
        document.querySelectorAll('.aeterna-card, .animate__animated').forEach((el) => {
            gsap.from(el, {
                scrollTrigger: { trigger: el, start: "top 90%", toggleActions: "play none none none" },
                y: 30, opacity: 0, duration: 0.8, ease: "power2.out"
            });
        });
        
        let deferredPrompt;
        const isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        async function handleInstall() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') deferredPrompt = null;
            } else {
                // Show manual guide for iOS or fallback
                document.getElementById('pwaManualModal')?.classList.remove('hidden');
            }
        }
    </script>
    <!-- Floating Install Button for Android/Everyone -->
    <button onclick="handleInstall()" class="fixed bottom-24 left-6 z-[999] bg-taupe text-charcoal w-14 h-14 rounded-2xl shadow-[0_10px_30px_rgba(190,183,169,0.4)] flex items-center justify-center animate__animated animate__bounceInUp animate__infinite animate__slow" style="animation-duration: 4s;">
        <i class="fas fa-download text-xl"></i>
        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-black">1</span>
    </button>
</body>
</html>

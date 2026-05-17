<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = $_POST['email'] ?? ''; // Can be email, phone, or customer_code
    $password = $_POST['password'] ?? '';
    
    if (empty($login_input) || empty($password)) {
        $error = 'الرجاء إدخال بيانات الدخول وكلمة المرور.';
    } else {
        // Try Admin/Employee Login (email, username, or user_code)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ? OR user_code = ?");
        $stmt->execute([$login_input, $login_input, $login_input]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            logActivity('تسجيل الدخول', 'قام ' . $user['name'] . ' بتسجيل الدخول للنظام.');
            header("Location: dashboard.php");
            exit;
        } else {
            // Not an employee, check if it's a customer (customers table)
            // They can login with phone, customer_code, or username
            $stmt_cust = $pdo->prepare("SELECT id, name, password_hash FROM customers WHERE phone = ? OR customer_code = ? OR username = ?");
            $stmt_cust->execute([$login_input, $login_input, $login_input]);
            $customer = $stmt_cust->fetch();
            
            if ($customer && $customer['password_hash'] && password_verify($password, $customer['password_hash'])) {
                $_SESSION['client_portal_id'] = $customer['id'];
                $_SESSION['client_portal_name'] = $customer['name'];
                
                try {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (NULL, 'دخول عميل', ?, ?)");
                    $stmt->execute(['قام العميل ' . $customer['name'] . ' بتسجيل الدخول للنظام.', $ip]);
                } catch(Exception $e){}
                
                header("Location: customer_dashboard.php");
                exit;
            } else {
                $error = 'بيانات الدخول أو كلمة المرور غير صحيحة.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json?v=2">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then(reg => {
                    console.log('Aeterna PWA Ready');
                }).catch(err => console.log('PWA Error: ', err));
            });
        }
    </script>
    <?php
    $sysName = getSetting('system_name', 'Aeterna ERP');
    $fontFamily = getSetting('primary_font', 'Cairo');
    $favIcon = getSetting('favicon_path', '');
    $logoPath = getSetting('logo_path', '');
    ?>
    <title>تسجيل الدخول | <?php echo htmlspecialchars($sysName); ?></title>
    
    <link rel="icon" type="image/png" href="<?php echo asset($favIcon) ?: 'uploads/assets/icon-192.png'; ?>">
    <link rel="apple-touch-icon" href="uploads/assets/icon-192.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=<?php echo $fontFamily; ?>:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
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
        body { 
            font-family: '<?php echo $fontFamily; ?>', sans-serif; 
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior-y: none; /* Disable pull-to-refresh & bounce */
            -webkit-tap-highlight-color: transparent;
        }
        .glass-panel {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .bg-pattern {
            background-color: #1F1E1C;
        }

        /* Smooth Input Focus */
        input:focus {
            border-color: #BEB7A9;
            box-shadow: 0 0 0 3px rgba(190, 183, 169, 0.1);
        }
        
        .login-btn:active { transform: scale(0.98); }
    </style>
    <style>
        /* Mobile specific adjustments */
        @media (max-width: 640px) {
            body { 
                padding: 1rem !important; 
            }
            .glass-panel {
                border-radius: 1.5rem !important;
                width: 100% !important;
            }
            input {
                font-size: 16px !important;
                padding: 0.8rem 1rem !important;
                border-radius: 0.75rem !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-pattern flex items-center justify-center overflow-x-hidden p-4">

    <div class="glass-panel rounded-3xl shadow-xl w-full max-w-4xl overflow-hidden flex flex-col md:flex-row relative z-10">
        
        <!-- Branding Side (Desktop Only) -->
        <div class="hidden md:flex w-1/2 bg-charcoal p-12 flex-col justify-between relative overflow-hidden">
            <div class="absolute inset-0 bg-charcoal"></div>
            
            <div class="relative z-10">
                <?php if($logoPath): ?>
                    <div class="logo-container inline-block relative mb-8">
                        <img src="<?php echo asset($logoPath); ?>" alt="Logo" class="max-h-28 relative z-10 object-contain">
                    </div>
                <?php else: ?>
                    <h1 class="text-4xl font-black tracking-widest text-white mb-2">
                        <?php 
                            $words = explode(' ', $sysName);
                            if(count($words) > 1) {
                                echo '<span class="text-taupe">'.mb_substr($words[0],0,1,'UTF-8').'</span> ' . htmlspecialchars(substr($sysName, 1));
                            } else {
                                echo htmlspecialchars($sysName);
                            }
                        ?>
                    </h1>
                <?php endif; ?>
                <p class="text-stone font-semibold tracking-wider">KITCHEN & DRESSING</p>
            </div>
            
            <div class="relative z-10 mt-12 bg-white/5 p-8 rounded-3xl border border-white/10">
                <h2 class="text-3xl font-bold text-white mb-4 leading-tight">Modern Living,<br>Beautifully Crafted.</h2>
                <p class="text-gray-300/80 text-sm leading-relaxed font-light">
                    نظام الإدارة المتكامل (ERP) لتتبع المشاريع، العملاء، شؤون الموظفين، والمالية. مصمم خصيصاً ليتناسب مع جودة وحرفية إيتيرنا.
                </p>
            </div>
            
            <div class="relative z-10 mt-12 flex items-center gap-3 text-taupe text-sm font-semibold">
                <i class="fas fa-shield-alt text-lg"></i>
                <span>نظام آمن ومشفّر بالكامل</span>
            </div>
        </div>

        <!-- Login Form Side -->
        <div class="w-full md:w-1/2 p-8 sm:p-12 md:p-16 bg-white flex flex-col justify-center relative overflow-hidden">
            <!-- Mobile subtle pattern inside form -->
            <div class="absolute top-0 right-0 w-full h-32 bg-gradient-to-b from-gray-50 to-transparent pointer-events-none opacity-50 md:hidden"></div>
            
            <!-- Mobile Logo (Shows only on small screens) -->
            <div class="md:hidden text-center mb-10">
                <?php if($logoPath): ?>
                    <div class="relative inline-block">
                        <img src="<?php echo asset($logoPath); ?>" alt="Logo" class="max-h-20 mx-auto relative z-10 object-contain">
                    </div>
                <?php else: ?>
                    <h1 class="text-4xl font-black tracking-widest text-charcoal">
                        <?php 
                            if(count($words) > 1) {
                                echo '<span class="text-taupe">'.mb_substr($words[0],0,1,'UTF-8').'</span> ' . htmlspecialchars(substr($sysName, 1));
                            } else {
                                echo htmlspecialchars($sysName);
                            }
                        ?>
                    </h1>
                <?php endif; ?>
            </div>

            <div class="mb-10 text-center md:text-right">
                <h2 class="text-3xl font-black text-charcoal mb-3 tracking-tight">مرحباً بك</h2>
                <p class="text-gray-500 font-medium">يرجى تسجيل الدخول للوصول إلى لوحة التحكم.</p>
            </div>
            
            <?php if (isset($_GET['reason']) && $_GET['reason'] === 'timeout'): ?>
                <div class="bg-amber-50 border-r-4 border-amber-500 text-amber-800 px-4 py-3 rounded mb-6">
                    <i class="fas fa-clock ml-2"></i> انتهت مهلة الجلسة بسبب عدم النشاط. يرجى تسجيل الدخول مرة أخرى.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle ml-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="space-y-5">
                <div>
                    <label for="email" class="block text-charcoal text-xs font-black uppercase tracking-widest mb-3 pr-1">اسم المستخدم / الكود</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-300 group-focus-within:text-taupe transition-colors">
                            <i class="fas fa-user-circle text-lg"></i>
                        </div>
                        <input type="text" name="email" id="email" class="bg-gray-50/50 border border-gray-100 text-gray-900 text-sm block w-full pr-12 p-4 transition-all duration-300 outline-none focus:bg-white" dir="ltr" placeholder="EMP-1001" required>
                    </div>
                </div>
                <div>
                    <label for="password" class="block text-charcoal text-xs font-black uppercase tracking-widest mb-3 pr-1">كلمة المرور</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-300 group-focus-within:text-taupe transition-colors">
                            <i class="fas fa-key text-lg"></i>
                        </div>
                        <input type="password" name="password" id="password" class="bg-gray-50/50 border border-gray-100 text-gray-900 text-sm block w-full pr-12 p-4 transition-all duration-300 outline-none focus:bg-white" dir="ltr" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-2">
                    <label class="flex items-center text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-taupe focus:ring-taupe ml-2">
                        تذكرني
                    </label>
                    <a href="#" class="text-sm text-taupe hover:text-charcoal font-semibold transition-colors">نسيت كلمة المرور؟</a>
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="w-full bg-gradient-to-r from-charcoal to-graphite text-white font-black rounded-2xl py-4 hover:shadow-[0_10px_30px_rgba(0,0,0,0.2)] hover:-translate-y-1 active:scale-[0.98] transition-all duration-300 flex justify-center items-center gap-3 mt-2 shadow-lg">
                        <span>تسجيل الدخول</span> 
                        <i class="fas fa-arrow-left text-xs bg-white/10 w-6 h-6 rounded-full flex items-center justify-center"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>

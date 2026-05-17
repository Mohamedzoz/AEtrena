<?php
require_once 'config.php';
requireLogin();

// Only Managers/Admins can access settings
if (!isManagerOrAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$success = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated_count = 0;
    
    // Handle standard text/select settings
    $text_settings = [
        'system_name', 'company_phone', 'company_address', 
        'primary_font', 'tax_rate', 'currency', 
        'gps_mandatory', 'projects_mandatory_checklist',
        'workplace_lat', 'workplace_lng', 'workplace_radius',
        'whatsapp_number', 'hotline', 'company_email', 'facebook_link',
        'session_timeout', 'gemini_api_key', 'show_ai_assistant'
    ];
    
    foreach ($text_settings as $key) {
        if (isset($_POST[$key])) {
            $val = $_POST[$key];
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $val, $val]);
            $updated_count++;
        }
    }
    
    // Handle File Uploads (Logo & Favicon)
    $upload_dir = 'uploads/settings/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logo_path = $upload_dir . 'logo.' . $ext;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('logo_path', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$logo_path, $logo_path]);
            $updated_count++;
        }
    }
    
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
        $ext = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
        $fav_path = $upload_dir . 'favicon.' . $ext;
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $fav_path)) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('favicon_path', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$fav_path, $fav_path]);
            $updated_count++;
        }
    }
    
    if ($updated_count > 0) {
        logActivity('تحديث الإعدادات', 'قام المدير بتحديث إعدادات النظام الرئيسية.');
        $_SESSION['flash_success'] = "تم حفظ الإعدادات بنجاح!";
        header("Location: settings.php");
        exit;
    }
}

// Fetch all settings for the form
$stmt = $pdo->query("SELECT * FROM system_settings");
$current_settings = [];
while ($row = $stmt->fetch()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

function val($key, $default = '', $arr = []) {
    return isset($arr[$key]) ? htmlspecialchars($arr[$key]) : $default;
}

include 'layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-charcoal"><i class="fas fa-cogs text-taupe ml-2"></i> إعدادات المنصة (Control Panel)</h2>
    <button type="submit" form="settingsForm" class="btn-primary flex items-center gap-2 px-6 py-2.5 text-sm shadow-xl active:scale-95 transition-all">
        <i class="fas fa-save"></i>
        <span>حفظ التغييرات</span>
    </button>
</div>

<?php if ($success): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded shadow-sm mb-6 animate__animated animate__fadeIn">
        <i class="fas fa-check-circle ml-1"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<form id="settingsForm" method="POST" action="settings.php" enctype="multipart/form-data">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- General & Branding -->
        <div class="lg:col-span-2 space-y-6">
            <div class="aeterna-card mb-0">
                <h3 class="font-bold text-lg text-charcoal mb-4 border-b pb-2"><i class="fas fa-paint-brush text-taupe ml-2"></i> الهوية البصرية والعامة</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">اسم المنصة / الشركة</label>
                        <input type="text" name="system_name" value="<?php echo val('system_name', 'Aeterna ERP', $current_settings); ?>" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">الخط الأساسي للنظام (Google Fonts)</label>
                        <select name="primary_font" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50">
                            <option value="Cairo" <?php echo val('primary_font', '', $current_settings) == 'Cairo' ? 'selected' : ''; ?>>Cairo (الافتراضي)</option>
                            <option value="Almarai" <?php echo val('primary_font', '', $current_settings) == 'Almarai' ? 'selected' : ''; ?>>Almarai</option>
                            <option value="Tajawal" <?php echo val('primary_font', '', $current_settings) == 'Tajawal' ? 'selected' : ''; ?>>Tajawal</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">شعار المنصة (Logo)</label>
                        <input type="file" name="logo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-charcoal hover:file:bg-gray-200">
                        <?php if(!empty($current_settings['logo_path'])): ?>
                            <img src="<?php echo asset($current_settings['logo_path']); ?>" class="h-10 mt-2 rounded">
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">أيقونة المتصفح (Favicon)</label>
                        <input type="file" name="favicon" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-charcoal hover:file:bg-gray-200">
                        <?php if(!empty($current_settings['favicon_path'])): ?>
                            <img src="<?php echo asset($current_settings['favicon_path']); ?>" class="h-8 w-8 mt-2 rounded">
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">رقم التواصل العادي</label>
                        <input type="text" name="company_phone" dir="ltr" value="<?php echo val('company_phone', '', $current_settings); ?>" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">رقم الواتساب (WhatsApp)</label>
                        <input type="text" name="whatsapp_number" dir="ltr" placeholder="مثال: 201xxxxxxxxx" value="<?php echo val('whatsapp_number', '', $current_settings); ?>" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">الخط الساخن (Hotline)</label>
                        <input type="text" name="hotline" dir="ltr" value="<?php echo val('hotline', '', $current_settings); ?>" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني للشركة</label>
                        <input type="email" name="company_email" dir="ltr" value="<?php echo val('company_email', '', $current_settings); ?>" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">رابط صفحة فيسبوك</label>
                        <input type="url" name="facebook_link" dir="ltr" value="<?php echo val('facebook_link', '', $current_settings); ?>" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">عنوان الشركة (المقر الرئيسي)</label>
                        <input type="text" name="company_address" value="<?php echo val('company_address', '', $current_settings); ?>" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50">
                    </div>
                </div>
            </div>

            <div class="aeterna-card mb-0">
                <h3 class="font-bold text-lg text-charcoal mb-4 border-b pb-2"><i class="fas fa-file-invoice-dollar text-taupe ml-2"></i> إعدادات الحسابات والمالية</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">نسبة ضريبة القيمة المضافة (VAT %)</label>
                        <input type="number" step="0.1" name="tax_rate" dir="ltr" value="<?php echo val('tax_rate', '14', $current_settings); ?>" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">العملة الافتراضية</label>
                        <select name="currency" class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50">
                            <option value="EGP" <?php echo val('currency', 'EGP', $current_settings) == 'EGP' ? 'selected' : ''; ?>>جنيه مصري (EGP)</option>
                            <option value="USD" <?php echo val('currency', '', $current_settings) == 'USD' ? 'selected' : ''; ?>>دولار أمريكي (USD)</option>
                            <option value="SAR" <?php echo val('currency', '', $current_settings) == 'SAR' ? 'selected' : ''; ?>>ريال سعودي (SAR)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Logic & Workflow -->
        <div class="space-y-6">
            <!-- Geofencing Settings -->
            <div class="aeterna-card mb-0 bg-white shadow-sm border border-gray-100">
                <h3 class="font-bold text-lg text-charcoal mb-4 border-b pb-2"><i class="fas fa-map-marker-alt text-taupe ml-2"></i> الموقع الجغرافي للمقر (Geofencing)</h3>
                <p class="text-xs text-gray-500 mb-4">يُستخدم لتحديد ما إذا كان الموظف داخل أو خارج المقر عند تسجيل الحضور والانصراف.</p>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-1">خط العرض (Latitude)</label>
                        <input type="text" id="wp_lat" name="workplace_lat" value="<?php echo val('workplace_lat', '', $current_settings); ?>" dir="ltr" class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-1">خط الطول (Longitude)</label>
                        <input type="text" id="wp_lng" name="workplace_lng" value="<?php echo val('workplace_lng', '', $current_settings); ?>" dir="ltr" class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-xs font-bold mb-1">النطاق المسموح (بالمتر)</label>
                    <input type="number" name="workplace_radius" value="<?php echo val('workplace_radius', '100', $current_settings); ?>" dir="ltr" class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-taupe bg-gray-50 text-left" placeholder="مثال: 100">
                </div>

                <button type="button" onclick="getCurrentCoords()" class="w-full bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold py-2 rounded-lg text-sm transition-colors border border-blue-200">
                    <i class="fas fa-location-crosshairs ml-1"></i> سحب إحداثياتي الحالية
                </button>
                
                <script>
                function getCurrentCoords() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            document.getElementById('wp_lat').value = position.coords.latitude;
                            document.getElementById('wp_lng').value = position.coords.longitude;
                            showNotification('تم التقاط الإحداثيات بنجاح!', 'success');
                        }, function(error) {
                            alert('فشل الحصول على الموقع. تأكد من تفعيل الـ GPS في متصفحك.');
                        });
                    } else {
                        alert("المتصفح لا يدعم تحديد الموقع.");
                    }
                }
                </script>
            </div>
            <div class="aeterna-card mb-0 bg-gray-800 text-white border-0 shadow-xl">
                <h3 class="font-bold text-lg mb-4 border-b border-gray-700 pb-2"><i class="fas fa-cogs text-taupe ml-2"></i> محرك النظام (System Logic)</h3>
                
                <div class="mb-5">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="block text-sm font-bold">إلزامية تسجيل الـ GPS</span>
                            <span class="block text-[10px] text-gray-400">منع الموظفين من تسجيل الحضور خارج نطاق العمل</span>
                        </div>
                        <div class="relative">
                            <select name="gps_mandatory" class="py-1 px-2 text-xs bg-gray-700 border-0 rounded text-white focus:ring-taupe">
                                <option value="1" <?php echo val('gps_mandatory', '1', $current_settings) == '1' ? 'selected' : ''; ?>>مفعل</option>
                                <option value="0" <?php echo val('gps_mandatory', '1', $current_settings) == '0' ? 'selected' : ''; ?>>معطل</option>
                            </select>
                        </div>
                    </label>
                </div>
                
                <div class="mb-5">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="block text-sm font-bold">التحقق الصارم للمشاريع</span>
                            <span class="block text-[10px] text-gray-400">منع انتقال المشروع للمرحلة التالية قبل إنهاء المهام (Checklists)</span>
                        </div>
                        <div class="relative">
                            <select name="projects_mandatory_checklist" class="py-1 px-2 text-xs bg-gray-700 border-0 rounded text-white focus:ring-taupe">
                                <option value="1" <?php echo val('projects_mandatory_checklist', '1', $current_settings) == '1' ? 'selected' : ''; ?>>مفعل</option>
                                <option value="0" <?php echo val('projects_mandatory_checklist', '1', $current_settings) == '0' ? 'selected' : ''; ?>>معطل</option>
                            </select>
                        </div>
                    </label>
                </div>

                <!-- Session Timeout -->
                <div class="mb-5 pt-4 border-t border-gray-700">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-7 h-7 rounded-lg bg-amber-500/20 flex items-center justify-center">
                            <i class="fas fa-clock text-amber-400 text-xs"></i>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-white">تسجيل الخروج التلقائي</span>
                            <span class="block text-[10px] text-gray-400">مدة الخمول قبل تسجيل الخروج تلقائياً</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <select name="session_timeout" class="flex-1 py-2 px-3 text-sm bg-gray-700 border border-gray-600 rounded-xl text-white focus:ring-2 focus:ring-amber-400 focus:outline-none">
                            <option value="10"  <?= val('session_timeout','30',$current_settings)=='10'  ?'selected':'' ?>>10 دقائق</option>
                            <option value="15"  <?= val('session_timeout','30',$current_settings)=='15'  ?'selected':'' ?>>15 دقيقة</option>
                            <option value="30"  <?= val('session_timeout','30',$current_settings)=='30'  ?'selected':'' ?>>30 دقيقة (افتراضي)</option>
                            <option value="60"  <?= val('session_timeout','30',$current_settings)=='60'  ?'selected':'' ?>>ساعة كاملة</option>
                            <option value="120" <?= val('session_timeout','30',$current_settings)=='120' ?'selected':'' ?>>ساعتان</option>
                            <option value="480" <?= val('session_timeout','30',$current_settings)=='480' ?'selected':'' ?>>8 ساعات (يوم عمل)</option>
                        </select>
                        <div class="text-amber-400 text-lg">⏱️</div>
                    </div>
                    <p class="text-[10px] text-gray-500 mt-2">
                        <i class="fas fa-shield-alt ml-1 text-amber-400/60"></i>
                        تحذير يظهر قبل الخروج بدقيقتين — بعدها يخرج تلقائياً
                    </p>
                </div>

                
            </div>

            <!-- AI Settings Section - Premium Glassmorphism Look -->
            <div class="mt-8 rounded-3xl overflow-hidden shadow-2xl relative">
                <!-- Glowing Background Effect -->
                <div class="absolute inset-0 bg-gradient-to-br from-[#4f46e5] via-[#7c3aed] to-[#db2777] opacity-90"></div>
                
                <div class="relative p-8 backdrop-blur-md border border-white/20">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-xl border border-white/30 shadow-inner">
                                <i class="fas fa-robot text-white text-xl animate-bounce"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-xl text-white tracking-tight">مساعد Aeterna الذكي </h3>
                                <p class="text-xs text-white/70">تحليل البيانات والذكاء الاصطناعي بواسطة Gemini</p>
                            </div>
                        </div>
                        <!-- Status Badge -->
                        <div class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?php echo val('show_ai_assistant', '1', $current_settings) == '1' ? 'bg-green-400/20 text-green-300 border border-green-400/30' : 'bg-red-400/20 text-red-300 border border-red-400/30'; ?>">
                            <?php echo val('show_ai_assistant', '1', $current_settings) == '1' ? '● Active' : '● Hidden'; ?>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Toggle Switch Design -->
                        <div class="flex items-center justify-between p-4 bg-black/20 rounded-2xl border border-white/10 hover:bg-black/30 transition-all">
                            <div>
                                <span class="block text-sm font-black text-white">حالة ظهور المساعد</span>
                                <span class="block text-[10px] text-white/50">تفعيل أو إخفاء أيقونة الذكاء الاصطناعي من الموقع</span>
                            </div>
                            <div class="flex bg-white/10 p-1 rounded-xl">
                                <button type="button" onclick="document.getElementById('ai_toggle_select').value='1'; this.form.submit();" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all <?php echo val('show_ai_assistant', '1', $current_settings) == '1' ? 'bg-white text-purple-700 shadow-lg' : 'text-white/60'; ?>">ظهور</button>
                                <button type="button" onclick="document.getElementById('ai_toggle_select').value='0'; this.form.submit();" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all <?php echo val('show_ai_assistant', '1', $current_settings) == '0' ? 'bg-white text-purple-700 shadow-lg' : 'text-white/60'; ?>">إخفاء</button>
                                <input type="hidden" name="show_ai_assistant" id="ai_toggle_select" value="<?php echo val('show_ai_assistant', '1', $current_settings); ?>">
                            </div>
                        </div>

                        <!-- Dynamic API Key Boxes -->
                        <div id="api_keys_container" class="space-y-3">
                            <label class="block text-white/80 text-xs font-black mb-2 mr-1">Google Gemini API Keys (يمكنك إضافة أكثر من مفتاح)</label>
                            <?php 
                            $savedKeys = explode(',', val('gemini_api_key', '', $current_settings));
                            $savedKeys = array_filter(array_map('trim', $savedKeys));
                            if (empty($savedKeys)) $savedKeys = ['']; // Default one empty box
                            
                            foreach ($savedKeys as $index => $key): 
                            ?>
                            <div class="relative group api-key-row">
                                <input type="password" name="api_keys_list[]" value="<?php echo htmlspecialchars($key); ?>" 
                                       placeholder="أدخل مفتاح الـ API هنا..." 
                                       class="w-full py-3.5 px-5 bg-white/10 border border-white/20 rounded-2xl text-white placeholder-white/30 focus:ring-4 focus:ring-white/10 focus:border-white/40 outline-none transition-all font-mono text-xs shadow-inner">
                                <div class="absolute inset-y-0 left-4 flex items-center gap-2">
                                    <?php if ($index > 0): ?>
                                    <button type="button" onclick="this.closest('.api-key-row').remove()" class="text-red-400 hover:text-red-300 transition-colors"><i class="fas fa-trash-alt"></i></button>
                                    <?php endif; ?>
                                    <i class="fas fa-key text-white/20 group-focus-within:text-white/50 transition-colors"></i>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Add Button -->
                        <button type="button" onclick="addNewKeyBox()" class="text-[10px] bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl transition-all flex items-center gap-2">
                            <i class="fas fa-plus"></i> إضافة مفتاح جديد
                        </button>
                        
                        <input type="hidden" name="gemini_api_key" id="final_api_keys">

                        <script>
                        function addNewKeyBox() {
                            const container = document.getElementById('api_keys_container');
                            const div = document.createElement('div');
                            div.className = 'relative group api-key-row animate__animated animate__fadeInUp';
                            div.innerHTML = `
                                <input type="password" name="api_keys_list[]" value="" 
                                       placeholder="أدخل مفتاح الـ API هنا..." 
                                       class="w-full py-3.5 px-5 bg-white/10 border border-white/20 rounded-2xl text-white placeholder-white/30 focus:ring-4 focus:ring-white/10 focus:border-white/40 outline-none transition-all font-mono text-xs shadow-inner">
                                <div class="absolute inset-y-0 left-4 flex items-center gap-2">
                                    <button type="button" onclick="this.closest('.api-key-row').remove()" class="text-red-400 hover:text-red-300 transition-colors"><i class="fas fa-trash-alt"></i></button>
                                    <i class="fas fa-key text-white/20 group-focus-within:text-white/50 transition-colors"></i>
                                </div>
                            `;
                            container.appendChild(div);
                        }

                        // قبل الإرسال، نجمع المفاتيح في الحقل المخفي
                        document.querySelector('form').addEventListener('submit', function() {
                            const keys = Array.from(document.querySelectorAll('input[name="api_keys_list[]"]'))
                                              .map(input => input.value.trim())
                                              .filter(val => val !== '');
                            document.getElementById('final_api_keys').value = keys.join(',');
                        });
                        </script>

                        <div class="flex justify-between items-center mt-2 px-1">
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-[10px] text-white/60 hover:text-white flex items-center gap-1 transition-colors">
                                <i class="fas fa-external-link-alt text-[8px]"></i> احصل على مفتاح مجاني
                            </a>
                            <span class="text-[9px] text-white/40 italic">يتم توزيع الضغط تلقائياً بين المفاتيح</span>
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="submit" class="w-full bg-white text-[#7c3aed] font-black py-4 rounded-2xl shadow-xl hover:bg-purple-50 active:scale-95 transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-rocket"></i>
                            حفظ إعدادات الذكاء الاصطناعي
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Global Save Button -->
    <div class="mt-10 mb-6 flex justify-end">
        <button type="submit" class="btn-primary flex items-center gap-2 px-10 py-4 shadow-2xl hover:scale-105 transition-all">
            <i class="fas fa-save text-lg"></i>
            <span class="text-lg">حفظ كافة الإعدادات</span>
        </button>
    </div>
</form>

<?php if (isAdmin()): ?>
<?php
// Count files and DB tables for preview
$fileCount = 0;
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($iter as $f) { if ($f->isFile()) $fileCount++; }

try {
    $tableCount = $pdo->query("SHOW TABLES")->rowCount();
} catch(Exception $e) { $tableCount = '?'; }

$totalSize = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS)) as $f) {
    if ($f->isFile()) $totalSize += $f->getSize();
}
$sizeStr = $totalSize > 1048576 ? round($totalSize/1048576, 1).' MB' : round($totalSize/1024, 0).' KB';
?>

<!-- Backup Section -->
<div class="mt-8">
    <div class="aeterna-card border-2 border-dashed border-emerald-200 bg-emerald-50/20 relative overflow-hidden">
        <!-- Decorative -->
        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-emerald-100 rounded-full opacity-30 blur-2xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row items-start md:items-center gap-6 relative z-10">
            <!-- Icon + Title -->
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg flex-shrink-0">
                    <i class="fas fa-cloud-download-alt text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-black text-lg text-charcoal">النسخ الاحتياطي الكامل</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Backup شامل لكل الملفات وقاعدة البيانات</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="flex gap-4 flex-wrap flex-1">
                <div class="bg-white rounded-xl px-4 py-2 shadow-sm border border-gray-100 text-center">
                    <div class="text-xl font-black text-charcoal"><?= $fileCount ?></div>
                    <div class="text-[10px] text-gray-400">ملف في المشروع</div>
                </div>
                <div class="bg-white rounded-xl px-4 py-2 shadow-sm border border-gray-100 text-center">
                    <div class="text-xl font-black text-emerald-600"><?= $tableCount ?></div>
                    <div class="text-[10px] text-gray-400">جدول في الداتابيز</div>
                </div>
                <div class="bg-white rounded-xl px-4 py-2 shadow-sm border border-gray-100 text-center">
                    <div class="text-xl font-black text-blue-600"><?= $sizeStr ?></div>
                    <div class="text-[10px] text-gray-400">حجم تقريبي</div>
                </div>
            </div>

            <!-- Download Button -->
            <a href="backup.php"
               onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin ml-2\'></i> جاري التجهيز...'; this.classList.add('opacity-75');"
               class="flex-shrink-0 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black py-3 px-6 rounded-xl shadow-lg transition-all hover:-translate-y-1 hover:shadow-xl flex items-center gap-2 whitespace-nowrap">
                <i class="fas fa-download"></i>
                تحميل النسخة الاحتياطية
            </a>
        </div>

        <!-- Info Strip -->
        <div class="mt-4 pt-4 border-t border-emerald-100 grid grid-cols-1 sm:grid-cols-3 gap-3 relative z-10">
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <i class="fas fa-check-circle text-emerald-500"></i>
                يشمل كل ملفات PHP والمجلدات الفرعية تلقائياً
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <i class="fas fa-check-circle text-emerald-500"></i>
                يتضمن SQL dump كامل لقاعدة البيانات
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <i class="fas fa-check-circle text-emerald-500"></i>
                يحفظ ملف README بتعليمات الاستعادة
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>

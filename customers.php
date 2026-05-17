<?php
require_once 'config.php';
requireLogin();

// Role Check
if (!can('manage_revenues')) {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Handle Add Customer
if (isset($_GET['action']) && $_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    if (empty($name) || empty($phone)) {
        $error = 'الاسم ورقم الهاتف مطلوبان.';
    } else {
        try {
            $email = $_POST['email'] ?? '';
            $address = $_POST['address'] ?? '';
            $social_media = $_POST['social_media'] ?? '';
            $lead_source = $_POST['lead_source'] ?? 'Direct';

            $stmt = $pdo->query("SELECT MAX(id) FROM customers");
            $maxId = $stmt->fetchColumn() ?: 1000;
            $customerCode = 'CUST-' . ($maxId + 1);
            
            // Password gen
            $chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
            $password = $_POST['password'] ?: substr(str_shuffle($chars), 0, 6);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $username = $_POST['username'] ?: $customerCode;

            $stmt = $pdo->prepare("INSERT INTO customers (customer_code, name, email, phone, address, social_media, lead_source, username, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$customerCode, $name, $email, $phone, $address, $social_media, $lead_source, $username, $password_hash]);
            
            // ------------------------------------------------------------------
            // تجهيز رسالة الواتساب ورابط الإرسال (إضافة عميل)
            // ------------------------------------------------------------------
            $clean_phone = preg_replace('/[^0-9]/', '', $phone);
            if (strpos($clean_phone, '0') === 0) { $clean_phone = '2' . $clean_phone; }
            
            // رابط الدخول بطريقة أكثر دقة لمنع تكرار أو أخطاء الروابط
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $base_dir = dirname($_SERVER['PHP_SELF']);
            $base_dir = ($base_dir === '/' || $base_dir === '\\') ? '' : $base_dir;
            $login_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir . "/index.php";

            // صياغة احترافية لشركة Aeterna بدون أي إيموجي
            $wa_text = "أهلاً بك أستاذ *$name*،\n\n";
            $wa_text .= "بالنيابة عن فريق *Aeterna Cabinetry*، نرحب بك كعميل مميز لدينا. نحن سعداء باختيارك لنا لنكون جزءاً من تصميم مساحتك الخاصة.\n\n";
            $wa_text .= "لقد تم تفعيل حسابك على نظامنا بنجاح لتتمكن من متابعة تفاصيل مشروعك بكل شفافية وسهولة.\n\n";
            $wa_text .= "*بيانات الدخول الخاصة بك:*\n";
            $wa_text .= "- اسم المستخدم: $username\n";
            $wa_text .= "- كلمة المرور: $password\n";
            $wa_text .= "- رابط الدخول: $login_url\n\n";
            $wa_text .= "نحن دائماً في خدمتك لضمان أفضل تجربة لك،\nفريق Aeterna.";

            // استخدام rawurlencode يعالج المسافات والرموز بشكل أفضل لروابط الواتساب
            $wa_link = "https://wa.me/" . $clean_phone . "?text=" . rawurlencode($wa_text);

            // رسالة النجاح ومعاها زرار الواتساب
            $success_msg = "تمت إضافة العميل بنجاح. <br> <div class='mt-2'><strong>حساب الدخول: <span dir='ltr' class='bg-blue-100 text-blue-800 px-2 py-1 rounded mx-1'>$username</span> | كلمة المرور: <span dir='ltr' class='bg-green-100 text-green-800 px-2 py-1 rounded mx-1'>$password</span></strong></div>";
            $success_msg .= "<div class='mt-4'><a href='$wa_link' target='_blank' class='inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md hover:-translate-y-0.5'><i class='fab fa-whatsapp text-lg'></i> إرسال بيانات الدخول للعميل عبر واتساب</a></div>";
            
            $_SESSION['flash_success'] = $success_msg;
            header("Location: customers.php");
            exit;
        } catch (Exception $e) {
            $error = 'حدث خطأ أثناء الإضافة: ' . $e->getMessage();
        }
    }
}

// Handle Edit Customer
if (isset($_GET['action']) && $_GET['action'] === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if (empty($id) || empty($name) || empty($phone)) {
        $error = 'الاسم ورقم الهاتف مطلوبان.';
    } else {
        try {
            $email = $_POST['email'] ?? '';
            $address = $_POST['address'] ?? '';
            $social_media = $_POST['social_media'] ?? '';
            $lead_source = $_POST['lead_source'] ?? 'Direct';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (!empty($password)) {
                // تم إدخال باسورد جديد، هنعمله Update ونجهز رسالة واتساب بالتعديل
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, social_media = ?, lead_source = ?, username = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $address, $social_media, $lead_source, $username, $password_hash, $id]);
                
                // ------------------------------------------------------------------
                // تجهيز رسالة الواتساب ورابط الإرسال (تعديل باسورد العميل)
                // ------------------------------------------------------------------
                $clean_phone = preg_replace('/[^0-9]/', '', $phone);
                if (strpos($clean_phone, '0') === 0) { $clean_phone = '2' . $clean_phone; }
                
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $base_dir = dirname($_SERVER['PHP_SELF']);
                $base_dir = ($base_dir === '/' || $base_dir === '\\') ? '' : $base_dir;
                $login_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir . "/login.php";

                $wa_text_edit = "أهلاً بك أستاذ *$name*،\n\n";
                $wa_text_edit .= "يرجى العلم بأنه تم تحديث بيانات الدخول الخاصة بك على نظام *Aeterna Cabinetry* بنجاح.\n\n";
                $wa_text_edit .= "*بيانات الدخول الجديدة:*\n";
                $wa_text_edit .= "- اسم المستخدم: $username\n";
                $wa_text_edit .= "- كلمة المرور: $password\n";
                $wa_text_edit .= "- رابط الدخول: $login_url\n\n";
                $wa_text_edit .= "نحن دائماً في خدمتك،\nفريق Aeterna.";

                $wa_link_edit = "https://wa.me/" . $clean_phone . "?text=" . rawurlencode($wa_text_edit);

                $success_msg = "تم تحديث بيانات العميل بنجاح وتم تغيير كلمة المرور.";
                $success_msg .= "<div class='mt-4'><a href='$wa_link_edit' target='_blank' class='inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md hover:-translate-y-0.5'><i class='fab fa-whatsapp text-lg'></i> إرسال الباسورد الجديد للعميل عبر واتساب</a></div>";
                
                $_SESSION['flash_success'] = $success_msg;

            } else {
                // لم يتم تعديل الباسورد، هنعمل Update للبيانات العادية بس
                $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, social_media = ?, lead_source = ?, username = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $address, $social_media, $lead_source, $username, $id]);
                $_SESSION['flash_success'] = "تم تحديث بيانات العميل بنجاح.";
            }
            
            $redirect_to = $_POST['redirect_to'] ?? "customer_view.php?id=$id";
            header("Location: $redirect_to");
            exit;
        } catch (Exception $e) {
            $error = 'حدث خطأ أثناء التحديث: ' . $e->getMessage();
        }
    }
}

// Handle File Upload
if (isset($_GET['action']) && $_GET['action'] === 'upload_file' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? null;
    $project_id = $_POST['project_id'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!$customer_id || !isset($_FILES['customer_file'])) {
        $_SESSION['flash_error'] = 'بيانات ناقصة للرفع.';
        header("Location: customers.php");
        exit;
    }

    $file = $_FILES['customer_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash_error'] = 'خطأ في ملف الرفع أو حجمه كبير جداً.';
        header("Location: customer_view.php?id=$customer_id");
        exit;
    }

    // Prepare Upload Directory
    $upload_dir = getUploadPath($customer_id, $project_id);

    // Clean filename and move
    $original_name = basename($file['name']);
    $ext = pathinfo($original_name, PATHINFO_EXTENSION);
    $new_name = 'FILE_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target_path = $upload_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO customer_files (customer_id, project_id, file_name, file_path, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$customer_id, $project_id ?: null, $original_name, $target_path, $user_id]);
            
            $_SESSION['flash_success'] = 'تم رفع الملف وحفظه في أرشيف العميل بنجاح.';
        } catch (Exception $e) {
            // التحقق إذا كان الخطأ بسبب نقص عمود project_id
            if (strpos($e->getMessage(), 'project_id') !== false) {
                try {
                    // محاولة إضافة العمود تلقائياً
                    $pdo->exec("ALTER TABLE customer_files ADD COLUMN project_id INT NULL AFTER customer_id");
                    // إعادة محاولة الإدخال
                    $stmt = $pdo->prepare("INSERT INTO customer_files (customer_id, project_id, file_name, file_path, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$customer_id, $project_id ?: null, $original_name, $target_path, $user_id]);
                    $_SESSION['flash_success'] = 'تم رفع الملف بنجاح (تم تحديث قاعدة البيانات تلقائياً).';
                } catch (Exception $e2) {
                    if (file_exists($target_path)) unlink($target_path);
                    $_SESSION['flash_error'] = 'حدث خطأ أثناء تحديث قاعدة البيانات: ' . $e2->getMessage();
                }
            } else {
                if (file_exists($target_path)) unlink($target_path);
                $_SESSION['flash_error'] = 'حدث خطأ أثناء تسجيل الملف في قاعدة البيانات: ' . $e->getMessage();
            }
        }
    } else {
        $_SESSION['flash_error'] = 'فشل في نقل الملف إلى السيرفر. تأكد من صلاحيات المجلد.';
    }

    header("Location: customer_view.php?id=$customer_id");
    exit;
}

// Handle Delete File
if (isset($_GET['action']) && $_GET['action'] === 'delete_file') {
    $file_id = $_GET['file_id'] ?? null;
    $customer_id = $_GET['customer_id'] ?? null;

    if ($file_id) {
        try {
            // Get file path first
            $stmt = $pdo->prepare("SELECT file_path FROM customer_files WHERE id = ?");
            $stmt->execute([$file_id]);
            $f = $stmt->fetch();

            if ($f) {
                // Delete from DB
                $stmt = $pdo->prepare("DELETE FROM customer_files WHERE id = ?");
                $stmt->execute([$file_id]);

                // Delete from disk
                if (file_exists($f['file_path'])) {
                    unlink($f['file_path']);
                }
                $_SESSION['flash_success'] = 'تم حذف المستند بنجاح.';
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'حدث خطأ أثناء الحذف: ' . $e->getMessage();
        }
    }
    header("Location: customer_view.php?id=$customer_id");
    exit;
}

include 'layout/header.php';
?>

<div class="mb-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 stagger">
    <div>
        <h2 class="text-3xl font-black text-charcoal tracking-tight">إدارة العملاء (CRM)</h2>
        <p class="text-base text-gray-500 font-bold mt-2">سجل متكامل لتتبع بيانات العملاء والمستحقات.</p>
    </div>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full lg:w-auto">
        <div class="relative group flex-1 sm:flex-none">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-taupe transition-colors"></i>
            <input type="text" id="customerSearch" placeholder="بحث باسم العميل أو الكود..." 
                   class="pl-12 pr-6 py-4 bg-white border border-gray-100 rounded-2xl text-sm font-bold focus:ring-2 focus:ring-taupe outline-none shadow-premium transition-all w-full sm:w-72">
        </div>
        <button onclick="document.getElementById('addCustomerModal').classList.remove('hidden')" 
                class="btn-primary py-4 px-8 text-base flex items-center justify-center gap-3 shadow-premium active:scale-95 transition-all">
            <i class="fas fa-user-plus"></i>
            <span>إضافة عميل جديد</span>
        </button>
    </div>
</div>

<?php if ($success): ?>
    <div class="bg-green-50 border border-green-100 text-green-700 px-6 py-4 rounded-3xl shadow-sm mb-8 animate__animated animate__fadeIn">
        <div class="flex items-start gap-3">
            <i class="fas fa-check-circle text-xl mt-1"></i>
            <div class="text-sm"><?php echo $success; ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-50 border border-red-100 text-red-700 px-6 py-4 rounded-3xl shadow-sm mb-8 animate__animated animate__fadeIn">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-xl mt-1"></i>
            <div class="text-sm font-bold"><?php echo $error; ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Unified View: Grid on Mobile, Table on Desktop -->
<div class="mb-12">
    <!-- Mobile Grid View -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
        <?php
        $stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
        $customers = $stmt->fetchAll();
        foreach($customers as $customer):
            $clean_phone = preg_replace('/[^0-9]/', '', $customer['phone']);
            if (strpos($clean_phone, '0') === 0) { $clean_phone = '2' . $clean_phone; }
        ?>
        <div class="aeterna-card customer-row flex flex-col justify-between p-6 border-r-4 border-r-charcoal/20 hover:border-r-taupe group">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-charcoal to-graphite text-white flex items-center justify-center font-black shadow-lg group-hover:scale-105 transition-transform">
                        <?php echo mb_substr($customer['name'], 0, 1, 'UTF-8'); ?>
                    </div>
                    <div>
                        <h4 class="font-black text-charcoal text-lg leading-tight group-hover:text-taupe transition-colors"><?php echo htmlspecialchars($customer['name']); ?></h4>
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">#<?php echo $customer['customer_code']; ?></span>
                    </div>
                </div>
                <span class="px-3 py-1 bg-gray-50 text-gray-500 text-[9px] font-black rounded-lg border border-gray-100">
                    <?php echo htmlspecialchars($customer['lead_source']); ?>
                </span>
            </div>
            
            <div class="space-y-3 mb-6 bg-gray-50/50 p-4 rounded-2xl border border-gray-50">
                <div class="flex items-center justify-between text-xs font-bold text-gray-600">
                    <span class="opacity-50">الهاتف:</span>
                    <span dir="ltr"><?php echo htmlspecialchars($customer['phone']); ?></span>
                </div>
                <?php if($customer['address']): ?>
                <div class="flex items-center justify-between text-[10px] font-bold text-gray-400">
                    <span class="opacity-50">العنوان:</span>
                    <span class="truncate max-w-[150px]"><?php echo htmlspecialchars($customer['address']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2 mt-auto pt-4">
                <a href="customer_view.php?id=<?php echo $customer['id']; ?>" class="flex-1 bg-charcoal text-white font-black py-4 rounded-xl text-center text-xs shadow-lg active:scale-95 transition-all">
                    عرض التفاصيل
                </a>
                <button onclick='openEditModal(<?php echo json_encode($customer); ?>)' class="w-14 h-14 rounded-xl bg-gray-50 text-charcoal flex items-center justify-center border border-gray-100 hover:bg-taupe hover:text-white transition-all">
                    <i class="fas fa-edit"></i>
                </button>
                <a href="https://wa.me/<?php echo $clean_phone; ?>" target="_blank" class="w-14 h-14 rounded-xl bg-green-50 text-green-600 flex items-center justify-center border border-green-100 shadow-sm active:scale-95 transition-all">
                    <i class="fab fa-whatsapp text-xl"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden lg:block bg-white rounded-[2.5rem] shadow-premium border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 text-gray-400 text-[10px] font-black uppercase tracking-[0.2em] border-b border-gray-100">
                        <th class="p-6">العميل</th>
                        <th class="p-6">الهاتف</th>
                        <th class="p-6 text-center">المصدر</th>
                        <th class="p-6 text-center">الكود</th>
                        <th class="p-6 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach($customers as $customer): 
                        $clean_phone = preg_replace('/[^0-9]/', '', $customer['phone']);
                        if (strpos($clean_phone, '0') === 0) { $clean_phone = '2' . $clean_phone; }
                    ?>
                    <tr class="hover:bg-gray-50/80 transition-all customer-row group">
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-charcoal to-graphite text-white flex items-center justify-center text-sm font-black shadow-lg">
                                    <?php echo mb_substr($customer['name'], 0, 1, 'UTF-8'); ?>
                                </div>
                                <div>
                                    <a href="customer_view.php?id=<?php echo $customer['id']; ?>" class="text-sm font-black text-charcoal hover:text-taupe transition-colors block">
                                        <?php echo htmlspecialchars($customer['name']); ?>
                                    </a>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">#<?php echo $customer['customer_code']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="flex flex-col">
                                <span class="text-xs font-black text-charcoal" dir="ltr"><?php echo htmlspecialchars($customer['phone']); ?></span>
                                <a href="https://wa.me/<?php echo $clean_phone; ?>" target="_blank" class="text-[9px] text-green-500 font-black mt-1 hover:underline">ارسال واتساب <i class="fab fa-whatsapp"></i></a>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[10px] font-black rounded-lg border border-blue-100">
                                <?php echo htmlspecialchars($customer['lead_source']); ?>
                            </span>
                        </td>
                        <td class="p-6 text-center">
                            <span class="text-[10px] font-black text-taupe px-3 py-1 bg-gray-50 rounded-lg border border-gray-100">
                                <?php echo htmlspecialchars($customer['customer_code']); ?>
                            </span>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center justify-center gap-2">
                                <a href="customer_view.php?id=<?php echo $customer['id']; ?>" class="w-10 h-10 rounded-xl bg-white border border-gray-100 text-charcoal flex items-center justify-center shadow-sm hover:shadow-xl hover:border-taupe hover:-translate-y-1 transition-all">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                <button onclick='openEditModal(<?php echo json_encode($customer); ?>)' class="w-10 h-10 rounded-xl bg-gray-50 text-charcoal flex items-center justify-center shadow-sm hover:shadow-xl hover:bg-charcoal hover:text-white transition-all">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <a href="https://wa.me/<?php echo $clean_phone; ?>" target="_blank" class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center shadow-sm hover:shadow-xl transition-all">
                                    <i class="fab fa-whatsapp text-lg"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div id="addCustomerModal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-2 sm:p-4">
    <div data-lenis-prevent class="bg-white rounded-[2.5rem] shadow-2xl w-[96%] max-w-2xl p-6 sm:p-8 relative text-right animate__animated animate__zoomIn animate__faster max-h-[92vh] overflow-y-auto custom-scrollbar">
        <button onclick="document.getElementById('addCustomerModal').classList.add('hidden')" class="absolute top-5 left-5 text-gray-400 hover:text-charcoal transition-colors z-50"><i class="fas fa-times text-xl"></i></button>
        
        <div class="mb-8">
            <h3 class="text-2xl font-black text-charcoal">إضافة عميل جديد</h3>
            <p class="text-xs text-gray-400 mt-1">قم بإدخال البيانات الأساسية لبدء المعاملات المالية والمشاريع.</p>
        </div>
        
        <form method="POST" action="customers.php?action=add">
            <div class="space-y-6">
                <!-- Name & Phone -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">اسم العميل *</label>
                        <input type="text" name="name" required placeholder="الاسم بالكامل" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">رقم الهاتف *</label>
                        <input type="tel" name="phone" required dir="ltr" placeholder="01xxxxxxxxx" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">البريد الإلكتروني (اختياري)</label>
                    <input type="email" name="email" dir="ltr" placeholder="example@email.com" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">العنوان</label>
                    <textarea name="address" rows="2" placeholder="العنوان بالتفصيل..." class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none resize-none"></textarea>
                </div>

                <!-- Lead Source & Social Link -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">مصدر العميل (Lead Source)</label>
                        <select name="lead_source" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none">
                            <option value="Direct">مباشر (المعرض)</option>
                            <option value="Social">سوشيال ميديا</option>
                            <option value="Referral">توصية عميل</option>
                            <option value="Other">أخرى</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">روابط السوشيال ميديا</label>
                        <input type="text" name="social_media" dir="ltr" placeholder="@username or link" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                    </div>
                </div>

                <!-- Account Credentials Area -->
                <div class="bg-emerald-50/50 p-6 rounded-3xl border border-emerald-100">
                    <div class="flex items-center gap-2 mb-4 text-emerald-800">
                        <i class="fas fa-user-shield text-sm"></i>
                        <h4 class="text-xs font-black uppercase tracking-wider">بيانات حساب العميل التلقائية</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] font-black text-emerald-700/60 uppercase mb-2">اسم المستخدم (اختياري - يولد تلقائياً)</label>
                            <input type="text" name="username" placeholder="client_f38793" dir="ltr" class="w-full py-3 px-5 bg-white border border-emerald-100 rounded-xl focus:ring-2 focus:ring-emerald-400 font-bold text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-emerald-700/60 uppercase mb-2">كلمة المرور (اختياري - تولد تلقائياً)</label>
                            <input type="text" name="password" placeholder="يتم توليدها عشوائياً" dir="ltr" class="w-full py-3 px-5 bg-white border border-emerald-100 rounded-xl focus:ring-2 focus:ring-emerald-400 font-bold text-sm outline-none">
                        </div>
                    </div>
                    <p class="text-[9px] text-emerald-600/70 mt-3 font-bold"><i class="fas fa-info-circle ml-1"></i> يمكنك مسح وكتابة اسم مستخدم من اختيارك، أو تركها ليتم إنشاء الدخول تلقائياً.</p>
                </div>

                <button type="submit" class="w-full bg-charcoal text-white font-black py-5 rounded-[2rem] hover:bg-graphite transition-all shadow-xl hover:-translate-y-1">
                    إضافة العميل وإصدار الحساب <i class="fas fa-user-plus mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-2 sm:p-4">
    <div data-lenis-prevent class="bg-white rounded-[2.5rem] shadow-2xl w-[96%] max-w-2xl p-6 sm:p-8 relative text-right animate__animated animate__zoomIn animate__faster max-h-[92vh] overflow-y-auto custom-scrollbar">
        <button onclick="document.getElementById('editCustomerModal').classList.add('hidden')" class="absolute top-5 left-5 text-gray-400 hover:text-charcoal transition-colors z-50"><i class="fas fa-times text-xl"></i></button>
        
        <div class="mb-8">
            <h3 class="text-2xl font-black text-charcoal">تعديل بيانات العميل</h3>
            <p class="text-xs text-gray-400 mt-1">تحديث معلومات التواصل والعنوان ومصدر العميل.</p>
        </div>
        
        <form method="POST" action="customers.php?action=edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="redirect_to" value="customers.php">
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">اسم العميل *</label>
                        <input type="text" name="name" id="edit_name" required class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">رقم الهاتف *</label>
                        <input type="tel" name="phone" id="edit_phone" required dir="ltr" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">البريد الإلكتروني</label>
                    <input type="email" name="email" id="edit_email" dir="ltr" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">العنوان</label>
                    <textarea name="address" id="edit_address" rows="2" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none resize-none"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">مصدر العميل</label>
                        <select name="lead_source" id="edit_lead_source" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none">
                            <option value="Direct">مباشر (المعرض)</option>
                            <option value="Social">سوشيال ميديا</option>
                            <option value="Referral">توصية عميل</option>
                            <option value="Other">أخرى</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">روابط السوشيال ميديا</label>
                        <input type="text" name="social_media" id="edit_social_media" dir="ltr" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                    </div>
                </div>

                <div class="bg-emerald-50/50 p-6 rounded-3xl border border-emerald-100">
                    <div class="flex items-center gap-2 mb-4 text-emerald-800">
                        <i class="fas fa-user-shield text-sm"></i>
                        <h4 class="text-xs font-black uppercase tracking-wider">بيانات حساب العميل</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] font-black text-emerald-700/60 uppercase mb-2">اسم المستخدم</label>
                            <input type="text" name="username" id="edit_username" dir="ltr" class="w-full py-3 px-5 bg-white border border-emerald-100 rounded-xl focus:ring-2 focus:ring-emerald-400 font-bold text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-emerald-700/60 uppercase mb-2">كلمة مرور جديدة (اتركها فارغة للتجاهل)</label>
                            <input type="text" name="password" placeholder="********" dir="ltr" class="w-full py-3 px-5 bg-white border border-emerald-100 rounded-xl focus:ring-2 focus:ring-emerald-400 font-bold text-sm outline-none">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-charcoal text-white font-black py-5 rounded-[2rem] hover:bg-graphite transition-all shadow-xl hover:-translate-y-1">
                    حفظ التغييرات <i class="fas fa-check-circle mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(customer) {
        document.getElementById('edit_id').value = customer.id;
        document.getElementById('edit_name').value = customer.name;
        document.getElementById('edit_phone').value = customer.phone;
        document.getElementById('edit_email').value = customer.email || '';
        document.getElementById('edit_address').value = customer.address || '';
        document.getElementById('edit_social_media').value = customer.social_media || '';
        document.getElementById('edit_lead_source').value = customer.lead_source || 'Direct';
        document.getElementById('edit_username').value = customer.username || '';
        document.getElementById('editCustomerModal').classList.remove('hidden');
    }

    document.getElementById('customerSearch').addEventListener('input', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.customer-row');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include 'layout/footer.php'; ?>
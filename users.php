<?php
require_once 'config.php';
requireLogin();

// Only Managers/Admins can access users page
if (!isManagerOrAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';
$whatsapp_btn = ''; // متغير جديد لزرار الواتساب

// Handle Add User
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = trim($_POST['email'] ?? '');
    // تحويل النص الفارغ لـ null عشان نتجنب مشكلة الـ Unique Constraint
    $email = $email === '' ? null : $email;
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'] ?? null;
    
    // Get role name for backward compatibility
    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);
    $role_name = $stmt->fetchColumn() ?: 'Secretary';

    if (empty($name) || empty($password) || empty($username)) {
        $error = 'الاسم، اسم المستخدم، وكلمة المرور مطلوبان.';
    } else {
        // Generate user_code
        $stmt = $pdo->query("SELECT MAX(id) FROM users");
        $maxId = $stmt->fetchColumn() ?: 1000;
        $user_code = 'EMP-' . ($maxId + 1);
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (user_code, name, email, username, password_hash, role, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_code, $name, $email, $username, $password_hash, $role_name, $role_id]);
            $success = "تم إضافة الموظف بنجاح. كود الموظف: $user_code";
            
            // ------------------------------------------------------------------
            // تجهيز رسالة الواتساب للإرسال (إضافة موظف)
            // ------------------------------------------------------------------
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $base_dir = dirname($_SERVER['PHP_SELF']);
            $base_dir = ($base_dir === '/' || $base_dir === '\\') ? '' : $base_dir;
            $login_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir . "/index.php";

            $wa_text = "أهلاً بك *$name* في فريق عمل Aeterna،\n\n";
            $wa_text .= "تم إنشاء حساب لك على نظام الإدارة بنجاح.\n\n";
            $wa_text .= "*بيانات الدخول الخاصة بك:*\n";
            $wa_text .= "- اسم المستخدم: $username\n";
            $wa_text .= "- كلمة المرور: $password\n";
            $wa_text .= "- رابط الدخول: $login_url\n\n";
            
            $wa_link = "https://wa.me/?text=" . rawurlencode($wa_text);
            $whatsapp_btn = "<a href='$wa_link' target='_blank' class='inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md mt-3 hover:-translate-y-0.5'><i class='fab fa-whatsapp text-lg'></i> إرسال بيانات الدخول عبر واتساب</a>";

            $action = 'list';
        } catch(PDOException $e) {
            // أظهرنا الخطأ الفعلي هنا عشان لو في عمود ناقص في الداتابيز يوضحه
            $error = 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
    }
}

// Handle Edit Login (Username & Password)
if ($action === 'edit_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $new_name = $_POST['new_name'] ?? '';
    $new_email = trim($_POST['new_email'] ?? '');
    $new_email = $new_email === '' ? null : $new_email;
    $new_username = $_POST['new_username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_role_id = $_POST['new_role_id'] ?? null;
    
    // Get role name for compatibility
    $role_name = null;
    if ($new_role_id) {
        $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
        $stmt->execute([$new_role_id]);
        $role_name = $stmt->fetchColumn();
    }

    try {
        if (empty($new_name) || empty($new_username)) {
            $error = "اسم الموظف واسم المستخدم مطلوبان.";
        } else {
            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                if (isAdmin() && $new_role_id) {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, username = ?, password_hash = ?, role = ?, role_id = ? WHERE id = ?");
                    $stmt->execute([$new_name, $new_email, $new_username, $password_hash, $role_name, $new_role_id, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, username = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$new_name, $new_email, $new_username, $password_hash, $user_id]);
                }
                
                // ------------------------------------------------------------------
                // تجهيز رسالة الواتساب للإرسال (تعديل موظف)
                // ------------------------------------------------------------------
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $base_dir = dirname($_SERVER['PHP_SELF']);
                $base_dir = ($base_dir === '/' || $base_dir === '\\') ? '' : $base_dir;
                $login_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir . "/index.php";

                $wa_text = "أهلاً بك *$new_name*،\n\n";
                $wa_text .= "يرجى العلم بأنه تم تحديث بيانات الدخول الخاصة بك على نظام Aeterna بنجاح.\n\n";
                $wa_text .= "*بيانات الدخول الجديدة:*\n";
                $wa_text .= "- اسم المستخدم: $new_username\n";
                $wa_text .= "- كلمة المرور: $new_password\n";
                $wa_text .= "- رابط الدخول: $login_url\n\n";
                
                $wa_link = "https://wa.me/?text=" . rawurlencode($wa_text);
                $whatsapp_btn = "<a href='$wa_link' target='_blank' class='inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md mt-3 hover:-translate-y-0.5'><i class='fab fa-whatsapp text-lg'></i> إرسال الباسورد الجديد عبر واتساب</a>";
                
                $success = "تم تحديث البيانات بنجاح وتغيير كلمة المرور.";
                
            } else {
                if (isAdmin() && $new_role_id) {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, username = ?, role = ?, role_id = ? WHERE id = ?");
                    $stmt->execute([$new_name, $new_email, $new_username, $role_name, $new_role_id, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, username = ? WHERE id = ?");
                    $stmt->execute([$new_name, $new_email, $new_username, $user_id]);
                }
                $success = "تم تحديث البيانات بنجاح.";
            }
        }
    } catch(PDOException $e) {
        $error = "حدث خطأ: اسم المستخدم أو الإيميل قد يكون محجوزاً لموظف آخر.";
    }
    $action = 'list';
}

// Handle Change Role (Admin only)
if (isAdmin() && $action === 'change_role' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = (int)($_POST['user_id'] ?? 0);
    $new_role  = $_POST['new_role'] ?? '';
    if ($target_id && in_array($new_role, $roles ?? [])) {
        // Prevent self-demotion from Admin
        if ($target_id == $_SESSION['user_id'] && $new_role !== 'Admin') {
            $error = 'لا يمكنك تغيير دورك الخاص.';
        } else {
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $target_id]);
            logActivity('تغيير دور مستخدم', "تم تغيير صلاحية المستخدم #$target_id إلى: $new_role");
            $success = 'تم تغيير الدور بنجاح.';
        }
    }
    $action = 'list';
}

// Handle Delete User (Except self)
if ($action === 'delete' && isset($_GET['id'])) {
    $del_id = $_GET['id'];
    if ($del_id == $_SESSION['user_id']) {
        $error = "لا يمكنك حذف حسابك الخاص.";
    } else {
        moveToTrash('users', $del_id); // Move to trash before delete
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$del_id]);
        $success = "تم حذف المستخدم بنجاح (ونقله لسلة المهملات).";
    }
    $action = 'list';
}

$roles_db = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

include 'layout/header.php';
?>

<!-- تم نقل رسائل الخطأ والنجاح لتكون ظاهرة في كل الصفحات (الإضافة والعرض) -->
<?php if ($success): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-4 rounded shadow-sm mb-6 animate__animated animate__fadeIn">
        <div><i class="fas fa-check-circle ml-1"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php if(!empty($whatsapp_btn)) echo $whatsapp_btn; ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-50 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded shadow-sm mb-6 animate__animated animate__fadeIn">
        <i class="fas fa-exclamation-circle ml-1"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl font-bold text-charcoal">إدارة الموظفين (المستخدمين)</h2>
            <p class="text-sm text-gray-500">إضافة أو تعديل أو حذف حسابات وصلاحيات الموظفين.</p>
        </div>
        <a href="users.php?action=add" class="btn-primary py-2 px-4 text-sm">
            <i class="fas fa-plus ml-1"></i> موظف جديد
        </a>
    </div>

    <!-- Unified View: Grid on Mobile, Table on Desktop -->
    <div class="mb-12">
        <!-- Mobile Grid View -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
            <?php
            $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
            $users = $stmt->fetchAll();
            foreach ($users as $user):
                $isAdminRole = $user['role'] === 'Admin';
                $isManagerRole = $user['role'] === 'Manager';
            ?>
            <div class="aeterna-card flex flex-col justify-between p-6 border-r-4 <?php echo $isAdminRole ? 'border-r-amber-400' : ($isManagerRole ? 'border-r-charcoal' : 'border-r-gray-300'); ?> group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-charcoal to-graphite text-white flex items-center justify-center font-black shadow-lg group-hover:scale-105 transition-transform">
                            <?php echo mb_substr($user['name'], 0, 1, 'UTF-8'); ?>
                        </div>
                        <div>
                            <h4 class="font-black text-charcoal text-lg leading-tight group-hover:text-taupe transition-colors"><?php echo htmlspecialchars($user['name']); ?></h4>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1"><?php echo htmlspecialchars($user['user_code'] ?? 'EMP-N/A'); ?></span>
                        </div>
                    </div>
                    <?php if ($isAdminRole): ?>
                        <i class="fas fa-crown text-amber-400"></i>
                    <?php endif; ?>
                </div>
                
                <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-50 mb-6 space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-gray-400">اليوزر:</span>
                        <span class="text-charcoal" dir="ltr"><?php echo htmlspecialchars($user['username'] ?? '-'); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-gray-400">الدور:</span>
                        <span class="<?php echo $isAdminRole ? 'text-amber-600' : 'text-gray-600'; ?>"><?php echo htmlspecialchars($user['role']); ?></span>
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-auto">
                    <button onclick='openEditLoginModal(<?php echo json_encode($user); ?>)' class="flex-1 bg-charcoal text-white font-black py-4 rounded-xl text-xs shadow-lg active:scale-95 transition-all">
                        تعديل البيانات
                    </button>
                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('حذف؟');" class="w-14 h-14 rounded-xl bg-red-50 text-red-500 flex items-center justify-center border border-red-100 hover:bg-red-500 hover:text-white transition-all">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-sm text-gray-500">
                            <th class="p-5 font-bold">الموظف (والكود)</th>
                            <th class="p-5 font-bold">بيانات الدخول (يوزر/إيميل)</th>
                            <th class="p-5 font-bold">الصلاحية (الدور)</th>
                            <th class="p-5 font-bold">تاريخ الانضمام</th>
                            <th class="p-5 font-bold text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php
                        foreach ($users as $user):
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-charcoal to-graphite text-white flex items-center justify-center font-bold text-base shadow-sm">
                                        <?php echo mb_substr($user['name'], 0, 1, 'UTF-8'); ?>
                                    </div>
                                    <div>
                                        <div class="font-black text-charcoal"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <div class="text-[10px] font-black text-taupe mt-0.5 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded inline-block" dir="ltr"><?php echo htmlspecialchars($user['user_code'] ?? 'EMP-N/A'); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-5 text-sm text-gray-600" dir="ltr">
                                <span class="block font-black text-charcoal mb-1"><i class="fas fa-user text-gray-400 mr-1 text-xs"></i> <?php echo htmlspecialchars($user['username'] ?? '-'); ?></span>
                                <?php if(!empty($user['email'])): ?>
                                <span class="block text-[11px] text-gray-400"><i class="fas fa-envelope mr-1"></i> <?php echo htmlspecialchars($user['email']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-5">
                                <?php if ($user['role'] === 'Admin'): ?>
                                    <span class="bg-gradient-to-r from-amber-400 to-yellow-500 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-sm flex items-center gap-1 w-fit">
                                        <i class="fas fa-crown text-[9px]"></i> ADMIN
                                    </span>
                                <?php elseif ($user['role'] === 'Manager'): ?>
                                    <span class="bg-charcoal text-white text-[10px] font-black px-3 py-1 rounded-full w-fit block shadow-sm">
                                        <?= htmlspecialchars(strtoupper($user['role'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-700 text-[10px] font-black px-3 py-1 rounded-full border border-gray-200 w-fit block">
                                        <?= htmlspecialchars(strtoupper($user['role'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-5 text-[11px] font-bold text-gray-400"><?php echo formatDate($user['created_at']); ?></td>
                            <td class="p-5">
                                <div class="flex justify-center gap-2">
                                    <button onclick='openEditLoginModal(<?php echo json_encode($user); ?>)' class="bg-blue-50 text-blue-600 hover:bg-blue-100 w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm border border-blue-100" title="تعديل بيانات الدخول">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    
                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('حذف؟');" class="bg-red-50 text-red-600 hover:bg-red-100 w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm border border-red-100" title="حذف">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- Edit Login Modal -->
<div id="editLoginModal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-2 sm:p-4">
    <div data-lenis-prevent class="bg-white rounded-[2.5rem] shadow-2xl w-[96%] max-w-lg p-6 sm:p-8 relative text-right animate__animated animate__zoomIn animate__faster max-h-[92vh] overflow-y-auto custom-scrollbar">
        <button onclick="document.getElementById('editLoginModal').classList.add('hidden')" class="absolute top-5 left-5 text-gray-400 hover:text-charcoal transition-colors z-50"><i class="fas fa-times text-xl"></i></button>
        
        <div class="mb-8">
            <h3 class="text-2xl font-black text-charcoal">تعديل بيانات الموظف</h3>
            <p class="text-xs text-gray-400 mt-1" id="edit_login_subtitle">تحديث اسم الموظف، الإيميل، بيانات الدخول، والصلاحيات.</p>
        </div>
        
        <form method="POST" action="users.php?action=edit_login">
            <input type="hidden" name="user_id" id="edit_login_user_id">
            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">اسم الموظف *</label>
                    <input type="text" name="new_name" id="edit_login_name" required class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">البريد الإلكتروني (اختياري)</label>
                    <input type="email" name="new_email" id="edit_login_email" dir="ltr" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">اسم المستخدم *</label>
                    <input type="text" name="new_username" id="edit_login_username" required dir="ltr" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">كلمة مرور جديدة (اتركها فارغة للتجاهل)</label>
                    <input type="text" name="new_password" placeholder="••••••••" dir="ltr" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                </div>

                <?php if(isAdmin()): ?>
                <div class="bg-amber-50/50 p-6 rounded-3xl border border-amber-100" id="role_edit_section">
                    <div class="flex items-center gap-2 mb-4 text-amber-800">
                        <i class="fas fa-crown text-sm"></i>
                        <h4 class="text-xs font-black uppercase tracking-wider">تغيير الصلاحية (الأدمن فقط)</h4>
                    </div>
                    <select name="new_role_id" id="edit_login_role_id" class="w-full py-3 px-5 bg-white border border-amber-100 rounded-xl focus:ring-2 focus:ring-amber-400 font-bold text-sm outline-none">
                        <?php foreach($roles_db as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <button type="submit" class="w-full bg-charcoal text-white font-black py-5 rounded-[2rem] hover:bg-graphite transition-all shadow-xl hover:-translate-y-1">
                    حفظ التعديلات <i class="fas fa-check-circle mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditLoginModal(user) {
    document.getElementById('edit_login_user_id').value = user.id;
    document.getElementById('edit_login_name').value = user.name || '';
    document.getElementById('edit_login_email').value = user.email || '';
    document.getElementById('edit_login_username').value = user.username || '';
    document.getElementById('edit_login_subtitle').innerText = 'تعديل بيانات: ' + user.name;
    
    const roleSelect = document.getElementById('edit_login_role_id');
    if (roleSelect) {
        roleSelect.value = user.role_id;
    }
    
    document.getElementById('editLoginModal').classList.remove('hidden');
}
</script>

<?php elseif ($action === 'add'): ?>
    <div class="flex items-center mb-6">
        <a href="users.php" class="text-gray-500 hover:text-charcoal ml-3 bg-white p-2 rounded-full shadow-sm">
            <i class="fas fa-arrow-right"></i>
        </a>
        <h2 class="text-xl font-bold text-charcoal">إضافة موظف جديد</h2>
    </div>

    <div class="aeterna-card max-w-2xl mx-auto">
        <form method="POST" action="users.php?action=add">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">اسم الموظف *</label>
                    <input type="text" name="name" required class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">الدور / الصلاحية *</label>
                    <select name="role_id" required class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50">
                        <?php foreach($roles_db as $r): ?>
                            <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">اسم المستخدم (للدخول به) *</label>
                    <input type="text" name="username" required dir="ltr" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني (اختياري)</label>
                    <input type="email" name="email" dir="ltr" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">كلمة المرور المبدئية *</label>
                <input type="text" name="password" required dir="ltr" class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-taupe bg-gray-50 text-left">
            </div>
            
            <button type="submit" class="btn-primary w-full text-lg">
                <i class="fas fa-save ml-2"></i> حفظ بيانات الموظف
            </button>
        </form>
    </div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>
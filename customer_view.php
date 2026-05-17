<?php
require_once 'config.php';
requireLogin();

if (!can('manage_revenues')) {
    header("Location: dashboard.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: customers.php");
    exit;
}

// Fetch Customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    header("Location: customers.php");
    exit;
}

// Fetch Projects
$stmt_projects = $pdo->prepare("SELECT * FROM projects WHERE customer_id = ? ORDER BY id DESC");
$stmt_projects->execute([$id]);
$projects = $stmt_projects->fetchAll();

// Fetch Payments
$stmt_payments = $pdo->prepare("SELECT py.*, p.title as project_title FROM payments py JOIN projects p ON py.project_id = p.id WHERE p.customer_id = ? ORDER BY py.id DESC");
$stmt_payments->execute([$id]);
$payments = $stmt_payments->fetchAll();

// Fetch Files
$stmt_files = $pdo->prepare("SELECT f.*, u.name as uploader FROM customer_files f JOIN users u ON f.uploaded_by = u.id WHERE f.customer_id = ? ORDER BY f.id DESC");
$stmt_files->execute([$id]);
$files = $stmt_files->fetchAll();

include 'layout/header.php';

$success = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

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

<div class="mb-10 flex items-center gap-4 animate__animated animate__fadeInLeft">
    <a href="customers.php" class="w-12 h-12 rounded-2xl bg-white border border-gray-100 flex items-center justify-center text-charcoal shadow-sm hover:shadow-xl transition-all">
        <i class="fas fa-arrow-right text-sm"></i>
    </a>
    <div>
        <h2 class="text-3xl font-black text-charcoal tracking-tight"><?php echo htmlspecialchars($customer['name']); ?></h2>
        <p class="text-sm text-gray-400 mt-1">الملف الشامل للعميل | سجل المعاملات والمشاريع.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-8 mb-12">
    
    <!-- Profile Card -->
    <div class="lg:col-span-1 space-y-6 animate__animated animate__fadeInUp">
        <div class="aeterna-card p-10 text-center">
            <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-charcoal to-graphite text-white flex items-center justify-center text-4xl font-black shadow-2xl mx-auto mb-6">
                <?php echo mb_substr($customer['name'], 0, 1, 'UTF-8'); ?>
            </div>
            <h3 class="font-black text-xl text-charcoal mb-1"><?php echo htmlspecialchars($customer['name']); ?></h3>
            <span class="text-xs font-black text-taupe uppercase tracking-widest bg-gray-50 px-3 py-1 rounded-lg border border-gray-100">
                <?php echo htmlspecialchars($customer['customer_code']); ?>
            </span>
            
            <div class="mt-10 space-y-4 text-right">
                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <div class="w-8 h-8 rounded-xl bg-white text-charcoal flex items-center justify-center text-xs shadow-sm"><i class="fas fa-phone"></i></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">رقم الهاتف</p>
                        <p class="text-sm font-black text-charcoal truncate" dir="ltr"><?php echo htmlspecialchars($customer['phone']); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <div class="w-8 h-8 rounded-xl bg-white text-charcoal flex items-center justify-center text-xs shadow-sm"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">العنوان</p>
                        <p class="text-sm font-black text-charcoal truncate"><?php echo htmlspecialchars($customer['address'] ?: 'غير محدد'); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <div class="w-8 h-8 rounded-xl bg-white text-charcoal flex items-center justify-center text-xs shadow-sm"><i class="fas fa-envelope"></i></div>
                    <div class="flex-1 min-w-0 text-right">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">البريد الإلكتروني</p>
                        <p class="text-sm font-black text-charcoal truncate" dir="ltr"><?php echo htmlspecialchars($customer['email'] ?: 'غير مسجل'); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <div class="w-8 h-8 rounded-xl bg-white text-charcoal flex items-center justify-center text-xs shadow-sm"><i class="fas fa-share-alt"></i></div>
                    <div class="flex-1 min-w-0 text-right">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">سوشيال ميديا</p>
                        <p class="text-sm font-black text-charcoal truncate" dir="ltr"><?php echo htmlspecialchars($customer['social_media'] ?: 'غير محدد'); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <div class="w-8 h-8 rounded-xl bg-white text-charcoal flex items-center justify-center text-xs shadow-sm"><i class="fas fa-bullseye"></i></div>
                    <div class="flex-1 min-w-0 text-right">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">مصدر العميل</p>
                        <p class="text-sm font-black text-charcoal truncate"><?php echo htmlspecialchars($customer['lead_source']); ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <button onclick="document.getElementById('editCustomerModal').classList.remove('hidden')" class="w-full py-4 bg-charcoal text-white rounded-2xl font-black text-xs shadow-xl hover:bg-graphite transition-all">
                    تعديل البيانات <i class="fas fa-edit mr-2"></i>
                </button>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-2 gap-4">
            <div class="aeterna-card p-6 text-center mb-0">
                <p class="text-[10px] font-black text-gray-400 uppercase mb-1">المشاريع</p>
                <p class="text-2xl font-black text-charcoal"><?php echo count($projects); ?></p>
            </div>
            <div class="aeterna-card p-6 text-center mb-0">
                <p class="text-[10px] font-black text-gray-400 uppercase mb-1">الملفات</p>
                <p class="text-2xl font-black text-charcoal"><?php echo count($files); ?></p>
            </div>
        </div>
    </div>

    <!-- Details Tabs -->
    <div class="lg:col-span-3 space-y-8 animate__animated animate__fadeInUp">
        
        <!-- Projects Section -->
        <div class="aeterna-card p-8">
            <h3 class="font-black text-xl text-charcoal mb-6 flex items-center gap-2">
                <i class="fas fa-project-diagram text-taupe text-sm"></i> المشاريع المرتبطة
            </h3>
            
            <?php if(count($projects) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach($projects as $p): ?>
                        <a href="project_view.php?id=<?php echo $p['id']; ?>" class="flex items-center gap-4 p-4 border border-gray-100 rounded-3xl hover:border-taupe hover:shadow-xl transition-all group">
                            <div class="w-12 h-12 rounded-2xl bg-gray-50 text-charcoal flex items-center justify-center text-xl group-hover:bg-charcoal group-hover:text-white transition-all">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-black text-sm text-charcoal"><?php echo htmlspecialchars($p['title']); ?></h4>
                                <p class="text-[10px] text-gray-400 mt-1">المرحلة: <?php echo $p['stage']; ?>/8</p>
                            </div>
                            <i class="fas fa-chevron-left text-gray-200 group-hover:text-taupe transition-colors"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="p-8 text-center text-gray-400 font-bold border border-dashed border-gray-100 rounded-3xl">لا توجد مشاريع مرتبطة.</div>
            <?php endif; ?>
        </div>

        <!-- Financial Record -->
        <div class="aeterna-card p-8">
            <h3 class="font-black text-xl text-charcoal mb-6 flex items-center gap-3">
                <i class="fas fa-receipt text-taupe"></i> سجل المدفوعات
            </h3>
            
            <!-- Mobile Payments View -->
            <div class="space-y-4 lg:hidden">
                <?php foreach($payments as $pay): ?>
                    <div class="p-5 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col gap-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-black text-charcoal text-sm leading-tight"><?php echo htmlspecialchars($pay['project_title']); ?></h4>
                                <span class="text-[10px] text-gray-400 font-bold"><?php echo formatDate($pay['created_at']); ?></span>
                            </div>
                            <div class="text-left">
                                <p class="font-black text-charcoal text-base" dir="ltr"><?php echo number_format($pay['amount'], 0); ?> EGP</p>
                                <span class="px-2 py-0.5 rounded-lg text-[9px] font-black <?php echo $pay['status'] == 'Paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'; ?>">
                                    <?php echo $pay['status'] == 'Paid' ? 'تم التحصيل' : 'قيد الانتظار'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($payments)): ?>
                    <p class="text-center text-gray-400 font-bold py-8">لا توجد مدفوعات مسجلة.</p>
                <?php endif; ?>
            </div>

            <!-- Desktop Payments Table -->
            <div class="hidden lg:block table-responsive rounded-2xl overflow-hidden border border-gray-100">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                            <th class="p-4">المشروع</th>
                            <th class="p-4">المبلغ</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($payments as $pay): ?>
                            <tr class="text-sm hover:bg-gray-50/50 transition-colors">
                                <td class="p-4 font-black text-charcoal"><?php echo htmlspecialchars($pay['project_title']); ?></td>
                                <td class="p-4 font-black text-charcoal" dir="ltr">EGP <?php echo number_format($pay['amount'], 2); ?></td>
                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-lg text-[10px] font-black <?php echo $pay['status'] == 'Paid' ? 'bg-green-50 text-green-600 border border-green-100' : 'bg-amber-50 text-amber-600 border border-amber-100'; ?>">
                                        <?php echo $pay['status']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-400 font-bold" dir="ltr"><?php echo formatDate($pay['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- File Archive -->
        <div class="aeterna-card p-8">
            <h3 class="font-black text-xl text-charcoal mb-6 flex items-center gap-2">
                <i class="fas fa-cloud-upload-alt text-taupe text-sm"></i> أرشيف الملفات والمستندات
            </h3>
            
            <!-- Upload Area -->
            <form method="POST" action="customers.php?action=upload_file" enctype="multipart/form-data" class="mb-8 p-6 bg-gray-50 rounded-[2rem] border border-dashed border-gray-300 text-center">
                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                <div class="flex flex-col sm:flex-row gap-4 items-center">
                    <select name="project_id" class="w-full sm:w-64 py-3 px-6 bg-white border-none rounded-2xl text-sm font-bold shadow-sm focus:ring-2 focus:ring-taupe">
                        <option value="">ملف عام للعميل</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="file" name="customer_file" required class="flex-1 text-xs text-gray-400 file:bg-charcoal file:text-white file:border-none file:px-4 file:py-2 file:rounded-xl file:font-black file:text-[10px] cursor-pointer">
                    <button type="submit" class="bg-taupe text-charcoal px-8 py-3 rounded-2xl text-xs font-black shadow-lg hover:bg-white transition-all">رفع الآن</button>
                </div>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach($files as $f): ?>
                    <div class="p-4 bg-white border border-gray-100 rounded-3xl flex items-center justify-between hover:shadow-xl transition-all">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-black text-charcoal truncate" dir="ltr"><?php echo htmlspecialchars($f['file_name']); ?></p>
                                <p class="text-[9px] text-gray-400 font-bold uppercase"><?php echo formatDate($f['uploaded_at']); ?></p>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <a href="<?php echo htmlspecialchars($f['file_path']); ?>" target="_blank" class="w-8 h-8 rounded-lg bg-gray-50 text-charcoal flex items-center justify-center hover:bg-taupe hover:text-white transition-all"><i class="fas fa-eye text-[10px]"></i></a>
                            <a href="<?php echo htmlspecialchars($f['file_path']); ?>" download class="w-8 h-8 rounded-lg bg-gray-50 text-charcoal flex items-center justify-center hover:bg-charcoal hover:text-white transition-all"><i class="fas fa-download text-[10px]"></i></a>
                            <a href="customers.php?action=delete_file&file_id=<?php echo $f['id']; ?>&customer_id=<?php echo $customer['id']; ?>" onclick="return confirm('هل أنت متأكد من حذف هذا الملف؟')" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all"><i class="fas fa-trash text-[10px]"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

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
            <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
            <input type="hidden" name="redirect_to" value="customer_view.php?id=<?php echo $customer['id']; ?>">
            <div class="space-y-6">
                <!-- Name & Phone -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">اسم العميل *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">رقم الهاتف *</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>" required dir="ltr" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">البريد الإلكتروني</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" dir="ltr" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">العنوان</label>
                    <textarea name="address" rows="2" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none resize-none"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                </div>

                <!-- Lead Source & Social Link -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">مصدر العميل</label>
                        <select name="lead_source" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm outline-none">
                            <option value="Direct" <?php echo $customer['lead_source'] == 'Direct' ? 'selected' : ''; ?>>مباشر (المعرض)</option>
                            <option value="Social" <?php echo $customer['lead_source'] == 'Social' ? 'selected' : ''; ?>>سوشيال ميديا</option>
                            <option value="Referral" <?php echo $customer['lead_source'] == 'Referral' ? 'selected' : ''; ?>>توصية عميل</option>
                            <option value="Other" <?php echo $customer['lead_source'] == 'Other' ? 'selected' : ''; ?>>أخرى</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">روابط السوشيال ميديا</label>
                        <input type="text" name="social_media" value="<?php echo htmlspecialchars($customer['social_media'] ?? ''); ?>" dir="ltr" class="w-full py-3.5 px-6 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-taupe font-bold text-sm text-left outline-none">
                    </div>
                </div>

                <!-- Account Credentials Area -->
                <div class="bg-emerald-50/50 p-6 rounded-3xl border border-emerald-100">
                    <div class="flex items-center gap-2 mb-4 text-emerald-800">
                        <i class="fas fa-user-shield text-sm"></i>
                        <h4 class="text-xs font-black uppercase tracking-wider">بيانات حساب العميل</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] font-black text-emerald-700/60 uppercase mb-2">اسم المستخدم</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($customer['username']); ?>" dir="ltr" class="w-full py-3 px-5 bg-white border border-emerald-100 rounded-xl focus:ring-2 focus:ring-emerald-400 font-bold text-sm outline-none">
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
<?php include 'layout/footer.php'; ?>

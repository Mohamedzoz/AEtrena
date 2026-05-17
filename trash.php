<?php
require_once 'config.php';
requireLogin();

// Only Managers/Admins can access trash
if (!isManagerOrAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$action = $_GET['action'] ?? 'list';
$success = '';

if ($action === 'restore' && isset($_GET['id'])) {
    $trash_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM trash WHERE id = ?");
    $stmt->execute([$trash_id]);
    $trash_item = $stmt->fetch();
    
    if ($trash_item) {
        $table = $trash_item['table_name'];
        $data = json_decode($trash_item['data_json'], true);
        
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $col_names = implode('`,`', $columns);
        
        try {
            $stmt_restore = $pdo->prepare("INSERT INTO `$table` (`$col_names`) VALUES ($placeholders)");
            $stmt_restore->execute($values);
            
            $stmt_del = $pdo->prepare("DELETE FROM trash WHERE id = ?");
            $stmt_del->execute([$trash_id]);
            
            logActivity('استعادة بيانات', "تم استعادة سجل محذوف من جدول $table بنجاح.");
            $success = "تم استعادة البيانات بنجاح!";
        } catch (Exception $e) {
            $error = "تعذر الاستعادة التلقائية: " . $e->getMessage();
        }
    }
}

if ($action === 'permanent_delete' && isset($_GET['id'])) {
    $trash_id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM trash WHERE id = ?");
    if ($stmt->execute([$trash_id])) {
        logActivity('حذف نهائي', "تم حذف سجل من سلة المهملات نهائياً.");
        $success = "تم حذف السجل نهائياً بنجاح.";
    }
}

include 'layout/header.php';
?>

<div class="mb-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 stagger">
    <div>
        <h2 class="text-3xl font-black text-charcoal tracking-tight flex items-center gap-3">
            <i class="fas fa-trash-restore text-red-500"></i> سلة المهملات
        </h2>
        <p class="text-base text-gray-500 font-bold mt-2">إدارة العناصر المحذوفة: يمكنك استعادتها لتعود لمكانها الطبيعي في النظام.</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="bg-red-50 text-red-600 px-4 py-2 rounded-xl text-[10px] font-black border border-red-100 flex items-center gap-2">
            <i class="fas fa-info-circle"></i> يتم حفظ المحذوفات مؤقتاً هنا
        </span>
    </div>
</div>

<?php if ($success): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-6 py-4 rounded-2xl shadow-sm mb-8 animate__animated animate__fadeIn">
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle text-xl"></i>
            <span class="font-bold"><?php echo htmlspecialchars($success); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="bg-red-50 border-r-4 border-red-500 text-red-700 px-6 py-4 rounded-2xl shadow-sm mb-8 animate__animated animate__fadeIn">
        <div class="flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-xl"></i>
            <span class="font-bold"><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
<?php endif; ?>

<!-- Premium Grid View (Consistent with Projects Module) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 stagger">
    <?php
    $trashes = $pdo->query("SELECT t.*, u.name as deleter_name FROM trash t LEFT JOIN users u ON t.deleted_by = u.id ORDER BY t.id DESC LIMIT 100")->fetchAll();
    
    foreach ($trashes as $item):
        $data = json_decode($item['data_json'], true);
        $preview = '';
        if(isset($data['name'])) $preview = $data['name'];
        elseif(isset($data['title'])) $preview = $data['title'];
        elseif(isset($data['email'])) $preview = $data['email'];
        else $preview = 'سجل #' . $item['original_id'];

        $tableName = $item['table_name'];
        $icon = 'fa-file';
        $accentColor = 'taupe';
        $typeName = 'عنصر';
        
        if ($tableName === 'projects') { $accentColor = 'blue-500'; $icon = 'fa-project-diagram'; $typeName = 'مشروع'; }
        elseif ($tableName === 'revenues') { $accentColor = 'emerald-500'; $icon = 'fa-arrow-down'; $typeName = 'مقبوضات'; }
        elseif ($tableName === 'expenses') { $accentColor = 'red-500'; $icon = 'fa-arrow-up'; $typeName = 'مصروفات'; }
        elseif ($tableName === 'attendance') { $accentColor = 'amber-500'; $icon = 'fa-fingerprint'; $typeName = 'حضور'; }
        elseif ($tableName === 'users') { $accentColor = 'purple-500'; $icon = 'fa-user-shield'; $typeName = 'مستخدم'; }
    ?>
    <div class="aeterna-card group h-full flex flex-col p-6 hover:shadow-2xl transition-all relative overflow-hidden bg-white/70 backdrop-blur-sm border border-white/60">
        <!-- Glow Effect -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-<?php echo $accentColor; ?>/5 rounded-full blur-3xl group-hover:bg-<?php echo $accentColor; ?>/10 transition-colors"></div>
        
        <div class="flex items-start justify-between mb-6 relative z-10">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-xl shadow-sm border border-gray-100 group-hover:bg-charcoal group-hover:text-white transition-all duration-500">
                    <i class="fas <?php echo $icon; ?>"></i>
                </div>
                <div>
                    <h4 class="font-black text-charcoal text-base leading-tight group-hover:text-taupe transition-colors"><?php echo htmlspecialchars($preview); ?></h4>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1 block"><?php echo $typeName; ?> (<?php echo htmlspecialchars($tableName); ?>)</span>
                </div>
            </div>
            <span class="text-[10px] font-black text-charcoal bg-gray-100 px-2.5 py-1 rounded-lg border border-gray-200">#<?php echo $item['original_id']; ?></span>
        </div>

        <div class="space-y-3 mb-8 relative z-10 flex-1">
            <div class="flex items-center justify-between text-xs font-bold">
                <span class="text-gray-400">حُذف بواسطة:</span>
                <span class="text-charcoal"><?php echo htmlspecialchars($item['deleter_name'] ?? 'النظام'); ?></span>
            </div>
            <div class="flex items-center justify-between text-xs font-bold">
                <span class="text-gray-400">تاريخ الحذف:</span>
                <span class="text-charcoal" dir="ltr"><?php echo formatDateTime($item['deleted_at']); ?></span>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-gray-100 mt-auto relative z-10">
            <a href="trash.php?action=restore&id=<?php echo $item['id']; ?>" 
               onclick="return confirm('استعادة هذا السجل؟');" 
               class="flex-1 bg-taupe hover:bg-charcoal text-white font-black py-3 rounded-xl text-xs text-center shadow-md active:scale-95 transition-all flex items-center justify-center gap-2">
                <i class="fas fa-undo"></i> استعادة
            </a>
            <a href="trash.php?action=permanent_delete&id=<?php echo $item['id']; ?>" 
               onclick="return confirm('حذف نهائي؟ لا يمكن التراجع!');" 
               class="w-12 h-11 bg-red-50 text-red-500 rounded-xl flex items-center justify-center border border-red-100 hover:bg-red-600 hover:text-white transition-all shadow-sm active:scale-90" 
               title="حذف نهائي">
                <i class="fas fa-trash-alt"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if(empty($trashes)): ?>
        <div class="col-span-full py-24 text-center">
            <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-5xl text-gray-200 shadow-inner border border-gray-100">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3 class="text-xl font-black text-charcoal mb-2">سلة المهملات فارغة</h3>
            <p class="text-gray-400 font-bold">لا توجد عناصر محذوفة حالياً في النظام.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'layout/footer.php'; ?>

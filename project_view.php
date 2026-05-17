<?php
require_once 'config.php';
requireLogin();

// Role Check: manage_projects capability
if (!can('manage_projects')) {
    header("Location: dashboard.php");
    exit;
}

$project_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT p.*, c.name as customer_name, c.phone as customer_phone FROM projects p JOIN customers c ON p.customer_id = c.id WHERE p.id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    die('<div style="text-align:center;padding:50px;font-family:Cairo;">المشروع غير موجود أو تم حذفه.</div>');
}

$stages = [
    1 => 'المعاينة',
    2 => 'تصميم مبدئي',
    3 => 'تعاقد',
    4 => 'رسومات تنفيذية',
    5 => 'تصنيع',
    6 => 'توريد',
    7 => 'تركيب وتسليم',
    8 => 'فيدباك'
];

$success = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Handle Update Stage
if (isset($_POST['update_stage'])) {
    $new_stage = $_POST['stage'];
    $stage_deadline_raw = $_POST['stage_deadline'] ?? '';
    $stage_deadline = !empty($stage_deadline_raw) ? parseDate($stage_deadline_raw) : null;
    
    if (empty($stage_deadline)) {
        $error = "يجب تحديد موعد إنجاز المرحلة الجديدة.";
    } else {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM project_tasks WHERE project_id = ? AND stage = ? AND is_completed = 0 AND task_name NOT LIKE '%متابعة تنفيذ تعديلات الجوب اوردر%'");
        $stmt_check->execute([$project_id, $project['stage']]);
        if ($stmt_check->fetchColumn() > 0) {
            $error = "لا يمكن الانتقال للمرحلة التالية قبل إنهاء مهام المرحلة الحالية.";
        } else {
            $stmt_upd = $pdo->prepare("UPDATE projects SET stage = ?, stage_deadline = ? WHERE id = ?");
            if ($stmt_upd->execute([$new_stage, $stage_deadline, $project_id])) {
                $stmt_hist = $pdo->prepare("INSERT INTO project_stage_history (project_id, stage_num, deadline_at) VALUES (?, ?, ?)");
                $stmt_hist->execute([$project_id, $new_stage, $stage_deadline]);
                $success = "تم ترقية المشروع إلى مرحلة: " . $stages[$new_stage];
                logActivity('تحديث مرحلة مشروع', "تم نقل المشروع #$project_id إلى " . $stages[$new_stage]);
                
                // Add reference tasks for the new stage
                $stmt_ref = $pdo->prepare("SELECT task_name FROM stage_reference_tasks WHERE stage = ?");
                $stmt_ref->execute([$new_stage]);
                $ref_tasks = $stmt_ref->fetchAll(PDO::FETCH_COLUMN);
                
                $stmt_tasks = $pdo->prepare("INSERT INTO project_tasks (project_id, stage, task_name) VALUES (?, ?, ?)");
                foreach($ref_tasks as $tname) {
                    $stmt_tasks->execute([$project_id, $new_stage, $tname]);
                }

                notifyAdmins("تحديث مرحلة مشروع", "تم نقل المشروع [{$project['title']}] إلى مرحلة [{$stages[$new_stage]}]", "project_view.php?id=$project_id", 'info');
                header("Location: project_view.php?id=$project_id&success=1");
                exit;
            }
        }
    }
}

// Handle Task Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_task') {
    $task_id = $_GET['task_id'];
    $stmt = $pdo->prepare("UPDATE project_tasks SET is_completed = NOT is_completed, completed_by = ?, completed_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $task_id]);
    header("Location: project_view.php?id=$project_id");
    exit;
}

// Handle Add Task
if (isset($_POST['add_task'])) {
    $task_name = trim($_POST['task_name']);
    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
    if (!empty($task_name)) {
        $stmt = $pdo->prepare("INSERT INTO project_tasks (project_id, stage, task_name, assigned_to) VALUES (?, ?, ?, ?)");
        $stmt->execute([$project_id, $project['stage'], $task_name, $assigned_to]);
        if ($assigned_to) {
            sendNotification($assigned_to, "مهمة جديدة مسندة إليك", "تم تكليفك بمهمة: [$task_name] في مشروع [{$project['title']}]", "project_view.php?id=$project_id", 'task');
        }
        $success = "تمت إضافة المهمة بنجاح.";
    }
}

// Handle Revert Stage
if (isset($_POST['revert_stage']) && $project['stage'] > 1) {
    $prev_stage = $project['stage'] - 1;
    $stmt = $pdo->prepare("UPDATE projects SET stage = ?, stage_deadline = NULL WHERE id = ?");
    $stmt->execute([$prev_stage, $project_id]);
    logActivity('تراجع عن مرحلة', "Reverted project #$project_id to stage " . $stages[$prev_stage]);
    header("Location: project_view.php?id=$project_id&success=1");
    exit;
}

// Handle Stage Update (Notes & Files)
if (isset($_POST['add_stage_update'])) {
    $notes = trim($_POST['stage_notes']);
    $lat = !empty($_POST['stage_lat']) ? (float)$_POST['stage_lat'] : null;
    $lng = !empty($_POST['stage_lng']) ? (float)$_POST['stage_lng'] : null;
    $file_path = '';
    
    if (isset($_FILES['stage_file']) && $_FILES['stage_file']['error'] === 0) {
        $upload_dir = getUploadPath($project['customer_id'], $project_id);
        $ext = pathinfo($_FILES['stage_file']['name'], PATHINFO_EXTENSION);
        $filename = 'stage_' . $project_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['stage_file']['tmp_name'], $upload_dir . $filename)) {
            $file_path = $upload_dir . $filename;
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO project_stage_updates (project_id, stage, notes, file_path, latitude, longitude, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$project_id, $project['stage'], $notes, $file_path, $lat, $lng, $_SESSION['user_id']]);
        logActivity('توثيق مرحلة', "تم إضافة توثيق للمرحلة " . $project['stage'] . " للمشروع #$project_id");
        header("Location: project_view.php?id=$project_id&success=1");
        exit;
    } catch (Exception $e) {
        $error = "حدث خطأ فني أثناء الحفظ: " . $e->getMessage();
    }
}

// Handle Delete Stage Update (Admin only)
if (isset($_GET['action']) && $_GET['action'] === 'delete_update' && isAdmin()) {
    $update_id = (int)$_GET['update_id'];
    $stmt = $pdo->prepare("SELECT file_path FROM project_stage_updates WHERE id = ?");
    $stmt->execute([$update_id]);
    $file_to_delete = $stmt->fetchColumn();
    if ($file_to_delete && file_exists($file_to_delete)) unlink($file_to_delete);
    $pdo->prepare("DELETE FROM project_stage_updates WHERE id = ?")->execute([$update_id]);
    logActivity('حذف توثيق مرحلة', "Deleted stage update #$update_id for project #$project_id");
    header("Location: project_view.php?id=$project_id&success=1");
    exit;
}

// Handle Update Initial Payment
if (isset($_POST['update_initial_payment'])) {
    $amount = (float)$_POST['initial_payment_amount'];
    $project_id = (int)$_POST['project_id'];
    
    // Check if Down Payment exists
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE project_id = ? AND type = 'Down Payment' LIMIT 1");
    $stmt->execute([$project_id]);
    $payment_id = $stmt->fetchColumn();
    
    if ($payment_id) {
        $stmt = $pdo->prepare("UPDATE payments SET amount = ? WHERE id = ?");
        $stmt->execute([$amount, $payment_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO payments (project_id, amount, type, status, notes, created_by) VALUES (?, ?, 'Down Payment', 'Paid', 'دفعة حجز أولية', ?)");
        $stmt->execute([$project_id, $amount, $_SESSION['user_id']]);
    }
    
    $success = "تم تحديث مبلغ الحجز.";
    header("Location: project_view.php?id=$project_id&success=1");
    exit;
}

include 'layout/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="projects.php" class="w-10 h-10 rounded-full bg-white shadow-md flex items-center justify-center text-gray-400 hover:text-charcoal transition-all">
            <i class="fas fa-arrow-right"></i>
        </a>
        <div>
            <h2 class="text-2xl font-black text-charcoal leading-tight"><?php echo htmlspecialchars($project['title']); ?></h2>
            <p class="text-sm text-gray-400">ملف المشروع الشامل والمتابعة</p>
        </div>
    </div>
    <div class="flex gap-2">
         <span class="bg-charcoal text-taupe text-[11px] font-black px-4 py-2 rounded-full shadow-md border border-taupe/20">
            كود المشروع: #<?php echo $project['id']; ?>
         </span>
         <a href="site_modifications.php?project_id=<?php echo $project_id; ?>" class="bg-taupe text-white text-[11px] font-black px-4 py-2 rounded-full shadow-md hover:bg-opacity-90 transition-all flex items-center gap-2">
            <i class="fas fa-hard-hat"></i> تعديلات المطلوبه ف المواقع
         </a>
    </div>
</div>

<?php if ($success || isset($_GET['success'])): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded shadow-sm mb-6">
        <i class="fas fa-check-circle ml-1"></i> تم تنفيذ الإجراء بنجاح.
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-50 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded shadow-sm mb-6">
        <i class="fas fa-exclamation-circle ml-1"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <div class="aeterna-card border-t-4 border-taupe">
            <h3 class="font-bold text-lg mb-6 flex items-center gap-2 text-charcoal">
                <i class="fas fa-info-circle text-taupe"></i> تفاصيل التعاقد
            </h3>
            <div class="space-y-4">
                <div class="p-3 bg-gray-50 rounded-2xl border border-gray-200">
                    <p class="text-[10px] text-gray-500 font-black mb-1 uppercase tracking-widest">العميل</p>
                    <p class="font-bold text-charcoal text-base"><?php echo htmlspecialchars($project['customer_name']); ?></p>
                    <p class="text-sm text-gray-600 mt-1 font-bold" dir="ltr"><?php echo htmlspecialchars($project['customer_phone']); ?></p>
                </div>
                
                <?php if($project['stage'] == 1): ?>
                <div class="p-3 bg-taupe/10 border border-taupe/20 rounded-2xl">
                    <p class="text-[10px] text-taupe font-black mb-1 uppercase tracking-widest flex items-center justify-between">
                        <span>مبلغ الحجز (Initial)</span>
                        <button onclick="document.getElementById('editInitialModal').classList.remove('hidden')" class="text-charcoal hover:underline">تعديل</button>
                    </p>
                    <?php
                    $stmt_p = $pdo->prepare("SELECT amount FROM payments WHERE project_id = ? AND type = 'Down Payment' LIMIT 1");
                    $stmt_p->execute([$project_id]);
                    $init_val = $stmt_p->fetchColumn() ?: 0;
                    ?>
                    <p class="font-black text-charcoal text-lg" dir="ltr"><?php echo number_format($init_val, 0); ?> <span class="text-[10px] text-gray-400">EGP</span></p>
                </div>
                <?php endif; ?>
                <div class="p-3 bg-charcoal text-white rounded-2xl shadow-lg relative overflow-hidden">
                    <p class="text-[10px] opacity-70 font-black mb-1 uppercase tracking-widest relative z-10">المرحلة الحالية</p>
                    <p class="font-black text-base relative z-10"><?php echo $project['stage']; ?>. <?php echo $stages[$project['stage']]; ?></p>
                </div>
                <?php if(!empty($project['stage_deadline'])): ?>
                    <div class="p-3 bg-amber-50 border border-amber-100 rounded-2xl">
                        <p class="text-[10px] text-amber-600 font-black mb-1 uppercase tracking-widest">الموعد المستهدف للمرحلة</p>
                        <p class="font-bold text-amber-800" dir="ltr"><?php echo formatDate($project['stage_deadline']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="aeterna-card">
            <h3 class="font-bold text-lg mb-4 text-charcoal">إدارة المرحلة</h3>
            <form method="POST">
                <?php if($project['stage'] < 8): ?>
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-400 mb-2">المرحلة التالية</label>
                        <select name="stage" class="w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold">
                            <?php foreach($stages as $num => $name): if($num > $project['stage']): ?>
                                <option value="<?php echo $num; ?>"><?php echo $num; ?>. <?php echo $name; ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-gray-400 mb-2">الموعد المستهدف (إجباري)</label>
                        <input type="text" name="stage_deadline" required placeholder="DD-MM-YYYY" class="flatpickr w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold">
                    </div>
                    <button type="submit" name="update_stage" class="w-full bg-charcoal text-white font-black py-4 rounded-2xl shadow-xl mb-3">نقل للمرحلة التالية</button>
                <?php endif; ?>
                <?php if($project['stage'] > 1): ?>
                    <button type="submit" name="revert_stage" onclick="return confirm('تراجع؟')" class="w-full py-3 bg-red-50 text-red-600 rounded-2xl font-bold text-sm border border-red-100">العودة للمرحلة السابقة</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Documentation Form with GPS -->
        <div class="aeterna-card">
            <h3 class="font-bold text-lg text-charcoal mb-4 flex items-center gap-2">
                <i class="fas fa-camera text-taupe"></i> توثيق المرحلة
            </h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="stage_lat" id="stage_lat">
                <input type="hidden" name="stage_lng" id="stage_lng">
                <textarea name="stage_notes" placeholder="اكتب ملاحظات الإنجاز..." class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-bold h-24"></textarea>
                
                <div id="gps-status-box" class="p-3 bg-blue-50 border border-blue-100 rounded-2xl flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div id="gps-indicator" class="w-2.5 h-2.5 rounded-full bg-orange-400 animate-pulse"></div>
                        <span id="gps-text" class="text-[10px] font-black text-blue-700 uppercase">تحديد موقع الـ GPS...</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <label class="flex items-center gap-2 p-3 bg-gray-50 border border-dashed border-gray-300 rounded-2xl cursor-pointer">
                        <i class="fas fa-image text-gray-400 text-xl"></i>
                        <span class="text-xs text-gray-500 font-bold">إرفاق صورة</span>
                        <input type="file" name="stage_file" class="hidden">
                    </label>
                    <button type="submit" name="add_stage_update" class="w-full bg-charcoal text-white py-3 rounded-2xl font-bold text-sm shadow-lg">حفظ التوثيق</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Checklist -->
        <div class="aeterna-card">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h3 class="font-black text-lg text-charcoal flex items-center gap-2"><i class="fas fa-tasks text-taupe"></i> قائمة المهام</h3>
                <span class="text-[10px] font-black bg-charcoal text-taupe px-3 py-1.5 rounded-full border border-taupe/20">مرحلة <?php echo $stages[$project['stage']]; ?></span>
            </div>
            <?php /* 
            if(isManagerOrAdmin()): ?>
            <form method="POST" class="flex flex-col sm:flex-row gap-2 mb-8 bg-gray-50 p-4 rounded-2xl border border-dashed">
                <input type="text" name="task_name" required placeholder="أضف مهمة..." class="flex-1 py-3 px-5 bg-white border rounded-xl text-sm font-bold">
                <select name="assigned_to" class="sm:w-40 py-3 px-4 bg-white border rounded-xl text-sm font-bold">
                    <option value="">للجميع</option>
                    <?php
                    $all_users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
                    foreach($all_users as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_task" class="bg-taupe text-white px-6 py-3 rounded-xl font-bold">إضافة</button>
            </form>
            <?php endif; 
            */ ?>

            <div class="space-y-3">
                <?php
                $task_where = "t.project_id = ? AND t.stage = ?";
                $task_params = [$project_id, $project['stage']];
                if (!isManagerOrAdmin()) { $task_where .= " AND (t.assigned_to = ? OR t.assigned_to IS NULL)"; $task_params[] = $_SESSION['user_id']; }
                $stmt_tasks = $pdo->prepare("SELECT t.*, users.name as completer_name, assigned.name as assigned_name FROM project_tasks t LEFT JOIN users ON t.completed_by = users.id LEFT JOIN users as assigned ON t.assigned_to = assigned.id WHERE $task_where ORDER BY t.id DESC");
                $stmt_tasks->execute($task_params);
                $tasks = $stmt_tasks->fetchAll();
                if($tasks): foreach($tasks as $task): ?>
                    <div class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl gap-4">
                        <div class="flex items-center gap-4">
                            <a href="project_view.php?id=<?php echo $project_id; ?>&action=toggle_task&task_id=<?php echo $task['id']; ?>" class="w-8 h-8 rounded-xl border-2 flex items-center justify-center <?php echo $task['is_completed'] ? 'bg-green-500 border-green-500 text-white' : 'border-gray-200 text-transparent'; ?>"><i class="fas fa-check text-xs"></i></a>
                            <div>
                                <span class="text-sm font-bold <?php echo $task['is_completed'] ? 'text-gray-300 line-through' : 'text-charcoal'; ?>"><?php echo htmlspecialchars($task['task_name']); ?></span>
                                <?php if($task['assigned_name']): ?><span class="block text-[9px] font-black text-taupe uppercase">لـ: <?php echo htmlspecialchars($task['assigned_name']); ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?><p class="text-center text-xs text-gray-400 font-bold py-6">لا توجد مهام.</p><?php endif; ?>
            </div>
        </div>

        <!-- Unified Timeline -->
        <div class="aeterna-card">
            <h3 class="font-black text-lg text-charcoal mb-6 flex items-center gap-2"><i class="fas fa-history text-taupe"></i> السجل الزمني والتوثيق</h3>
            <div class="space-y-6">
                <?php
                $stmt_up = $pdo->prepare("(SELECT 'stage' as type, up.id, up.notes as content, up.file_path, up.latitude, up.longitude, up.created_at, up.stage as stage_num, users.name as creator_name FROM project_stage_updates up LEFT JOIN users ON up.created_by = users.id WHERE up.project_id = ?) UNION ALL (SELECT 'report' as type, s.id, s.report_text as content, s.photo_path as file_path, s.latitude, s.longitude, s.created_at, NULL as stage_num, users.name as creator_name FROM site_reports s LEFT JOIN users ON s.user_id = users.id WHERE s.project_id = ?) ORDER BY created_at DESC");
                $stmt_up->execute([$project_id, $project_id]);
                $all_docs = $stmt_up->fetchAll();
                if($all_docs): foreach($all_docs as $doc): $is_report = ($doc['type'] === 'report'); ?>
                    <div class="p-6 bg-gray-50 rounded-3xl border <?php echo $is_report ? 'border-taupe/20 bg-taupe/5' : 'border-gray-100'; ?> relative">
                        <?php if($is_report): ?><div class="absolute top-0 left-0 px-4 py-1 bg-taupe text-white text-[9px] font-black rounded-bl-2xl uppercase">تقرير موقع</div><?php endif; ?>
                        <div class="flex flex-col md:flex-row gap-6">
                            <?php if($doc['file_path']): ?>
                                <div class="w-full md:w-40 h-40 rounded-2xl overflow-hidden shadow-sm flex-shrink-0">
                                    <img src="<?php echo $doc['file_path']; ?>" class="w-full h-full object-cover">
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-charcoal mb-4"><?php echo nl2br(htmlspecialchars($doc['content'])); ?></p>
                                <div class="flex justify-between items-center pt-4 border-t border-gray-200/50">
                                    <span class="text-[10px] text-gray-400 font-bold"><?php echo htmlspecialchars($doc['creator_name'] ?? 'نظام'); ?> - <?php echo date('d-m-Y H:i', strtotime($doc['created_at'])); ?></span>
                                    <?php if($doc['latitude']): ?><a href="https://maps.google.com/?q=<?php echo $doc['latitude'].','.$doc['longitude']; ?>" target="_blank" class="text-[10px] text-taupe font-black"><i class="fas fa-map-marker-alt"></i> GPS الموقع</a><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?><p class="text-center text-xs text-gray-400 font-bold py-10">لا يوجد سجل تاريخي.</p><?php endif; ?>
            </div>
        </div>

        <!-- Archive -->
        <div class="aeterna-card">
            <h3 class="font-black text-lg text-charcoal mb-6 flex items-center gap-2"><i class="fas fa-archive text-taupe"></i> أرشيف المشروع</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">المستندات</h4>
                    <div class="space-y-3">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM customer_files WHERE customer_id = ? ORDER BY id DESC LIMIT 5");
                        $stmt->execute([$project['customer_id']]);
                        $c_files = $stmt->fetchAll();
                        if($c_files): foreach($c_files as $cf): ?>
                            <div class="p-3 bg-gray-50 border rounded-xl flex justify-between">
                                <p class="text-[11px] font-bold text-charcoal truncate"><?php echo htmlspecialchars($cf['file_name']); ?></p>
                                <a href="<?php echo $cf['file_path']; ?>" target="_blank" class="text-gray-400 hover:text-blue-500"><i class="fas fa-external-link-alt text-[10px]"></i></a>
                            </div>
                        <?php endforeach; else: ?><p class="text-[10px] text-gray-400 italic">لا توجد مستندات.</p><?php endif; ?>
                    </div>
                </div>
                <div>
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">المعرض</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <?php
                        $stmt = $pdo->prepare("SELECT file_path FROM project_stage_updates WHERE project_id = ? AND file_path != '' ORDER BY id DESC LIMIT 6");
                        $stmt->execute([$project_id]);
                        $imgs = $stmt->fetchAll();
                        foreach($imgs as $im): ?>
                            <a href="<?php echo $im['file_path']; ?>" target="_blank" class="aspect-square rounded-lg overflow-hidden border block"><img src="<?php echo $im['file_path']; ?>" class="w-full h-full object-cover"></a>
                        <?php endforeach; ?>
                    </div>
                    <a href="archive.php?project_id=<?php echo $project_id; ?>" class="block text-center mt-4 text-[10px] font-black text-taupe uppercase hover:underline">فتح الأرشيف الكامل</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Initial Payment -->
<div id="editInitialModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-8 relative text-right animate__animated animate__zoomIn animate__faster">
        <button onclick="document.getElementById('editInitialModal').classList.add('hidden')" class="absolute top-6 left-6 text-gray-400 hover:text-charcoal transition-colors"><i class="fas fa-times text-xl"></i></button>
        <h3 class="text-xl font-black mb-2 text-charcoal">تعديل مبلغ الحجز</h3>
        <p class="text-xs text-gray-400 mb-6">تغيير قيمة دفعة التعاقد المبدئية للمشروع.</p>
        
        <form method="POST">
            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
            <div class="mb-6">
                <label class="block text-gray-700 text-xs font-bold mb-3">مبلغ الحجز (EGP)</label>
                <input type="number" name="initial_payment_amount" value="<?php echo $init_val; ?>" required dir="ltr" class="w-full py-4 px-5 border-2 border-taupe/20 rounded-2xl focus:outline-none focus:ring-2 focus:ring-taupe bg-taupe/5 text-left font-black text-xl outline-none">
            </div>
            
            <button type="submit" name="update_initial_payment" class="w-full bg-charcoal text-white font-black py-4 rounded-2xl hover:bg-graphite transition-all shadow-xl">
                حفظ التعديلات <i class="fas fa-save mr-2"></i>
            </button>
        </form>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
    function getGPS() {
        const stageLat = document.getElementById('stage_lat');
        const stageLng = document.getElementById('stage_lng');
        const gpsIndicator = document.getElementById('gps-indicator');
        const gpsText = document.getElementById('gps-text');
        const gpsStatusBox = document.getElementById('gps-status-box');

        if (!stageLat || !stageLng) return;

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    stageLat.value = pos.coords.latitude;
                    stageLng.value = pos.coords.longitude;
                    if (gpsIndicator) {
                        gpsIndicator.classList.remove('bg-orange-400', 'animate-pulse');
                        gpsIndicator.classList.add('bg-green-500');
                    }
                    if (gpsText) gpsText.innerText = 'تم تحديد الموقع بنجاح (GPS ON)';
                    if (gpsStatusBox) {
                        gpsStatusBox.classList.replace('bg-blue-50', 'bg-green-50');
                        gpsStatusBox.classList.replace('border-blue-100', 'border-green-100');
                    }
                },
                (err) => {
                    console.warn("GPS Error:", err);
                    if (gpsText) gpsText.innerText = 'تنبيه: تعذر تحديد الموقع (يمكنك الحفظ بدون GPS)';
                    if (gpsIndicator) {
                        gpsIndicator.classList.remove('bg-orange-400', 'animate-pulse');
                        gpsIndicator.classList.add('bg-gray-400');
                    }
                },
                { enableHighAccuracy: true, timeout: 5000 }
            );
        } else {
            if (gpsText) gpsText.innerText = 'المتصفح لا يدعم تحديد الموقع';
        }
    }
    document.addEventListener('DOMContentLoaded', getGPS);
</script>

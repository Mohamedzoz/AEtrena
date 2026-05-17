<?php
require_once 'config.php';
requireLogin();

if (!can('manage_projects')) {
    header("Location: dashboard.php");
    exit;
}

$project_id = $_GET['project_id'] ?? 0;
$status_filter = $_GET['status'] ?? '';

$success = '';
$error = '';

// Handle Add Modification
if (isset($_POST['add_modification'])) {
    $pid = $_POST['project_id'];
    $desc = trim($_POST['description']);
    $assigned = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
    if (!empty($desc) && $pid > 0) {
        $stmt = $pdo->prepare("INSERT INTO project_site_modifications (project_id, description, assigned_to, designer_id) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$pid, $desc, $assigned, $_SESSION['user_id']])) {
            $success = "تم إضافة طلب التعديل بنجاح.";
            if ($assigned) {
                sendNotification($assigned, "تعديل موقع جديد", "تم إسناد تعديل موقع إليك في مشروع #$pid", "site_modifications.php?project_id=$pid", 'task');
            }
            logActivity('إضافة تعديل موقع', "تم إضافة تعديل للمشروع #$pid" . ($assigned ? " وإسناده لـ #$assigned" : ""));
        }
    } else {
        $error = "يرجى كتابة وصف التعديل واختيار المشروع.";
    }
}

// Handle Update Report
if (isset($_POST['update_report'])) {
    $mid = $_POST['mod_id'];
    $report = trim($_POST['engineer_report']);
    $status = $_POST['status'];
    if ($mid > 0) {
        $stmt = $pdo->prepare("UPDATE project_site_modifications SET engineer_report = ?, status = ?, engineer_id = ? WHERE id = ?");
        if ($stmt->execute([$report, $status, $_SESSION['user_id'], $mid])) {
            $success = "تم تحديث التقرير بنجاح.";
            logActivity('تحديث تعديل موقع', "تم تحديث حالة التعديل #$mid إلى $status");
        }
    }
}

// Fetch Modifications
$query = "SELECT m.*, p.title as project_title, d.name as designer_name, e.name as engineer_name, a.name as assigned_name 
          FROM project_site_modifications m 
          JOIN projects p ON m.project_id = p.id 
          LEFT JOIN users d ON m.designer_id = d.id 
          LEFT JOIN users e ON m.engineer_id = e.id 
          LEFT JOIN users a ON m.assigned_to = a.id 
          WHERE 1=1";
$params = [];

if ($project_id > 0) {
    $query .= " AND m.project_id = ?";
    $params[] = $project_id;
}
if (!empty($status_filter)) {
    $query .= " AND m.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$modifications = $stmt->fetchAll();

include 'layout/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-black text-charcoal leading-tight">تعديلات المطلوبة في المواقع</h2>
        <p class="text-sm text-gray-400">متابعة تنفيذ تعديلات الجوب اوردر والتنسيق بين التصميم والموقع</p>
    </div>
    <div class="flex gap-2">
        <button onclick="document.getElementById('addModModal').classList.remove('hidden')" class="bg-charcoal text-taupe text-xs font-black px-6 py-3 rounded-full shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
            <i class="fas fa-plus"></i> إضافة تعديل جديد
        </button>
    </div>
</div>

<?php if ($success): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded shadow-sm mb-6">
        <i class="fas fa-check-circle ml-1"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-50 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded shadow-sm mb-6">
        <i class="fas fa-exclamation-circle ml-1"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-8 flex flex-wrap gap-4 items-center">
    <div class="flex items-center gap-2">
        <span class="text-xs font-bold text-gray-400">تصفية حسب الحالة:</span>
        <a href="site_modifications.php?project_id=<?php echo $project_id; ?>" class="px-4 py-1.5 rounded-full text-[10px] font-black border transition-all <?php echo empty($status_filter) ? 'bg-charcoal text-taupe border-charcoal' : 'bg-gray-50 text-gray-400 border-gray-200'; ?>">الكل</a>
        <a href="site_modifications.php?project_id=<?php echo $project_id; ?>&status=Pending" class="px-4 py-1.5 rounded-full text-[10px] font-black border transition-all <?php echo $status_filter == 'Pending' ? 'bg-amber-500 text-white border-amber-500' : 'bg-gray-50 text-gray-400 border-gray-200'; ?>">قيد التنفيذ</a>
        <a href="site_modifications.php?project_id=<?php echo $project_id; ?>&status=Completed" class="px-4 py-1.5 rounded-full text-[10px] font-black border transition-all <?php echo $status_filter == 'Completed' ? 'bg-green-500 text-white border-green-500' : 'bg-gray-50 text-gray-400 border-gray-200'; ?>">مكتمل</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6">
    <?php if($modifications): foreach($modifications as $mod): ?>
        <div class="aeterna-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full <?php echo $mod['status'] == 'Completed' ? 'bg-green-500' : 'bg-amber-500'; ?>"></div>
            
            <div class="flex flex-col md:flex-row justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase <?php echo $mod['status'] == 'Completed' ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-600'; ?>">
                            <?php echo $mod['status'] == 'Completed' ? 'مكتمل' : 'قيد التنفيذ'; ?>
                        </span>
                        <span class="text-[10px] text-gray-400 font-bold"><i class="far fa-calendar-alt ml-1"></i> <?php echo date('d-m-Y', strtotime($mod['created_at'])); ?></span>
                        <a href="project_view.php?id=<?php echo $mod['project_id']; ?>" class="text-taupe text-[10px] font-black hover:underline"># مشروع: <?php echo htmlspecialchars($mod['project_title']); ?></a>
                        <?php if($mod['assigned_name']): ?>
                            <span class="px-3 py-1 bg-charcoal text-white text-[9px] rounded-full font-black">موجه لـ: <?php echo htmlspecialchars($mod['assigned_name']); ?></span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-gray-100 text-gray-400 text-[9px] rounded-full font-black">موجه للجميع</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-6">
                        <p class="text-[10px] text-gray-400 font-black mb-1 uppercase tracking-widest">وصف التعديل المطلوبة (المصمم: <?php echo htmlspecialchars($mod['designer_name'] ?? 'غير معروف'); ?>)</p>
                        <p class="text-sm font-bold text-charcoal leading-relaxed"><?php echo nl2br(htmlspecialchars($mod['description'])); ?></p>
                    </div>
                    
                    <?php if(!empty($mod['engineer_report'])): ?>
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <p class="text-[10px] text-taupe font-black mb-1 uppercase tracking-widest">تقرير الموقع (المهندس: <?php echo htmlspecialchars($mod['engineer_name'] ?? 'لم يوقع بعد'); ?>)</p>
                            <p class="text-sm font-bold text-gray-600"><?php echo nl2br(htmlspecialchars($mod['engineer_report'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="md:w-64 flex flex-col justify-center border-r md:border-r-0 md:border-t-0 border-t md:pr-0 pt-4 md:pt-0 pr-0">
                    <button onclick="openReportModal(<?php echo $mod['id']; ?>, '<?php echo addslashes($mod['engineer_report']); ?>', '<?php echo $mod['status']; ?>')" class="w-full py-3 bg-white border border-gray-200 rounded-xl text-xs font-black text-charcoal hover:bg-gray-50 transition-all shadow-sm">
                        تحديث التقرير والحالة <i class="fas fa-edit mr-1 text-taupe"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; else: ?>
        <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-200">
            <i class="fas fa-clipboard-list text-4xl text-gray-200 mb-4"></i>
            <p class="text-gray-400 font-bold">لا توجد تعديلات حالياً.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add Modification -->
<div id="addModModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8 relative text-right">
        <button onclick="document.getElementById('addModModal').classList.add('hidden')" class="absolute top-6 left-6 text-gray-400 hover:text-charcoal"><i class="fas fa-times text-xl"></i></button>
        <h3 class="text-xl font-black mb-2 text-charcoal">إضافة طلب تعديل جديد</h3>
        <p class="text-xs text-gray-400 mb-6">قم بتوضيح التعديلات المطلوبة لمهندس الموقع.</p>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-xs font-bold mb-2">المشروع</label>
                <select name="project_id" required class="w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-taupe outline-none">
                    <?php
                    $projs = $pdo->query("SELECT id, title FROM projects WHERE stage < 8 ORDER BY title")->fetchAll();
                    foreach($projs as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $p['id'] == $project_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-xs font-bold mb-2">توجيه إلى (اختياري)</label>
                <select name="assigned_to" class="w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-taupe outline-none">
                    <option value="">للجميع</option>
                    <?php
                    $users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll();
                    foreach($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-xs font-bold mb-2">وصف التعديل</label>
                <textarea name="description" required placeholder="اكتب تفاصيل التعديلات المطلوبة هنا..." class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-bold h-32 focus:ring-2 focus:ring-taupe outline-none"></textarea>
            </div>
            <button type="submit" name="add_modification" class="w-full bg-charcoal text-white font-black py-4 rounded-2xl shadow-xl hover:bg-graphite transition-all">حفظ الطلب</button>
        </form>
    </div>
</div>

<!-- Modal: Update Report -->
<div id="reportModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8 relative text-right">
        <button onclick="document.getElementById('reportModal').classList.add('hidden')" class="absolute top-6 left-6 text-gray-400 hover:text-charcoal"><i class="fas fa-times text-xl"></i></button>
        <h3 class="text-xl font-black mb-2 text-charcoal">تحديث تقرير الموقع</h3>
        <p class="text-xs text-gray-400 mb-6">سجل ما تم تنفيذه في الموقع بخصوص هذا التعديل.</p>
        
        <form method="POST">
            <input type="hidden" name="mod_id" id="modal_mod_id">
            <div class="mb-4">
                <label class="block text-gray-700 text-xs font-bold mb-2">تقرير التنفيذ</label>
                <textarea name="engineer_report" id="modal_report" placeholder="ماذا حدث في الموقع؟" class="w-full p-4 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-bold h-32 focus:ring-2 focus:ring-taupe outline-none"></textarea>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-xs font-bold mb-2">الحالة</label>
                <select name="status" id="modal_status" class="w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-taupe outline-none">
                    <option value="Pending">قيد التنفيذ</option>
                    <option value="Completed">مكتمل</option>
                </select>
            </div>
            <button type="submit" name="update_report" class="w-full bg-taupe text-white font-black py-4 rounded-2xl shadow-xl hover:bg-opacity-90 transition-all">تحديث التقرير والحالة</button>
        </form>
    </div>
</div>

<script>
function openReportModal(id, report, status) {
    document.getElementById('modal_mod_id').value = id;
    document.getElementById('modal_report').value = report;
    document.getElementById('modal_status').value = status;
    document.getElementById('reportModal').classList.remove('hidden');
}
</script>

<?php include 'layout/footer.php'; ?>

<?php
require_once 'config.php';
requireLogin();

if (!can('view_reports')) {
    header("Location: dashboard.php");
    exit;
}

$project_id = $_GET['project_id'] ?? null;
$project = null;
if ($project_id) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as customer_name FROM projects p JOIN customers c ON p.customer_id = c.id WHERE p.id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
}

// Handle Delete (Admin only)
if (isAdmin() && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $report_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT photo_path FROM site_reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $photo = $stmt->fetchColumn();
    if ($photo && file_exists($photo)) @unlink($photo);
    
    $pdo->prepare("DELETE FROM site_reports WHERE id = ?")->execute([$report_id]);
    logActivity('حذف تقرير موقع', "تم حذف تقرير موقع #$report_id نهائياً.");
    header("Location: " . ($project_id ? "admin_reports.php?project_id=$project_id" : "admin_reports.php") . "&success=1");
    exit;
}

include 'layout/header.php';
?>

<div class="mb-8">
    <?php if ($project): ?>
        <div class="flex items-center gap-3 mb-2">
            <a href="admin_reports.php" class="text-taupe hover:text-stone transition-colors font-bold text-sm"><i class="fas fa-chevron-right"></i> إدارة التقارير</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal font-black text-sm"><?php echo htmlspecialchars($project['title']); ?></span>
        </div>
        <h2 class="text-2xl font-black text-charcoal">سجل النشاط الميداني</h2>
    <?php else: ?>
        <h2 class="text-2xl font-black text-charcoal tracking-tight">إدارة التقارير الميدانية</h2>
        <p class="text-sm text-gray-400 mt-1">متابعة التقارير وتوثيقات المراحل لكل مشروع.</p>
    <?php endif; ?>
</div>

<?php if (!$project): ?>
    <!-- Overview: Projects List -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        // Simplified Query to avoid subquery issues
        $projects_list = $pdo->query("
            SELECT p.id, p.title, c.name as customer_name
            FROM projects p
            JOIN customers c ON p.customer_id = c.id
            ORDER BY p.id DESC
        ")->fetchAll();

        if ($projects_list):
            foreach ($projects_list as $p):
                // Get count for each
                $stmt = $pdo->prepare("SELECT 
                    (SELECT COUNT(*) FROM site_reports WHERE project_id = ?) + 
                    (SELECT COUNT(*) FROM project_stage_updates WHERE project_id = ?) as total");
                $stmt->execute([$p['id'], $p['id']]);
                $count = $stmt->fetchColumn();
                
                if($count > 0):
        ?>
            <a href="admin_reports.php?project_id=<?php echo $p['id']; ?>" class="aeterna-card p-6 group hover:border-taupe transition-all border-r-4 border-r-taupe flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gray-50 text-taupe flex items-center justify-center group-hover:bg-taupe group-hover:text-white transition-all">
                            <i class="fas fa-folder-open text-xl"></i>
                        </div>
                        <span class="bg-charcoal text-white text-[10px] font-black px-3 py-1 rounded-full"><?php echo $count; ?> سجل</span>
                    </div>
                    <h4 class="font-black text-charcoal text-base mb-1 truncate"><?php echo htmlspecialchars($p['title']); ?></h4>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-4"><?php echo htmlspecialchars($p['customer_name']); ?></p>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                    <span class="text-[9px] font-bold text-gray-400">انقر للتفاصيل</span>
                    <i class="fas fa-chevron-left text-taupe text-[10px]"></i>
                </div>
            </a>
        <?php endif; endforeach; else: ?>
            <p class="col-span-full text-center text-gray-400 py-20 font-bold">لا توجد مشاريع مسجلة.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Project Detailed View -->
    <div class="space-y-6">
        <?php
        // Detailed UNION query for unified view
        $stmt = $pdo->prepare("
            (SELECT 'report' as type, s.id, s.report_text as content, s.photo_path as file_path, s.latitude, s.longitude, s.created_at, u.name as user_name, u.role as user_role 
             FROM site_reports s 
             LEFT JOIN users u ON s.user_id = u.id 
             WHERE s.project_id = ?)
            UNION ALL
            (SELECT 'stage' as type, up.id, up.notes as content, up.file_path, up.latitude, up.longitude, up.created_at, u.name as user_name, u.role as user_role 
             FROM project_stage_updates up 
             LEFT JOIN users u ON up.created_by = u.id 
             WHERE up.project_id = ?)
            ORDER BY created_at DESC
        ");
        $stmt->execute([$project_id, $project_id]);
        $all_data = $stmt->fetchAll();
        
        if($all_data): foreach ($all_data as $row): 
            $is_stage = ($row['type'] === 'stage');
        ?>
            <div class="bg-white rounded-3xl border <?php echo $is_stage ? 'border-blue-100' : 'border-gray-100'; ?> p-6 hover:shadow-xl transition-all group relative overflow-hidden">
                <?php if($is_stage): ?>
                    <div class="absolute top-0 left-0 px-4 py-1 bg-blue-500 text-white text-[8px] font-black uppercase rounded-br-xl">تحديث مرحلة</div>
                <?php endif; ?>

                <div class="flex flex-col md:flex-row gap-8">
                    <?php if($row['file_path']): ?>
                        <div class="w-full md:w-56 h-56 rounded-2xl overflow-hidden shadow-sm flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($row['file_path']); ?>" class="w-full h-full object-cover">
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-charcoal text-taupe flex items-center justify-center font-black text-xs">
                                        <?php echo mb_substr($row['user_name'], 0, 1, 'UTF-8'); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-charcoal text-sm"><?php echo htmlspecialchars($row['user_name']); ?></h4>
                                        <p class="text-[10px] text-taupe font-bold"><?php echo htmlspecialchars($row['user_role']); ?></p>
                                    </div>
                                </div>
                                <div class="text-left text-[10px] text-gray-400 font-bold">
                                    <?php echo date('d-m-Y | H:i', strtotime($row['created_at'])); ?>
                                </div>
                            </div>
                            <div class="bg-gray-50/50 rounded-2xl p-4 border border-gray-100">
                                <p class="text-sm font-bold text-charcoal leading-relaxed"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mt-6">
                            <?php if($row['latitude']): ?>
                                <a href="https://maps.google.com/?q=<?php echo $row['latitude'].','.$row['longitude']; ?>" target="_blank" class="px-4 py-2 bg-green-50 text-green-700 rounded-xl text-[10px] font-black flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt"></i> عرض الموقع (GPS)
                                </a>
                            <?php else: ?>
                                <span class="text-[9px] text-gray-300 italic">لا توجد بيانات موقع</span>
                            <?php endif; ?>
                            
                            <?php if(isAdmin() && !$is_stage): ?>
                                <a href="admin_reports.php?project_id=<?php echo $project_id; ?>&action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('حذف؟');" class="text-red-400 hover:text-red-600"><i class="fas fa-trash-alt text-xs"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; else: ?>
            <p class="text-center text-gray-400 py-10 font-bold">لا توجد بيانات لهذا المشروع.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>

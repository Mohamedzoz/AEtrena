<?php
require_once 'config.php';
requireLogin();

if (!can('view_hr_admin') && !can('manage_revenues') && !can('view_reports')) {
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

include 'layout/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <?php if ($project): ?>
            <div class="flex items-center gap-3 mb-2">
                <a href="archive.php" class="text-taupe hover:text-stone transition-colors"><i class="fas fa-chevron-right"></i> المكتبة المركزية</a>
                <span class="text-gray-300">/</span>
                <span class="text-charcoal font-black"><?php echo htmlspecialchars($project['title']); ?></span>
            </div>
            <h2 class="text-3xl font-black text-charcoal">أرشيف المشروع</h2>
        <?php else: ?>
            <h2 class="text-3xl font-black text-charcoal tracking-tight">المكتبة المركزية للوثائق</h2>
            <p class="text-sm text-gray-400 mt-1">أرشيف سحابي موحد لجميع الملفات، الصور، والمستندات مرتبة حسب المشاريع.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!$project): ?>
    <!-- Folder View: Projects -->
    <div class="aeterna-card p-6 mb-8 bg-gray-50 border-dashed">
        <div class="relative group">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-taupe transition-colors"></i>
            <input type="text" id="projectSearch" placeholder="ابحث عن مشروع أو عميل..." 
                   class="w-full pl-12 pr-6 py-4 bg-white border border-gray-100 rounded-2xl text-sm font-bold focus:ring-2 focus:ring-taupe outline-none transition-all shadow-sm">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="projectGrid">
        <?php
        $projects = $pdo->query("
            SELECT p.id, p.title, c.name as customer_name,
            (SELECT COUNT(*) FROM customer_files WHERE customer_id = p.customer_id) as file_count,
            (SELECT COUNT(*) FROM site_reports WHERE project_id = p.id) as report_count,
            (SELECT COUNT(*) FROM project_stage_updates WHERE project_id = p.id AND file_path != '') as doc_count
            FROM projects p
            JOIN customers c ON p.customer_id = c.id
            ORDER BY p.id DESC
        ")->fetchAll();

        foreach ($projects as $proj):
            $total_assets = $proj['file_count'] + $proj['report_count'] + $proj['doc_count'];
        ?>
            <a href="archive.php?project_id=<?php echo $proj['id']; ?>" class="aeterna-card p-0 group overflow-hidden hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 project-folder-card">
                <div class="p-8 flex flex-col items-center text-center bg-gradient-to-br from-white to-gray-50 relative">
                    <div class="w-20 h-20 rounded-3xl bg-taupe/5 text-taupe flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                        <i class="fas fa-folder-open text-4xl"></i>
                    </div>
                    
                    <h4 class="font-black text-charcoal text-base mb-1 px-4 leading-tight"><?php echo htmlspecialchars($proj['title']); ?></h4>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($proj['customer_name']); ?></p>
                    
                    <div class="mt-8 pt-6 border-t border-gray-100 w-full flex justify-around">
                        <div class="text-center">
                            <p class="text-[14px] font-black text-charcoal"><?php echo $proj['doc_count'] + $proj['report_count']; ?></p>
                            <p class="text-[8px] text-gray-400 font-bold uppercase">صور</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[14px] font-black text-charcoal"><?php echo $proj['file_count']; ?></p>
                            <p class="text-[8px] text-gray-400 font-bold uppercase">ملفات</p>
                        </div>
                    </div>
                    
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                         <i class="fas fa-external-link-alt text-taupe text-xs"></i>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    
    <script>
        document.getElementById('projectSearch').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.project-folder-card').forEach(card => {
                let text = card.innerText.toLowerCase();
                card.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>

<?php else: ?>
    <!-- Detailed Project Archive View -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar Summary -->
        <div class="lg:col-span-1">
            <div class="aeterna-card sticky top-24">
                <div class="w-16 h-16 rounded-2xl bg-charcoal text-taupe flex items-center justify-center mb-4">
                    <i class="fas fa-project-diagram text-2xl"></i>
                </div>
                <h3 class="font-black text-charcoal text-lg leading-tight mb-1"><?php echo htmlspecialchars($project['title']); ?></h3>
                <p class="text-xs text-gray-400 font-bold mb-6"><?php echo htmlspecialchars($project['customer_name']); ?></p>
                
                <div class="space-y-3">
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                        <p class="text-[10px] text-gray-400 font-black uppercase">إجمالي المحتوى</p>
                        <p class="text-sm font-black text-charcoal">
                            <?php 
                            $c1 = $pdo->query("SELECT COUNT(*) FROM customer_files WHERE customer_id = {$project['customer_id']}")->fetchColumn();
                            $c2 = $pdo->query("SELECT COUNT(*) FROM site_reports WHERE project_id = {$project_id}")->fetchColumn();
                            $c3 = $pdo->query("SELECT COUNT(*) FROM project_stage_updates WHERE project_id = {$project_id} AND file_path != ''")->fetchColumn();
                            echo ($c1 + $c2 + $c3) . " عنصر";
                            ?>
                        </p>
                    </div>
                </div>
                
                <a href="project_view.php?id=<?php echo $project_id; ?>" class="w-full mt-6 py-3 bg-white border border-charcoal text-charcoal rounded-xl text-xs font-black flex items-center justify-center gap-2 hover:bg-charcoal hover:text-white transition-all">
                    <i class="fas fa-eye"></i> الذهاب لملف المشروع
                </a>
            </div>
        </div>

        <!-- Main Content: Categorized Assets -->
        <div class="lg:col-span-3 space-y-10">
            
            <!-- Category: Contracts & Documents -->
            <div>
                <h3 class="font-black text-charcoal mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center text-xs"><i class="fas fa-file-contract"></i></span>
                    المستندات والتعاقدات
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM customer_files WHERE customer_id = ? ORDER BY id DESC");
                    $stmt->execute([$project['customer_id']]);
                    $files = $stmt->fetchAll();
                    if($files): foreach($files as $f):
                    ?>
                        <div class="p-4 bg-white rounded-2xl border border-gray-100 flex items-center justify-between group hover:border-blue-200 transition-all">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-file-pdf text-red-500 text-2xl"></i>
                                <div>
                                    <p class="text-xs font-black text-charcoal truncate max-w-[150px]"><?php echo htmlspecialchars($f['file_name']); ?></p>
                                    <p class="text-[10px] text-gray-400 font-bold"><?php echo formatDate($f['uploaded_at']); ?></p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="<?php echo $f['file_path']; ?>" target="_blank" class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center hover:bg-blue-500 hover:text-white transition-all"><i class="fas fa-eye text-xs"></i></a>
                                <a href="<?php echo $f['file_path']; ?>" download class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center hover:bg-charcoal hover:text-white transition-all"><i class="fas fa-download text-xs"></i></a>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-xs text-gray-400 italic">لا توجد مستندات مرفوعة.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category: Stage Documentation -->
            <div>
                <h3 class="font-black text-charcoal mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center text-xs"><i class="fas fa-images"></i></span>
                    صور توثيق المراحل
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM project_stage_updates WHERE project_id = ? AND file_path != '' ORDER BY id DESC");
                    $stmt->execute([$project_id]);
                    $docs = $stmt->fetchAll();
                    if($docs): foreach($docs as $d):
                    ?>
                        <div class="relative aspect-square rounded-2xl overflow-hidden border border-gray-100 group shadow-sm hover:shadow-xl transition-all">
                            <img src="<?php echo $d['file_path']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                <a href="<?php echo $d['file_path']; ?>" target="_blank" class="w-10 h-10 rounded-xl bg-white text-charcoal flex items-center justify-center"><i class="fas fa-expand"></i></a>
                            </div>
                            <div class="absolute bottom-2 right-2 px-2 py-0.5 bg-black/50 backdrop-blur-md text-white text-[8px] font-black rounded-full">مرحلة <?php echo $d['stage']; ?></div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="col-span-full text-xs text-gray-400 italic">لا توجد صور توثيق مراحل.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category: Site Reports -->
            <div>
                <h3 class="font-black text-charcoal mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center text-xs"><i class="fas fa-map-marked-alt"></i></span>
                    تقارير المواقع (GPS)
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php
                    $stmt = $pdo->prepare("SELECT s.*, u.name as user_name FROM site_reports s LEFT JOIN users u ON s.user_id = u.id WHERE s.project_id = ? ORDER BY s.id DESC");
                    $stmt->execute([$project_id]);
                    $reps = $stmt->fetchAll();
                    if($reps): foreach($reps as $r):
                    ?>
                        <div class="p-4 bg-white rounded-2xl border border-gray-100 flex flex-col group hover:border-amber-200 transition-all">
                            <div class="flex items-center gap-3 mb-3">
                                <?php if($r['photo_path']): ?>
                                    <img src="<?php echo $r['photo_path']; ?>" class="w-12 h-12 rounded-lg object-cover">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-lg bg-gray-50 flex items-center justify-center text-gray-300"><i class="fas fa-camera"></i></div>
                                <?php endif; ?>
                                <div>
                                    <p class="text-xs font-black text-charcoal leading-tight"><?php echo htmlspecialchars(mb_substr($r['report_text'], 0, 40, 'UTF-8')) . '...'; ?></p>
                                    <p class="text-[9px] text-gray-400 font-bold"><?php echo htmlspecialchars($r['user_name']); ?> - <?php echo formatDate($r['created_at']); ?></p>
                                </div>
                            </div>
                            <div class="flex justify-between items-center mt-auto">
                                <a href="https://maps.google.com/?q=<?php echo $r['latitude'].','.$r['longitude']; ?>" target="_blank" class="text-[9px] font-black text-taupe flex items-center gap-1"><i class="fas fa-location-arrow"></i> GPS الموقع</a>
                                <a href="<?php echo $r['photo_path']; ?>" target="_blank" class="text-[9px] font-black text-charcoal">فتح التقرير <i class="fas fa-chevron-left ml-1"></i></a>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-xs text-gray-400 italic">لا توجد تقارير مواقع.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>

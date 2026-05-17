<?php
require_once 'config.php';
requireLogin();

// Role Check: manage_projects capability
if (!can('manage_projects')) {
    header("Location: dashboard.php");
    exit;
}

$stages = [
    1 => 'معاينة',
    2 => 'تصميم مبدئي',
    3 => 'تعاقد',
    4 => 'رسومات تنفيذية',
    5 => 'تصنيع',
    6 => 'توريد',
    7 => 'تركيب وتسليم',
    8 => 'فيدباك'
];

$success = $_GET['success'] ?? '';
include 'layout/header.php';
?>

<!-- CSS مخصص لحركة الفتح والقفل بنعومة وتنسيق الكروت -->
<style>
    .stage-collapse {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stage-collapse.is-open {
        grid-template-rows: 1fr;
    }
    .stage-collapse-inner {
        overflow: hidden;
    }
    .chevron-icon {
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .chevron-icon.is-open {
        transform: rotate(180deg);
    }
    
    /* تحسين شكل زر المرحلة */
    .stage-btn {
        background: linear-gradient(145deg, #ffffff, #f9fafb);
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    .stage-btn:hover {
        background: linear-gradient(145deg, #f9fafb, #f3f4f6);
        border-color: #d1d5db;
    }
</style>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
    <div>
        <h2 class="text-3xl font-black text-charcoal tracking-tight">سير عمل المشاريع</h2>
        <p class="text-base text-gray-600 font-bold mt-2">تتبع المراحل الـ 8 من المعاينة حتى التسليم النهائي بكل سهولة.</p>
    </div>
    <div class="flex gap-3 flex-wrap">
        <a href="site_modifications.php" class="bg-taupe text-white py-3.5 px-8 text-base font-black flex items-center shadow-premium transform hover:scale-105 transition-all shrink-0 rounded-2xl">
            <i class="fas fa-hard-hat ml-2"></i> تعديلات المطلوبه ف المواقع
        </a>
        <?php if(hasRole(['Manager', 'Secretary', 'Admin'])): ?>
        <a href="project_add.php" class="btn-primary py-3.5 px-8 text-base font-black flex items-center shadow-premium transform hover:scale-105 transition-all shrink-0">
            <i class="fas fa-plus ml-2"></i> بدء مشروع جديد
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($success): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-4 rounded-2xl shadow-sm mb-8 animate__animated animate__fadeIn">
        <i class="fas fa-check-circle ml-2 text-xl"></i> تمت العملية بنجاح.
    </div>
<?php endif; ?>

<!-- Kanban Board (Accordion Layout) -->
<div class="space-y-5">
    <?php foreach ($stages as $stage_num => $stage_name): 
        $stmt = $pdo->prepare("SELECT p.*, c.name as customer_name FROM projects p JOIN customers c ON p.customer_id = c.id WHERE p.stage = ? ORDER BY p.id DESC");
        $stmt->execute([$stage_num]);
        $projects = $stmt->fetchAll();
        $count = count($projects);
        $stage_active = $count > 0;
        
        // مقفولة دايماً بشكل افتراضي
        $isOpen = false;
    ?>
        <div class="rounded-3xl transition-all duration-300">
            
            <!-- Stage Header (Toggle Button) -->
            <!-- تم إضافة relative z-10 لضمان إن الزرار يفضل فوق القائمة وميغطيش عليها -->
            <button onclick="toggleStage('stage-<?php echo $stage_num; ?>', this)" class="stage-btn w-full flex items-center justify-between text-right p-4 rounded-2xl transition-all group outline-none relative z-10">
                <div class="flex items-center gap-5">
                    <!-- رقم المرحلة -->
                    <div class="w-16 h-16 shrink-0 <?php echo $stage_active ? 'bg-charcoal text-white shadow-lg' : 'bg-gray-100 text-gray-400'; ?> rounded-2xl flex items-center justify-center font-black text-2xl transition-transform duration-300 group-hover:scale-105">
                        <?php echo $stage_num; ?>
                    </div>
                    
                    <!-- اسم المرحلة والعدد -->
                    <div>
                        <h3 class="font-black text-charcoal text-2xl group-hover:text-taupe transition-colors"><?php echo $stage_name; ?></h3>
                        <div class="flex items-center mt-2">
                            <span class="text-sm text-white font-black tracking-wider <?php echo $stage_active ? 'bg-charcoal' : 'bg-gray-500'; ?> px-4 py-1.5 rounded-full shadow-md">
                                <?php echo $count; ?> مشاريع
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Chevron Arrow -->
                <div class="ml-2 w-12 h-12 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 group-hover:bg-charcoal group-hover:text-white group-hover:border-charcoal shadow-sm transition-all chevron-icon text-xl <?php echo $isOpen ? 'is-open' : ''; ?>">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </button>
            
            <!-- Collapsible Content -->
            <div id="stage-<?php echo $stage_num; ?>" class="stage-collapse <?php echo $isOpen ? 'is-open' : ''; ?>">
                <div class="stage-collapse-inner">
                    <!-- تم إزالة z-[-1] اللي كانت بتلغي الكليكات واستبدالها بـ z-0 -->
                    <div class="pt-8 pb-6 px-2 lg:px-4 bg-gray-50/50 rounded-b-3xl border-x border-b border-gray-100 -mt-4 relative z-0">
                        
                        <!-- Projects Grid -->
                        <?php if ($stage_active): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mt-4">
                                <?php foreach ($projects as $project): 
                                    // Time Status Calculation
                                    $status_badge = '';
                                    if (!empty($project['stage_deadline'])) {
                                        $deadline = new DateTime($project['stage_deadline']);
                                        $today = new DateTime('today');
                                        $diff = $today->diff($deadline);
                                        $days = $diff->days;
                                        $is_past = $diff->invert;

                                        if ($is_past && $days > 0) {
                                            $status_badge = '<span class="text-xs bg-red-100 text-red-700 px-3 py-1.5 rounded-lg font-black flex items-center gap-1.5 animate-pulse border border-red-200"><i class="fas fa-exclamation-triangle"></i> متأخر '.$days.' يوم</span>';
                                        } elseif ($days == 0) {
                                            $status_badge = '<span class="text-xs bg-amber-100 text-amber-700 px-3 py-1.5 rounded-lg font-black flex items-center gap-1.5 border border-amber-200"><i class="fas fa-clock"></i> تسليم اليوم</span>';
                                        } else {
                                            $status_badge = '<span class="text-xs bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-lg font-black flex items-center gap-1.5 border border-emerald-200"><i class="fas fa-calendar-check"></i> متبقي '.$days.' يوم</span>';
                                        }
                                    } else {
                                        $status_badge = '<span class="text-xs bg-gray-100 text-gray-500 px-3 py-1.5 rounded-lg font-black border border-gray-200">بدون ميعاد</span>';
                                    }
                                ?>
                                    <!-- الكارت بالكامل بقى لينك بيفتح في نفس التاب -->
                                    <a href="project_view.php?id=<?php echo $project['id']; ?>" class="block bg-white border border-gray-200 rounded-2xl p-5 hover:-translate-y-1 hover:shadow-lg transition-all group border-r-4 hover:border-r-taupe h-full cursor-pointer">
                                        <div class="flex flex-col h-full">
                                            <div class="flex justify-between items-start mb-4">
                                                <h4 class="font-black text-charcoal text-lg leading-tight group-hover:text-taupe transition-colors"><?php echo htmlspecialchars($project['title']); ?></h4>
                                                <span class="text-xs font-black text-charcoal bg-gray-100 px-2.5 py-1 rounded-lg border border-gray-200">#<?php echo $project['id']; ?></span>
                                            </div>
                                            
                                            <div class="space-y-4 mb-6 flex-1">
                                                <p class="text-sm font-black text-gray-700 flex items-center gap-2">
                                                    <i class="fas fa-user-circle text-gray-500 text-lg"></i>
                                                    <?php echo htmlspecialchars($project['customer_name']); ?>
                                                </p>
                                                <?php if(can('view_budgets')): ?>
                                                <p class="text-sm font-black text-accent flex items-center gap-2">
                                                    <i class="fas fa-coins text-taupe text-lg"></i>
                                                    EGP <?php echo number_format($project['total_value']); ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="flex justify-between items-center pt-4 border-t border-gray-100 mt-auto">
                                                <div><?php echo $status_badge; ?></div>
                                                <!-- الزرار موجود كشكل جمالي لتعزيز الـ UX (Call to action) -->
                                                <span class="text-xs font-black text-white bg-taupe group-hover:bg-charcoal px-4 py-2 rounded-xl shadow-md transition-all flex items-center gap-2">
                                                    تفاصيل <i class="fas fa-external-link-alt"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="py-12 border-2 border-dashed border-gray-300 rounded-2xl text-center text-gray-500 text-base font-bold bg-white mt-4">
                               <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl text-gray-300">
                                   <i class="fas fa-folder-open"></i>
                               </div>
                               لا توجد مشاريع في هذه المرحلة حالياً
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            
        </div>
    <?php endforeach; ?>
</div>

<!-- Extra Spacer for mobile navigation -->
<div class="h-24"></div>

<script>
// سكربت الفتح والقفل
function toggleStage(stageId, btnElement) {
    const content = document.getElementById(stageId);
    const chevron = btnElement.querySelector('.chevron-icon');

    content.classList.toggle('is-open');
    chevron.classList.toggle('is-open');
}
</script>

<?php include 'layout/footer.php'; ?>
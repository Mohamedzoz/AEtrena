<?php
require 'config.php';

// 1. Create task_templates table
$sql_table = "CREATE TABLE IF NOT EXISTS task_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_num INT NOT NULL,
    task_name VARCHAR(255) NOT NULL
)";
$pdo->query($sql_table);

// 2. Clear and Populate task_templates
$pdo->query("TRUNCATE TABLE task_templates");

$tasks = [
    1 => ['رفع المقاسات', 'تصوير الموقع', 'مقاسات وأنواع الاجهزة الكهربائية المبدأية', 'متابعة تنفيذ تعديلات الجوب اوردر اللازمة في الموقع', 'رسم الرفع الموقعي 2d و 3d'],
    2 => ['الموافقة على التصميم مع العميل', 'اختيار الخامات بالأكواد والألوان والصور', 'تأكيد التكلفة النهائية'],
    3 => ['توقيع العقد والمرفقات', 'تحصيل الدفعة الأولى'],
    4 => ['الحصول على المقاسات والانواع النهائية للاجهزة من العميل', 'إعداد الـ Job Order ومراجعة المواصفات والتوقيع من المدير', 'التوقيع من العميل', 'تحصيل الدفعة الثانية', 'تبليغ مهندس التنفيذ بمتابعة تعديلات الموقع مع المسؤول'],
    5 => ['تحصيل الدفعة الثالثة', 'استلام المطبخ على ارض المصنع', 'متابعة وانهاء تعاملات الموردين الخارجين', 'توثيق تنفيذ المطبخ مع العميل في حالة طلبه'],
    6 => ['تنسيق موعد التسليم بين العميل والمورد', 'توقيع محضر استلام من الموردين', 'توقيع محضر استلام في الموقع من العميل'],
    7 => ['تركيب المنتج النهائي', 'تشغيل المنتج وتسليمه للعميل', 'توقيع محضر استلام نهائي من العميل'],
    8 => ['جمع تقييم العميل', 'أرشفة صور المشروع النهائية']
];

$stmt = $pdo->prepare("INSERT INTO task_templates (stage_num, task_name) VALUES (?, ?)");
foreach($tasks as $stage => $stage_tasks) {
    foreach($stage_tasks as $tname) {
        $stmt->execute([$stage, $tname]);
    }
}

echo "task_templates created and populated.\n";

// 3. Sync existing projects (as requested by user's SQL snippet)
// This will add missing tasks to projects at their current stages.
$pdo->query("
    INSERT INTO project_tasks (project_id, stage, task_name)
    SELECT p.id, p.stage, t.task_name
    FROM projects p
    JOIN task_templates t ON p.stage = t.stage_num
    WHERE NOT EXISTS (
        SELECT 1 FROM project_tasks pt
        WHERE pt.project_id = p.id
        AND pt.stage = p.stage
        AND pt.task_name = t.task_name
    )
");

echo "Existing projects synced with new task list.\n";
?>

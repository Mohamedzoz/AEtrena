<?php
require_once 'config.php';
requireLogin();

// Role Check: create_project capability
if (!can('create_project')) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle Add Project
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? 0;
    $title = trim($_POST['title'] ?? '');
    $total_value = $_POST['total_value'] ?? 0;
    $stage_deadline = !empty($_POST['stage_deadline']) ? $_POST['stage_deadline'] : null;
    
    // Handle Quick Customer Add
    if ($customer_id === 'new') {
        $new_name = trim($_POST['new_customer_name'] ?? '');
        $new_phone = trim($_POST['new_customer_phone'] ?? '');
        if (empty($new_name) || empty($new_phone)) {
            $error = 'يجب إدخال اسم ورقم هاتف العميل الجديد.';
        } else {
            $stmt = $pdo->query("SELECT MAX(id) FROM customers");
            $maxId = $stmt->fetchColumn() ?: 1000;
            $customerCode = 'CUST-' . ($maxId + 1);
            $password_hash = password_hash(substr(str_shuffle('123456789ABCDEF'), 0, 6), PASSWORD_DEFAULT);
            
            $stmt_cust = $pdo->prepare("INSERT INTO customers (customer_code, name, phone, password_hash) VALUES (?, ?, ?, ?)");
            $stmt_cust->execute([$customerCode, $new_name, $new_phone, $password_hash]);
            $customer_id = $pdo->lastInsertId();
        }
    }
    
    if (empty($error)) {
        if (empty($title) || empty($customer_id)) {
            $error = 'الرجاء إدخال اسم المشروع واختيار العميل.';
        } else {
            $initial_payment = (float)($_POST['initial_payment'] ?? 0);
            $start_stage = 1;
            $task_assignments = $_POST['task_assignee'] ?? []; // Array of task_name => user_id

            $stmt = $pdo->prepare("INSERT INTO projects (customer_id, title, total_value, stage, stage_deadline) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$customer_id, $title, $total_value, $start_stage, $stage_deadline])) {
                $project_id = $pdo->lastInsertId();

                // Record initial payment if exists
                if ($initial_payment > 0) {
                    $stmt_pay = $pdo->prepare("INSERT INTO payments (project_id, amount, type, status, notes, created_by) VALUES (?, ?, 'Down Payment', 'Paid', 'دفعة حجز أولية عند فتح المشروع', ?)");
                    $stmt_pay->execute([$project_id, $initial_payment, $_SESSION['user_id']]);
                }

                // Add initial tasks from reference table
                $stmt_ref = $pdo->prepare("SELECT task_name FROM stage_reference_tasks WHERE stage = ?");
                $stmt_ref->execute([$start_stage]);
                $initial_tasks = $stmt_ref->fetchAll(PDO::FETCH_COLUMN);

                $stmt_tasks = $pdo->prepare("INSERT INTO project_tasks (project_id, stage, task_name, assigned_to) VALUES (?, ?, ?, ?)");
                foreach($initial_tasks as $task_name) {
                    $assignee = !empty($task_assignments[$task_name]) ? (int)$task_assignments[$task_name] : null;
                    $stmt_tasks->execute([$project_id, $start_stage, $task_name, $assignee]);
                    
                    if ($assignee) {
                        sendNotification($assignee, "مهمة جديدة مسندة إليك", "تم تكليفك بمهمة: [$task_name] في مشروع جديد: [$title]", "project_view.php?id=$project_id", 'task');
                    }
                }
                
                logActivity('فتح مشروع', "تم بدء مشروع جديد: $title للعميل #$customer_id بمبلغ حجز $initial_payment");
                header("Location: projects.php?success=1");
                exit;
            } else {
                $error = 'حدث خطأ أثناء إنشاء المشروع.';
            }
        }
    }
}

include 'layout/header.php';
?>

<div class="mb-8 flex items-center gap-4">
    <a href="projects.php" class="w-10 h-10 rounded-full bg-white shadow-md flex items-center justify-center text-gray-400 hover:text-charcoal transition-all">
        <i class="fas fa-arrow-right"></i>
    </a>
    <div>
        <h2 class="text-2xl font-black text-charcoal">بدء مشروع جديد</h2>
        <p class="text-sm text-gray-400">افتتاح ملف مشروع وعميل جديد في النظام.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="bg-red-50 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded shadow-sm mb-6">
        <i class="fas fa-exclamation-circle ml-1"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white p-8 rounded-3xl shadow-2xl border border-gray-100">
        <form method="POST" action="" class="space-y-6">
            
            <!-- Customer Selection -->
            <div>
                <label class="block text-charcoal text-xs font-black mb-3 uppercase tracking-widest">اختيار العميل *</label>
                <div class="relative">
                    <select name="customer_id" id="customer_id" required class="w-full py-4 px-5 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-taupe bg-gray-50 appearance-none transition-all font-bold text-sm">
                        <option value="">-- اضغط لاختيار عميل من المسجلين --</option>
                        <option value="new" class="text-taupe font-black">✨ إضافة عميل جديد سريعاً (Quick Add)</option>
                        <?php
                        $stmt = $pdo->query("SELECT id, name, customer_code FROM customers ORDER BY name");
                        while($row = $stmt->fetch()):
                        ?>
                            <option value="<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['name'] . ' (' . $row['customer_code'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 pointer-events-none text-gray-300">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>

                <!-- Quick Add Customer Fields -->
                <div id="newCustomerFields" class="hidden mt-4 bg-blue-50 border border-blue-100 p-6 rounded-2xl space-y-4 animate__animated animate__fadeInDown">
                    <h4 class="font-black text-xs text-blue-800 uppercase tracking-widest flex items-center gap-2"><i class="fas fa-user-plus"></i> بيانات العميل الجديد</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <input type="text" name="new_customer_name" placeholder="اسم العميل الكامل" class="w-full py-3 px-4 border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-taupe outline-none text-sm">
                        <input type="text" name="new_customer_phone" dir="ltr" placeholder="رقم الهاتف" class="w-full py-3 px-4 border border-gray-200 rounded-xl text-left bg-white focus:ring-2 focus:ring-taupe outline-none text-sm">
                    </div>
                </div>
            </div>
            
            <!-- Project Title -->
            <div>
                <label class="block text-charcoal text-xs font-black mb-3 uppercase tracking-widest">وصف أو عنوان المشروع *</label>
                <input type="text" name="title" required placeholder="مثال: مطبخ فيلا الشيخ زايد - الأستاذ محمد" class="w-full py-4 px-5 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-taupe bg-gray-50 outline-none font-bold">
            </div>
            
            <!-- Values & Deadlines -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-charcoal text-xs font-black mb-3 uppercase tracking-widest">القيمة التعاقدية التقديرية</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="total_value" dir="ltr" placeholder="0.00" class="w-full py-4 px-5 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-taupe bg-gray-50 text-left font-black text-lg outline-none">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 font-bold text-xs">EGP</span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-charcoal text-xs font-black mb-3 uppercase tracking-widest">مبلغ الحجز المبدئي (Initial)</label>
                    <div class="relative">
                        <input type="number" step="1" name="initial_payment" value="3000" dir="ltr" placeholder="3000" class="w-full py-4 px-5 border-2 border-taupe/20 rounded-2xl focus:outline-none focus:ring-2 focus:ring-taupe bg-taupe/5 text-left font-black text-lg outline-none">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-taupe font-bold text-xs">EGP</span>
                    </div>
                    <p class="text-[9px] text-gray-400 mt-2 font-bold"><i class="fas fa-info-circle ml-1"></i> دفع مبلغ حجز يحول المشروع تلقائياً لمرحلة "المعاينة".</p>
                </div>

                <input type="hidden" name="stage" value="1">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100 sm:col-span-2">
                    <label class="block text-charcoal text-xs font-black mb-4 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-tasks text-taupe"></i> إسناد مهام المعاينة (المرحلة 1)
                    </label>
                    <div id="initialTasksContainer" class="space-y-4">
                        <!-- Tasks will be loaded here via AJAX -->
                        <?php 
                        $stmt_ref = $pdo->prepare("SELECT task_name FROM stage_reference_tasks WHERE stage = 1");
                        $stmt_ref->execute();
                        $initial_tasks = $stmt_ref->fetchAll(PDO::FETCH_COLUMN);
                        $users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
                        foreach($initial_tasks as $task_name): 
                        ?>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-white rounded-xl border border-gray-100">
                            <span class="text-sm font-bold text-charcoal"><?php echo $task_name; ?></span>
                            <select name="task_assignee[<?php echo $task_name; ?>]" class="py-2 px-4 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-taupe bg-gray-50 text-xs font-bold w-full sm:w-48">
                                <option value="">-- للجميع --</option>
                                <?php foreach($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="sm:col-span-2">
                    <label class="block text-charcoal text-xs font-black mb-3 uppercase tracking-widest">موعد المعاينة المتوقع</label>
                    <input type="date" name="stage_deadline" class="w-full py-4 px-5 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-taupe bg-gray-50 outline-none text-sm font-bold">
                </div>
            </div>
            
            <!-- Action -->
            <div class="pt-4">
                <button type="submit" class="w-full bg-charcoal text-white py-5 rounded-2xl font-black text-lg shadow-xl hover:bg-graphite hover:shadow-2xl transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-3">
                    <i class="fas fa-rocket"></i> بدء ملف المشروع الآن
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('customer_id').addEventListener('change', function() {
        const fields = document.getElementById('newCustomerFields');
        if(this.value === 'new') {
            fields.classList.remove('hidden');
        } else {
            fields.classList.add('hidden');
        }
    });
</script>

<?php include 'layout/footer.php'; ?>

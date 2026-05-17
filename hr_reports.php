<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Site Report Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $project_id = $_POST['project_id'] ?? null;
    $report_text = $_POST['report_text'] ?? '';
    $lat = $_POST['report_lat'] ?? 0;
    $lng = $_POST['report_lng'] ?? 0;
    $photo_path = null;

    if (isset($_FILES['site_photo']) && $_FILES['site_photo']['error'] == 0) {
        $customer_id = null;
        if ($project_id) {
            $stmt_c = $pdo->prepare("SELECT customer_id FROM projects WHERE id = ?");
            $stmt_c->execute([$project_id]);
            $customer_id = $stmt_c->fetchColumn();
        }

        if ($customer_id) {
            $upload_dir = getUploadPath($customer_id, $project_id);
        } else {
            $upload_dir = 'uploads/General_Reports/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['site_photo']['name']);
        $photo_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['site_photo']['tmp_name'], $photo_path);
    }
    
    if (empty($report_text)) {
        $error = "الرجاء كتابة تفاصيل التقرير.";
    } elseif ($lat == 0 || $lng == 0) {
        $error = "عذراً، يجب السماح بالوصول للموقع الجغرافي (GPS) لرفع تقرير الموقع.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO site_reports (user_id, project_id, report_text, photo_path, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $project_id, $report_text, $photo_path, $lat, $lng])) {
            $success = "تم رفع تقرير الموقع بنجاح.";
            logActivity('تقرير موقع', "قام " . $_SESSION['user_name'] . " برفع تقرير موقع جديد من الإحداثيات ($lat, $lng)");
            
            // Notify Admins
            $proj_name = $project_id ? "لمشروع " . $pdo->query("SELECT title FROM projects WHERE id = $project_id")->fetchColumn() : "عام";
            notifyAdmins("تقرير موقع جديد", "قام المهندس [{$_SESSION['user_name']}] برفع تقرير موقع جديد ($proj_name).", "admin_reports.php", 'info');
        } else {
            $error = "حدث خطأ أثناء رفع التقرير.";
        }
    }
}

include 'layout/header.php';
?>

<div class="mb-6 flex justify-between items-end">
    <div>
        <h2 class="text-xl font-bold text-charcoal flex items-center gap-2">
            <i class="fas fa-camera text-taupe"></i> تقارير المواقع اليومية
        </h2>
        <p class="text-xs text-gray-400 mt-1">وثّق إنجازاتك اليومية من أرض الموقع بالصور والموقع الجغرافي.</p>
    </div>
    <?php if(can('view_reports')): ?>
    <a href="admin_reports.php" class="bg-charcoal text-white px-4 py-2 rounded-xl text-[10px] font-black shadow-lg hover:bg-taupe transition-all">
        <i class="fas fa-users-cog ml-1"></i> لوحة تحكم الإدارة
    </a>
    <?php endif; ?>
</div>

<?php if ($success): ?>
    <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded shadow-sm mb-6 animate__animated animate__fadeIn">
        <i class="fas fa-check-circle ml-1"></i> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-50 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded shadow-sm mb-6 animate__animated animate__fadeIn">
        <i class="fas fa-exclamation-circle ml-1"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Report Form -->
    <div class="lg:col-span-1">
        <div class="aeterna-card sticky top-24">
            <h3 class="font-bold text-lg mb-4 text-charcoal">كتابة تقرير جديد</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="report_lat" id="report_lat">
                <input type="hidden" name="report_lng" id="report_lng">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-xs font-bold mb-2">المشروع المرتبط</label>
                    <select name="project_id" class="w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-taupe outline-none">
                        <option value="">-- تقرير عام (بدون مشروع) --</option>
                        <?php
                        $stmt = $pdo->query("SELECT id, title FROM projects ORDER BY id DESC");
                        while($row = $stmt->fetch()):
                        ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-xs font-bold mb-2">تفاصيل الإنجاز</label>
                    <textarea name="report_text" required rows="4" placeholder="ماذا تم إنجازه اليوم؟" class="w-full py-3 px-4 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-taupe outline-none"></textarea>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-xs font-bold mb-2">صورة الموقع (GPS Tagged)</label>
                    <label for="site_photo" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-all overflow-hidden relative group">
                        <div id="upload-prompt" class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-camera text-xl text-taupe mb-2"></i>
                            <p class="text-[10px] text-gray-400 font-bold">اضغط للتصوير أو الرفع</p>
                        </div>
                        <div id="image-preview-container" class="absolute inset-0 w-full h-full hidden bg-white">
                            <img id="image-preview" src="" class="w-full h-full object-cover">
                        </div>
                        <input id="site_photo" type="file" name="site_photo" accept="image/*" capture="environment" class="hidden" onchange="previewImage(this)" />
                    </label>
                </div>
                
                <div id="gps-status" class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-xl flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div id="gps-indicator" class="w-2.5 h-2.5 rounded-full bg-orange-400 animate-pulse"></div>
                        <span id="gps-text" class="text-[10px] font-bold text-blue-700 uppercase">جاري تحديد الموقع...</span>
                    </div>
                    <i class="fas fa-location-arrow text-blue-400 text-xs"></i>
                </div>

                <button type="submit" name="submit_report" id="submit-btn" disabled class="w-full bg-gray-300 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg flex items-center justify-center gap-2 cursor-not-allowed">
                    <i class="fas fa-lock text-xs"></i> بانتظار تحديد الموقع...
                </button>
            </form>
        </div>
    </div>

    <!-- Reports History -->
    <div class="lg:col-span-2">
        <div class="aeterna-card">
            <h3 class="font-bold text-lg mb-4 text-charcoal">أرشيف تقاريري</h3>
            <div class="space-y-4">
                <?php
                $stmt = $pdo->prepare("SELECT s.*, p.title as project_title FROM site_reports s LEFT JOIN projects p ON s.project_id = p.id WHERE s.user_id = ? ORDER BY s.id DESC");
                $stmt->execute([$user_id]);
                $reports = $stmt->fetchAll();
                
                if (count($reports) > 0):
                    foreach ($reports as $rep):
                ?>
                    <div class="bg-white rounded-3xl border border-gray-100 p-5 hover:shadow-xl hover:border-taupe/30 transition-all duration-300 group">
                        <div class="flex flex-col md:flex-row gap-6">
                            <?php if(!empty($rep['photo_path'])): ?>
                                <div class="relative w-full md:w-48 h-48 rounded-2xl overflow-hidden flex-shrink-0 shadow-inner bg-gray-50 border border-gray-200">
                                    <img src="<?php echo htmlspecialchars($rep['photo_path']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                                        <a href="<?php echo htmlspecialchars($rep['photo_path']); ?>" target="_blank" class="w-full py-2 bg-white/20 backdrop-blur-md text-white rounded-xl text-center text-[10px] font-black uppercase tracking-widest border border-white/30 hover:bg-white/40 transition-colors">
                                            <i class="fas fa-expand-alt mr-1"></i> تكبير الصورة
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="w-full md:w-48 h-48 rounded-2xl bg-gray-50 border-2 border-dashed border-gray-200 flex flex-col items-center justify-center text-gray-300">
                                    <i class="fas fa-image text-3xl mb-2"></i>
                                    <span class="text-[9px] font-bold">لا توجد صورة</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex-1 flex flex-col">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="bg-charcoal text-taupe text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-tighter border border-taupe/20">
                                                <i class="fas fa-project-diagram mr-1 opacity-60"></i>
                                                <?php echo $rep['project_title'] ? htmlspecialchars($rep['project_title']) : 'تقرير عام للموقع'; ?>
                                            </span>
                                        </div>
                                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest"><?php echo formatDateTime($rep['created_at']); ?></h4>
                                    </div>
                                    
                                    <?php if(isAdmin()): ?>
                                        <a href="admin_reports.php?action=delete&id=<?php echo $rep['id']; ?>" onclick="return confirm('حذف هذا التقرير نهائياً؟');" class="w-8 h-8 rounded-full bg-red-50 text-red-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                            <i class="fas fa-trash-alt text-[10px]"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="bg-gray-50/50 rounded-2xl p-4 border border-gray-100/50 mb-4 flex-1">
                                    <p class="text-sm font-bold text-charcoal leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($rep['report_text'])); ?>
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <?php if ($rep['latitude']): ?>
                                        <a href="https://maps.google.com/?q=<?php echo $rep['latitude'].','.$rep['longitude']; ?>" target="_blank" class="px-3 py-2 bg-taupe/5 hover:bg-taupe/10 border border-taupe/10 rounded-xl text-[10px] font-black text-taupe flex items-center gap-2 transition-all">
                                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                            <i class="fas fa-map-marked-alt"></i> موقع الإرسال الجغرافي (GPS)
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-folder-open text-2xl text-gray-200"></i>
                        </div>
                        <p class="text-gray-400 text-sm font-bold">لا توجد تقارير سابقة.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const reportLat = document.getElementById('report_lat');
    const reportLng = document.getElementById('report_lng');
    const gpsIndicator = document.getElementById('gps-indicator');
    const gpsText = document.getElementById('gps-text');
    const gpsStatusBox = document.getElementById('gps-status');
    const submitBtn = document.getElementById('submit-btn');

    function getGPS() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    reportLat.value = pos.coords.latitude;
                    reportLng.value = pos.coords.longitude;
                    
                    // Success UI
                    gpsIndicator.classList.remove('bg-orange-400', 'animate-pulse');
                    gpsIndicator.classList.add('bg-green-500');
                    gpsText.innerText = 'تم تحديد الموقع بنجاح';
                    gpsStatusBox.classList.remove('bg-blue-50', 'border-blue-100');
                    gpsStatusBox.classList.add('bg-green-50', 'border-green-100');
                    
                    // Enable Button
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('bg-gray-300', 'cursor-not-allowed');
                    submitBtn.classList.add('bg-charcoal', 'hover:bg-graphite');
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane text-xs"></i> إرسال التقرير';
                },
                (err) => {
                    gpsText.innerText = 'خطأ: برجاء تفعيل الـ GPS';
                    gpsIndicator.classList.remove('bg-orange-400', 'animate-pulse');
                    gpsIndicator.classList.add('bg-red-500');
                    gpsStatusBox.classList.replace('bg-blue-50', 'bg-red-50');
                    gpsStatusBox.classList.replace('border-blue-100', 'border-red-100');
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        } else {
            gpsText.innerText = 'المتصفح لا يدعم تحديد الموقع';
        }
    }

    // Run on load
    getGPS();

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('image-preview').src = e.target.result;
                document.getElementById('image-preview-container').classList.remove('hidden');
                document.getElementById('upload-prompt').classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include 'layout/footer.php'; ?>

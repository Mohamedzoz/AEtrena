<?php
// ai_reconcile.php - أداة مطابقة كشوف الحساب بالذكاء الاصطناعي
require_once 'config.php';
requireLogin();

$sysName = getSetting('system_name', 'Aeterna');
$favIcon = getSetting('favicon_path', 'uploads/assets/icon-192.png');

// جلب قائمة العملاء والموردين للمطابقة
$customers = $pdo->query("SELECT id, name, customer_code FROM customers ORDER BY name ASC")->fetchAll();

include 'layout/header.php';
?>

<div class="max-w-5xl mx-auto py-8 px-4">
    <!-- Header Area -->
    <div class="rounded-[2.5rem] overflow-hidden shadow-2xl relative mb-10 group">
        <div class="absolute inset-0 bg-gradient-to-r from-[#4f46e5] via-[#7c3aed] to-[#db2777] animate-gradient-xy"></div>
        <div class="relative p-10 backdrop-blur-sm border border-white/10 flex flex-col md:flex-row items-center justify-between gap-6 text-white">
            <div class="text-center md:text-right">
                <div class="flex items-center gap-3 justify-center md:justify-start mb-2">
                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-xl border border-white/30 shadow-inner">
                        <i class="fas fa-file-invoice text-2xl animate-pulse"></i>
                    </div>
                    <h2 class="text-3xl font-black tracking-tight">مطابقة كشوف الحساب ذكياً</h2>
                </div>
                <p class="text-white/80 font-medium">قم بإرفاق كشف حساب (PDF أو صورة) وسيقوم المساعد بمطابقته فوراً مع حركات النظام.</p>
            </div>
            <div class="flex-shrink-0 bg-white/10 p-4 rounded-3xl border border-white/20 shadow-xl">
                <img src="<?= htmlspecialchars($favIcon) ?>" class="h-16 w-16 object-contain filter brightness-0 invert" alt="AI">
            </div>
        </div>
    </div>

    <!-- Main Tool Container -->
    <div class="aeterna-card p-0 overflow-hidden border-0 shadow-premium bg-white/80 backdrop-blur-xl">
        <form id="reconcileForm" class="p-8 md:p-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                
                <!-- Left Side: Selection & Logic -->
                <div class="space-y-8">
                    <div class="space-y-3">
                        <label class="block text-charcoal font-black text-sm pr-1">اختر العميل / المورد للمطابقة</label>
                        <div class="relative">
                            <select name="entity_id" id="entity_id" required class="w-full h-14 bg-gray-50 border border-gray-200 rounded-2xl px-5 pr-12 focus:ring-4 focus:ring-purple-100 outline-none transition-all appearance-none font-bold text-charcoal shadow-inner">
                                <option value="">-- اختر من القائمة --</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['customer_code'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-user-tie absolute left-5 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        </div>
                    </div>

                    <div class="p-6 bg-purple-50 rounded-3xl border border-purple-100 relative overflow-hidden group">
                        <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-purple-200/50 rounded-full blur-2xl group-hover:scale-125 transition-transform"></div>
                        <h4 class="font-black text-purple-700 text-sm mb-2 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i> كيف تعمل المطابقة؟
                        </h4>
                        <ul class="text-xs text-purple-600/80 space-y-2 font-bold leading-relaxed relative z-10">
                            <li>1. المساعد يحلل التواريخ والمبالغ من الملف المرفق.</li>
                            <li>2. يتم البحث عن كل دفعة في "المقبوضات" المسجلة بالنظام.</li>
                            <li>3. يظهر لك تقرير بالفروقات (مبالغ مفقودة أو اختلاف تواريخ).</li>
                        </ul>
                    </div>
                </div>

                <!-- Right Side: File Upload Zone -->
                <div class="space-y-3">
                    <label class="block text-charcoal font-black text-sm pr-1">ملف كشف الحساب (من الطرف الآخر)</label>
                    <div id="dropZone" class="relative border-4 border-dashed border-gray-100 rounded-[2rem] p-10 flex flex-col items-center justify-center text-center hover:border-purple-300 hover:bg-purple-50 transition-all cursor-pointer group min-h-[300px]">
                        <input type="file" id="statementFile" name="statement" accept="image/*,.pdf" class="absolute inset-0 opacity-0 cursor-pointer">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-white shadow-sm transition-all">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 group-hover:text-purple-500"></i>
                        </div>
                        <h5 class="font-black text-charcoal text-lg mb-2">اضغط هنا أو اسحب الملف</h5>
                        <p class="text-xs text-gray-400 font-bold">(PDF, PNG, JPG)</p>
                        <div id="fileNamePreview" class="mt-4 hidden text-purple-600 font-black text-xs bg-white px-4 py-2 rounded-full shadow-sm border border-purple-100 animate__animated animate__fadeIn"></div>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="mt-12">
                <button type="submit" id="startReconcile" class="w-full bg-gradient-to-r from-[#7c3aed] to-[#db2777] text-white font-black py-5 rounded-2xl shadow-xl hover:shadow-purple-500/30 active:scale-[0.98] transition-all flex items-center justify-center gap-3 text-lg">
                    <i class="fas fa-magic"></i>
                    ابدأ المطابقة الذكية الآن
                </button>
            </div>
        </form>

        <!-- Results Area (Hidden by default) -->
        <div id="reconcileResults" class="hidden border-t border-gray-100 p-8 md:p-12 bg-gray-50/50 animate__animated animate__fadeInUp">
            <div class="flex items-center justify-between mb-8">
                <h3 class="font-black text-charcoal text-xl">تقرير المطابقة الذكي</h3>
                <button onclick="printReport()" class="text-xs font-black text-purple-600 hover:underline"><i class="fas fa-print ml-1"></i> طباعة التقرير</button>
            </div>
            <div id="resultsContent" class="space-y-6">
                <!-- AI Generated Content Goes Here -->
            </div>
        </div>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('statementFile');
const preview = document.getElementById('fileNamePreview');
const form = document.getElementById('reconcileForm');
const resultsArea = document.getElementById('reconcileResults');
const resultsContent = document.getElementById('resultsContent');
const btn = document.getElementById('startReconcile');

// تحديث اسم الملف عند الاختيار
fileInput.addEventListener('change', () => {
    if(fileInput.files[0]) {
        preview.innerText = 'الملف المختار: ' + fileInput.files[0].name;
        preview.classList.remove('hidden');
        dropZone.classList.add('border-purple-200', 'bg-purple-50');
    }
});

// معالجة الإرسال
form.onsubmit = async (e) => {
    e.preventDefault();
    
    const formData = new FormData(form);
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري تحليل الملف والمطابقة...';
    resultsArea.classList.add('hidden');

    try {
        // نستخدم handler الشات نفسه مع تعديل الإجراء
        const response = await fetch('ai_handler.php', {
            method: 'POST',
            body: formData // سيتم تعديل الـ handler ليدعم FormData
        });
        const data = await response.json();
        
        resultsArea.classList.remove('hidden');
        resultsContent.innerHTML = `<div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 leading-loose text-charcoal font-medium text-sm">${data.response}</div>`;
        
        // التمرير للنتائج
        resultsArea.scrollIntoView({ behavior: 'smooth' });

    } catch (error) {
        showNotification('عذراً، حدث خطأ أثناء الاتصال بالذكاء الاصطناعي.', 'error');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-magic"></i> ابدأ المطابقة الذكية الآن';
};

function printReport() {
    const content = resultsContent.innerHTML;
    const win = window.open('', '', 'height=700,width=900');
    win.document.write('<html><head><title>تقرير المطابقة</title>');
    win.document.write('<style>body{font-family:Cairo,sans-serif; direction:rtl; padding:40px; line-height:2;}</style>');
    win.document.write('</head><body>');
    win.document.write('<h2 style="text-align:center;">تقرير مطابقة كشف الحساب - Aeterna AI</h2>');
    win.document.write(content);
    win.document.write('</body></html>');
    win.document.close();
    win.print();
}
</script>

<style>
@keyframes gradient-xy {
    0%, 100% { background-size: 400% 400%; background-position: left center; }
    50% { background-position: right center; }
}
.animate-gradient-xy { animation: gradient-xy 15s ease infinite; }
</style>

<?php include 'layout/footer.php'; ?>
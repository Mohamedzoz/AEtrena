<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Distance calculation function (Haversine)
function getDistanceMeters($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // in meters
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    $a = sin($latDelta / 2) * sin($latDelta / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) * sin($lonDelta / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

// Handle Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_action'])) {
    $att_action = $_POST['attendance_action']; // sign_in or sign_out
    $lat = $_POST['lat'] ?? 0;
    $lng = $_POST['lng'] ?? 0;
    
    // Check Geofencing
    $wp_lat = getSetting('workplace_lat', '');
    $wp_lng = getSetting('workplace_lng', '');
    $wp_radius = getSetting('workplace_radius', 100);
    $is_inside = null;

    if ($wp_lat && $wp_lng && $lat && $lng) {
        $distance = getDistanceMeters($lat, $lng, $wp_lat, $wp_lng);
        $is_inside = ($distance <= $wp_radius) ? 1 : 0;
    }
    
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, action, latitude, longitude, is_inside_location, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $att_action, $lat, $lng, $is_inside, $now])) {
        $success = "تم تسجيل " . ($att_action == 'sign_in' ? 'الحضور' : 'الانصراف') . " بنجاح.";
        logActivity('حضور وانصراف', "قام " . $_SESSION['user_name'] . " بتسجيل " . ($att_action == 'sign_in' ? 'حضور' : 'انصراف'));
    } else {
        $error = "حدث خطأ أثناء تسجيل الحضور.";
    }
}

include 'layout/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-xl font-bold text-charcoal flex items-center gap-2">
            <i class="fas fa-fingerprint text-taupe"></i> تسجيل الحضور والانصراف (GPS)
        </h2>
        <p class="text-xs text-gray-400 mt-1">يرجى التأكد من تفعيل الموقع الجغرافي قبل التبصيم.</p>
    </div>
    <div class="text-xs font-bold text-gray-500 bg-white px-3 py-2 rounded-xl shadow-sm border border-gray-100">
        <i class="fas fa-clock text-taupe ml-1"></i> <?php echo date('h:i A'); ?>
    </div>
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

<div class="max-w-2xl mx-auto">
    <div class="aeterna-card overflow-hidden">
        <div class="relative h-48 bg-gray-100 mb-6 rounded-2xl overflow-hidden border border-gray-200">
            <!-- Simulated Map or Status -->
            <div id="map-overlay" class="absolute inset-0 flex flex-col items-center justify-center bg-charcoal/5 backdrop-blur-[2px] z-10">
                <div id="locationStatus" class="text-center p-4">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-xl">
                        <i class="fas fa-map-marker-alt text-2xl text-taupe animate-bounce"></i>
                    </div>
                    <p class="text-sm font-bold text-charcoal">جاري تحديد موقعك...</p>
                    <p class="text-[10px] text-gray-400 mt-1">تأكد من وجودك في نطاق العمل</p>
                </div>
            </div>
            <!-- If we had a static map image or real map it would go here -->
             <div class="absolute inset-0 bg-gradient-to-tr from-taupe/10 to-transparent"></div>
        </div>

        <div class="px-2">
            <form method="POST" action="" id="attendanceForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <input type="hidden" name="lat" id="lat_input">
                <input type="hidden" name="lng" id="lng_input">
                
                <button type="submit" name="attendance_action" value="sign_in" id="btnSignIn" class="group relative bg-white border-2 border-green-500 text-green-600 font-black py-6 rounded-[2rem] opacity-50 cursor-not-allowed shadow-lg transition-all flex flex-col items-center gap-2 overflow-hidden" disabled>
                    <div class="absolute inset-0 bg-green-500 translate-y-full group-hover:translate-y-0 transition-transform duration-300 -z-10"></div>
                    <i class="fas fa-sign-in-alt text-3xl group-hover:text-white transition-colors"></i>
                    <span class="group-hover:text-white transition-colors text-lg">تسجيل حضور</span>
                    <span class="text-[10px] opacity-60 group-hover:text-white transition-colors">Check-in Now</span>
                </button>

                <button type="submit" name="attendance_action" value="sign_out" id="btnSignOut" class="group relative bg-white border-2 border-red-500 text-red-600 font-black py-6 rounded-[2rem] opacity-50 cursor-not-allowed shadow-lg transition-all flex flex-col items-center gap-2 overflow-hidden" disabled>
                    <div class="absolute inset-0 bg-red-500 translate-y-full group-hover:translate-y-0 transition-transform duration-300 -z-10"></div>
                    <i class="fas fa-sign-out-alt text-3xl group-hover:text-white transition-colors"></i>
                    <span class="group-hover:text-white transition-colors text-lg">تسجيل انصراف</span>
                    <span class="text-[10px] opacity-60 group-hover:text-white transition-colors">Check-out Now</span>
                </button>
            </form>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100">
            <div class="flex items-center justify-between text-xs font-bold text-gray-500">
                <span class="flex items-center gap-1"><i class="fas fa-info-circle text-taupe"></i> نظام التبصيم الجغرافي الذكي</span>
                <a href="hr_history.php" class="text-taupe hover:underline">عرض سجلاتي اليوم <i class="fas fa-chevron-left text-[8px] mr-1"></i></a>
            </div>
        </div>
    </div>
</div>

<script>
    const latInput = document.getElementById('lat_input');
    const lngInput = document.getElementById('lng_input');
    const statusText = document.getElementById('locationStatus');
    const btnSignIn = document.getElementById('btnSignIn');
    const btnSignOut = document.getElementById('btnSignOut');

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                latInput.value = position.coords.latitude;
                lngInput.value = position.coords.longitude;
                
                statusText.innerHTML = `
                    <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm border border-green-100">
                        <i class="fas fa-check text-2xl text-green-500"></i>
                    </div>
                    <p class="text-sm font-bold text-green-600">تم تحديد موقعك بدقة</p>
                    <p class="text-[10px] text-gray-400 mt-1">${position.coords.latitude.toFixed(4)}, ${position.coords.longitude.toFixed(4)}</p>
                `;
                
                // Enable buttons
                [btnSignIn, btnSignOut].forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                });
            },
            (error) => {
                let msg = 'فشل في تحديد الموقع.';
                if (error.code === 1) msg = 'تم رفض الوصول للموقع. يرجى تفعيله.';
                statusText.innerHTML = `
                    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm border border-red-100">
                        <i class="fas fa-times text-2xl text-red-500"></i>
                    </div>
                    <p class="text-sm font-bold text-red-600">${msg}</p>
                `;
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } else {
        statusText.innerHTML = '<p class="text-red-600 font-bold">متصفحك لا يدعم تحديد الموقع.</p>';
    }
</script>

<?php include 'layout/footer.php'; ?>

<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

include 'layout/header.php';
?>

<div class="mb-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 stagger">
    <div>
        <h2 class="text-3xl font-black text-charcoal tracking-tight flex items-center gap-3">
            <i class="fas fa-history text-taupe"></i> سجل الحركات (History)
        </h2>
        <p class="text-base text-gray-500 font-bold mt-2">أرشيف مفصل لجميع عمليات تسجيل الحضور والانصراف الجغرافية.</p>
    </div>
</div>

<!-- Unified View: Grid on Mobile, Table on Desktop -->
<div class="mb-12">
    <!-- Mobile Cards View -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
        <?php
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY id DESC LIMIT 500");
        $stmt->execute([$user_id]);
        $attendances = $stmt->fetchAll();
        foreach ($attendances as $att):
            $isSignIn = $att['action'] == 'sign_in';
        ?>
        <div class="aeterna-card flex flex-col justify-between p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1"><?php echo formatDate($att['created_at']); ?></p>
                    <h4 class="font-black text-charcoal text-xl leading-tight" dir="ltr">
                        <i class="fas fa-clock text-taupe text-xs mr-1"></i> <?php echo date('h:i A', strtotime($att['created_at'])); ?>
                    </h4>
                </div>
                <div>
                    <?php if($isSignIn): ?>
                        <span class="bg-green-50 text-green-600 px-3 py-1.5 rounded-xl text-[10px] font-black border border-green-100">حضور</span>
                    <?php else: ?>
                        <span class="bg-red-50 text-red-600 px-3 py-1.5 rounded-xl text-[10px] font-black border border-red-100">انصراف</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-50">
                <div class="flex items-center gap-2">
                    <?php if ($att['is_inside_location'] === 1): ?>
                        <span class="inline-flex items-center gap-1 text-green-600 text-[10px] font-black">
                            <i class="fas fa-check-circle"></i> من المقر
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-red-600 text-[10px] font-black">
                            <i class="fas fa-exclamation-triangle"></i> خارج المقر
                        </span>
                    <?php endif; ?>
                </div>
                <a href="https://maps.google.com/?q=<?php echo $att['latitude'].','.$att['longitude']; ?>" target="_blank" class="bg-gray-50 text-charcoal px-3 py-1.5 rounded-lg text-[9px] font-black hover:bg-taupe hover:text-white transition-all">
                    <i class="fas fa-map-marker-alt"></i> الخريطة
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($attendances)): ?>
            <div class="col-span-full py-20 text-center">
                <i class="fas fa-fingerprint text-4xl text-gray-200 mb-4 block"></i>
                <p class="text-gray-400 font-bold">لا يوجد سجل حركات.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden lg:block bg-white rounded-[2.5rem] shadow-premium border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] uppercase tracking-[0.2em] text-gray-400 font-black">
                        <th class="p-6">التاريخ</th>
                        <th class="p-6">الوقت</th>
                        <th class="p-6">نوع الحركة</th>
                        <th class="p-6 text-center">الموقع</th>
                        <th class="p-6 text-center">GPS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($attendances as $att): ?>
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="p-6 text-sm font-bold text-gray-500" dir="ltr">
                            <?php echo formatDate($att['created_at']); ?>
                        </td>
                        <td class="p-6 font-black text-charcoal text-base" dir="ltr">
                            <?php echo date('h:i A', strtotime($att['created_at'])); ?>
                        </td>
                        <td class="p-6">
                            <?php if($att['action'] == 'sign_in'): ?>
                                <span class="bg-green-50 text-green-600 px-3 py-1.5 rounded-xl text-xs font-black border border-green-100">حضور</span>
                            <?php else: ?>
                                <span class="bg-red-50 text-red-600 px-3 py-1.5 rounded-xl text-xs font-black border border-red-100">انصراف</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-6 text-center">
                            <?php if ($att['is_inside_location'] === 1): ?>
                                <span class="bg-green-50 text-green-700 px-3 py-1 rounded-full text-[10px] font-black border border-green-200">داخل النطاق</span>
                            <?php else: ?>
                                <span class="bg-red-50 text-red-700 px-3 py-1 rounded-full text-[10px] font-black border border-red-200">خارج النطاق</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-6 text-center">
                            <a href="https://maps.google.com/?q=<?php echo $att['latitude'].','.$att['longitude']; ?>" target="_blank" class="w-10 h-10 rounded-xl bg-gray-50 text-charcoal flex items-center justify-center hover:bg-taupe hover:text-white transition-all shadow-sm border border-gray-100 mx-auto">
                                <i class="fas fa-map-marker-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

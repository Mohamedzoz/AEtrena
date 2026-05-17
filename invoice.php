<?php
require_once 'config.php';
// Modified access check to allow both internal users AND portal customers
if (!isset($_SESSION['user_id']) && !isset($_SESSION['client_portal_id'])) {
    header("Location: index.php");
    exit;
}

// This file handles both Quotations (if project_id is passed) and Receipts (if receipt_id is passed)

$type = 'quotation';
$data = null;
$customer = null;

if (isset($_GET['receipt_id'])) {
    $type = 'receipt';
    $stmt = $pdo->prepare("SELECT py.*, p.title as project_title, p.customer_id, c.name as customer_name, c.phone as customer_phone, c.address as customer_address 
                         FROM payments py 
                         JOIN projects p ON py.project_id = p.id 
                         JOIN customers c ON p.customer_id = c.id 
                         WHERE py.id = ?");
    $stmt->execute([$_GET['receipt_id']]);
    $data = $stmt->fetch();
    
    if(!$data) die("Receipt not found");

    // Security Check: If it's a customer portal user, ensure they own this receipt
    if (isset($_SESSION['client_portal_id']) && !isset($_SESSION['user_id'])) {
        if ($data['customer_id'] != $_SESSION['client_portal_id']) {
            die("Unauthorized access to this receipt.");
        }
    }
} elseif (isset($_GET['project_id'])) {
    $type = 'quotation';
    $stmt = $pdo->prepare("SELECT p.*, p.customer_id, c.name as customer_name, c.phone as customer_phone, c.address as customer_address 
                         FROM projects p 
                         JOIN customers c ON p.customer_id = c.id 
                         WHERE p.id = ?");
    $stmt->execute([$_GET['project_id']]);
    $data = $stmt->fetch();
    
    if(!$data) die("Project not found");
} else {
    // Show a selection form if no ID passed
    include 'layout/header.php';
    ?>
    <div class="aeterna-card max-w-md mx-auto mt-10">
        <h2 class="text-xl font-bold mb-4">إنشاء عرض سعر / فاتورة</h2>
        <form method="GET" action="invoice.php">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">اختر المشروع لإصدار عرض سعر (Quotation)</label>
                <select name="project_id" class="w-full py-2 px-3 border rounded">
                    <?php
                    $stmt = $pdo->query("SELECT id, title FROM projects");
                    while($row = $stmt->fetch()) echo "<option value='{$row['id']}'>{$row['title']}</option>";
                    ?>
                </select>
            </div>
            <button type="submit" class="btn-primary w-full">إصدار</button>
        </form>
    </div>
    <?php
    include 'layout/footer.php';
    exit;
}

// Prepare Data
$invoice_number = $type === 'receipt' ? 'REC-' . str_pad($data['id'], 5, '0', STR_PAD_LEFT) : 'QUO-' . str_pad($data['id'], 5, '0', STR_PAD_LEFT);
$date = $type === 'receipt' ? date('Y-m-d', strtotime($data['paid_at'])) : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php echo $type === 'receipt' ? 'إيصال استلام' : 'عرض سعر'; ?> | Aeterna</title>
    <?php $favIcon = getSetting('favicon_path', ''); ?>
    <link rel="icon" type="image/png" href="<?php echo $favIcon ?: 'uploads/assets/icon-192.png'; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f3f4f6; overscroll-behavior-y: none; }
        .print-area { max-width: 800px; margin: 40px auto; background: white; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .brand-text { color: #1F1E1C; }
        .brand-accent { color: #BEB7A9; }
        @media print {
            body { background: white; }
            .print-area { box-shadow: none; margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="bg-gray-800 text-white px-6 py-2 rounded font-bold hover:bg-gray-700">
            طباعة / حفظ كـ PDF
        </button>
        <a href="javascript:history.back()" class="bg-gray-200 text-gray-800 px-6 py-2 rounded font-bold hover:bg-gray-300 mr-2">
            رجوع
        </a>
    </div>

    <div class="print-area relative">
        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-gray-100 pb-8 mb-8">
            <div>
                <h1 class="text-4xl font-black tracking-widest brand-text mb-1"><span class="brand-accent">A</span> ETERNA</h1>
                <p class="text-xs text-gray-400 tracking-wider">KITCHEN & DRESSING</p>
            </div>
            <div class="text-left" dir="ltr">
                <h2 class="text-2xl font-bold brand-text uppercase"><?php echo $type === 'receipt' ? 'RECEIPT' : 'QUOTATION'; ?></h2>
                <p class="text-gray-500 font-bold mt-1">#<?php echo $invoice_number; ?></p>
                <p class="text-gray-400 text-sm">Date: <?php echo $date; ?></p>
            </div>
        </div>

        <!-- Customer & Company Details -->
        <div class="flex justify-between mb-10">
            <div>
                <p class="text-sm font-bold text-gray-400 mb-1">إلى العميل (Billed To):</p>
                <h3 class="text-xl font-bold brand-text"><?php echo htmlspecialchars($data['customer_name']); ?></h3>
                <p class="text-gray-600"><?php echo htmlspecialchars($data['customer_phone']); ?></p>
                <p class="text-gray-600"><?php echo htmlspecialchars($data['customer_address'] ?? ''); ?></p>
            </div>
            <div class="text-left" dir="ltr">
                <p class="text-sm font-bold text-gray-400 mb-1">من (From):</p>
                <h3 class="text-xl font-bold brand-text">Aeterna Kitchens</h3>
                <p class="text-gray-600">Cairo, Egypt</p>
                <p class="text-gray-600">info@aeterna.com</p>
            </div>
        </div>

        <!-- Details Table -->
        <table class="w-full mb-10 text-right">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-sm">
                    <th class="py-3 px-4 font-bold border-b">الوصف / البيان</th>
                    <th class="py-3 px-4 font-bold border-b" dir="ltr">المبلغ (EGP)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($type === 'quotation'): ?>
                    <tr>
                        <td class="py-4 px-4 border-b">
                            <p class="font-bold brand-text"><?php echo htmlspecialchars($data['title']); ?></p>
                            <p class="text-sm text-gray-500 mt-1">عرض سعر مبدئي بناءً على المعاينة والمقاسات الأولية.</p>
                        </td>
                        <td class="py-4 px-4 border-b font-bold text-lg" dir="ltr"><?php echo number_format($data['total_value']); ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td class="py-4 px-4 border-b">
                            <p class="font-bold brand-text">دفعة لمشروع: <?php echo htmlspecialchars($data['project_title']); ?></p>
                            <p class="text-sm text-gray-500 mt-1">نوع الدفعة: <?php echo htmlspecialchars($data['type']); ?></p>
                        </td>
                        <td class="py-4 px-4 border-b font-bold text-lg" dir="ltr"><?php echo number_format($data['amount']); ?></td>
                    </tr>
                    <?php if ($data['is_vat_included']): ?>
                    <tr>
                        <td class="py-4 px-4 border-b text-gray-500">ضريبة القيمة المضافة (VAT 14%)</td>
                        <td class="py-4 px-4 border-b text-red-500 font-bold" dir="ltr">+ <?php echo number_format($data['vat_amount']); ?></td>
                    </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end mb-12">
            <div class="w-1/2 bg-gray-50 p-6 rounded-lg">
                <?php if ($type === 'quotation'): ?>
                    <div class="flex justify-between font-black text-xl brand-text border-t-2 border-gray-200 pt-2 mt-2">
                        <span>الإجمالي المتوقع</span>
                        <span dir="ltr"><?php echo number_format($data['total_value']); ?> EGP</span>
                    </div>
                <?php else: ?>
                    <div class="flex justify-between font-black text-xl brand-text border-t-2 border-gray-200 pt-2 mt-2">
                        <span>إجمالي المدفوع</span>
                        <span dir="ltr"><?php echo number_format($data['amount'] + ($data['is_vat_included'] ? $data['vat_amount'] : 0)); ?> EGP</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-400 mt-10 pt-10 border-t border-gray-100">
            <p class="font-bold brand-text mb-2">شكرًا لثقتكم في إيتيرنا للمطابخ والدريسنج.</p>
            <p>True luxury should never expire.</p>
        </div>
        
        <!-- Watermark -->
        <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none overflow-hidden z-[-1]">
            <h1 class="text-9xl font-black transform -rotate-45 brand-text">A E T E R N A</h1>
        </div>
    </div>

</body>
</html>

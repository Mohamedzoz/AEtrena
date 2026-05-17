<?php
// ai_handler.php - المحرك الذكي لنظام Aeterna ERP
require_once 'config.php';

// التأكد من بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['response' => 'يرجى تسجيل الدخول أولاً لاستخدام المساعد الذكي.']);
    exit;
}

header('Content-Type: application/json');

// 1. استقبال بيانات المستخدم
$input = json_decode(file_get_contents('php://input'), true);

// إرسال سجل المحادثة القديم لو الواجهة طلبته
if (isset($input['action']) && $input['action'] === 'get_history') {
    echo json_encode(['history' => $_SESSION['ai_chat_history'] ?? []]);
    exit;
}

$user_query = $input['query'] ?? '';

if (empty($user_query)) {
    echo json_encode(['response' => 'أنا سامعك.. إزاي أقدر أساعدك النهاردة؟']);
    exit;
}

// التأكد من دعم اللغة العربية في البحث
try {
    $pdo->exec("SET NAMES utf8mb4");
} catch (Exception $e) {}

// تهيئة ذاكرة المحادثة
if (!isset($_SESSION['ai_chat_history'])) {
    $_SESSION['ai_chat_history'] = [];
}

if (in_array(trim($user_query), ['امسح المحادثة', 'مسح المحادثة', 'انسى اللي فات', 'ابدأ من جديد'])) {
    $_SESSION['ai_chat_history'] = [];
    echo json_encode(['response' => 'تم مسح الذاكرة. إيه الموضوع الجديد اللي حابب نتكلم فيه؟']);
    exit;
}

$current_messages = $_SESSION['ai_chat_history'];
$current_messages[] = ["role" => "user", "content" => $user_query];

if (count($current_messages) > 6) {
    $current_messages = array_slice($current_messages, -6);
}

// 2. دالة لجلب مخطط قاعدة البيانات
function getDatabaseSchema($pdo) {
    $schema = "جداول قاعدة البيانات المتاحة:\n";
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $schema .= "- $table (";
            $cols = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            $colNames = array_column($cols, 'Field');
            $schema .= implode(", ", $colNames) . ")\n";
        }
    } catch (Exception $e) {
        $schema = "(تعذر جلب مخطط قاعدة البيانات)\n";
    }
    return $schema;
}

$db_schema = getDatabaseSchema($pdo);

// 3. دليل العلاقات الذكي (Cheat Sheet for AI)
$db_relations = "
[دليل العلاقات بين الجداول (استخدم JOIN بناءً عليها لجلب الأسماء دائماً بدلاً من الأرقام)]:
1. مشاريع العميل: projects.customer_id = customers.id (لجلب customers.name كاسم العميل)
2. دفعات المشاريع: payments.project_id = projects.id (لجلب projects.title كاسم المشروع)
3. مهام المشاريع: project_tasks.project_id = projects.id (لجلب projects.title كاسم المشروع)
4. المصروفات وأقسامها: expenses.category_id = expense_categories.id (لجلب expense_categories.name)
5. تقارير الموقع: site_reports.project_id = projects.id وَ site_reports.user_id = users.id (لجلب projects.title وَ users.name)
6. الحضور والغياب: attendance.user_id = users.id (لجلب users.name)

[دليل المصطلحات المحاسبية للنظام]:
- المقبوضات/الإيرادات/المبيعات/الدفعات: ابحث في جدول `payments`.
- إيرادات اليوم: استخدم جدول `payments` بشرط DATE(paid_at) = CURDATE()
- العملاء المتأخرين: `payments` بشرط `status = 'Late'` (اربطه بـ projects و customers لجلب اسم العميل).
- المصروفات: ابحث في جدول `expenses`.
";

// 4. إعداد الاتصال
$rawKeys = getSetting('gemini_api_key', '');
$keys = array_filter(array_map('trim', explode(',', $rawKeys)));

if (empty($keys)) {
    echo json_encode(['response' => "⚠️ لم يتم العثور على مفتاح API. يرجى إدخاله في صفحة الإعدادات."]);
    exit;
}

$apiKey = $keys[array_rand($keys)];
$key_display = substr($apiKey, 0, 4) . "..." . substr($apiKey, -4);
$key_len = strlen($apiKey);

if ($key_len < 10) {
    echo json_encode(['response' => "⚠️ مفتاح API يبدو غير صالح (الطول: $key_len). يرجى التأكد من إدخاله بشكل صحيح في الإعدادات."]);
    exit;
}
$models = ["google/gemini-1.5-pro", "google/gemini-pro", "openai/gpt-4o-mini"];

function callOpenRouterAI($sys_prompt, $messages_array, $apiKey, $models) {
    $api_url = "https://openrouter.ai/api/v1/chat/completions";
    foreach ($models as $model_name) {
        $api_messages = [["role" => "system", "content" => $sys_prompt]];
        foreach ($messages_array as $msg) { $api_messages[] = $msg; }

        $data = [
            "model" => $model_name,
            "messages" => $api_messages
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'HTTP-Referer: https://aeterna-erp.com', 
            'X-Title: Aeterna ERP' 
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); 

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($http_code == 200 && isset($result['choices'][0]['message']['content'])) {
            return ['success' => true, 'text' => $result['choices'][0]['message']['content']];
        } else {
            $api_error = $result['error']['message'] ?? ($result['error'] ?? 'No error message from API');
            if (is_array($api_error)) $api_error = json_encode($api_error);
            
            $kd = substr($apiKey, 0, 4) . "..." . substr($apiKey, -4);
            $kl = strlen($apiKey);
            
            $last_error = "($model_name) - HTTP $http_code: $api_error | Key: $kd ($kl chars)";
            if (!empty($curl_error)) $last_error .= " | CURL Error: $curl_error";
            continue; 
        }
    }
    return ['success' => false, 'error' => $last_error ?? 'لم يتم استلام أي استجابة من السيرفر.'];
}

$user_role = $_SESSION['user_role'] ?? 'موظف عادي';

// 5. الخطوة الأولى: التفكير والاستعلام
$system_prompt_step1 = "أنت 'Aeterna AI'، محلل بيانات ونظام ذكي لـ Aeterna ERP.
التاريخ الحالي: " . date('Y-m-d') . "
دور المستخدم الذي يتحدث معك حالياً هو: $user_role

هذا هو مخطط قاعدة البيانات الفعلي:
$db_schema

وهذا دليل العلاقات المهم جداً:
$db_relations

تعليمات صارمة لكتابة استعلام قاعدة البيانات:
1. ⚠️ الصلاحيات: 
   - (Manager/Admin/مدير) يرى كل شيء.
   - (Accountant) يرى الأمور المالية والمصروفات والعملاء والمشاريع.
   - إذا طلب المستخدم بيانات خارج صلاحيات دوره، لا تكتب استعلام SQL، بل اعتذر بلطف.
2. ⚠️ أنت متصل تماماً بالبيانات، ممنوع أن تعتذر وتقول أنك لا تستطيع الوصول للمعلومات. طالما الصلاحية تسمح، اكتب استعلام SQL فوراً.
3. راجع المحادثة السابقة إذا لزم الأمر.
4. ممنوع التخمين مطلقاً: استخدم الجداول والعلاقات المذكورة أعلاه فقط.
5. ⚠️ إجباري جداً: استخدم `JOIN` دائماً في الـ SQL لجلب الأسماء الوصفية (مثل عنوان المشروع `projects.title` واسم العميل `customers.name` واسم الموظف `users.name`) بدلاً من إرجاع معرّفات رقمية مبهمة (مثل `project_id`).
6. اكتب استعلام SQL صحيح يبدأ بـ `SELECT`.
7. عند البحث عن نصوص (كالأسماء)، قسّم الكلمات وابحث بـ `LIKE` لكل كلمة مع `AND`.
8. يجب أن يكون استعلام SQL هو النص الوحيد في إجابتك (يبدأ بـ SELECT فقط).
9. إذا كان السؤال عاماً ولا يحتاج لقاعدة بيانات، أجب مباشرة.";

$step1_result = callOpenRouterAI($system_prompt_step1, $current_messages, $apiKey, $models);

if (!$step1_result['success']) {
    echo json_encode(['response' => "⚠️ عذراً، لم أستطع الاتصال بالمحرك. الخطأ: " . $step1_result['error']]);
    exit;
}

$ai_initial_response = trim($step1_result['text']);
$ai_initial_response = str_replace(['```sql', '```'], '', $ai_initial_response); 
$ai_initial_response = trim($ai_initial_response);

$sql_query = "";
if (preg_match('/(SELECT\s+.*)/is', $ai_initial_response, $matches)) {
    $sql_query = trim($matches[1]);
}

$final_text = "";

// 6. تنفيذ الاستعلام وصياغة الرد
if (!empty($sql_query) && stripos($sql_query, 'SELECT') === 0) {
    
    if (preg_match('/(UPDATE|DELETE|DROP|INSERT|ALTER|TRUNCATE|GRANT|REVOKE)/i', $sql_query)) {
         echo json_encode(['response' => "⚠️ عذراً، الاستعلام بيحتوي على أوامر غير مسموح بيها."]);
         exit;
    }

    try {
        $stmt = $pdo->query($sql_query);
        $db_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($db_data)) {
            $db_json = "لم أجد أي بيانات مطابقة لطلبك في قاعدة البيانات.";
        } else {
            $db_json = json_encode($db_data, JSON_UNESCAPED_UNICODE);
        }

        $system_prompt_step2 = "أنت 'Aeterna AI'.
قم بصياغة إجابة مفيدة توضح نتيجة البحث للمستخدم بشكل مباشر.
البيانات المستخرجة:
$db_json

⚠️ تحذير صارم ونهائي: ممنوع كتابة أو عرض أي أكواد SQL أو تفاصيل برمجية في إجابتك.
- أجب كنص بشري طبيعي. اذكر الأرقام والتواريخ المستخرجة بوضوح.
- نسق الإجابة بذكاء، مثلاً قم بتجميع المهام أو المصروفات تحت اسم المشروع أو العميل الخاص بها لتكون القراءة منظمة وشيك.
- إذا لم تجد بيانات أخبره بلطف.
تحدث باللهجة المصرية العامية، بشكل مباشر وواضح وبدون تكرار.";

        $formatting_messages = $current_messages;
        $last_idx = count($formatting_messages) - 1;
        $formatting_messages[$last_idx]['content'] = "سأل المستخدم: $user_query \n\nنتيجة الداتابيز:\n$db_json";

        $step2_result = callOpenRouterAI($system_prompt_step2, $formatting_messages, $apiKey, $models);
        
        if ($step2_result['success']) {
            $final_text = $step2_result['text'];
        } else {
            $final_text = "دي البيانات اللي سحبتها:\n" . $db_json;
        }

    } catch (Exception $e) {
         $final_text = "معلش، واجهت مشكلة فنية وأنا بحاول أسحب البيانات دي (خطأ في الربط). ياريت تجرب بصيغة تانية.";
    }
} else {
    // الذكاء الاصطناعي رفض أو جاوب رد عادي
    $final_text = $ai_initial_response;
}

// الفلتر الإجباري لحذف أي أكواد SQL من الظهور للعميل
$final_text = preg_replace('/```[\s\S]*?```/', '', $final_text); 
$final_text = preg_replace('/SELECT\s+.*?\s+FROM.*?/i', '', $final_text); 

$_SESSION['ai_chat_history'][] = ["role" => "user", "content" => $user_query];
$_SESSION['ai_chat_history'][] = ["role" => "assistant", "content" => $final_text];

if (count($_SESSION['ai_chat_history']) > 8) {
    $_SESSION['ai_chat_history'] = array_slice($_SESSION['ai_chat_history'], -8);
}

$final_text = str_replace(['**', '*'], '', $final_text);
$final_text = nl2br(trim($final_text));

if (empty($final_text)) {
    $final_text = "معلش ممكن توضح سؤالك أكتر؟";
}

echo json_encode(['response' => $final_text]);
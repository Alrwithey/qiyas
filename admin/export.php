<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../db.php';

// التأكد من أن المدير مسجل دخوله
require_login();

// إذا كان هناك أي مخزن مؤقت للـ output فافرغه حتى لا يفسد الـ BOM
if (ob_get_level()) {
    ob_end_clean();
}

// 1. إعداد الهيدر لتنزيل الملف كـ CSV
$filename = "survey_responses_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// 2. فتح مؤشر للكتابة إلى الإخراج وأضف BOM مباشرة
$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF"); // BOM لـ UTF-8

// 3. تحديد الفاصل
$delimiter = ';';

// 4. بناء رؤوس الأعمدة ديناميكياً
$questions = get_all_questions($conn);
$header = ['ID', 'تاريخ الإرسال', 'اسم المستفيد', 'رقم الجوال', 'الجنس', 'البرنامج', 'مقترحات'];
$question_texts_ordered = [];

foreach ($questions as $question) {
    if (!in_array($question['question_text'], ["اسم المستفيد", "رقم الجوال", "الجنس", "البرنامج المستفاد منه"])) {
        $header[] = $question['question_text'];
        $question_texts_ordered[] = $question['question_text'];
    }
}

// 5. كتابة صف العناوين
fputcsv($output, $header, $delimiter);

// 6. جلب كل الاستجابات
$responses = get_all_survey_responses_with_answers($conn);

// 7. كتابة كل استجابة
foreach ($responses as $response) {
    // البيانات الأساسية
    $row = [
        $response['id'] ?? '',
        $response['submission_date'] ?? '',
        $response['beneficiary_name'] ?? '',
        // نستخدم اقتباس مفرد لرقم الجوال حتى لا يتحول لعدد
        "'" . ($response['phone_number'] ?? ''),
        ($response['gender'] ?? '') === 'male' ? 'ذكر'
            : (($response['gender'] ?? '') === 'female' ? 'أنثى' : ''),
        $response['program_name'] ?? '',
        str_replace(["\r", "\n"], ' ', $response['suggestions'] ?? '')
    ];

    // إعداد خريطة الإجابات
    $answers_map = [];
    if (!empty($response['answers'])) {
        foreach ($response['answers'] as $answer) {
            $val = $answer['rating'] ?? $answer['answer_text'];
            $answers_map[$answer['question_text']] = str_replace(["\r", "\n"], ' ', $val);
        }
    }

    // إضافة الإجابات حسب الترتيب
    foreach ($question_texts_ordered as $q_text) {
        $row[] = $answers_map[$q_text] ?? '';
    }

    // كتابة الصف
    fputcsv($output, $row, $delimiter);
}

// 8. إغلاق المؤشر
fclose($output);
exit;

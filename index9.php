<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

if (!$conn || $conn->connect_error) {
    die("<h1>خطأ في النظام</h1><p>عذراً، لا يمكن الاتصال بالخدمة حالياً.</p>");
}

$settings = get_latest_settings($conn);
$questions = get_all_questions($conn);
$programs = get_all_programs($conn);

// ... (بقية كود PHP يبقى كما هو) ...
$success_message = "";
$error_message = "";
$posted_data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (منطق معالجة الفورم يبقى كما هو) ...
    $posted_data = $_POST;
    $beneficiary_name = sanitize_input($_POST["beneficiary_name"] ?? "");
    $phone_number = sanitize_input($_POST["phone_number"] ?? "");
    $gender = sanitize_input($_POST["gender"] ?? "");
    $program_id = isset($_POST["program_id"]) ? intval($_POST["program_id"]) : null;
    $suggestions = sanitize_input($_POST["suggestions"] ?? "");
    $answers = $_POST["answers"] ?? [];
    
    $errors = [];
    if (empty($gender)) { $errors[] = "الرجاء اختيار الجنس."; }
    if (empty($program_id)) { $errors[] = "الرجاء اختيار البرنامج المستفاد منه."; }
    
    foreach ($questions as $question) {
        if (in_array($question["question_text"], ["اسم المستفيد", "رقم الجوال", "الجنس", "البرنامج المستفاد منه"])) { continue; }
        if ($question["is_required"] && empty($answers[$question["id"]])) {
            $errors[] = "السؤال \"" . htmlspecialchars($question["question_text"]) . "\" مطلوب.";
        }
    }

    if (empty($errors)) {
        $data_to_save = ["beneficiary_name" => $beneficiary_name, "phone_number" => $phone_number, "gender" => $gender, "program_id" => $program_id, "suggestions" => $suggestions, "answers" => $answers];
        if (save_survey_response($conn, $data_to_save)) {
            $success_message = "شكراً لك! تم إرسال استبيانك بنجاح.";
        } else {
            $error_message = "حدث خطأ أثناء حفظ البيانات.";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_survey_head($settings); ?>

<style>
    /* 1. تعريف متغيرات الألوان ديناميكياً من الإعدادات */
    :root {
        --primary-color: <?php echo htmlspecialchars($settings['primary_color'] ?? '#1a535c'); ?>;
        --primary-color-darker: <?php echo htmlspecialchars(adjust_brightness($settings['primary_color'] ?? '#1a535c', -20)); ?>; /* لون أغمق للطيّة */
        --secondary-color: <?php echo htmlspecialchars($settings['secondary_color'] ?? '#f7b538'); ?>;
    }

    /* 2. تطبيق الحد العلوي للحاوية */
    .registration-section {
        border-top: 5px solid var(--primary-color);
    }

    /* 3. تنسيق الخط الفاصل */
    .title-separator {
        border: none;
        height: 2px;
        background-color: var(--secondary-color);
        width: 100%;
        margin: 25px 0;
    }

    <?php // ==================== يبدأ التعديل هنا: تصميم جديد للأسئلة ==================== ?>

    /* حاوية السؤال الكاملة */
    .question-block {
        background-color: #fdfdfd; /* خلفية بيضاء فاتحة لمنطقة الإجابة */
        border: 1px solid #f0f0f0;
        border-radius: 8px;
        margin-bottom: 35px; /* زيادة المسافة السفلية */
        padding: 20px;
        padding-top: 0; /* إزالة الحشو العلوي لإلصاق الشريط */
        box-shadow: 0 4px 10px rgba(0,0,0,0.04);
        border-bottom: none; /* إزالة الخط المتقطع القديم */
    }

    /* تصميم شريط عنوان السؤال (الراية) */
    .question-title {
        background-color: var(--primary-color);
        color: white !important; /* لون النص أبيض */
        padding: 12px 20px;
        padding-left: 35px; /* مسافة إضافية يساراً للطيّة */
        margin: 0 -20px 20px -20px; /* لتمتد الراية على عرض الحاوية */
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        position: relative; /* ضروري لتحديد مكان الطيّة */
        font-weight: 700 !important;
        font-size: 1.1rem !important;
    }

   /* إنشاء الطيّة باستخدام عنصر زائف */
    .question-title::after {
        content: '';
        position: absolute;
        top: 100%;
        
        <?php // ==================== يبدأ التعديل هنا ==================== ?>
        
        right: 0;   /* <--- تم التغيير من left إلى right لتناسب RTL */
        
        width: 0;
        height: 0;
        border-top: 15px solid var(--primary-color-darker);
        
        border-left: 15px solid transparent; /* <--- تم التغيير من border-right إلى border-left لعكس المثلث */
        
        <?php // ==================== ينتهي التعديل هنا ==================== ?>
    }

    /* التأكد من أن النجمة المطلوبة بيضاء أيضاً */
    .question-title .required-star {
        color: #ffdddd;
    }

    /* إضافة مسافة فوق منطقة الإجابة */
    .question-block .options-group,
    .question-block .form-control,
    .question-block .star-rating {
        margin-top: 15px;
    }
    
    <?php // ==================== ينتهي التعديل هنا ==================== ?>

</style>

<body>
    <div class="container">
        <div class="registration-section">
             <?php if (!empty($settings["logo_path"])) : ?>
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="<?php echo htmlspecialchars($settings["logo_path"]); ?>" alt="Logo" style="height: 80px; width: auto;">
                </div>
            <?php endif; ?>

            <h2 class="registration-title"><?php echo htmlspecialchars($settings['site_name'] ?? 'استبيان قياس رضا المستفيدين'); ?></h2>
            <h2 class="registration-title"><?php echo htmlspecialchars($settings["system_name"]); ?></h2>

            <hr class="title-separator">

            <p class="registration-subtitle">يهمنا رأيكم لتحسين خدماتنا. نرجو منكم تعبئة الاستبيان التالي.</p>

            <?php if (!empty($success_message)) : ?>
                <div class="alert alert-success"><h3><?php echo $success_message; ?></h3><p>نقدر وقتكم ومشاركتكم القيمة.</p></div>
            <?php elseif (!empty($error_message)) : ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if (empty($success_message)) : ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                
                <!-- الفورم يبقى كما هو بدون أي تغيير -->
                <div class="form-row">
                    <div class="form-group-half">
                        <label class="form-label">اسم المستفيد (اختياري)</label>
                        <input type="text" name="beneficiary_name" class="form-control" placeholder="الاسم الكامل" value="<?php echo htmlspecialchars($posted_data['beneficiary_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group-half">
                        <label class="form-label">رقم الجوال (اختياري)</label>
                        <input type="text" name="phone_number" class="form-control" placeholder="رقم الجوال" value="<?php echo htmlspecialchars($posted_data['phone_number'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label class="form-label">الجنس <span class="required">*</span></label>
                        <div class="options-group">
                            <div class="radio-option"><input type="radio" name="gender" value="male" id="gender_male" <?php if (isset($posted_data['gender']) && $posted_data['gender'] == 'male') echo 'checked'; ?> required><label for="gender_male">ذكر</label></div>
                            <div class="radio-option"><input type="radio" name="gender" value="female" id="gender_female" <?php if (isset($posted_data['gender']) && $posted_data['gender'] == 'female') echo 'checked'; ?> required><label for="gender_female">أنثى</label></div>
                        </div>
                    </div>
                    <div class="form-group-half">
                        <label class="form-label">البرنامج المستفاد منه <span class="required">*</span></label>
                        <select name="program_id" class="form-control" required>
                            <option value="">اختر البرنامج...</option>
                            <?php foreach ($programs as $program) : ?>
                                <option value="<?php echo $program["id"]; ?>" <?php if (isset($posted_data['program_id']) && $posted_data['program_id'] == $program["id"]) echo 'selected'; ?>><?php echo htmlspecialchars($program["program_name"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php foreach ($questions as $question) : ?>
                    <?php if (in_array($question["question_text"], ["اسم المستفيد", "رقم الجوال", "الجنس", "البرنامج المستفاد منه"])) { continue; } ?>
                    <div class="question-block">
                        <label class="question-title"><?php echo htmlspecialchars($question["question_text"]); ?><?php if ($question["is_required"]) : ?><span class="required-star">*</span><?php endif; ?></label>
                        <?php 
                        $question_id = $question["id"];
                        $is_required_attr = $question["is_required"] ? 'required' : '';
                        $current_answer = $posted_data['answers'][$question_id] ?? null;
                        switch ($question["question_type"]) {
                            case 'text': echo "<input type='text' name='answers[{$question_id}]' class='form-control' value='" . htmlspecialchars($current_answer ?? '') . "' {$is_required_attr}>"; break;
                            case 'single_choice': $options = get_question_options($conn, $question_id); echo "<div class='options-group'>"; foreach ($options as $option) { $checked = ($current_answer == $option['option_text']) ? 'checked' : ''; echo "<div class='radio-option'><input type='radio' name='answers[{$question_id}]' value='" . htmlspecialchars($option["option_text"]) . "' id='option_{$option['id']}' {$checked} {$is_required_attr}><label for='option_{$option['id']}'>" . htmlspecialchars($option["option_text"]) . "</label></div>"; } echo "</div>"; break;
                            case 'multiple_choice': $options = get_question_options($conn, $question_id); echo "<div class='options-group'>"; foreach ($options as $option) { $checked = (is_array($current_answer) && in_array($option['option_text'], $current_answer)) ? 'checked' : ''; echo "<div class='checkbox-option'><input type='checkbox' name='answers[{$question_id}][]' value='" . htmlspecialchars($option['option_text']) . "' id='option_{$option['id']}' {$checked}><label for='option_{$option['id']}'>" . htmlspecialchars($option['option_text']) . "</label></div>"; } echo "</div>"; break;
                           case 'rating':
    echo "<div class='star-rating'>";
    echo "<span style='color:#690909;'>غير راضي </span>";
    echo "<div class='stars'>";
    for ($i = 5; $i >= 1; $i--) {
        $checked = ($current_answer == $i) ? 'checked' : '';
        echo "
            <input type='radio' id='star{$question_id}_{$i}' name='answers[{$question_id}]' value='{$i}' {$checked} {$is_required_attr}>
            <label for='star{$question_id}_{$i}'><i class='fas fa-star'></i></label>
        ";
    }
    echo "</div>";
    echo "<span style='color:#055946;'>راضي جدا</span>";
    echo "</div>";
    break;
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
                <div class="question-block">
                    <label class="question-title">مقترحات للتحسين (اختياري)</label>
                    <textarea name="suggestions" class="form-control" rows="4" placeholder="اكتب مقترحاتك هنا..."><?php echo htmlspecialchars($posted_data['suggestions'] ?? ''); ?></textarea>
                </div>
                <div class="submit-block">
                    <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> إرسال التقييم</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
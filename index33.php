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

$success_message = "";
$error_message = "";
// Initialize all variables to prevent errors
$beneficiary_name = ""; $phone_number = ""; $gender = ""; $program_id = null; $suggestions = ""; $answers = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and capture all posted data
    $beneficiary_name = sanitize_input($_POST["beneficiary_name"] ?? "");
    $phone_number = sanitize_input($_POST["phone_number"] ?? "");
    $gender = sanitize_input($_POST["gender"] ?? ""); // Capture gender
    $program_id = isset($_POST["program_id"]) ? intval($_POST["program_id"]) : null;
    $suggestions = sanitize_input($_POST["suggestions"] ?? "");
    $answers = $_POST["answers"] ?? [];
    
    // Perform validation
    $errors = [];
    if (empty($gender)) { $errors[] = "الرجاء اختيار الجنس."; }
    if (empty($program_id)) { $errors[] = "الرجاء اختيار البرنامج المستفاد منه."; }
    
    foreach ($questions as $question) {
        if (in_array($question["question_text"], ["اسم المستفيد", "رقم الجوال", "الجنس", "البرنامج المستفاد منه"])) {
            continue;
        }
        if ($question["is_required"] && empty($answers[$question["id"]])) {
            $errors[] = "السؤال \"" . htmlspecialchars($question["question_text"]) . "\" مطلوب.";
        }
    }

    if (empty($errors)) {
        $data_to_save = [
            "beneficiary_name" => $beneficiary_name,
            "phone_number" => $phone_number,
            "gender" => $gender,
            "program_id" => $program_id,
            "suggestions" => $suggestions,
            "answers" => $answers
        ];
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
<body>
<div class="survey-container">
    <div class="survey-header">
        <?php if (!empty($settings["logo_path"])) : ?>
            <img src="<?php echo htmlspecialchars($settings["logo_path"]); ?>" alt="Logo" class="survey-logo">
        <?php else : ?>
            <h1><?php echo htmlspecialchars($settings["site_name"]); ?></h1>
        <?php endif; ?>
        <h2>استبيان قياس رضا المستفيدين</h2>
        <p>يهمنا رأيكم لتحسين خدماتنا. نرجو منكم تعبئة الاستبيان التالي.</p>
    </div>

    <?php if (!empty($success_message)) : ?>
        <div class="alert alert-success"><h3><?php echo $success_message; ?></h3><p>نقدر وقتكم ومشاركتكم القيمة.</p></div>
    <?php elseif (!empty($error_message)) : ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if (empty($success_message)) : ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="survey-form">
        <div class="form-row">
            <div class="form-group-half"><label class="question-title">اسم المستفيد (اختياري)</label><input type="text" name="beneficiary_name" class="form-control" value="<?php echo htmlspecialchars($beneficiary_name); ?>"></div>
            <div class="form-group-half"><label class="question-title">رقم الجوال (اختياري)</label><input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($phone_number); ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group-half">
                <label class="question-title">الجنس *</label>
                <!-- ===== THIS IS THE CORRECTED, SIMPLIFIED GENDER SELECTION ===== -->
                <div class="options-group">
                    <div class="radio-option">
                        <input type="radio" name="gender" value="ذكر" id="gender_male" <?php if ($gender == 'ذكر') echo 'checked'; ?> required>
                        <label for="gender_male">ذكر</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" name="gender" value="أنثى" id="gender_female" <?php if ($gender == 'أنثى') echo 'checked'; ?> required>
                        <label for="gender_female">أنثى</label>
                    </div>
                </div>
            </div>
            <div class="form-group-half">
                <label class="question-title">البرنامج المستفاد منه *</label>
                <select name="program_id" class="form-control" required>
                    <option value="">اختر البرنامج</option>
                    <?php foreach ($programs as $program) : ?>
                        <option value="<?php echo $program["id"]; ?>" <?php if ($program_id == $program["id"]) echo 'selected'; ?>><?php echo htmlspecialchars($program["program_name"]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- ===== THIS IS THE RESTORED QUESTIONS LOOP ===== -->
        <?php foreach ($questions as $question) : ?>
            <?php
            // Skip special questions handled above
            if (in_array($question["question_text"], ["اسم المستفيد", "رقم الجوال", "الجنس", "البرنامج المستفاد منه"])) {
                continue;
            }
            ?>
            <div class="question-block">
                <label class="question-title">
                    <?php echo htmlspecialchars($question["question_text"]); ?>
                    <?php if ($question["is_required"]) : ?><span class="required-star">*</span><?php endif; ?>
                </label>

                <?php 
                $question_id = $question["id"];
                $is_required_attr = $question["is_required"] ? 'required' : '';
                $current_answer = $answers[$question_id] ?? null;

                // This switch statement renders the correct input for each question type
                switch ($question["question_type"]) {
                    case 'text':
                        echo "<input type=\"text\" name=\"answers[{$question_id}]\" class=\"form-control\" value=\"" . htmlspecialchars($current_answer ?? '') . "\" {$is_required_attr}>";
                        break;

                    case 'single_choice':
                        $options = get_question_options($conn, $question_id);
                        echo "<div class=\"options-group\">";
                        foreach ($options as $option) {
                            $checked = ($current_answer == $option['option_text']) ? 'checked' : '';
                            echo "<div class=\"radio-option\"><input type=\"radio\" name=\"answers[{$question_id}]\" value=\"" . htmlspecialchars($option["option_text"]) . "\" id=\"option_{$option['id']}\" {$checked} {$is_required_attr}><label for=\"option_{$option['id']}\">" . htmlspecialchars($option["option_text"]) . "</label></div>";
                        }
                        echo "</div>";
                        break;

                    case 'dropdown':
                        $options = get_question_options($conn, $question_id);
                        echo "<select name=\"answers[{$question_id}]\" class=\"form-control\" {$is_required_attr}>";
                        echo "<option value=\"\">اختر...</option>";
                        foreach ($options as $option) {
                            $selected = ($current_answer == $option['option_text']) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($option["option_text"]) . "\" {$selected}>" . htmlspecialchars($option["option_text"]) . "</option>";
                        }
                        echo "</select>";
                        break;

                    case 'multiple_choice':
                         $options = get_question_options($conn, $question_id);
                         echo "<div class=\"options-group\">";
                         foreach ($options as $option) {
                             $checked = (is_array($current_answer) && in_array($option['option_text'], $current_answer)) ? 'checked' : '';
                             echo "<div class=\"checkbox-option\"><input type=\"checkbox\" name=\"answers[{$question_id}][]\" value=\"" . htmlspecialchars($option['option_text']) . "\" id=\"option_{$option['id']}\" {$checked}><label for=\"option_{$option['id']}\">" . htmlspecialchars($option['option_text']) . "</label></div>";
                         }
                         echo "</div>";
                         break;

                    case 'rating':
                        echo "<div class=\"rating-group\"><span>غير راضٍ تماماً</span>";
                        for ($i = 1; $i <= 5; $i++) {
                            $checked = ($current_answer == $i) ? 'checked' : '';
                            echo "<label class=\"rating-label\"><input type=\"radio\" name=\"answers[{$question_id}]\" value=\"{$i}\" class=\"rating-radio\" {$checked} {$is_required_attr}><span class=\"rating-number\">{$i}</span></label>";
                        }
                        echo "<span>راضٍ جداً</span></div>";
                        break;
                }
                ?>
            </div>
        <?php endforeach; ?>
        
        <div class="question-block">
            <label class="question-title">مقترحات للتحسين (اختياري)</label>
            <textarea name="suggestions" class="form-control" rows="4"><?php echo htmlspecialchars($suggestions); ?></textarea>
        </div>
        <div class="submit-block">
            <button type="submit" class="btn btn-primary">إرسال التقييم</button>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php echo get_survey_footer(); ?>
</body>
</html>
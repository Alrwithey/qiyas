<?php
// We will rely on the functions from functions.php for URLs and other settings.
// No need to define BASE_URL here if get_base_url() exists.

session_start();

// Include necessary files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

// --- CRITICAL CHECK ADDED HERE ---
// Check if the database connection was successful.
// If not, stop everything and show a clear error message.
if (!$conn || $conn->connect_error) {
    // For security, don't show the detailed error to the public.
    // Instead, log it and show a generic message.
    // error_log("Database connection failed: " . ($conn ? $conn->connect_error : "Unknown error")); // This logs the error for the admin
    die("<h1>خطأ في النظام</h1><p>عذراً، لا يمكن الاتصال بالخدمة حالياً. يرجى المحاولة مرة أخرى في وقت لاحق.</p>");
}
// --- END OF CRITICAL CHECK ---


// If the connection is successful, proceed with the rest of the script.
$settings = get_latest_settings($conn);
$questions = get_all_questions($conn);
$programs = get_all_programs($conn);

$success_message = "";
$error_message = "";
$beneficiary_name = ""; // Initialize variables to avoid errors
$phone_number = "";     // Initialize variables to avoid errors
$gender = "";           // Initialize variables to avoid errors
$program_id = null;     // Initialize variables to avoid errors


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Form Submission Logic ---
    $beneficiary_name = isset($_POST["beneficiary_name"]) ? sanitize_input($_POST["beneficiary_name"]) : "";
    $phone_number = isset($_POST["phone_number"]) ? sanitize_input($_POST["phone_number"]) : "";
    $gender = isset($_POST["gender"]) ? sanitize_input($_POST["gender"]) : "";
    $program_id = isset($_POST["program_id"]) ? intval($_POST["program_id"]) : null;
    $suggestions = isset($_POST["suggestions"]) ? sanitize_input($_POST["suggestions"]) : "";

    $answers = isset($_POST["answers"]) ? $_POST["answers"] : [];

    // Basic validation
       // Basic validation
    $errors = [];

    // First, validate the special, hard-coded fields that are always required
    if (empty($gender)) {
        $errors[] = "الرجاء اختيار الجنس.";
    }
    if (empty($program_id)) {
        $errors[] = "الرجاء اختيار البرنامج المستفاد منه.";
    }

    // Second, loop through the dynamic questions from the database
    foreach ($questions as $question) {
        // Skip special fields because we already validated them above
        if (in_array($question["question_text"], ["اسم المستفيد", "رقم الجوال", "الجنس", "البرنامج المستفاد منه"])) {
            continue;
        }

        // Now, validate the rest of the required questions
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
            $error_message = "حدث خطأ أثناء حفظ البيانات. يرجى المحاولة مرة أخرى.";
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
            <img src="<?php echo htmlspecialchars(get_base_url() . $settings["logo_path"]); ?>" alt="<?php echo htmlspecialchars($settings["site_name"]); ?> Logo" class="survey-logo">
        <?php else : ?>
            <h1><?php echo htmlspecialchars($settings["site_name"]); ?></h1>
        <?php endif; ?>
        <h2>استبيان قياس رضا المستفيدين</h2>
        <p>يهمنا رأيكم لتحسين خدماتنا. نرجو منكم تعبئة الاستبيان التالي.</p>
    </div>

    <?php if (!empty($success_message)) : ?>
        <div class="alert alert-success">
            <h3><?php echo $success_message; ?></h3>
            <p>نقدر وقتكم ومشاركتكم القيمة.</p>
        </div>
    <?php elseif (!empty($error_message)) : ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($success_message)) : ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="survey-form">
        <div class="form-row">
            <div class="form-group-half">
                <label class="question-title">اسم المستفيد (اختياري)</label>
                <input type="text" name="beneficiary_name" class="form-control" value="<?php echo htmlspecialchars($beneficiary_name); ?>">
            </div>
            <div class="form-group-half">
                <label class="question-title">رقم الجوال (اختياري)</label>
                <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($phone_number); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group-half">
                <label class="question-title">الجنس *</label>
                <div class="options-group">
                    <?php
                    // This logic seems complex, let's assume get_question_id_by_text works as intended
                    $gender_question_id = get_question_id_by_text($conn, "الجنس");
                    if($gender_question_id) {
                        $gender_options = get_question_options($conn, $gender_question_id);
                        foreach ($gender_options as $option) {
                            echo "<div class=\"radio-option\">";
                            echo "<input type=\"radio\" name=\"gender\" value=\"" . htmlspecialchars($option["option_text"]) . "\" id=\"gender_" . $option["id"] . "\" " . (($gender == $option["option_text"]) ? 'checked' : '') . " required>";
                            echo "<label for=\"gender_" . $option["id"] . "\">" . htmlspecialchars($option["option_text"]) . "</label>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="form-group-half">
                <label class="question-title">البرنامج المستفاد منه *</label>
                <select name="program_id" class="form-control" required>
                    <option value="">اختر البرنامج</option>
                    <?php foreach ($programs as $program) : ?>
                        <option value="<?php echo $program["id"]; ?>" <?php echo (($program_id == $program["id"]) ? 'selected' : ''); ?>><?php echo htmlspecialchars($program["program_name"]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php foreach ($questions as $question) : ?>
            <?php
            // Skip these questions as they are handled separately above
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
                switch ($question["question_type"]) {
                    case 'text':
                        echo "<input type=\"text\" name=\"answers[$question_id]\" class=\"form-control\" $is_required_attr>";
                        break;

                    case 'single_choice':
                        $options = get_question_options($conn, $question_id);
                        echo "<div class=\"options-group\">";
                        foreach ($options as $option) {
                            echo "<div class=\"radio-option\">";
                            echo "<input type=\"radio\" name=\"answers[$question_id]\" value=\"" . htmlspecialchars($option["option_text"]) . "\" id=\"option_$option[id]\" $is_required_attr>";
                            echo "<label for=\"option_$option[id]\">" . htmlspecialchars($option["option_text"]) . "</label>";
                            echo "</div>";
                        }
                        echo "</div>";
                        break;

                    case 'dropdown':
                        $options = get_question_options($conn, $question_id);
                        echo "<select name=\"answers[$question_id]\" class=\"form-control\" $is_required_attr>";
                        echo "<option value=\"\">اختر...</option>";
                        foreach ($options as $option) {
                            echo "<option value=\"" . htmlspecialchars($option["option_text"]) . "\">" . htmlspecialchars($option["option_text"]) . "</option>";
                        }
                        echo "</select>";
                        break;

                    case 'multiple_choice':
                        $options = get_question_options($conn, $question_id);
                        echo "<div class=\"options-group\">";
                        foreach ($options as $option) {
                            echo "<div class=\"checkbox-option\">";
                            echo "<input type=\"checkbox\" name=\"answers[$question_id][]\" value=\"" . htmlspecialchars($option["option_text"]) . "\" id=\"option_$option[id]\">";
                            echo "<label for=\"option_$option[id]\">" . htmlspecialchars($option["option_text"]) . "</label>";
                            echo "</div>";
                        }
                        echo "</div>";
                        break;

                    case 'rating':
                        echo "<div class=\"rating-group\">";
                        echo "<span>غير راضٍ تماماً</span>";
                        for ($i = 1; $i <= 5; $i++) {
                            echo "<label class=\"rating-label\">";
                            echo "<input type=\"radio\" name=\"answers[$question_id]\" value=\"$i\" class=\"rating-radio\" $is_required_attr>";
                            echo "<span class=\"rating-number\">$i</span>";
                            echo "</label>";
                        }
                        echo "<span>راضٍ جداً</span>";
                        echo "</div>";
                        break;
                }
                ?>
            </div>
        <?php endforeach; ?>
        
        <div class="question-block">
             <label class="question-title">مقترحات للتحسين (اختياري)</label>
             <textarea name="suggestions" class="form-control" rows="4"><?php echo htmlspecialchars($suggestions ?? ''); ?></textarea>
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
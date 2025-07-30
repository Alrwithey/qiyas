<?php
session_start();
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../functions.php";
require_once __DIR__ . "/../db.php";

require_login();

$settings = get_latest_settings($conn);
$message = "";
$error = "";
$question_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($question_id == 0) {
    redirect("questions.php");
}

$question = get_question_by_id($conn, $question_id);
$options = get_question_options($conn, $question_id);

if (!$question) {
    redirect("questions.php");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_question"])) {
        $question_text = sanitize_input($_POST["question_text"]);
        $question_type = sanitize_input($_POST["question_type"]);
        $is_required = isset($_POST["is_required"]) ? 1 : 0;

        if (update_question($conn, $question_id, $question_text, $question_type, $is_required)) {
            $message = "تم تحديث السؤال بنجاح.";
            $question = get_question_by_id($conn, $question_id); // Refresh data
        } else {
            $error = "حدث خطأ أثناء تحديث السؤال.";
        }
    } elseif (isset($_POST["add_option"])) {
        $option_text = sanitize_input($_POST["option_text"]);
        if (add_question_option($conn, $question_id, $option_text)) {
            $message = "تمت إضافة الخيار بنجاح.";
            $options = get_question_options($conn, $question_id); // Refresh options
        } else {
            $error = "حدث خطأ أثناء إضافة الخيار.";
        }
    } elseif (isset($_POST["delete_option"])) {
        $option_id = intval($_POST["option_id"]);
        if (delete_question_option($conn, $option_id)) {
            $message = "تم حذف الخيار بنجاح.";
            $options = get_question_options($conn, $question_id); // Refresh options
        } else {
            $error = "حدث خطأ أثناء حذف الخيار.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_admin_head($settings, "تعديل السؤال"); ?>
<body>
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="admin-wrapper">
        <div class="sidebar">
            <?php echo get_admin_header($settings); ?>
        </div>
        <div class="main-content">
            <h1>تعديل السؤال</h1>

            <?php if ($message) : ?><div class="success"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error) : ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

            <div class="form-container">
                <h2>تفاصيل السؤال</h2>
                <form action="edit_question.php?id=<?php echo $question_id; ?>" method="post" class="admin-form">
                    <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                    <div class="form-group">
                        <label for="question_text">نص السؤال</label>
                        <input type="text" id="question_text" name="question_text" value="<?php echo htmlspecialchars($question["question_text"]); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="question_type">نوع السؤال</label>
                        <select id="question_type" name="question_type">
                            <option value="text" <?php echo ($question["question_type"] == "text") ? "selected" : ""; ?>>نصي</option>
                            <option value="single_choice" <?php echo ($question["question_type"] == "single_choice") ? "selected" : ""; ?>>اختيار من متعدد (إجابة واحدة)</option>
                            <option value="multiple_choice" <?php echo ($question["question_type"] == "multiple_choice") ? "selected" : ""; ?>>اختيار من متعدد (عدة إجابات)</option>
                            
                            <?php // ==================== يبدأ التعديل هنا: إضافة نوع القائمة المنسدلة ==================== ?>
                            <option value="dropdown" <?php echo ($question["question_type"] == "dropdown") ? "selected" : ""; ?>>قائمة منسدلة</option>
                            <?php // ==================== ينتهي التعديل هنا ==================== ?>
                            
                            <option value="rating" <?php echo ($question["question_type"] == "rating") ? "selected" : ""; ?>>تقييم (1-5)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="is_required" value="1" <?php echo $question["is_required"] ? "checked" : ""; ?>> سؤال إلزامي</label>
                    </div>
                    <button type="submit" name="update_question">تحديث السؤال</button>
                </form>
            </div>

            <?php // ==================== يبدأ التعديل هنا: تحديث الشرط ليشمل القائمة المنسدلة ==================== ?>
            <?php if ($question["question_type"] == "single_choice" || $question["question_type"] == "multiple_choice" || $question["question_type"] == "dropdown") : ?>
            <?php // ==================== ينتهي التعديل هنا ==================== ?>

            <div class="table-container">
                <h2>خيارات السؤال</h2>
                <table class="table">
                    <tbody>
                        <?php foreach ($options as $option) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($option["option_text"]); ?></td>
                                <td style="text-align:left;">
                                    <form action="edit_question.php?id=<?php echo $question_id; ?>" method="post" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا الخيار؟');" style="display:inline;">
                                        <input type="hidden" name="option_id" value="<?php echo $option["id"]; ?>">
                                        <button type="submit" name="delete_option" class="btn-delete">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-container">
                <h3>إضافة خيار جديد</h3>
                <form action="edit_question.php?id=<?php echo $question_id; ?>" method="post" class="admin-form">
                    <div class="form-group">
                        <label for="option_text">نص الخيار</label>
                        <input type="text" id="option_text" name="option_text" required>
                    </div>
                    <button type="submit" name="add_option">إضافة خيار</button>
                </form>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php echo get_admin_footer(); ?>
</body>
</html>
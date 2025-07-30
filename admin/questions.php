<?php
session_start();
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../functions.php";
require_once __DIR__ . "/../db.php";

require_login();

$settings = get_latest_settings($conn);
$message = "";
$error = "";

// Handle Add/Edit/Delete Actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_question"])) {
        $question_text = sanitize_input($_POST["question_text"]);
        $question_type = sanitize_input($_POST["question_type"]);
        $is_required = isset($_POST["is_required"]) ? 1 : 0;
        $options = isset($_POST["options"]) ? $_POST["options"] : [];

        if (add_question($conn, $question_text, $question_type, $is_required, $options)) {
            $message = "تمت إضافة السؤال بنجاح.";
        } else {
            $error = "حدث خطأ أثناء إضافة السؤال.";
        }
    } elseif (isset($_POST["update_question"])) {
        $id = intval($_POST["question_id"]);
        $question_text = sanitize_input($_POST["question_text"]);
        $question_type = sanitize_input($_POST["question_type"]);
        $is_required = isset($_POST["is_required"]) ? 1 : 0;

        if (update_question($conn, $id, $question_text, $question_type, $is_required)) {
            $message = "تم تحديث السؤال بنجاح.";
        } else {
            $error = "حدث خطأ أثناء تحديث السؤال.";
        }
    } elseif (isset($_POST["delete_question"])) {
        $id = intval($_POST["question_id"]);
        if (delete_question($conn, $id)) {
            $message = "تم حذف السؤال بنجاح.";
        } else {
            $error = "حدث خطأ أثناء حذف السؤال.";
        }
    }
}

$questions = get_all_questions($conn);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_admin_head($settings, "إدارة الأسئلة"); ?>

<?php // ==================== يبدأ التعديل هنا: إضافة CSS للأزرار ==================== ?>
<style>
    .option-input {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    .option-input input[type="text"] {
        flex-grow: 1;
    }
    .btn-action-option {
        padding: 8px 12px;
        border: none;
        border-radius: 5px;
        color: white !important;
        cursor: pointer;
        font-size: 1.2rem;
        font-weight: bold;
        line-height: 1;
        width: 40px; /* تحديد عرض ثابت */
        text-align: center;
    }
    .btn-add-option {
        background-color: var(--success-color, #27ae60);
    }
    .btn-remove-option {
        background-color: var(--danger-color, #e74c3c);
    }
</style>
<?php // ==================== ينتهي التعديل هنا ==================== ?>

<body>
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="admin-wrapper">
        <div class="sidebar">
            <?php echo get_admin_header($settings); ?>
        </div>
        <div class="main-content">
            <h1>إدارة الأسئلة</h1>

            <?php if ($message) : ?><div class="success"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error) : ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

            <div class="form-container">
                <h2>إضافة سؤال جديد</h2>
                <form action="questions.php" method="post" class="admin-form">
                    <div class="form-group">
                        <label for="question_text">نص السؤال</label>
                        <input type="text" id="question_text" name="question_text" required>
                    </div>
                    <div class="form-group">
                        <label for="question_type">نوع السؤال</label>
                        <select id="question_type" name="question_type" onchange="toggleOptions(this.value)">
                            <option value="text">نصي</option>
                            <option value="single_choice">اختيار من متعدد (إجابة واحدة)</option>
                            <option value="multiple_choice">اختيار من متعدد (عدة إجابات)</option>
                            <?php // ==================== يبدأ التعديل هنا: إضافة نوع جديد ==================== ?>
                            <option value="dropdown">قائمة منسدلة</option>
                            <?php // ==================== ينتهي التعديل هنا ==================== ?>
                            <option value="rating">تقييم (1-5)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="is_required" value="1"> سؤال إلزامي</label>
                    </div>
                    <div id="options-container" style="display:none;">
                        <div class="form-group">
                            <label>الخيارات</label>
                            <div id="options-wrapper">
                                <div class="option-input">
                                    <input type="text" name="options[]" placeholder="نص الخيار 1">
                                    <?php // ==================== يبدأ التعديل هنا: تعديل تصميم الزر ==================== ?>
                                    <button type="button" onclick="addOption()" class="btn-action-option btn-add-option">+</button>
                                    <?php // ==================== ينتهي التعديل هنا ==================== ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="add_question">إضافة السؤال</button>
                </form>
            </div>

            <div class="table-container">
                <h2>الأسئلة الحالية</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الترتيب</th>
                                <th>نص السؤال</th>
                                <th>النوع</th>
                                <th>إلزامي</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $question) : ?>
                                <tr>
                                    <td><?php echo $question["question_order"]; ?></td>
                                    <td><?php echo htmlspecialchars($question["question_text"]); ?></td>
                                    <td><?php echo htmlspecialchars($question["question_type"]); ?></td>
                                    <td><?php echo $question["is_required"] ? '<span class="status-yes">نعم</span>' : '<span class="status-no">لا</span>'; ?></td>
                                    <td>
                                        <a href="edit_question.php?id=<?php echo $question["id"]; ?>" class="btn-edit"><i class="fas fa-edit"></i> تعديل</a>
                                        <form action="questions.php" method="post" style="display:inline-block;" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا السؤال؟');">
                                            <input type="hidden" name="question_id" value="<?php echo $question["id"]; ?>">
                                            <button type="submit" name="delete_question" class="btn-delete"><i class="fas fa-trash"></i> حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php echo get_admin_footer(); ?>
    <script>
        function toggleOptions(type) {
            const optionsContainer = document.getElementById("options-container");
            // ==================== يبدأ التعديل هنا: تحديث الشرط ====================
            if (type === "single_choice" || type === "multiple_choice" || type === "dropdown") {
            // ==================== ينتهي التعديل هنا ====================
                optionsContainer.style.display = "block";
            } else {
                optionsContainer.style.display = "none";
            }
        }

        function addOption() {
            const wrapper = document.getElementById("options-wrapper");
            const newOption = document.createElement("div");
            newOption.className = "option-input";
            const inputCount = wrapper.getElementsByTagName("input").length;
            // ==================== يبدأ التعديل هنا: تعديل تصميم زر الحذف ====================
            newOption.innerHTML = 
                `<input type="text" name="options[]" placeholder="نص الخيار ${inputCount + 1}">
                 <button type="button" onclick="removeOption(this)" class="btn-action-option btn-remove-option">-</button>`;
            // ==================== ينتهي التعديل هنا ====================
            wrapper.appendChild(newOption);
        }

        function removeOption(button) {
            button.parentElement.remove();
        }
    </script>
</body>
</html>
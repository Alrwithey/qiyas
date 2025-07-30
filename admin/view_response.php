<?php
session_start();
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../functions.php";
require_once __DIR__ . "/../db.php";

require_login();
if (!isset($_GET["id"]) || empty($_GET["id"])) { redirect("survey_results.php"); }
$response_id = intval($_GET["id"]);
$settings = get_latest_settings($conn);

$sql_response = "SELECT sr.id, sr.beneficiary_name, sr.phone_number, sr.gender, p.program_name, sr.suggestions, sr.submission_date FROM survey_responses sr LEFT JOIN programs p ON sr.program_id = p.id WHERE sr.id = ?";
$stmt_response = $conn->prepare($sql_response);
$stmt_response->bind_param("i", $response_id);
$stmt_response->execute();
$response_result = $stmt_response->get_result();
$response = $response_result->fetch_assoc();

if (!$response) { redirect("survey_results.php"); }

// Translate gender for display
$gender_display = 'لم يحدد';
if ($response['gender'] === 'male') {
    $gender_display = 'ذكر';
} elseif ($response['gender'] === 'female') {
    $gender_display = 'أنثى';
}

$sql_answers = "SELECT q.question_text, q.question_type, sa.answer_text, sa.rating FROM survey_answers sa JOIN questions q ON sa.question_id = q.id WHERE sa.response_id = ? ORDER BY q.question_order ASC";
$stmt_answers = $conn->prepare($sql_answers);
$stmt_answers->bind_param("i", $response_id);
$stmt_answers->execute();
$answers_result = $stmt_answers->get_result();
$answers = [];
if ($answers_result && $answers_result->num_rows > 0) { while ($row = $answers_result->fetch_assoc()) { $answers[] = $row; } }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_admin_head($settings, "عرض الاستبيان"); ?>
<body>
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="admin-wrapper">
        <div class="sidebar"><?php echo get_admin_header($settings); ?></div>
        <div class="main-content">
            <h1>تفاصيل الاستبيان رقم #<?php echo htmlspecialchars($response["id"]); ?></h1>
            <div class="details-wrapper">
                <div class="details-section">
                    <h3><i class="fas fa-user"></i> معلومات المستفيد</h3>
                    <div class="detail-grid">
                        <div class="detail-pair"><strong>اسم المستفيد:</strong><span><?php echo htmlspecialchars($response["beneficiary_name"] ?? "<em>لم يدخل</em>"); ?></span></div>
                        <div class="detail-pair"><strong>رقم الجوال:</strong><span><?php echo htmlspecialchars($response["phone_number"] ?? "<em>لم يدخل</em>"); ?></span></div>
                        <div class="detail-pair"><strong>الجنس:</strong><span><?php echo htmlspecialchars($gender_display); ?></span></div>
                        <div class="detail-pair"><strong>البرنامج المستفاد منه:</strong><span><?php echo htmlspecialchars($response["program_name"] ?? "غير محدد"); ?></span></div>
                        <div class="detail-pair"><strong>تاريخ المشاركة:</strong><span><?php echo htmlspecialchars($response["submission_date"]); ?></span></div>
                    </div>
                </div>

                <div class="actions-section">
                    <h3><i class="fas fa-info-circle"></i> ملخص</h3>
                    <p>هنا يمكنك عرض ملخص سريع للاستبيان.</p>
                    <a href="survey_results.php" class="btn-action" style="background-color: #34495e;"><i class="fas fa-arrow-right"></i> العودة إلى القائمة</a>
                </div>
            </div>

            <div class="form-container">
                <h3><i class="fas fa-poll-h"></i> إجابات الاستبيان</h3>
                <?php if (!empty($answers)): ?>
                    <?php foreach ($answers as $answer) : ?>
                        <div class="question-block">
                            <p class="question-title"><?php echo htmlspecialchars($answer["question_text"]); ?></p>
                            <div class="answer">
                                <?php
                                switch ($answer["question_type"]) {
                                    case "rating":
                                        echo "<div class=\"rating-display\">";
                                        echo "<span class=\"rating-text\">(" . htmlspecialchars($answer["rating"] ?? 0) . "/5)</span>";
                                        echo "<div class=\"rating-stars\">";
                                        for ($i = 1; $i <= 5; $i++) {
                                            $class = ($i <= $answer["rating"]) ? "fas fa-star rated" : "fas fa-star";
                                            echo "<i class=\"{$class}\"></i>";
                                        }
                                        echo "</div>"; // End rating-stars
                                        echo "</div>"; // End rating-display
                                        break;
                                    case "multiple_choice":
                                        echo htmlspecialchars($answer["multiple_choice_answers"] ?? 'لا توجد إجابة');
                                        break;
                                    default:
                                        echo htmlspecialchars($answer["answer_text"] ?? "لا توجد إجابة");
                                        break;
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                     <p>لم يتم العثور على إجابات لهذا الاستبيان.</p>
                <?php endif; ?>

                <?php if (!empty($response["suggestions"])) : ?>
                    <div class="question-block">
                        <p class="question-title">اقتراحات وملاحظات لتحسين الخدمة:</p>
                        <div class="answer">
                            <p><?php echo nl2br(htmlspecialchars($response["suggestions"])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php echo get_admin_footer(); ?>
</body>
</html>
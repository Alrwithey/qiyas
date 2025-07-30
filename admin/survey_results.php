<?php
session_start();
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../functions.php";
require_once __DIR__ . "/../db.php";

require_login();

$settings = get_latest_settings($conn);

// Fetch all survey responses (This query is from your original code and is correct)
$sql = "SELECT sr.id, sr.beneficiary_name, sr.phone_number, sr.gender, p.program_name, sr.submission_date FROM survey_responses sr LEFT JOIN programs p ON sr.program_id = p.id ORDER BY sr.submission_date DESC";
$result = $conn->query($sql);
$responses = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $responses[] = $row;
    }
}

// Handle deletion (This logic is from your original code and is correct)
if (isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"])) {
    $response_id = intval($_GET["id"]);
    if (delete_survey_response($conn, $response_id)) {
        $_SESSION["message"] = "تم حذف الاستبيان بنجاح.";
    } else {
        $_SESSION["error"] = "حدث خطأ أثناء حذف الاستبيان.";
    }
    redirect("survey_results.php");
}

$message = isset($_SESSION["message"]) ? $_SESSION["message"] : "";
unset($_SESSION["message"]);
$error = isset($_SESSION["error"]) ? $_SESSION["error"] : "";
unset($_SESSION["error"]);

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_admin_head($settings, "نتائج الاستبيانات"); ?>
<body>
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="admin-wrapper">
        <div class="sidebar">
            <?php echo get_admin_header($settings); ?>
        </div>
        <div class="main-content">
            <h1>نتائج الاستبيانات</h1>

            <?php if ($message) : ?><div class="success"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error) : ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>رقم المشاركة</th>
                                <th>اسم المستفيد</th>
                                <th>رقم الجوال</th>
                                <th>الجنس</th>
                                <th>البرنامج</th>
                                <th>تاريخ المشاركة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($responses)) : ?>
                                <?php foreach ($responses as $response) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($response["id"]); ?></td>
                                        <td><?php echo htmlspecialchars($response["beneficiary_name"] ?? "غير محدد"); ?></td>
                                        <td><?php echo htmlspecialchars($response["phone_number"] ?? "غير محدد"); ?></td>
                                        
                                        <!-- ===== THIS IS THE ONLY MODIFIED PART ===== -->
                                        <td>
                                            <?php
                                            if ($response['gender'] === 'male') {
                                                echo 'ذكر';
                                            } elseif ($response['gender'] === 'female') {
                                                echo 'أنثى';
                                            } else {
                                                // Display the saved value if it's something else, or a default text
                                                echo htmlspecialchars($response["gender"] ?? "غير محدد");
                                            }
                                            ?>
                                        </td>
                                        <!-- ===== END OF MODIFICATION ===== -->

                                        <td><?php echo htmlspecialchars($response["program_name"] ?? "غير محدد"); ?></td>
                                        <td><?php echo htmlspecialchars($response["submission_date"]); ?></td>
                                        <td>
                                            <a href="view_response.php?id=<?php echo $response["id"]; ?>" class="btn-edit"><i class="fas fa-eye"></i> عرض</a>
                                            <a href="survey_results.php?action=delete&id=<?php echo $response["id"]; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا الاستبيان؟');"><i class="fas fa-trash"></i> حذف</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7">لا توجد استبيانات متاحة.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <?php echo get_admin_footer(); ?>
</body>
</html>
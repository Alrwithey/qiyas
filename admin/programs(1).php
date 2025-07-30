<?php
session_start();
require_once __DIR__ . 
    "/../config.php";
require_once __DIR__ . 
    "/../functions.php";
require_once __DIR__ . 
    "/../db.php";

require_login();

$settings = get_latest_settings($conn);
$message = "";
$error = "";

// Handle Add/Edit/Delete Actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_program"])) {
        $program_name = sanitize_input($_POST["program_name"]);
        if (add_program($conn, $program_name)) {
            $message = "تمت إضافة البرنامج بنجاح.";
        } else {
            $error = "حدث خطأ أثناء إضافة البرنامج.";
        }
    } elseif (isset($_POST["update_program"])) {
        $id = intval($_POST["program_id"]);
        $program_name = sanitize_input($_POST["program_name"]);
        if (update_program($conn, $id, $program_name)) {
            $message = "تم تحديث البرنامج بنجاح.";
        } else {
            $error = "حدث خطأ أثناء تحديث البرنامج.";
        }
    } elseif (isset($_POST["delete_program"])) {
        $id = intval($_POST["program_id"]);
        if (delete_program($conn, $id)) {
            $message = "تم حذف البرنامج بنجاح.";
        } else {
            $error = "حدث خطأ أثناء حذف البرنامج.";
        }
    }
}

$programs = get_all_programs($conn);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_admin_head($settings, "إدارة البرامج"); ?>
<body>
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="admin-wrapper">
        <div class="sidebar">
            <?php echo get_admin_header($settings); ?>
        </div>
        <div class="main-content">
            <h1>إدارة البرامج</h1>

            <?php if ($message) : ?><div class="success"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error) : ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

            <div class="form-container">
                <h2>إضافة برنامج جديد</h2>
                <form action="programs.php" method="post" class="admin-form">
                    <div class="form-group">
                        <label for="program_name">اسم البرنامج</label>
                        <input type="text" id="program_name" name="program_name" required>
                    </div>
                    <button type="submit" name="add_program">إضافة برنامج</button>
                </form>
            </div>

            <div class="table-container">
                <h2>البرامج الحالية</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $program) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($program["program_name"]); ?></td>
                                    <td>
                                        <form action="programs.php" method="post" style="display:inline-block;">
                                            <input type="hidden" name="program_id" value="<?php echo $program["id"]; ?>">
                                            <input type="text" name="program_name" value="<?php echo htmlspecialchars($program["program_name"]); ?>" required>
                                            <button type="submit" name="update_program" class="btn-edit"><i class="fas fa-edit"></i> تحديث</button>
                                            <button type="submit" name="delete_program" class="btn-delete" onclick="return confirm(
'هل أنت متأكد من رغبتك في حذف هذا البرنامج؟
');"><i class="fas fa-trash"></i> حذف</button>
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
</body>
</html>



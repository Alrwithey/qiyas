<?php
session_start();
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../functions.php";
require_once __DIR__ . "/../db.php";

require_login();

$settings = get_latest_settings($conn);
$message = "";
$error = "";

$google_fonts = [
    'Tajawal' => 'https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap',
    'Cairo' => 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;700&display=swap',
    'Almarai' => 'https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap',
    'Noto Sans Arabic' => 'https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;700&display=swap',
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ======================= يبدأ الكود الجديد: معالجة تغيير كلمة المرور =======================
    if (isset($_POST["change_password"])) {
        $old_password = $_POST["old_password"];
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];

        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error = "الرجاء ملء جميع حقول كلمة المرور.";
        } elseif ($old_password !== ADMIN_PASSWORD) {
            $error = "كلمة المرور القديمة غير صحيحة.";
        } elseif (strlen($new_password) < 6) {
            $error = "كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.";
        } elseif ($new_password !== $confirm_password) {
            $error = "كلمة المرور الجديدة وتأكيدها غير متطابقين.";
        } else {
            // All checks passed, update the credentials file
            $credentials_path = __DIR__ . '/../admin_credentials.php';
            $new_content = "<?php\n";
            $new_content .= "define('ADMIN_USERNAME', '" . addslashes(ADMIN_USERNAME) . "');\n";
            $new_content .= "define('ADMIN_PASSWORD', '" . addslashes($new_password) . "');\n";
            
            if (file_put_contents($credentials_path, $new_content)) {
                $message = "تم تغيير كلمة المرور بنجاح.";
            } else {
                $error = "حدث خطأ أثناء تحديث ملف بيانات الدخول. تأكد من صلاحيات الكتابة على الملف.";
            }
        }
    }
    // ======================= ينتهي الكود الجديد =======================
    
    // Handle settings update
    elseif (isset($_POST["update_settings"])) {
        $data_to_save = [
            'site_name' => sanitize_input($_POST["site_name"]),
            'system_name' => sanitize_input($_POST["system_name"]),
            'primary_color' => sanitize_input($_POST["primary_color"]),
            'secondary_color' => sanitize_input($_POST["secondary_color"]),
            'logo_path' => $settings["logo_path"] ?? ''
        ];
        $selected_font_name = sanitize_input($_POST["font_name"]);
        if (array_key_exists($selected_font_name, $google_fonts)) {
            $data_to_save['primary_font_name'] = $selected_font_name;
            $data_to_save['primary_font_url'] = $google_fonts[$selected_font_name];
        }
        if (isset($_FILES["logo"]) && $_FILES["logo"]["error"] == 0) {
            $target_dir = __DIR__ . "/../uploads/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
            $imageFileType = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid('logo_', true) . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;
            $check = getimagesize($_FILES["logo"]["tmp_name"]);
            if ($check === false) { $error = "الملف الذي تم رفعه ليس صورة."; }
            elseif ($_FILES["logo"]["size"] > 2000000) { $error = "عذراً، حجم الملف كبير جداً."; }
            elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) { $error = "عذراً، يُسمح فقط بملفات الصور."; }
            else {
                if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                    if (!empty($settings['logo_path']) && file_exists(__DIR__ . '/../' . $settings['logo_path'])) { unlink(__DIR__ . '/../' . $settings['logo_path']); }
                    $data_to_save['logo_path'] = "uploads/" . $new_filename;
                } else { $error = "عذراً، حدث خطأ أثناء رفع ملفك."; }
            }
        }
        if (empty($error)) {
            if (update_settings($conn, $data_to_save)) {
                $message = "تم تحديث الإعدادات بنجاح.";
                $settings = get_latest_settings($conn);
            } else { $error = "حدث خطأ أثناء تحديث الإعدادات."; }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_admin_head($settings, "إعدادات الموقع"); ?>
<body class="settings-page">
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="admin-wrapper">
        <div class="sidebar">
            <?php echo get_admin_header($settings); ?>
        </div>
        <div class="main-content">
            <h1>إعدادات الموقع</h1>

            <?php if ($message) : ?><div class="success"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error) : ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

            <div class="form-container">
                <form action="settings.php" method="post" class="admin-form" enctype="multipart/form-data">
                    <div class="form-section-header"><h3>الإعدادات العامة</h3></div>
                    <div class="form-group"><label for="site_name">اسم الموقع</label><input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings["site_name"]); ?>" required></div>
                    <div class="form-group"><label for="system_name">اسم النظام</label><input type="text" id="system_name" name="system_name" value="<?php echo htmlspecialchars($settings["system_name"]); ?>" required></div>
                    <div class="form-group">
                        <label for="logo">شعار الموقع</label>
                        <?php if (!empty($settings["logo_path"])) : ?><img src="<?php echo "../" . htmlspecialchars($settings["logo_path"]); ?>" alt="شعار الموقع الحالي" style="max-width: 150px; margin-bottom: 10px; background: #f0f0f0; padding: 5px; border-radius: 5px;"><br><?php endif; ?>
                        <input type="file" id="logo" name="logo" accept="image/*"><small>اترك فارغاً للاحتفاظ بالشعار الحالي.</small>
                    </div>
                    
                    <div class="form-section-header"><h3>التصميم والألوان</h3></div>
                    <div class="form-group">
                        <label for="font_name">الخط الرئيسي</label>
                        <select id="font_name" name="font_name" class="form-control">
                            <?php foreach ($google_fonts as $name => $url): ?><option value="<?php echo $name; ?>" <?php echo (isset($settings["primary_font_name"]) && $settings["primary_font_name"] == $name) ? "selected" : ""; ?>><?php echo $name; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="color-picker-group">
                        <div class="color-picker-wrapper"><label for="primary_color">اللون الأساسي</label><input type="color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($settings["primary_color"]); ?>"></div>
                        <div class="color-picker-wrapper"><label for="secondary_color">اللون الثانوي</label><input type="color" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars($settings["secondary_color"]); ?>"></div>
                    </div>
                    <br>
                    <button type="submit" name="update_settings">حفظ الإعدادات</button>
                </form>
            </div>

            <?php // ======================= يبدأ الكود الجديد: فورم تغيير كلمة المرور ======================= ?>
            <div class="form-container">
                <form action="settings.php" method="post" class="admin-form">
                    <div class="form-section-header"><h3>تغيير كلمة المرور</h3></div>
                    <div class="form-group">
                        <label for="old_password">كلمة المرور الحالية</label>
                        <input type="password" id="old_password" name="old_password" required>
                    </div>
                     <div class="form-group">
                        <label for="new_password">كلمة المرور الجديدة</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                     <div class="form-group">
                        <label for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password">تغيير كلمة المرور</button>
                </form>
            </div>
            <?php // ======================= ينتهي الكود الجديد ======================= ?>

        </div>
    </div>
    <?php echo get_admin_footer(); ?>
</body>
</html>
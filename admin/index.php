<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php'; // نحتاجها لدالة get_latest_settings
require_once __DIR__ . '/../db.php';       // نحتاجها لمتغير $conn

// إذا كان المستخدم مسجل دخوله، يتم توجيهه للوحة التحكم
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: dashboard.php");
    exit;
}

// جلب الإعدادات لجلب الشعار والعناوين
$settings = get_latest_settings($conn);

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["username"]))) {
        $username_err = "الرجاء إدخال اسم المستخدم.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "الرجاء إدخال كلمة المرور.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($username_err) && empty($password_err)) {
        if (defined('ADMIN_USERNAME') && defined('ADMIN_PASSWORD') && $username == ADMIN_USERNAME && $password == ADMIN_PASSWORD) {
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $username;
            header("Location: dashboard.php");
            exit();
        } else {
            $login_err = "اسم المستخدم أو كلمة المرور غير صحيحة.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?php echo htmlspecialchars($settings['site_name'] ?? 'لوحة التحكم'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* CSS مخصص ومطابق للصورة */
        :root {
            --primary-color: #1a535c; /* اللون الأساسي (الأخضر الداكن) */
            --light-bg: #f4f6f9;      /* لون الخلفية العام */
            --input-bg: #eef5fc;      /* لون خلفية حقول الإدخال الأزرق الفاتح */
            --input-border: #dce8f4;  /* لون حدود حقول الإدخال */
            --white-color: #ffffff;
            --text-color: #333;
            --danger-color: #e74c3c;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--light-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }

        .login-card {
            background: var(--white-color);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-top: 5px solid var(--primary-color);
            text-align: center;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-logo {
            max-width: 180px; /* أو الحجم المناسب لشعارك */
            height: auto;
            margin-bottom: 15px;
        }

        .login-subtitle {
            font-size: 1.3rem;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .login-title {
            font-size: 1.9rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .login-title2 {
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: right;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 1rem;
            color: #555;
        }

        .form-control {
            display: block;
            width: 100%;
            height: 55px;
            padding: 10px 16px;
            font-size: 1.1rem;
            font-family: 'Tajawal', sans-serif;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            background-color: var(--input-bg);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 4px rgba(26, 83, 92, 0.1);
        }

        .is-invalid {
            border-color: var(--danger-color) !important;
        }
        
        .invalid-feedback {
            display: block; color: var(--danger-color); font-size: 0.875rem; margin-top: 5px;
        }

        .alert-danger {
            padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb;
            border-radius: 6px; color: #721c24; background-color: #f8d7da; text-align: center;
        }
        
        .btn-primary {
            width: 100%;
            padding: 15px;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--white-color);
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        .btn-primary:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">

            <div class="login-header">
                <?php if (!empty($settings['logo_path'])): ?>
                    <img src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="شعار الجهة" class="login-logo">
                <?php endif; ?>

                     <h2 class="login-title2"><?php echo htmlspecialchars($settings["system_name"]); ?></h2>
     <h2 class="login-title2">لوحة التحكم</h2>
                <h2 class="login-title">تسجيل الدخول</h2>
            </div>

            <?php 
            if(!empty($login_err)){
                echo '<div class="alert alert-danger">' . htmlspecialchars($login_err) . '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="username">اسم المستخدم</label>
                    <input id="username" type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>    
                <div class="form-group">
                    <label for="password">كلمة المرور</label>
                    <input id="password" type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group" style="margin-top: 30px;">
                    <input type="submit" class="btn btn-primary" value="دخول">
                </div>
            </form>
        </div>
    </div>
</body>
</html>
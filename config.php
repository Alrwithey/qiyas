<?php
// إعدادات قاعدة البيانات
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'qiyas_enhanced');

// إعدادات النظام
define('SITE_TITLE', 'نظام قياس رضا المستفيدين المطور');
define('ADMIN_EMAIL', 'admin@example.com');
define('DEFAULT_LANGUAGE', 'ar');

// إعدادات الأمان
define('SESSION_TIMEOUT', 3600); // ساعة واحدة
define('PASSWORD_MIN_LENGTH', 6);

// مسارات الملفات
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 ميجابايت

// إعدادات افتراضية للأنظمة الجديدة
define('DEFAULT_PRIMARY_COLOR', '#1a535c');
define('DEFAULT_SECONDARY_COLOR', '#f7b538');
define('DEFAULT_FONT_FAMILY', 'Almarai');
define('DEFAULT_FONT_URL', 'https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap');

// تشغيل الجلسات
session_start();

// تحديد المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');
?>


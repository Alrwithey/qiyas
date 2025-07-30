<?php
// Database credentials
define("DB_SERVER", "localhost");
define("DB_USERNAME", "u414795990_qiyas"); // Replace with your database username
define("DB_PASSWORD", "Taiba@eye2024"); // Replace with your database password
define("DB_NAME", "u414795990_qiyas"); // Replace with your database name

// Admin credentials (for simplicity, hardcoded for now. In a real app, use a proper user management system)
define("ADMIN_USERNAME", "admin");
define("ADMIN_PASSWORD", "password"); // Change this to a strong password

// Dynamically determine BASE_URL
$protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
$host = $_SERVER["HTTP_HOST"];
$script_name = $_SERVER["SCRIPT_NAME"];
$script_dir = dirname($script_name);

// If the script is in the root of the domain, $script_dir will be "/" or "\".
// If it's in a subdirectory, it will be like "/survey_system" or "/reeda2".
// We need to ensure it ends without a slash for consistent concatenation.
$base_url_dynamic = $protocol . "://" . $host . ($script_dir === '/' || $script_dir === '\\' ? '' : $script_dir);

define("BASE_URL", $base_url_dynamic);

// Default settings (can be overridden by database settings)
define("DEFAULT_FONT_FAMILY", "Tajawal, sans-serif");
define("DEFAULT_PRIMARY_COLOR", "#1a535c");
define("DEFAULT_SECONDARY_COLOR", "#f7b538");

// Pagination settings
define("DEFAULT_ENABLE_PAGINATION", true);
define("DEFAULT_QUESTIONS_PER_PAGE", 5);

// Other constants
define("SITE_NAME_DEFAULT", "جمعية عيون طيبة الخيرية");
define("SYSTEM_NAME_DEFAULT", "نظام قياس رضا المستفيدين");
?>


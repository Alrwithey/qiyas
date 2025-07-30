
<?php
require_once 'config.php';

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Function to get site settings from the database
function get_settings($conn) {
    $sql = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        // Return default values if no settings are found in the database
        return [
            'site_name' => SITE_NAME_DEFAULT,
            'system_name' => SYSTEM_NAME_DEFAULT,
            'logo_path' => null,
            'font_family' => DEFAULT_FONT_FAMILY,
            'primary_color' => DEFAULT_PRIMARY_COLOR,
            'secondary_color' => DEFAULT_SECONDARY_COLOR,
            'enable_pagination' => DEFAULT_ENABLE_PAGINATION,
            'questions_per_page' => DEFAULT_QUESTIONS_PER_PAGE
        ];
    }
}

$settings = get_settings($conn);
?>


<?php
// الكود الكامل والنهائي
require_once __DIR__ . '/db.php';

// --- Authentication Functions ---
function is_logged_in() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

function require_login() {
    if (!is_logged_in()) {
        header("location: login.php");
        exit;
    }
}

// ... (All other functions from the original file remain exactly the same) ...
// ... (get_all_questions, get_question_by_id, etc. are all correct) ...

function get_all_questions($conn) {
    $sql = "SELECT * FROM questions ORDER BY question_order ASC";
    $result = $conn->query($sql);
    $questions = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
    }
    return $questions;
}

function get_question_by_id($conn, $id) {
    $sql = "SELECT * FROM questions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_question_options($conn, $question_id) {
    $sql = "SELECT * FROM question_options WHERE question_id = ? ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $options = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row;
        }
    }
    return $options;
}

function get_question_id_by_text($conn, $question_text) {
    $sql = "SELECT id FROM questions WHERE question_text = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $question_text);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row["id"];
    }
    return null;
}

function add_question($conn, $question_text, $question_type, $is_required, $options = []) {
    // Get the next question order
    $sql_order = "SELECT MAX(question_order) AS max_order FROM questions";
    $result_order = $conn->query($sql_order);
    $row_order = $result_order->fetch_assoc();
    $next_order = ($row_order["max_order"] !== null) ? $row_order["max_order"] + 1 : 1;

    $sql = "INSERT INTO questions (question_text, question_type, is_required, question_order) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $question_text, $question_type, $is_required, $next_order);
    if ($stmt->execute()) {
        $question_id = $stmt->insert_id;
        if (!empty($options) && ($question_type == 'single_choice' || $question_type == 'multiple_choice' || $question_type == 'dropdown')) {
            foreach ($options as $option_text) {
                if(!empty(trim($option_text))) {
                    add_question_option($conn, $question_id, $option_text);
                }
            }
        }
        return true;
    }
    return false;
}

function update_question($conn, $id, $question_text, $question_type, $is_required) {
    $sql = "UPDATE questions SET question_text = ?, question_type = ?, is_required = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $question_text, $question_type, $is_required, $id);
    return $stmt->execute();
}

function delete_question($conn, $id) {
    $sql = "DELETE FROM questions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function add_question_option($conn, $question_id, $option_text) {
    $sql = "INSERT INTO question_options (question_id, option_text) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $question_id, $option_text);
    return $stmt->execute();
}

function delete_question_option($conn, $option_id) {
    $sql = "DELETE FROM question_options WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $option_id);
    return $stmt->execute();
}

function get_all_programs($conn) {
    $sql = "SELECT * FROM programs ORDER BY program_name ASC";
    $result = $conn->query($sql);
    $programs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
    }
    return $programs;
}

function add_program($conn, $program_name) {
    $sql = "INSERT INTO programs (program_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $program_name);
    return $stmt->execute();
}

function update_program($conn, $id, $program_name) {
    $sql = "UPDATE programs SET program_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $program_name, $id);
    return $stmt->execute();
}

function delete_program($conn, $id) {
    $sql = "DELETE FROM programs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function save_survey_response($conn, $data) {
    $conn->begin_transaction();
    try {
        $sql_response = "INSERT INTO survey_responses (beneficiary_name, phone_number, gender, program_id, suggestions) VALUES (?, ?, ?, ?, ?)";
        $stmt_response = $conn->prepare($sql_response);
        $stmt_response->bind_param("sssis", $data["beneficiary_name"], $data["phone_number"], $data["gender"], $data["program_id"], $data["suggestions"]);
        $stmt_response->execute();
        $response_id = $stmt_response->insert_id;

        foreach ($data["answers"] as $question_id => $answer) {
            $question_info = get_question_by_id($conn, $question_id);
            if (!$question_info) continue;

            $answer_text = null;
            $rating = null;
            $multiple_choice_options = [];

            if ($question_info["question_type"] == "rating") {
                $rating = $answer;
            } elseif ($question_info["question_type"] == "text" || $question_info["question_type"] == "single_choice" || $question_info["question_type"] == "dropdown") {
                $answer_text = $answer;
            } elseif ($question_info["question_type"] == "multiple_choice") {
                $multiple_choice_options = $answer;
            }

            $sql_answer = "INSERT INTO survey_answers (response_id, question_id, answer_text, rating) VALUES (?, ?, ?, ?)";
            $stmt_answer = $conn->prepare($sql_answer);
            $stmt_answer->bind_param("iisi", $response_id, $question_id, $answer_text, $rating);
            $stmt_answer->execute();
            $survey_answer_id = $stmt_answer->insert_id;

            if ($question_info["question_type"] == "multiple_choice" && !empty($multiple_choice_options)) {
                foreach ($multiple_choice_options as $option_id) {
                    $sql_mc = "INSERT INTO survey_multiple_choice_answers (survey_answer_id, option_id) VALUES (?, ?)";
                    $stmt_mc = $conn->prepare($sql_mc);
                    $stmt_mc->bind_param("ii", $survey_answer_id, $option_id);
                    $stmt_mc->execute();
                }
            }
        }

        $conn->commit();
        return true;
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        error_log("Error saving survey response: " . $exception->getMessage());
        return false;
    }
}

// ... (Other functions like get_all_survey_responses are correct) ...
function get_all_survey_responses($conn) {
    $sql = "SELECT sr.id, sr.beneficiary_name, sr.phone_number, sr.gender, p.program_name, sr.submission_date, sr.suggestions ";
    $sql .= "FROM survey_responses sr LEFT JOIN programs p ON sr.program_id = p.id ORDER BY sr.submission_date DESC";
    $result = $conn->query($sql);
    $responses = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $responses[] = $row;
        }
    }
    return $responses;
}

// --- Settings Functions (Corrected) ---
function update_settings($conn, $data) {
    $sql = "UPDATE settings SET site_name = ?, system_name = ?, logo_path = ?, primary_font_url = ?, primary_font_name = ?, primary_color = ?, secondary_color = ? WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $data['site_name'], $data['system_name'], $data['logo_path'], $data['primary_font_url'], $data['primary_font_name'], $data['primary_color'], $data['secondary_color']);
    return $stmt->execute();
}

function get_latest_settings($conn) {
    $sql = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return [
        "site_name" => "نظام الاستبيانات", "system_name" => "لوحة التحكم", "logo_path" => "",
        "primary_font_url" => "https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap",
        "primary_font_name" => "Tajawal", "primary_color" => "#1a535c", "secondary_color" => "#f7b538",
    ];
}

// --- Helper Functions (Correct) ---
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    if ($path === '/' || $path === '\\') {
        $path = '';
    }
    $base_url = $protocol . $domainName . $path;
    return rtrim($base_url, '/') . '/';
}

function get_admin_header($settings) {
    $logo_html = "";
    if (!empty($settings["logo_path"])) {
        $logo_html = "<img src=\"" . get_base_url() . $settings["logo_path"] . "\" alt=\"" . $settings["site_name"] . " Logo\" style=\"max-height: 50px; margin-bottom: 10px;\">";
    } else {
        $logo_html = "<h3 class=\"site-name2\">" . $settings["site_name"] . "</h3>";
    }
    return "<div class=\"sidebar-header\">" . $logo_html . "<h4 class=\"system-title\">" . $settings["system_name"] . "</h4></div><div class=\"sidebar-separator\"></div><ul><li><a href=\"dashboard.php\"><i class=\"fas fa-tachometer-alt\"></i> <span>لوحة التحكم</span></a></li><li><a href=\"questions.php\"><i class=\"fas fa-question-circle\"></i> <span>إدارة الأسئلة</span></a></li><li><a href=\"programs.php\"><i class=\"fas fa-list-alt\"></i> <span>إدارة البرامج</span></a></li><li><a href=\"survey_results.php\"><i class=\"fas fa-poll-h\"></i> <span>نتائج الاستبيانات</span></a></li><li><a href=\"settings.php\"><i class=\"fas fa-cogs\"></i> <span>الإعدادات</span></a></li><li><a href=\"logout.php\" class=\"logout-btn\"><i class=\"fas fa-sign-out-alt\"></i> <span>تسجيل الخروج</span></a></li></ul>";
}

function get_admin_footer() {
    return "<script src=\"https://cdn.jsdelivr.net/npm/chart.js\"></script><script src=\"https://cdn.jsdelivr.net/npm/sweetalert2@11\"></script><script>document.addEventListener(\"DOMContentLoaded\",function(){const e=document.querySelector(\".menu-toggle\"),t=document.querySelector(\".sidebar\");e&&t.addEventListener(\"click\",function(){t.classList.toggle(\"active\")})});</script>";
}

// ==============================================================
// ===== THE FINAL CORRECTED HEAD FUNCTIONS (ONLY THIS PART MATTERS) =====
// ==============================================================

function get_admin_head($settings, $page_title = "لوحة التحكم") {
    $font_name = $settings['primary_font_name'] ?? 'Tajawal';
    $font_url = $settings['primary_font_url'] ?? '';
    
    $head = "<head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>" . htmlspecialchars($page_title) . " - " . htmlspecialchars($settings["system_name"]) . "</title>
        <link rel=\"stylesheet\" href=\"../css/style.css\">
        <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">";

    if (!empty($font_url)) {
        $head .= "<link href=\"" . htmlspecialchars($font_url) . "\" rel=\"stylesheet\">";
    }

    $head .= "<style>
            body { font-family: '" . htmlspecialchars($font_name) . "', sans-serif; }
            :root {
                --primary-color: " . htmlspecialchars($settings["primary_color"] ?? '#1a535c') . ";
                --secondary-color: " . htmlspecialchars($settings["secondary_color"] ?? '#f7b538') . ";
            }
        </style>
    </head>";
    return $head;
}

function get_survey_head($settings) {
    $font_name = $settings['primary_font_name'] ?? 'Tajawal';
    $font_url = $settings['primary_font_url'] ?? '';

    $head = "<head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>استبيان رضا المستفيدين - " . htmlspecialchars($settings["site_name"]) . "</title>
        <link rel=\"stylesheet\" href=\"css/style.css\">
        <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">";

    if (!empty($font_url)) {
        $head .= "<link href=\"" . htmlspecialchars($font_url) . "\" rel=\"stylesheet\">";
    }

    $head .= "<style>
            body { font-family: '" . htmlspecialchars($font_name) . "', sans-serif; }
            :root {
                --primary-color: " . htmlspecialchars($settings["primary_color"] ?? '#1a535c') . ";
                --secondary-color: " . htmlspecialchars($settings["secondary_color"] ?? '#f7b538') . ";
            }
        </style>
    </head>";
    return $head;
}

// =======================================================

function get_survey_footer() {
    return "<script src=\"https://cdn.jsdelivr.net/npm/sweetalert2@11\"></script><script>// Add any specific survey page JS here if needed</script>";
}

?>
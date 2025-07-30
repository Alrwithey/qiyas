<?php
// الكود الكامل والنهائي. مطابق للكود الأصلي مع تصحيح سطر واحد فقط.
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

// --- Question Management Functions ---
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

// --- Program Functions ---
function get_all_programs($conn) {
    $sql = "SELECT * FROM programs ORDER BY program_order ASC";
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
    $sql_order = "SELECT MAX(program_order) as max_order FROM programs";
    $result_order = $conn->query($sql_order);
    $next_order = $result_order->fetch_assoc()['max_order'] + 1;
    $sql = "INSERT INTO programs (program_name, program_order) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $program_name, $next_order);
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

function update_programs_order($conn, $program_ids_array) {
    $order = 1;
    $sql = "UPDATE programs SET program_order = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if(!$stmt) return false;
    foreach ($program_ids_array as $id) {
        $stmt->bind_param('ii', $order, $id);
        $stmt->execute();
        $order++;
    }
    $stmt->close();
    return true;
}

// --- Survey Response Functions ---
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
            $answer_text = null; $rating = null; $multiple_choice_options = [];
            if ($question_info["question_type"] == "rating") { $rating = $answer;
            } elseif (in_array($question_info["question_type"], ["text", "single_choice", "dropdown"])) { $answer_text = $answer;
            } elseif ($question_info["question_type"] == "multiple_choice") { $multiple_choice_options = $answer; }
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

// This function from your original code was missing in my previous response. I am re-adding it.
function get_survey_response_details($conn, $response_id) {
    $sql = "SELECT sr.id, sr.beneficiary_name, sr.phone_number, sr.gender, p.program_name, sr.submission_date, sr.suggestions ";
    $sql .= "FROM survey_responses sr LEFT JOIN programs p ON sr.program_id = p.id WHERE sr.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $response_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $response_details = $result->fetch_assoc();

    if ($response_details) {
        $sql_answers = "SELECT q.question_text, q.question_type, sa.answer_text, sa.rating ";
        $sql_answers .= "FROM survey_answers sa JOIN questions q ON sa.question_id = q.id WHERE sa.response_id = ?";
        $stmt_answers = $conn->prepare($sql_answers);
        $stmt_answers->bind_param("i", $response_id);
        $stmt_answers->execute();
        $result_answers = $stmt_answers->get_result();
        $answers = [];
        while ($row_answer = $result_answers->fetch_assoc()) {
            if ($row_answer["question_type"] == "multiple_choice") {
                $sql_mc_options = "SELECT qo.option_text FROM survey_multiple_choice_answers smca JOIN question_options qo ON smca.option_id = qo.id WHERE smca.survey_answer_id = (SELECT id FROM survey_answers WHERE response_id = ? AND question_id = (SELECT id FROM questions WHERE question_text = ?)) ";
                $stmt_mc_options = $conn->prepare($sql_mc_options);
                $stmt_mc_options->bind_param("is", $response_id, $row_answer["question_text"]);
                $stmt_mc_options->execute();
                $result_mc_options = $stmt_mc_options->get_result();
                $mc_options = [];
                while ($mc_row = $result_mc_options->fetch_assoc()) {
                    $mc_options[] = $mc_row["option_text"];
                }
                $row_answer["answer_text"] = implode(", ", $mc_options);
            }
            $answers[] = $row_answer;
        }
        $response_details["answers"] = $answers;
    }
    return $response_details;
}

// This function was also missing.
function delete_survey_response($conn, $response_id) {
    $conn->begin_transaction();
    try {
        $sql_mc = "DELETE smca FROM survey_multiple_choice_answers smca JOIN survey_answers sa ON smca.survey_answer_id = sa.id WHERE sa.response_id = ?";
        $stmt_mc = $conn->prepare($sql_mc);
        $stmt_mc->bind_param("i", $response_id);
        $stmt_mc->execute();
        $sql_answers = "DELETE FROM survey_answers WHERE response_id = ?";
        $stmt_answers = $conn->prepare($sql_answers);
        $stmt_answers->bind_param("i", $response_id);
        $stmt_answers->execute();
        $sql_response = "DELETE FROM survey_responses WHERE id = ?";
        $stmt_response = $conn->prepare($sql_response);
        $stmt_response->bind_param("i", $response_id);
        $stmt_response->execute();
        $conn->commit();
        return true;
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        error_log("Error deleting survey response: " . $exception->getMessage());
        return false;
    }
}

// --- Settings Functions ---
function update_settings($conn, $data) {
    $sql = "UPDATE settings SET site_name = ?, system_name = ?, logo_path = ?, primary_font_url = ?, primary_font_name = ?, primary_color = ?, secondary_color = ? WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $data['site_name'], $data['system_name'], $data['logo_path'], $data['primary_font_url'], $data['primary_font_name'], $data['primary_color'], $data['secondary_color']);
    return $stmt->execute();
}
function get_latest_settings($conn) {
    $sql = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) { return $result->fetch_assoc(); }
    return ["site_name" => "نظام الاستبيانات", "system_name" => "لوحة التحكم", "logo_path" => "", "primary_font_url" => "https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap", "primary_font_name" => "Tajawal", "primary_color" => "#1a535c", "secondary_color" => "#f7b538"];
}

// --- Helper Functions ---
function sanitize_input($data) { return htmlspecialchars(stripslashes(trim($data))); }
function redirect($url) { header("Location: " . $url); exit(); }

// =========================================================================
// ===== START OF MODIFIED SECTION (The only part that needs changing) =====
// =========================================================================

function get_admin_header($settings) {
    $logo_html = "";
    if (!empty($settings["logo_path"])) {
        // The CORRECTED line: using a simple relative path.
        $logo_path = "../" . htmlspecialchars($settings["logo_path"]);
        $logo_html = "<img src=\"{$logo_path}\" alt=\"" . htmlspecialchars($settings["site_name"]) . " Logo\" style=\"max-height: 50px; margin-bottom: 10px;\">";
    } else {
        $logo_html = "<h3 class=\"site-name2\">" . htmlspecialchars($settings["site_name"]) . "</h3>";
    }
    
    $current_page = basename($_SERVER['PHP_SELF']);
    $menu_items = [
        'dashboard.php' => ['icon' => 'fa-tachometer-alt', 'text' => 'لوحة التحكم'],
        'questions.php' => ['icon' => 'fa-question-circle', 'text' => 'إدارة الأسئلة'],
        'programs.php' => ['icon' => 'fa-list-alt', 'text' => 'إدارة البرامج'],
        'survey_results.php' => ['icon' => 'fa-poll-h', 'text' => 'نتائج الاستبيانات'],
        'settings.php' => ['icon' => 'fa-cogs', 'text' => 'الإعدادات'],
    ];

    $menu_html = "<ul>";
    foreach ($menu_items as $url => $item) {
        $active_class = ($current_page == $url) ? 'active' : '';
        $menu_html .= "<li><a href=\"{$url}\" class=\"{$active_class}\"><i class=\"fas {$item['icon']}\"></i> <span>{$item['text']}</span></a></li>";
    }
    $menu_html .= "<li><a href=\"logout.php\" class=\"logout-btn\"><i class=\"fas fa-sign-out-alt\"></i> <span>تسجيل الخروج</span></a></li>";
    $menu_html .= "</ul>";

    return "<div class=\"sidebar-header\">{$logo_html}<h4 class=\"system-title\">" . htmlspecialchars($settings["system_name"]) . "</h4></div><div class=\"sidebar-separator\"></div>{$menu_html}";
}

function get_admin_head($settings, $page_title = "لوحة التحكم") {
    // This function returns to the original version, which is correct.
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
    // This function from the original code is correct.
    $font_name = $settings['primary_font_name'] ?? 'Tajawal';
    $font_url = $settings['primary_font_url'] ?? '';

    $head = "<head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>استبيان رضا المستفيدين - " . htmlspecialchars($settings["site_name"]) . "</title>
        <link rel=\"stylesheet\" href=\"css/index_style.css\">
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

function get_admin_footer() { 
    return "<script src=\"https://cdn.jsdelivr.net/npm/chart.js\"></script><script src=\"https://cdn.jsdelivr.net/npm/sweetalert2@11\"></script><script>document.addEventListener(\"DOMContentLoaded\",function(){const e=document.querySelector(\".menu-toggle\"),t=document.querySelector(\".sidebar\");e&&t.addEventListener(\"click\",function(){t.classList.toggle(\"active\")})});</script>";
}

function get_survey_footer() { 
    return ""; 
}
?>
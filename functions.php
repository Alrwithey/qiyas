<?php
require_once 'db.php';




// دوال إدارة أنظمة القياس
function createSurveySystem($data) {
    global $db;
    
    // التحقق من عدم تكرار الـ slug
    $existingSystem = $db->fetchOne("SELECT id FROM survey_systems WHERE system_slug = ?", [$data['system_slug']]);
    if ($existingSystem) {
        throw new Exception("الرابط المختصر مستخدم مسبقاً");
    }
    
    return $db->insert('survey_systems', $data);
}

function updateSurveySystem($id, $data) {
    global $db;
    
    // التحقق من عدم تكرار الـ slug (باستثناء النظام الحالي)
    if (isset($data['system_slug'])) {
        $existingSystem = $db->fetchOne("SELECT id FROM survey_systems WHERE system_slug = ? AND id != ?", [$data['system_slug'], $id]);
        if ($existingSystem) {
            throw new Exception("الرابط المختصر مستخدم مسبقاً");
        }
    }
    
    return $db->update('survey_systems', $data, 'id = ?', [$id]);
}

function deleteSurveySystem($id) {
    global $db;
    return $db->delete('survey_systems', 'id = ?', [$id]);
}

function getSurveySystem($id) {
    global $db;
    return $db->fetchOne("SELECT * FROM survey_systems WHERE id = ?", [$id]);
}

function getSurveySystemBySlug($slug) {
    global $db;
    return $db->fetchOne("SELECT * FROM survey_systems WHERE system_slug = ? AND is_active = 1", [$slug]);
}

function getAllSurveySystems() {
    global $db;
    return $db->fetchAll("SELECT * FROM survey_systems ORDER BY created_date DESC");
}

function getActiveSurveySystems() {
    global $db;
    return $db->fetchAll("SELECT * FROM survey_systems WHERE is_active = 1 ORDER BY created_date DESC");
}

// دوال إدارة البرامج
function createProgram($surveySystemId, $data) {
    global $db;
    $data['survey_system_id'] = $surveySystemId;
    return $db->insert('programs', $data);
}

function updateProgram($id, $data) {
    global $db;
    return $db->update('programs', $data, 'id = ?', [$id]);
}

function deleteProgram($id) {
    global $db;
    return $db->delete('programs', 'id = ?', [$id]);
}

function getProgram($id) {
    global $db;
    return $db->fetchOne("SELECT * FROM programs WHERE id = ?", [$id]);
}

function getProgramsBySurveySystem($surveySystemId) {
    global $db;
    return $db->fetchAll("SELECT * FROM programs WHERE survey_system_id = ? ORDER BY program_order ASC", [$surveySystemId]);
}

// دوال إدارة الأسئلة
function createQuestion($surveySystemId, $data) {
    global $db;
    $data['survey_system_id'] = $surveySystemId;
    return $db->insert('questions', $data);
}

function updateQuestion($id, $data) {
    global $db;
    return $db->update('questions', $data, 'id = ?', [$id]);
}

function deleteQuestion($id) {
    global $db;
    return $db->delete('questions', 'id = ?', [$id]);
}

function getQuestion($id) {
    global $db;
    return $db->fetchOne("SELECT * FROM questions WHERE id = ?", [$id]);
}

function getQuestionsBySurveySystem($surveySystemId) {
    global $db;
    return $db->fetchAll("SELECT * FROM questions WHERE survey_system_id = ? ORDER BY question_order ASC", [$surveySystemId]);
}

// دوال إدارة خيارات الأسئلة
function createQuestionOption($questionId, $optionText) {
    global $db;
    return $db->insert('question_options', [
        'question_id' => $questionId,
        'option_text' => $optionText
    ]);
}

function updateQuestionOption($id, $optionText) {
    global $db;
    return $db->update('question_options', ['option_text' => $optionText], 'id = ?', [$id]);
}

function deleteQuestionOption($id) {
    global $db;
    return $db->delete('question_options', 'id = ?', [$id]);
}

function getQuestionOptions($questionId) {
    global $db;
    return $db->fetchAll("SELECT * FROM question_options WHERE question_id = ? ORDER BY id ASC", [$questionId]);
}

// دوال إدارة الردود
function createSurveyResponse($surveySystemId, $data) {
    global $db;
    $data['survey_system_id'] = $surveySystemId;
    return $db->insert('survey_responses', $data);
}

function getSurveyResponse($id) {
    global $db;
    return $db->fetchOne("SELECT * FROM survey_responses WHERE id = ?", [$id]);
}

function getSurveyResponsesBySurveySystem($surveySystemId, $limit = null, $offset = null) {
    global $db;
    $sql = "SELECT sr.*, p.program_name 
            FROM survey_responses sr 
            LEFT JOIN programs p ON sr.program_id = p.id 
            WHERE sr.survey_system_id = ? 
            ORDER BY sr.submission_date DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT " . intval($limit);
        if ($offset !== null) {
            $sql .= " OFFSET " . intval($offset);
        }
    }
    
    return $db->fetchAll($sql, [$surveySystemId]);
}

function countSurveyResponsesBySurveySystem($surveySystemId) {
    global $db;
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM survey_responses WHERE survey_system_id = ?", [$surveySystemId]);
    return $result['count'];
}

// دوال إدارة إجابات الأسئلة
function createSurveyAnswer($responseId, $questionId, $answerText = null, $rating = null) {
    global $db;
    return $db->insert('survey_answers', [
        'response_id' => $responseId,
        'question_id' => $questionId,
        'answer_text' => $answerText,
        'rating' => $rating
    ]);
}

function getSurveyAnswersByResponse($responseId) {
    global $db;
    return $db->fetchAll("
        SELECT sa.*, q.question_text, q.question_type 
        FROM survey_answers sa 
        JOIN questions q ON sa.question_id = q.id 
        WHERE sa.response_id = ? 
        ORDER BY q.question_order ASC
    ", [$responseId]);
}

// دوال الإحصائيات
function getSurveySystemStats($surveySystemId) {
    global $db;
    
    $stats = [];
    
    // عدد الردود الكلي
    $totalResponses = $db->fetchOne("SELECT COUNT(*) as count FROM survey_responses WHERE survey_system_id = ?", [$surveySystemId]);
    $stats['total_responses'] = $totalResponses['count'];
    
    // عدد الردود اليوم
    $todayResponses = $db->fetchOne("SELECT COUNT(*) as count FROM survey_responses WHERE survey_system_id = ? AND DATE(submission_date) = CURDATE()", [$surveySystemId]);
    $stats['today_responses'] = $todayResponses['count'];
    
    // عدد الردود هذا الشهر
    $monthResponses = $db->fetchOne("SELECT COUNT(*) as count FROM survey_responses WHERE survey_system_id = ? AND MONTH(submission_date) = MONTH(CURDATE()) AND YEAR(submission_date) = YEAR(CURDATE())", [$surveySystemId]);
    $stats['month_responses'] = $monthResponses['count'];
    
    // توزيع الردود حسب الجنس
    $genderStats = $db->fetchAll("SELECT gender, COUNT(*) as count FROM survey_responses WHERE survey_system_id = ? AND gender IS NOT NULL GROUP BY gender", [$surveySystemId]);
    $stats['gender_distribution'] = $genderStats;
    
    // توزيع الردود حسب البرنامج
    $programStats = $db->fetchAll("
        SELECT p.program_name, COUNT(sr.id) as count 
        FROM programs p 
        LEFT JOIN survey_responses sr ON p.id = sr.program_id AND sr.survey_system_id = ?
        WHERE p.survey_system_id = ?
        GROUP BY p.id, p.program_name 
        ORDER BY count DESC
    ", [$surveySystemId, $surveySystemId]);
    $stats['program_distribution'] = $programStats;
    
    // متوسط التقييمات
    $ratingStats = $db->fetchAll("
        SELECT q.question_text, AVG(sa.rating) as avg_rating, COUNT(sa.rating) as rating_count
        FROM questions q 
        LEFT JOIN survey_answers sa ON q.id = sa.question_id 
        LEFT JOIN survey_responses sr ON sa.response_id = sr.id 
        WHERE q.survey_system_id = ? AND q.question_type = 'rating' AND sr.survey_system_id = ?
        GROUP BY q.id, q.question_text 
        ORDER BY q.question_order ASC
    ", [$surveySystemId, $surveySystemId]);
    $stats['rating_averages'] = $ratingStats;
    
    return $stats;
}

// دوال المساعدة
function generateSlug($text) {
    // تحويل النص العربي إلى slug
    $text = trim($text);
    $text = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s-]/u', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    
    // إذا كان النص فارغاً بعد التنظيف، استخدم timestamp
    if (empty($text)) {
        $text = 'survey-' . time();
    }
    
    return strtolower($text);
}

function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception("لم يتم رفع الملف بشكل صحيح");
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception("حجم الملف كبير جداً");
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedTypes)) {
        throw new Exception("نوع الملف غير مسموح");
    }
    
    $fileName = uniqid() . '.' . $fileExtension;
    $uploadPath = UPLOAD_PATH . $fileName;
    
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception("فشل في رفع الملف");
    }
    
    return $uploadPath;
}

function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}

// دوال الأمان
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// دوال إدارة المديرين
function createAdmin($data) {
    global $db;
    
    // التحقق من عدم تكرار اسم المستخدم
    $existingAdmin = $db->fetchOne("SELECT id FROM admin_users WHERE username = ?", [$data['username']]);
    if ($existingAdmin) {
        throw new Exception("اسم المستخدم مستخدم مسبقاً");
    }
    
    // تشفير كلمة المرور
    $data['password'] = hashPassword($data['password']);
    
    return $db->insert('admin_users', $data);
}

function updateAdmin($id, $data) {
    global $db;
    
    // التحقق من عدم تكرار اسم المستخدم (باستثناء المدير الحالي)
    if (isset($data['username'])) {
        $existingAdmin = $db->fetchOne("SELECT id FROM admin_users WHERE username = ? AND id != ?", [$data['username'], $id]);
        if ($existingAdmin) {
            throw new Exception("اسم المستخدم مستخدم مسبقاً");
        }
    }
    
    // تشفير كلمة المرور إذا تم تحديثها
    if (isset($data['password']) && !empty($data['password'])) {
        $data['password'] = hashPassword($data['password']);
    } else {
        unset($data['password']);
    }
    
    return $db->update('admin_users', $data, 'id = ?', [$id]);
}

function deleteAdmin($id) {
    global $db;
    return $db->delete('admin_users', 'id = ?', [$id]);
}

function getAdmin($id) {
    global $db;
    return $db->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$id]);
}

function getAdminByUsername($username) {
    global $db;
    return $db->fetchOne("SELECT * FROM admin_users WHERE username = ?", [$username]);
}

function getAllAdmins() {
    global $db;
    return $db->fetchAll("SELECT id, username, email, full_name, is_super_admin, created_date, last_login FROM admin_users ORDER BY created_date DESC");
}

function authenticateAdmin($username, $password) {
    $admin = getAdminByUsername($username);
    
    if ($admin && verifyPassword($password, $admin['password'])) {
        // تحديث وقت آخر تسجيل دخول
        global $db;
        $db->update('admin_users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$admin['id']]);
        
        // حفظ بيانات الجلسة
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['is_super_admin'] = $admin['is_super_admin'];
        
        return $admin;
    }
    
    return false;
}

// دوال إدارة صلاحيات الأنظمة
function grantSystemAccess($adminId, $surveySystemId, $permissions = []) {
    global $db;
    
    $data = [
        'admin_id' => $adminId,
        'survey_system_id' => $surveySystemId,
        'can_view' => isset($permissions['can_view']) ? $permissions['can_view'] : 1,
        'can_edit' => isset($permissions['can_edit']) ? $permissions['can_edit'] : 0,
        'can_delete' => isset($permissions['can_delete']) ? $permissions['can_delete'] : 0
    ];
    
    // التحقق من وجود صلاحية مسبقة
    $existingAccess = $db->fetchOne("SELECT id FROM admin_system_access WHERE admin_id = ? AND survey_system_id = ?", [$adminId, $surveySystemId]);
    
    if ($existingAccess) {
        return $db->update('admin_system_access', $data, 'admin_id = ? AND survey_system_id = ?', [$adminId, $surveySystemId]);
    } else {
        return $db->insert('admin_system_access', $data);
    }
}

function revokeSystemAccess($adminId, $surveySystemId) {
    global $db;
    return $db->delete('admin_system_access', 'admin_id = ? AND survey_system_id = ?', [$adminId, $surveySystemId]);
}

function getAdminSystemAccess($adminId) {
    global $db;
    return $db->fetchAll("
        SELECT asa.*, ss.system_name, ss.system_slug 
        FROM admin_system_access asa 
        JOIN survey_systems ss ON asa.survey_system_id = ss.id 
        WHERE asa.admin_id = ?
    ", [$adminId]);
}

function hasSystemAccess($adminId, $surveySystemId, $permission = 'can_view') {
    global $db;
    
    // المدير العام له صلاحية على جميع الأنظمة
    $admin = getAdmin($adminId);
    if ($admin && $admin['is_super_admin']) {
        return true;
    }
    
    $access = $db->fetchOne("SELECT {$permission} FROM admin_system_access WHERE admin_id = ? AND survey_system_id = ?", [$adminId, $surveySystemId]);
    
    return $access && $access[$permission];
}

// دوال الإعدادات
function getSetting($key, $default = null) {
    global $db;
    $setting = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $setting ? $setting['setting_value'] : $default;
}

function setSetting($key, $value, $description = null) {
    global $db;
    
    $existingSetting = $db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    
    if ($existingSetting) {
        $data = ['setting_value' => $value];
        if ($description !== null) {
            $data['description'] = $description;
        }
        return $db->update('settings', $data, 'setting_key = ?', [$key]);
    } else {
        return $db->insert('settings', [
            'setting_key' => $key,
            'setting_value' => $value,
            'description' => $description
        ]);
    }
}

function getAllSettings() {
    global $db;
    return $db->fetchAll("SELECT * FROM settings ORDER BY setting_key ASC");
}
?>


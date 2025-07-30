<?php
require_once '../config.php';
require_once '../functions.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $systemName = trim($_POST['system_name']);
        $systemSlug = trim($_POST['system_slug']);
        $description = trim($_POST['description']);
        $primaryColor = trim($_POST['primary_color']);
        $secondaryColor = trim($_POST['secondary_color']);
        $fontFamily = trim($_POST['font_family']);
        $fontUrl = trim($_POST['font_url']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // التحقق من البيانات المطلوبة
        if (empty($systemName)) {
            throw new Exception('اسم النظام مطلوب');
        }
        
        if (empty($systemSlug)) {
            $systemSlug = generateSlug($systemName);
        }
        
        // معالجة رفع الشعار
        $logoPath = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            $logoPath = uploadFile($_FILES['logo'], ['jpg', 'jpeg', 'png', 'gif']);
        }
        
        $data = [
            'system_name' => $systemName,
            'system_slug' => $systemSlug,
            'description' => $description,
            'logo_path' => $logoPath,
            'primary_color' => $primaryColor ?: DEFAULT_PRIMARY_COLOR,
            'secondary_color' => $secondaryColor ?: DEFAULT_SECONDARY_COLOR,
            'font_family' => $fontFamily ?: DEFAULT_FONT_FAMILY,
            'font_url' => $fontUrl ?: DEFAULT_FONT_URL,
            'is_active' => $isActive
        ];
        
        $systemId = createSurveySystem($data);
        
        // إنشاء أسئلة افتراضية
        $defaultQuestions = [
            ['question_text' => 'اسم المستفيد', 'question_type' => 'text', 'is_required' => 0, 'question_order' => 1],
            ['question_text' => 'رقم الجوال', 'question_type' => 'text', 'is_required' => 0, 'question_order' => 2],
            ['question_text' => 'الجنس', 'question_type' => 'single_choice', 'is_required' => 1, 'question_order' => 3],
            ['question_text' => 'مستوى الرضا العام عن الخدمات المقدمة', 'question_type' => 'rating', 'is_required' => 1, 'question_order' => 4],
            ['question_text' => 'اقتراحات وملاحظات لتحسين الخدمة', 'question_type' => 'text', 'is_required' => 0, 'question_order' => 5]
        ];
        
        foreach ($defaultQuestions as $questionData) {
            $questionId = createQuestion($systemId, $questionData);
            
            // إضافة خيارات للأسئلة التي تحتاج خيارات
            if ($questionData['question_text'] == 'الجنس') {
                createQuestionOption($questionId, 'ذكر');
                createQuestionOption($questionId, 'أنثى');
            }
        }
        
        $success = 'تم إنشاء النظام بنجاح';
        
        // إعادة توجيه بعد 2 ثانية
        header("refresh:2;url=system_dashboard.php?id={$systemId}");
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء نظام قياس جديد - <?php echo SITE_TITLE; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Almarai', sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .header {
            background: #1a535c;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .header-actions a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .form-header h2 {
            color: #1a535c;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1a535c;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .color-input {
            height: 50px !important;
            cursor: pointer;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-display {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border: 2px dashed #e1e5e9;
            border-radius: 8px;
            background: #f8f9fa;
            transition: border-color 0.3s;
        }
        
        .file-input-display:hover {
            border-color: #1a535c;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #1a535c;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2a6570;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-plus"></i> إنشاء نظام قياس جديد</h1>
            <div class="header-actions">
                <a href="dashboard.php"><i class="fas fa-arrow-right"></i> العودة للوحة التحكم</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2>إنشاء نظام قياس رضا جديد</h2>
                <p>املأ البيانات التالية لإنشاء نظام قياس رضا منفصل</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <br><small>سيتم توجيهك إلى لوحة تحكم النظام خلال ثوانٍ...</small>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="system_name">اسم النظام *</label>
                        <input type="text" id="system_name" name="system_name" required 
                               value="<?php echo htmlspecialchars($_POST['system_name'] ?? ''); ?>">
                        <div class="help-text">اسم النظام كما سيظهر للمستخدمين</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="system_slug">الرابط المختصر</label>
                        <input type="text" id="system_slug" name="system_slug" 
                               value="<?php echo htmlspecialchars($_POST['system_slug'] ?? ''); ?>"
                               pattern="[a-zA-Z0-9\-_]+" title="يسمح بالأحرف الإنجليزية والأرقام والشرطة فقط">
                        <div class="help-text">الرابط المختصر للنظام (اتركه فارغاً للإنشاء التلقائي)</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">وصف النظام</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <div class="help-text">وصف مختصر عن النظام وأهدافه</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="logo">شعار النظام</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="logo" name="logo" class="file-input" accept="image/*">
                            <div class="file-input-display">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>اختر ملف الشعار (اختياري)</span>
                            </div>
                        </div>
                        <div class="help-text">الحد الأقصى: 5 ميجابايت - الأنواع المسموحة: JPG, PNG, GIF</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="primary_color">اللون الأساسي</label>
                        <input type="color" id="primary_color" name="primary_color" class="color-input"
                               value="<?php echo htmlspecialchars($_POST['primary_color'] ?? DEFAULT_PRIMARY_COLOR); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="secondary_color">اللون الثانوي</label>
                        <input type="color" id="secondary_color" name="secondary_color" class="color-input"
                               value="<?php echo htmlspecialchars($_POST['secondary_color'] ?? DEFAULT_SECONDARY_COLOR); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="font_family">نوع الخط</label>
                        <select id="font_family" name="font_family">
                            <option value="Almarai" <?php echo ($_POST['font_family'] ?? DEFAULT_FONT_FAMILY) == 'Almarai' ? 'selected' : ''; ?>>Almarai</option>
                            <option value="Cairo" <?php echo ($_POST['font_family'] ?? '') == 'Cairo' ? 'selected' : ''; ?>>Cairo</option>
                            <option value="Tajawal" <?php echo ($_POST['font_family'] ?? '') == 'Tajawal' ? 'selected' : ''; ?>>Tajawal</option>
                            <option value="Amiri" <?php echo ($_POST['font_family'] ?? '') == 'Amiri' ? 'selected' : ''; ?>>Amiri</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="font_url">رابط الخط</label>
                        <input type="url" id="font_url" name="font_url" 
                               value="<?php echo htmlspecialchars($_POST['font_url'] ?? DEFAULT_FONT_URL); ?>">
                        <div class="help-text">رابط Google Fonts أو أي مصدر خط آخر</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" 
                                   <?php echo isset($_POST['is_active']) || !isset($_POST['system_name']) ? 'checked' : ''; ?>>
                            <label for="is_active">تفعيل النظام</label>
                        </div>
                        <div class="help-text">يمكن للمستخدمين الوصول للنظام فقط عندما يكون مفعلاً</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> إنشاء النظام
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // تحديث عرض اسم الملف المختار
        document.getElementById('logo').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'اختر ملف الشعار (اختياري)';
            document.querySelector('.file-input-display span').textContent = fileName;
        });
        
        // إنشاء slug تلقائياً من اسم النظام
        document.getElementById('system_name').addEventListener('input', function(e) {
            const slugInput = document.getElementById('system_slug');
            if (!slugInput.value) {
                let slug = e.target.value
                    .toLowerCase()
                    .replace(/[^a-zA-Z0-9\u0600-\u06FF\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .trim();
                slugInput.value = slug;
            }
        });
    </script>
</body>
</html>


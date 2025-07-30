<?php
require_once 'config.php';
require_once 'functions.php';

// الحصول على النظام من الرابط
$systemSlug = $_GET['system'] ?? '';

if (empty($systemSlug)) {
    die('رابط النظام غير صحيح');
}

$surveySystem = getSurveySystemBySlug($systemSlug);

if (!$surveySystem) {
    die('النظام غير موجود أو غير مفعل');
}

// الحصول على الأسئلة والبرامج
$questions = getQuestionsBySurveySystem($surveySystem['id']);
$programs = getProgramsBySurveySystem($surveySystem['id']);

// معالجة إرسال الاستبيان
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // إنشاء الرد
        $responseData = [
            'beneficiary_name' => trim($_POST['beneficiary_name'] ?? ''),
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'gender' => $_POST['gender'] ?? null,
            'program_id' => $_POST['program_id'] ?? null,
            'suggestions' => trim($_POST['suggestions'] ?? '')
        ];
        
        $responseId = createSurveyResponse($surveySystem['id'], $responseData);
        
        // حفظ إجابات الأسئلة
        foreach ($questions as $question) {
            $answerKey = 'question_' . $question['id'];
            $answerText = null;
            $rating = null;
            
            if ($question['question_type'] == 'rating') {
                $rating = $_POST[$answerKey] ?? null;
            } else {
                $answerText = $_POST[$answerKey] ?? null;
            }
            
            if ($answerText !== null || $rating !== null) {
                createSurveyAnswer($responseId, $question['id'], $answerText, $rating);
            }
        }
        
        $success = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// الحصول على خيارات الأسئلة
$questionOptions = [];
foreach ($questions as $question) {
    if (in_array($question['question_type'], ['single_choice', 'multiple_choice', 'dropdown'])) {
        $questionOptions[$question['id']] = getQuestionOptions($question['id']);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($surveySystem['system_name']); ?></title>
    
    <?php if ($surveySystem['font_url']): ?>
        <link href="<?php echo htmlspecialchars($surveySystem['font_url']); ?>" rel="stylesheet">
    <?php endif; ?>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>;
            --secondary-color: <?php echo htmlspecialchars($surveySystem['secondary_color']); ?>;
            --font-family: '<?php echo htmlspecialchars($surveySystem['font_family']); ?>', sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .logo {
            max-width: 150px;
            max-height: 100px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .survey-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        .success-message {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .success-message i {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .success-message h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .success-message p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #fcc;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 16px;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            font-family: var(--font-family);
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 83, 92, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .radio-group,
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .radio-option,
        .checkbox-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .radio-option:hover,
        .checkbox-option:hover {
            border-color: var(--primary-color);
            background: rgba(26, 83, 92, 0.05);
        }
        
        .radio-option input,
        .checkbox-option input {
            width: auto;
            margin: 0;
        }
        
        .radio-option.selected,
        .checkbox-option.selected {
            border-color: var(--primary-color);
            background: rgba(26, 83, 92, 0.1);
        }
        
        .rating-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .rating-option {
            flex: 1;
            min-width: 60px;
            text-align: center;
            padding: 15px 10px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .rating-option:hover {
            border-color: var(--secondary-color);
            background: rgba(247, 181, 56, 0.1);
        }
        
        .rating-option.selected {
            border-color: var(--secondary-color);
            background: var(--secondary-color);
            color: white;
        }
        
        .rating-option input {
            display: none;
        }
        
        .rating-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 18px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .footer {
            text-align: center;
            color: white;
            opacity: 0.8;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .survey-form {
                padding: 25px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .header p {
                font-size: 16px;
            }
            
            .rating-group {
                flex-direction: column;
            }
            
            .rating-option {
                min-width: auto;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if ($surveySystem['logo_path']): ?>
                <img src="<?php echo htmlspecialchars($surveySystem['logo_path']); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($surveySystem['system_name']); ?></h1>
            <?php if ($surveySystem['description']): ?>
                <p><?php echo htmlspecialchars($surveySystem['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <h2>شكراً لك!</h2>
                <p>تم إرسال ردك بنجاح. نقدر وقتك ومشاركتك معنا.</p>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="survey-form">
                <?php foreach ($questions as $question): ?>
                    <div class="form-group">
                        <label for="question_<?php echo $question['id']; ?>">
                            <?php echo htmlspecialchars($question['question_text']); ?>
                            <?php if ($question['is_required']): ?>
                                <span class="required">*</span>
                            <?php endif; ?>
                        </label>
                        
                        <?php if ($question['question_type'] == 'text'): ?>
                            <input type="text" 
                                   id="question_<?php echo $question['id']; ?>" 
                                   name="question_<?php echo $question['id']; ?>"
                                   <?php echo $question['is_required'] ? 'required' : ''; ?>>
                        
                        <?php elseif ($question['question_type'] == 'single_choice'): ?>
                            <div class="radio-group">
                                <?php foreach ($questionOptions[$question['id']] ?? [] as $option): ?>
                                    <label class="radio-option">
                                        <input type="radio" 
                                               name="question_<?php echo $question['id']; ?>" 
                                               value="<?php echo htmlspecialchars($option['option_text']); ?>"
                                               <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                        <span><?php echo htmlspecialchars($option['option_text']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        
                        <?php elseif ($question['question_type'] == 'multiple_choice'): ?>
                            <div class="checkbox-group">
                                <?php foreach ($questionOptions[$question['id']] ?? [] as $option): ?>
                                    <label class="checkbox-option">
                                        <input type="checkbox" 
                                               name="question_<?php echo $question['id']; ?>[]" 
                                               value="<?php echo htmlspecialchars($option['option_text']); ?>">
                                        <span><?php echo htmlspecialchars($option['option_text']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        
                        <?php elseif ($question['question_type'] == 'dropdown'): ?>
                            <select id="question_<?php echo $question['id']; ?>" 
                                    name="question_<?php echo $question['id']; ?>"
                                    <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                <option value="">اختر...</option>
                                <?php foreach ($questionOptions[$question['id']] ?? [] as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option['option_text']); ?>">
                                        <?php echo htmlspecialchars($option['option_text']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        
                        <?php elseif ($question['question_type'] == 'rating'): ?>
                            <div class="rating-group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="rating-option">
                                        <input type="radio" 
                                               name="question_<?php echo $question['id']; ?>" 
                                               value="<?php echo $i; ?>"
                                               <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                        <div>
                                            <div style="font-size: 18px; font-weight: bold;"><?php echo $i; ?></div>
                                            <div style="font-size: 12px;">
                                                <?php
                                                $labels = ['ضعيف جداً', 'ضعيف', 'متوسط', 'جيد', 'ممتاز'];
                                                echo $labels[$i-1];
                                                ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- البرامج إذا كانت موجودة -->
                <?php if (!empty($programs)): ?>
                    <div class="form-group">
                        <label for="program_id">البرنامج المستفاد منه</label>
                        <select id="program_id" name="program_id">
                            <option value="">اختر البرنامج...</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?php echo $program['id']; ?>">
                                    <?php echo htmlspecialchars($program['program_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> إرسال الاستبيان
                </button>
            </form>
        <?php endif; ?>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> جميع الحقوق محفوظة</p>
        </div>
    </div>
    
    <script>
        // تفعيل تأثيرات التفاعل
        document.addEventListener('DOMContentLoaded', function() {
            // تأثيرات الراديو والتشيك بوكس
            const radioOptions = document.querySelectorAll('.radio-option');
            const checkboxOptions = document.querySelectorAll('.checkbox-option');
            const ratingOptions = document.querySelectorAll('.rating-option');
            
            radioOptions.forEach(option => {
                const input = option.querySelector('input[type="radio"]');
                input.addEventListener('change', function() {
                    // إزالة التحديد من جميع الخيارات في نفس المجموعة
                    const groupName = this.name;
                    document.querySelectorAll(`input[name="${groupName}"]`).forEach(radio => {
                        radio.closest('.radio-option').classList.remove('selected');
                    });
                    // إضافة التحديد للخيار الحالي
                    option.classList.add('selected');
                });
            });
            
            checkboxOptions.forEach(option => {
                const input = option.querySelector('input[type="checkbox"]');
                input.addEventListener('change', function() {
                    if (this.checked) {
                        option.classList.add('selected');
                    } else {
                        option.classList.remove('selected');
                    }
                });
            });
            
            ratingOptions.forEach(option => {
                const input = option.querySelector('input[type="radio"]');
                input.addEventListener('change', function() {
                    // إزالة التحديد من جميع خيارات التقييم في نفس المجموعة
                    const groupName = this.name;
                    document.querySelectorAll(`input[name="${groupName}"]`).forEach(radio => {
                        radio.closest('.rating-option').classList.remove('selected');
                    });
                    // إضافة التحديد للخيار الحالي
                    option.classList.add('selected');
                });
            });
        });
    </script>
</body>
</html>


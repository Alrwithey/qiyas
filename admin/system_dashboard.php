<?php
require_once '../config.php';
require_once '../functions.php';

requireLogin();

$systemId = $_GET['id'] ?? 0;
$surveySystem = getSurveySystem($systemId);

if (!$surveySystem) {
    header('Location: dashboard.php');
    exit;
}

// التحقق من صلاحية الوصول
if (!$_SESSION['is_super_admin'] && !hasSystemAccess($_SESSION['admin_id'], $systemId)) {
    die('ليس لديك صلاحية للوصول لهذا النظام');
}

// الحصول على الإحصائيات
$stats = getSurveySystemStats($systemId);
$recentResponses = getSurveyResponsesBySurveySystem($systemId, 10);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم <?php echo htmlspecialchars($surveySystem['system_name']); ?></title>
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
            background: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>;
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
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            transition: background 0.3s;
            font-size: 14px;
        }
        
        .header-actions a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .system-info {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .system-logo {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        .system-details h2 {
            color: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>;
            margin-bottom: 10px;
        }
        
        .system-details p {
            color: #666;
            margin-bottom: 5px;
        }
        
        .system-status {
            margin-left: auto;
            text-align: center;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-card.total .icon { color: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>; }
        .stat-card.today .icon { color: #ffc107; }
        .stat-card.month .icon { color: #28a745; }
        .stat-card.avg .icon { color: <?php echo htmlspecialchars($surveySystem['secondary_color']); ?>; }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h3 {
            font-size: 18px;
            color: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>;
        }
        
        .btn {
            padding: 8px 16px;
            background: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 25px;
        }
        
        .action-card {
            text-align: center;
            padding: 20px;
            border: 2px solid #eee;
            border-radius: 10px;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .action-card:hover {
            border-color: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>;
            transform: translateY(-2px);
            text-decoration: none;
            color: #333;
        }
        
        .action-card i {
            font-size: 30px;
            color: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>;
            margin-bottom: 10px;
        }
        
        .action-card h4 {
            margin-bottom: 5px;
        }
        
        .action-card p {
            font-size: 12px;
            color: #666;
        }
        
        .responses-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .responses-table th,
        .responses-table td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        
        .responses-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>;
        }
        
        .responses-table tr:hover {
            background: #f8f9fa;
        }
        
        .gender-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .gender-badge.male {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .gender-badge.female {
            background: #fce4ec;
            color: #c2185b;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 25px;
        }
        
        .chart-card {
            text-align: center;
        }
        
        .chart-card h4 {
            margin-bottom: 15px;
            color: <?php echo htmlspecialchars($surveySystem['primary_color']); ?>;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .system-info {
                flex-direction: column;
                text-align: center;
            }
            
            .system-status {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .responses-table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-tachometer-alt"></i> لوحة تحكم النظام</h1>
            <div class="header-actions">
                <a href="../survey.php?system=<?php echo $surveySystem['system_slug']; ?>" target="_blank">
                    <i class="fas fa-external-link-alt"></i> عرض الاستبيان
                </a>
                <a href="dashboard.php">
                    <i class="fas fa-arrow-right"></i> العودة للوحة الرئيسية
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- معلومات النظام -->
        <div class="system-info">
            <?php if ($surveySystem['logo_path']): ?>
                <img src="../<?php echo htmlspecialchars($surveySystem['logo_path']); ?>" alt="Logo" class="system-logo">
            <?php endif; ?>
            <div class="system-details">
                <h2><?php echo htmlspecialchars($surveySystem['system_name']); ?></h2>
                <p><strong>الرابط:</strong> <?php echo htmlspecialchars($surveySystem['system_slug']); ?></p>
                <?php if ($surveySystem['description']): ?>
                    <p><?php echo htmlspecialchars($surveySystem['description']); ?></p>
                <?php endif; ?>
                <p><small>تم الإنشاء: <?php echo date('Y-m-d H:i', strtotime($surveySystem['created_date'])); ?></small></p>
            </div>
            <div class="system-status">
                <div class="status-badge <?php echo $surveySystem['is_active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $surveySystem['is_active'] ? 'نشط' : 'غير نشط'; ?>
                </div>
            </div>
        </div>
        
        <!-- الإحصائيات -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="icon"><i class="fas fa-chart-bar"></i></div>
                <div class="number"><?php echo $stats['total_responses']; ?></div>
                <div class="label">إجمالي الردود</div>
            </div>
            <div class="stat-card today">
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
                <div class="number"><?php echo $stats['today_responses']; ?></div>
                <div class="label">ردود اليوم</div>
            </div>
            <div class="stat-card month">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="number"><?php echo $stats['month_responses']; ?></div>
                <div class="label">ردود الشهر</div>
            </div>
            <div class="stat-card avg">
                <div class="icon"><i class="fas fa-star"></i></div>
                <div class="number">
                    <?php 
                    $totalRating = 0;
                    $ratingCount = 0;
                    foreach ($stats['rating_averages'] as $rating) {
                        if ($rating['avg_rating']) {
                            $totalRating += $rating['avg_rating'];
                            $ratingCount++;
                        }
                    }
                    echo $ratingCount > 0 ? number_format($totalRating / $ratingCount, 1) : '0';
                    ?>
                </div>
                <div class="label">متوسط التقييم</div>
            </div>
        </div>
        
        <!-- الإجراءات السريعة -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-bolt"></i> الإجراءات السريعة</h3>
            </div>
            <div class="quick-actions">
                <a href="manage_questions.php?system_id=<?php echo $systemId; ?>" class="action-card">
                    <i class="fas fa-question-circle"></i>
                    <h4>إدارة الأسئلة</h4>
                    <p>إضافة وتعديل أسئلة الاستبيان</p>
                </a>
                <a href="manage_programs.php?system_id=<?php echo $systemId; ?>" class="action-card">
                    <i class="fas fa-list"></i>
                    <h4>إدارة البرامج</h4>
                    <p>إضافة وتعديل البرامج</p>
                </a>
                <a href="responses.php?system_id=<?php echo $systemId; ?>" class="action-card">
                    <i class="fas fa-comments"></i>
                    <h4>عرض الردود</h4>
                    <p>استعراض جميع ردود المستخدمين</p>
                </a>
                <a href="statistics.php?system_id=<?php echo $systemId; ?>" class="action-card">
                    <i class="fas fa-chart-pie"></i>
                    <h4>الإحصائيات التفصيلية</h4>
                    <p>تقارير وإحصائيات متقدمة</p>
                </a>
                <a href="export.php?system_id=<?php echo $systemId; ?>" class="action-card">
                    <i class="fas fa-download"></i>
                    <h4>تصدير البيانات</h4>
                    <p>تحميل البيانات بصيغة Excel</p>
                </a>
                <a href="edit_system.php?id=<?php echo $systemId; ?>" class="action-card">
                    <i class="fas fa-cog"></i>
                    <h4>إعدادات النظام</h4>
                    <p>تعديل إعدادات ومظهر النظام</p>
                </a>
            </div>
        </div>
        
        <!-- أحدث الردود -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-clock"></i> أحدث الردود</h3>
                <a href="responses.php?system_id=<?php echo $systemId; ?>" class="btn">عرض الكل</a>
            </div>
            
            <?php if (empty($recentResponses)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h4>لا توجد ردود بعد</h4>
                    <p>ابدأ بمشاركة رابط الاستبيان مع المستخدمين</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="responses-table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>الاسم</th>
                                <th>الجوال</th>
                                <th>الجنس</th>
                                <th>البرنامج</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentResponses as $response): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($response['submission_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($response['beneficiary_name'] ?: 'غير محدد'); ?></td>
                                    <td><?php echo htmlspecialchars($response['phone_number'] ?: 'غير محدد'); ?></td>
                                    <td>
                                        <?php if ($response['gender']): ?>
                                            <span class="gender-badge <?php echo $response['gender']; ?>">
                                                <?php echo $response['gender'] == 'male' ? 'ذكر' : 'أنثى'; ?>
                                            </span>
                                        <?php else: ?>
                                            غير محدد
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($response['program_name'] ?: 'غير محدد'); ?></td>
                                    <td>
                                        <a href="view_response.php?id=<?php echo $response['id']; ?>" class="btn" style="padding: 4px 8px; font-size: 12px;">
                                            <i class="fas fa-eye"></i> عرض
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- إحصائيات سريعة -->
        <?php if (!empty($stats['gender_distribution']) || !empty($stats['program_distribution'])): ?>
            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-chart-pie"></i> إحصائيات سريعة</h3>
                </div>
                <div class="charts-grid">
                    <?php if (!empty($stats['gender_distribution'])): ?>
                        <div class="chart-card">
                            <h4>توزيع الردود حسب الجنس</h4>
                            <?php foreach ($stats['gender_distribution'] as $gender): ?>
                                <p>
                                    <?php echo $gender['gender'] == 'male' ? 'ذكر' : 'أنثى'; ?>: 
                                    <strong><?php echo $gender['count']; ?></strong>
                                </p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($stats['program_distribution'])): ?>
                        <div class="chart-card">
                            <h4>توزيع الردود حسب البرنامج</h4>
                            <?php foreach (array_slice($stats['program_distribution'], 0, 5) as $program): ?>
                                <p>
                                    <?php echo htmlspecialchars($program['program_name']); ?>: 
                                    <strong><?php echo $program['count']; ?></strong>
                                </p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>


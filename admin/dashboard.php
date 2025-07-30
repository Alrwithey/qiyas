<?php
require_once '../error_reporting.php';
require_once '../config.php';
require_once '../functions.php';

requireLogin();

$surveySystems = getAllSurveySystems();
$totalSystems = count($surveySystems);
$activeSystems = count(array_filter($surveySystems, function($system) {
    return $system['is_active'];
}));

// إحصائيات عامة
$totalResponses = 0;
$todayResponses = 0;
foreach ($surveySystems as $system) {
    $stats = getSurveySystemStats($system['id']);
    $totalResponses += $stats['total_responses'];
    $todayResponses += $stats['today_responses'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo SITE_TITLE; ?></title>
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
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .header-actions span {
            font-size: 14px;
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
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
        
        .stat-card.systems .icon { color: #1a535c; }
        .stat-card.responses .icon { color: #28a745; }
        .stat-card.today .icon { color: #ffc107; }
        .stat-card.active .icon { color: #17a2b8; }
        
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
        
        .section-header h2 {
            font-size: 20px;
            color: #1a535c;
        }
        
        .btn {
            padding: 10px 20px;
            background: #1a535c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #2a6570;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .systems-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 25px;
        }
        
        .system-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .system-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .system-card.inactive {
            opacity: 0.6;
        }
        
        .system-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .system-title {
            font-size: 18px;
            font-weight: bold;
            color: #1a535c;
            margin-bottom: 5px;
        }
        
        .system-slug {
            font-size: 12px;
            color: #666;
            background: #f8f9fa;
            padding: 2px 8px;
            border-radius: 3px;
        }
        
        .system-status {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 3px;
            font-weight: bold;
        }
        
        .system-status.active {
            background: #d4edda;
            color: #155724;
        }
        
        .system-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .system-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .system-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .system-stats span {
            color: #666;
        }
        
        .system-stats strong {
            color: #1a535c;
        }
        
        .system-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #1a535c;
            color: #1a535c;
        }
        
        .btn-outline:hover {
            background: #1a535c;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .systems-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-tachometer-alt"></i> لوحة التحكم</h1>
            <div class="header-actions">
                <span>مرحباً، <?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?></span>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- الإحصائيات العامة -->
        <div class="stats-grid">
            <div class="stat-card systems">
                <div class="icon"><i class="fas fa-poll"></i></div>
                <div class="number"><?php echo $totalSystems; ?></div>
                <div class="label">إجمالي الأنظمة</div>
            </div>
            <div class="stat-card active">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="number"><?php echo $activeSystems; ?></div>
                <div class="label">الأنظمة النشطة</div>
            </div>
            <div class="stat-card responses">
                <div class="icon"><i class="fas fa-chart-bar"></i></div>
                <div class="number"><?php echo $totalResponses; ?></div>
                <div class="label">إجمالي الردود</div>
            </div>
            <div class="stat-card today">
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
                <div class="number"><?php echo $todayResponses; ?></div>
                <div class="label">ردود اليوم</div>
            </div>
        </div>
        
        <!-- أنظمة القياس -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-poll"></i> أنظمة قياس الرضا</h2>
                <a href="create_system.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> إنشاء نظام جديد
                </a>
            </div>
            
            <?php if (empty($surveySystems)): ?>
                <div class="empty-state">
                    <i class="fas fa-poll"></i>
                    <h3>لا توجد أنظمة قياس</h3>
                    <p>ابدأ بإنشاء نظام قياس رضا جديد</p>
                    <a href="create_system.php" class="btn btn-success">إنشاء النظام الأول</a>
                </div>
            <?php else: ?>
                <div class="systems-grid">
                    <?php foreach ($surveySystems as $system): ?>
                        <?php $stats = getSurveySystemStats($system['id']); ?>
                        <div class="system-card <?php echo $system['is_active'] ? '' : 'inactive'; ?>">
                            <div class="system-header">
                                <div>
                                    <div class="system-title"><?php echo htmlspecialchars($system['system_name']); ?></div>
                                    <div class="system-slug"><?php echo htmlspecialchars($system['system_slug']); ?></div>
                                </div>
                                <div class="system-status <?php echo $system['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $system['is_active'] ? 'نشط' : 'غير نشط'; ?>
                                </div>
                            </div>
                            
                            <?php if ($system['description']): ?>
                                <div class="system-description">
                                    <?php echo htmlspecialchars($system['description']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="system-stats">
                                <span>الردود: <strong><?php echo $stats['total_responses']; ?></strong></span>
                                <span>اليوم: <strong><?php echo $stats['today_responses']; ?></strong></span>
                                <span>الشهر: <strong><?php echo $stats['month_responses']; ?></strong></span>
                            </div>
                            
                            <div class="system-actions">
                                <a href="system_dashboard.php?id=<?php echo $system['id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                                </a>
                                <a href="edit_system.php?id=<?php echo $system['id']; ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-edit"></i> تعديل
                                </a>
                                <a href="../survey.php?system=<?php echo $system['system_slug']; ?>" target="_blank" class="btn btn-outline btn-sm">
                                    <i class="fas fa-external-link-alt"></i> عرض
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


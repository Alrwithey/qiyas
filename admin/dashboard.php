<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../db.php';

require_login();

$settings = get_latest_settings($conn);

// ... (كل كود PHP في الأعلى يبقى كما هو بدون تغيير) ...
$total_responses_sql = "SELECT COUNT(*) AS total FROM survey_responses";
$total_responses_result = $conn->query($total_responses_sql);
$total_responses = $total_responses_result->fetch_assoc()["total"];
$avg_rating_sql = "SELECT AVG(rating) AS avg_rating FROM survey_answers WHERE rating IS NOT NULL";
$avg_rating_result = $conn->query($avg_rating_sql);
$avg_rating = $avg_rating_result->fetch_assoc()["avg_rating"];
$avg_rating = ($avg_rating !== null) ? round($avg_rating, 2) : 0;
$highest_rated_sql = "SELECT q.question_text, AVG(sa.rating) AS average_rating FROM survey_answers sa JOIN questions q ON sa.question_id = q.id WHERE q.question_type = 'rating' AND sa.rating IS NOT NULL GROUP BY q.id, q.question_text ORDER BY average_rating DESC LIMIT 1";
$highest_rated_result = $conn->query($highest_rated_sql);
$highest_rated_question = $highest_rated_result->fetch_assoc();
$lowest_rated_sql = "SELECT q.question_text, AVG(sa.rating) AS average_rating FROM survey_answers sa JOIN questions q ON sa.question_id = q.id WHERE q.question_type = 'rating' AND sa.rating IS NOT NULL GROUP BY q.id, q.question_text ORDER BY average_rating ASC LIMIT 1";
$lowest_rated_result = $conn->query($lowest_rated_sql);
$lowest_rated_question = $lowest_rated_result->fetch_assoc();
$gender_sql = "SELECT gender, COUNT(*) AS count FROM survey_responses WHERE gender IS NOT NULL AND gender != '' GROUP BY gender";
$gender_result = $conn->query($gender_sql);
$gender_data = ["male" => 0, "female" => 0];
while ($row = $gender_result->fetch_assoc()) { if ($row['gender'] == 'male') { $gender_data["male"] = $row["count"]; } elseif ($row['gender'] == 'female') { $gender_data["female"] = $row["count"]; } }
$program_sql = "SELECT p.program_name, COUNT(sr.program_id) AS count FROM survey_responses sr JOIN programs p ON sr.program_id = p.id GROUP BY p.program_name ORDER BY count DESC LIMIT 5";
$program_result = $conn->query($program_sql);
$program_data = [];
while ($row = $program_result->fetch_assoc()) { $program_data[] = $row; }
$source_question_text = "كيف عرفت أو سمعت عن جمعية عيون طيبة الخيرية؟";
$source_question_id = get_question_id_by_text($conn, $source_question_text);
$source_data = [];
if ($source_question_id) { $source_sql = "SELECT sa.answer_text, COUNT(sa.id) AS count FROM survey_answers sa WHERE sa.question_id = ? AND sa.answer_text IS NOT NULL AND sa.answer_text != '' GROUP BY sa.answer_text ORDER BY count DESC"; $source_stmt = $conn->prepare($source_sql); $source_stmt->bind_param("i", $source_question_id); $source_stmt->execute(); $source_result = $source_stmt->get_result(); while ($row = $source_result->fetch_assoc()) { $source_data[] = $row; } $source_stmt->close(); }
$avg_ratings_per_question_sql = "SELECT q.question_text, AVG(sa.rating) AS average_rating FROM survey_answers sa JOIN questions q ON sa.question_id = q.id WHERE q.question_type = 'rating' AND sa.rating IS NOT NULL GROUP BY q.id, q.question_text ORDER BY q.question_order ASC";
$avg_ratings_result = $conn->query($avg_ratings_per_question_sql);
$avg_ratings_data = [];
while ($row = $avg_ratings_result->fetch_assoc()) { $avg_ratings_data[] = $row; }
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_admin_head($settings, "لوحة التحكم"); ?>

<style>
    /* ... (CSS الأصلي يبقى كما هو) ... */
    .charts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-top: 30px; }
    .chart-container { position: relative; width: 100%; height: 380px; }
    .full-width-chart { grid-column: 1 / -1; }
    @media (max-width: 1200px) { .charts-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .charts-grid { grid-template-columns: 1fr; } }
    
    /* ======================= يبدأ التعديل هنا ======================= */
    .widget .widget-icon {
        flex-shrink: 0; /* هذا هو السطر الأهم: يمنع الأيقونة من التقلص */
    }
    .widget-title, .widget-description { /* تطبيق نفس النمط على العنوان والنص الطويل */
        white-space: normal;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        font-size: 0.9em; 
    }
    /* ======================= ينتهي التعديل هنا ======================= */
</style>

<body>
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="admin-wrapper">
        <div class="sidebar">
            <?php echo get_admin_header($settings); ?>
        </div>
        <div class="main-content">
            <h1>لوحة التحكم</h1>

            <div class="dashboard-widgets">
                <!-- الويدجت الأول والثاني يبقيان كما هما -->
                <div class="widget">
                    <div class="widget-icon" style="background-color: #28a745;"><i class="fas fa-poll"></i></div>
                    <div class="widget-content">
                        <div class="widget-value"><?php echo $total_responses; ?></div>
                        <div class="widget-title">إجمالي الاستبيانات</div>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon" style="background-color: #007bff;"><i class="fas fa-star"></i></div>
                    <div class="widget-content">
                        <div class="widget-value"><?php echo $avg_rating; ?> / 5</div>
                        <div class="widget-title">متوسط الرضا العام</div>
                    </div>
                </div>
                
                <!-- الأعلى تقييماً (ب) -->
                <div class="widget">
                    <div class="widget-icon" style="background-color: #2ecc71;">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div class="widget-content">
                        <?php if ($highest_rated_question): ?>
                        <div class="widget-value" title="<?php echo htmlspecialchars($highest_rated_question['question_text']); ?>">
                        <?php echo round($highest_rated_question['average_rating'], 2); ?>
                            <span style="font-size: 14px; color: #1c9d53;">الأكثر تقييماً</span>

                        </div>
                        <?php // ======================= يبدأ التعديل هنا: استخدام كلاس widget-title ======================= ?>
                        <div class="widget-title"><?php echo htmlspecialchars($highest_rated_question['question_text']); ?></div>
                        <?php // ======================= ينتهي التعديل هنا ======================= ?>
                        <?php else: ?>
                        <div class="widget-value">-</div>
                        <div class="widget-title">لا توجد تقييمات</div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- الأقل تقييماً () -->
                <div class="widget">
                    <div class="widget-icon" style="background-color: #e74c3c;">
                        <i class="fas fa-thumbs-down"></i>
                    </div>
                    <div class="widget-content">
                         <?php if ($lowest_rated_question): ?>
                        <div class="widget-value" title="<?php echo htmlspecialchars($lowest_rated_question['question_text']); ?>">
                             <?php echo round($lowest_rated_question['average_rating'], 2); ?>
                            <span style="font-size: 14px; color: #e74c3c;">الأقل تقييماً</span>

                        </div>
                        <?php // ======================= يبدأ التعديل هنا: استخدام كلاس widget-title ======================= ?>
                        <div class="widget-title"><?php echo htmlspecialchars($lowest_rated_question['question_text']); ?></div>
                        <?php // ======================= ينتهي التعديل هنا ======================= ?>
                        <?php else: ?>
                        <div class="widget-value">-</div>
                        <div class="widget-title">لا توجد تقييمات</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <!-- كل الرسوم البيانية تبقى كما هي في الكود الأصلي -->
                <div class="form-container"><h2>البرامج الأكثر استفادة</h2><div class="chart-container"><canvas id="programsChart"></canvas></div></div>
                <div class="form-container"><h2>توزيع المستفيدين حسب الجنس</h2><div class="chart-container"><canvas id="genderChart"></canvas></div></div>
                <div class="form-container"><h2>كيف سمع المستفيدون عنا؟</h2><div class="chart-container"><canvas id="sourceChart"></canvas></div></div>
                <div class="form-container full-width-chart">
                    <h2>متوسط الرضا لكل سؤال</h2>
                    <div class="chart-container" style="height: 450px;">
                         <canvas id="avgRatingsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php echo get_admin_footer(); ?>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        function optionsWithBold(isLegend) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: isLegend ? {
                        display: true,
                        labels: { font: { weight: 'bold' } }
                    } : {
                        display: false,
                        labels: { font: { weight: 'bold' } }
                    }
                },
                scales: {
                    x: { ticks: { font: { weight: 'bold' } } },
                    y: { ticks: { font: { weight: 'bold' }, beginAtZero: true } }
                }
            };
        }

        // Chart: البرامج
        if (document.getElementById('programsChart')) {
            const ctx = document.getElementById('programsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($program_data, 'program_name')); ?>,
                    datasets: [{
                        label: 'عدد المستفيدين',
                        data: <?php echo json_encode(array_column($program_data, 'count')); ?>,
                        backgroundColor: ['rgba(26,83,92,0.7)','rgba(247,181,56,0.7)','rgba(52,152,219,0.7)','rgba(46,204,113,0.7)','rgba(155,89,182,0.7)'],
                        borderColor: ['rgba(26,83,92,1)','rgba(247,181,56,1)','rgba(52,152,219,1)','rgba(46,204,113,1)','rgba(155,89,182,1)'],
                        borderWidth: 1
                    }]
                },
                options: optionsWithBold(false)
            });
        }

        // Chart: الجنس
       if (document.getElementById('genderChart')) {
            const genderCtx = document.getElementById('genderChart').getContext('2d');
            new Chart(genderCtx, { 
                type: 'pie', 
                data: { 
                    labels: ['ذكور', 'إناث'], 
                    datasets: [{ 
                        data: [<?php echo $gender_data['male']; ?>, <?php echo $gender_data['female']; ?>], 
                        backgroundColor: ['rgba(54, 162, 235, 0.8)', 'rgba(255, 99, 132, 0.8)'], 
                        borderColor: ['rgba(54, 162, 235, 1)', 'rgba(255, 99, 132, 1)'], 
                        borderWidth: 1 
                    }] 
                }, 
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    plugins: { 
                        legend: { position: 'top' } 
                    },
                    //scales: { y: { display: false }, x: { display: false } } //  <-- الطريقة الأولى: إخفاء المحاور
                    // أو ببساطة، لا تقم بتعريف scales على الإطلاق
                } 
            });
        }

        // Chart: مصدر السمع
        if (document.getElementById('sourceChart')) {
            const ctx = document.getElementById('sourceChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($source_data, 'answer_text')); ?>,
                    datasets: [{
                        label: 'عدد الإجابات',
                        data: <?php echo json_encode(array_column($source_data, 'count')); ?>,
                        backgroundColor: ['rgba(255,159,64,0.8)','rgba(75,192,192,0.8)','rgba(153,102,255,0.8)','rgba(231,76,60,0.8)','rgba(54,162,235,0.8)'],
                        borderColor: ['rgba(255,159,64,1)','rgba(75,192,192,1)','rgba(153,102,255,1)','rgba(231,76,60,1)','rgba(54,162,235,1)'],
                        borderWidth: 1
                    }]
                },
                options: {
                    ...optionsWithBold(false),
                    indexAxis: 'y',
                    scales: {
                        x: { beginAtZero: true, ticks: { font: { weight: 'bold' } } },
                        y: { ticks: { font: { weight: 'bold' } } }
                    }
                }
            });
        }

        // Chart: متوسط التقييم لكل سؤال
        if (document.getElementById('avgRatingsChart')) {
            const ctx = document.getElementById('avgRatingsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($avg_ratings_data, 'question_text')); ?>,
                    datasets: [{
                        label: 'متوسط التقييم (من 5)',
                        data: <?php echo json_encode(array_map(fn($d) => round($d['average_rating'],2), $avg_ratings_data)); ?>,
                        backgroundColor: ['rgba(46,204,113,0.7)','rgba(52,152,219,0.7)','rgba(155,89,182,0.7)','rgba(241,196,15,0.7)','rgba(230,126,34,0.7)','rgba(231,76,60,0.7)','rgba(149,165,166,0.7)'],
                        borderColor: ['rgba(39,174,96,1)','rgba(41,128,185,1)','rgba(142,68,173,1)','rgba(243,156,18,1)','rgba(211,84,0,1)','rgba(192,57,43,1)','rgba(127,140,141,1)'],
                        borderWidth: 2,
                        barThickness: 50
                    }]
                },
                options: {
                    ...optionsWithBold(true),
                    scales: {
                        x: { ticks: { font: { weight: 'bold' } } },
                        y: { beginAtZero: true, max: 5, ticks: { stepSize: 1, font: { weight: 'bold' } } }
                    },
                    plugins: {
                        ...optionsWithBold(true).plugins,
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} ★`
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
</body>
</html>

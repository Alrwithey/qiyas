<?php
session_start();
require_once __DIR__ . 
    "/../config.php";
require_once __DIR__ . 
    "/../functions.php";
require_once __DIR__ . 
    "/../db.php";

require_login();

$settings = get_latest_settings($conn);

// Data for charts

// 1. Overall Satisfaction (Average Rating)
$overall_satisfaction_sql = "SELECT AVG(rating) AS avg_rating FROM survey_answers WHERE rating IS NOT NULL";
$overall_satisfaction_result = $conn->query($overall_satisfaction_sql);
$overall_satisfaction = $overall_satisfaction_result->fetch_assoc()["avg_rating"];
$overall_satisfaction = ($overall_satisfaction !== null) ? round($overall_satisfaction, 2) : 0;

// 2. Satisfaction per Question (for rating questions)
$question_satisfaction_sql = "SELECT q.question_text, AVG(sa.rating) AS avg_rating FROM survey_answers sa JOIN questions q ON sa.question_id = q.id WHERE q.question_type = 'rating' GROUP BY q.id ORDER BY q.question_order ASC";
$question_satisfaction_result = $conn->query($question_satisfaction_sql);
$question_satisfaction_data = [];
while ($row = $question_satisfaction_result->fetch_assoc()) {
    $question_satisfaction_data[] = [
        "question_text" => $row["question_text"],
        "avg_rating" => round($row["avg_rating"], 2)
    ];
}

// 3. Gender Distribution
$gender_distribution_sql = "SELECT gender, COUNT(*) AS count FROM survey_responses WHERE gender IS NOT NULL GROUP BY gender";
$gender_distribution_result = $conn->query($gender_distribution_sql);
$gender_distribution_data = [];
while ($row = $gender_distribution_result->fetch_assoc()) {
    $gender_distribution_data[$row["gender"]] = $row["count"];
}

// 4. Program Distribution
$program_distribution_sql = "SELECT p.program_name, COUNT(sr.program_id) AS count FROM survey_responses sr JOIN programs p ON sr.program_id = p.id GROUP BY p.program_name ORDER BY count DESC";
$program_distribution_result = $conn->query($program_distribution_sql);
$program_distribution_data = [];
while ($row = $program_distribution_result->fetch_assoc()) {
    $program_distribution_data[] = [
        "program_name" => $row["program_name"],
        "count" => $row["count"]
    ];
}

// 5. How they heard about the charity (assuming question ID 12 is 'كيف عرفت أو سمعت عن جمعية عيوني الصحية؟')
$how_heard_sql = "SELECT qo.option_text, COUNT(smca.option_id) AS count FROM survey_multiple_choice_answers smca JOIN question_options qo ON smca.option_id = qo.id JOIN questions q ON qo.question_id = q.id WHERE q.id = 12 GROUP BY qo.option_text ORDER BY count DESC";
$how_heard_result = $conn->query($how_heard_sql);
$how_heard_data = [];
while ($row = $how_heard_result->fetch_assoc()) {
    $how_heard_data[] = [
        "option_text" => $row["option_text"],
        "count" => $row["count"]
    ];
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<?php echo get_admin_head($settings, "الإحصائيات"); ?>
<body>
    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
    <div class="admin-wrapper">
        <div class="sidebar">
            <?php echo get_admin_header($settings); ?>
        </div>
        <div class="main-content">
            <h1>الإحصائيات والرسوم البيانية</h1>

            <div class="dashboard-widgets">
                <div class="widget">
                    <div class="widget-icon" style="background-color: #28a745;"><i class="fas fa-star"></i></div>
                    <div class="widget-content">
                        <div class="widget-value"><?php echo $overall_satisfaction; ?> / 5</div>
                        <div class="widget-title">متوسط الرضا العام</div>
                    </div>
                </div>
            </div>

            <div class="form-container">
                <h2>متوسط الرضا لكل سؤال</h2>
                <canvas id="questionSatisfactionChart"></canvas>
            </div>

            <div class="form-container">
                <h2>توزيع الجنس</h2>
                <canvas id="genderDistributionChart"></canvas>
            </div>

            <div class="form-container">
                <h2>توزيع البرامج المستفاد منها</h2>
                <canvas id="programDistributionChart"></canvas>
            </div>

            <div class="form-container">
                <h2>كيف عرفوا عن الجمعية؟</h2>
                <canvas id="howHeardChart"></canvas>
            </div>

        </div>
    </div>

    <?php echo get_admin_footer(); ?>
    <script>
        // Question Satisfaction Chart
        const questionSatisfactionCtx = document.getElementById("questionSatisfactionChart").getContext("2d");
        const questionSatisfactionChart = new Chart(questionSatisfactionCtx, {
            type: "bar",
            data: {
                labels: [<?php foreach($question_satisfaction_data as $data) { echo "\"" . htmlspecialchars($data["question_text"]) . "\","; } ?>],
                datasets: [{
                    label: "متوسط الرضا",
                    data: [<?php foreach($question_satisfaction_data as $data) { echo $data["avg_rating"] . ","; } ?>],
                    backgroundColor: "rgba(26, 83, 92, 0.7)",
                    borderColor: "rgba(26, 83, 92, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Gender Distribution Chart
        const genderDistributionCtx = document.getElementById("genderDistributionChart").getContext("2d");
        const genderDistributionChart = new Chart(genderDistributionCtx, {
            type: "pie",
            data: {
                labels: ["ذكور", "إناث"],
                datasets: [{
                    data: [<?php echo $gender_distribution_data["male"] ?? 0; ?>, <?php echo $gender_distribution_data["female"] ?? 0; ?>],
                    backgroundColor: [
                        "rgba(0, 123, 255, 0.7)",
                        "rgba(232, 62, 140, 0.7)"
                    ],
                    borderColor: [
                        "rgba(0, 123, 255, 1)",
                        "rgba(232, 62, 140, 1)"
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "top",
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || "";
                                if (label) {
                                    label += ": ";
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Program Distribution Chart
        const programDistributionCtx = document.getElementById("programDistributionChart").getContext("2d");
        const programDistributionChart = new Chart(programDistributionCtx, {
            type: "doughnut",
            data: {
                labels: [<?php foreach($program_distribution_data as $data) { echo "\"" . htmlspecialchars($data["program_name"]) . "\","; } ?>],
                datasets: [{
                    data: [<?php foreach($program_distribution_data as $data) { echo $data["count"] . ","; } ?>],
                    backgroundColor: [
                        "rgba(255, 99, 132, 0.7)",
                        "rgba(54, 162, 235, 0.7)",
                        "rgba(255, 206, 86, 0.7)",
                        "rgba(75, 192, 192, 0.7)",
                        "rgba(153, 102, 255, 0.7)",
                        "rgba(255, 159, 64, 0.7)"
                    ],
                    borderColor: [
                        "rgba(255, 99, 132, 1)",
                        "rgba(54, 162, 235, 1)",
                        "rgba(255, 206, 86, 1)",
                        "rgba(75, 192, 192, 1)",
                        "rgba(153, 102, 255, 1)",
                        "rgba(255, 159, 64, 1)"
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "top",
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || "";
                                if (label) {
                                    label += ": ";
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // How Heard Chart
        const howHeardCtx = document.getElementById("howHeardChart").getContext("2d");
        const howHeardChart = new Chart(howHeardCtx, {
            type: "bar",
            data: {
                labels: [<?php foreach($how_heard_data as $data) { echo "\"" . htmlspecialchars($data["option_text"]) . "\","; } ?>],
                datasets: [{
                    label: "عدد الاستجابات",
                    data: [<?php foreach($how_heard_data as $data) { echo $data["count"] . ","; } ?>],
                    backgroundColor: "rgba(247, 181, 56, 0.7)",
                    borderColor: "rgba(247, 181, 56, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>



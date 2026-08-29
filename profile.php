<?php
// profile.php
require 'db.php';

if (!isset($_GET['staff_id'])) {
    die("<div class='alert alert-danger m-4'>Error: No Staff ID provided.</div>");
}

$staffID = $_GET['staff_id'];

// Fetch staff details
$staffStmt = $pdo->prepare("
    SELECT s.StaffID, s.Name, d.DepName, p.PosName 
    FROM Staff s 
    LEFT JOIN Department d ON s.DepID = d.DepID 
    LEFT JOIN `Position` p ON s.PosID = p.PosID 
    WHERE s.StaffID = ?
");
$staffStmt->execute([$staffID]);
$staff = $staffStmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    die("<div class='alert alert-danger m-4'>Error: Staff member not found.</div>");
}

// Fetch the most recent feedback and recommendation
$feedbackStmt = $pdo->prepare("
    SELECT Year, Comment, Recommendation 
    FROM feedback
    WHERE StaffID = ? 
    ORDER BY Year DESC 
    LIMIT 1
");

$feedbackStmt->execute([$staffID]);
$latestFeedback = $feedbackStmt->fetch(PDO::FETCH_ASSOC);
// Fetch available report years
$yearsStmt = $pdo->prepare("
    SELECT DISTINCT Year 
    FROM KPIScore 
    WHERE StaffID = ? 
    ORDER BY Year DESC
");
$yearsStmt->execute([$staffID]);
$availableYears = $yearsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch data for the radar chart
$radarLabels = [];
$radarData = [];
$latestYearForChart = null;

if (!empty($availableYears)) {
    $latestYearForChart = $availableYears[0]['Year'];

    $radarStmt = $pdo->prepare("
        SELECT g.GroupName, AVG(ks.Score) as AvgScore
        FROM KPIScore ks
        JOIN KPI k ON ks.KPIID = k.KPIID
        JOIN KPIGroup g ON k.GroupID = g.GroupID
        WHERE ks.StaffID = ? AND ks.Year = ?
        GROUP BY g.GroupID, g.GroupName
        ORDER BY g.GroupID ASC
    ");
    $radarStmt->execute([$staffID, $latestYearForChart]);
    $radarResults = $radarStmt->fetchAll(PDO::FETCH_ASSOC);

    $mergedData = [];

    foreach ($radarResults as $row) {
        $cleanName = str_replace(['Section 1 - ', 'Section 2 - '], '', $row['GroupName']);
        $cleanName = preg_replace('/ \([0-9]+%\)/', '', $cleanName); // Removes the (5%) or (10%)
        $cleanName = trim($cleanName);

        // Group the scores together
        if (!isset($mergedData[$cleanName])) {
            $mergedData[$cleanName] = ['totalScore' => 0, 'kpiCount' => 0];
        }
        $mergedData[$cleanName]['totalScore'] += $row['AvgScore'];
        $mergedData[$cleanName]['kpiCount']++;
    }

    // Calculate the final averages and percentages
    foreach ($mergedData as $label => $data) {
        $radarLabels[] = $label;
        
        $mergedAvg = $data['totalScore'] / $data['kpiCount'];
        $percentage = round(($mergedAvg / 5) * 100, 1);
        $radarData[] = $percentage;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($staff['Name']) ?>'s Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; padding-top: 20px; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 10px 20px; margin-bottom: 5px; border-radius: 4px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .year-card { transition: transform 0.2s; border: 1px solid #dee2e6; cursor: pointer; text-decoration: none; color: inherit; display: block; }
        .year-card:hover { transform: translateY(-5px); border-color: #0d6efd; box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block sidebar">
            <h4 class="text-center mb-4">KPI System</h4>
            <a href="index.php">Dashboard</a>
            <a href="analytics.php">Analytics</a>
            <a href="reports.php">Reports</a>
            <a href="staff.php" class="active">Staff Directory</a>
            <a href="kpi_management.php">KPI Management</a>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <h1 class="h2">Employee Profile</h1>
                <a href="staff.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Directory</a>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card p-4 text-center h-100">
                        <div class="mb-3">
                            <i class="bi bi-person-circle text-secondary" style="font-size: 6rem;"></i>
                        </div>
                        <h3 class="fw-bold text-primary mb-1"><?= htmlspecialchars($staff['Name']) ?></h3>
                        <p class="text-muted mb-4">ID: <?= htmlspecialchars($staff['StaffID']) ?></p>
                        
                        <div class="text-start bg-light p-3 rounded border">
                            <p class="mb-2"><strong><i class="bi bi-building"></i> Department:</strong> <br><?= htmlspecialchars($staff['DepName'] ?? 'Unassigned') ?></p>
                            <p class="mb-0"><strong><i class="bi bi-briefcase"></i> Position:</strong> <br><?= htmlspecialchars($staff['PosName'] ?? 'Unassigned') ?></p>
                        </div>
                        
                        <div class="mt-4">
                            <a href="add_kpi_log.php?staff_id=<?= $staff['StaffID'] ?>" class="btn btn-success w-100 mb-2">
                                <i class="bi bi-plus-circle"></i> Add New Evaluation
                            </a>
                        </div>
						<div class="mt-4 pt-3 border-top">
							<h6 class="fw-bold text-muted mb-3">
								<i class="bi bi-chat-left-quote-fill text-primary me-2"></i> Latest Feedback <?= $latestFeedback ? '(' . htmlspecialchars($latestFeedback['Year']) . ')' : '' ?>
							</h6>
    
						<?php if ($latestFeedback): ?>
							
							<div class="mb-3">
								<span class="d-block text-dark fw-bold mb-1" style="font-size: 0.85rem;">Supervisor Comments</span>
								<div class="bg-light p-2 rounded border text-muted" style="font-size: 0.85rem;">
									<?= htmlspecialchars($latestFeedback['Comment'] ?: 'No comments provided during this evaluation.') ?>
								</div>
							</div>

							<div>
								<span class="d-block text-dark fw-bold mb-1" style="font-size: 0.85rem;">Training Recommendation</span>
								<div class="bg-warning bg-opacity-10 p-2 rounded border border-warning text-dark" style="font-size: 0.85rem;">
									<i class="bi bi-lightbulb-fill text-warning me-1"></i> 
									<?= htmlspecialchars($latestFeedback['Recommendation'] ?: 'No specific training required at this time.') ?>
								</div>
							</div>

						<?php else: ?>
							<div class="text-muted fst-italic text-center p-3 bg-light rounded border" style="font-size: 0.85rem;">
								No evaluations have been recorded for this staff member yet.
							</div>
						<?php endif; ?>
					</div>
                    </div>
                </div>

                <div class="col-md-8 mb-4">
                    
                    <?php if (!empty($radarData)): ?>
                    <div class="card p-4 mb-4">
                        <h5 class="fw-bold text-muted mb-3"><i class="bi bi-bullseye"></i> Skill Strength Analysis (<?= $latestYearForChart ?>)</h5>
                        <div style="position: relative; height: 350px; width: 100%; display: flex; justify-content: center;">
                            <canvas id="skillRadarChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card p-4">
                        <h5 class="mb-4 fw-bold text-muted"><i class="bi bi-clock-history"></i> Performance History</h5>
                        
                        <?php if (empty($availableYears)): ?>
                            <div class="alert alert-warning text-center py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                <h6 class="mb-0">No KPI scores recorded yet.</h6>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($availableYears as $yr): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <a href="view_report.php?staff_id=<?= $staff['StaffID'] ?>&year=<?= htmlspecialchars($yr['Year']) ?>" class="card p-3 text-center year-card bg-light">
                                            <i class="bi bi-file-earmark-bar-graph text-primary fs-2 mb-2"></i>
                                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($yr['Year']) ?> Report</h6>
                                            <span class="badge bg-success mt-2 mx-auto">View Details</span>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>

        </main>
    </div>
</div>

<script>
<?php if (!empty($radarData)): ?>
const ctx = document.getElementById('skillRadarChart').getContext('2d');
new Chart(ctx, {
    type: 'radar',
    data: {
        labels: <?= json_encode($radarLabels) ?>,
        datasets: [{
            label: 'Strength (%)',
            data: <?= json_encode($radarData) ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.2)', // Light blue fill
            borderColor: '#0d6efd', // Primary blue outline
            pointBackgroundColor: '#0d6efd',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#0d6efd',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            r: {
                angleLines: { display: true, color: 'rgba(0,0,0,0.05)' },
                grid: { color: 'rgba(0,0,0,0.05)' },
                suggestedMin: 0,
                suggestedMax: 100,
                ticks: {
                    stepSize: 20,
                    backdropColor: 'transparent',
                    callback: function(value) { return value + '%'; }
                }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.raw + '% Strength';
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

</body>
</html>
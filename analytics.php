<?php
// analytics.php
require 'db.php';

// Get selected year and department from filter
$selectedYear = $_GET['year'] ?? date('Y');
$selectedDept = $_GET['department'] ?? '';

$staffQuery = "
    SELECT s.StaffID, s.Name, d.DepName, AVG(ks.Score) as AvgScore
    FROM KPIScore ks
    JOIN Staff s ON ks.StaffID = s.StaffID
    LEFT JOIN Department d ON s.DepID = d.DepID
    WHERE ks.Year = ?
";
$staffParams = [$selectedYear];

if (!empty($selectedDept)) {
    $staffQuery .= " AND s.DepID = ?";
    $staffParams[] = $selectedDept;
}
$staffQuery .= " GROUP BY s.StaffID, s.Name, d.DepName";

$staffAveragesStmt = $pdo->prepare($staffQuery);
$staffAveragesStmt->execute($staffParams);
$staffScores = $staffAveragesStmt->fetchAll(PDO::FETCH_ASSOC);

$totalAnalyzed = count($staffScores);
$sumScores = 0;
$topPerformers = 0;
$atRiskStaff = 0;
$deptAverages = [];
$topStaffList = [];
$atRiskStaffList = [];

foreach ($staffScores as $staff) {
    $score = round($staff['AvgScore'], 2);
    $sumScores += $score;
    
    if ($score >= 4.0) {
        $topPerformers++;
        $topStaffList[] = $staff; // Adds staff to top list
    }
    if ($score < 3.0) {
        $atRiskStaff++;
        $atRiskStaffList[] = $staff; // Adds staff to at risk list
    }
    
    $dept = $staff['DepName'] ?? 'Unassigned';
    if (!isset($deptAverages[$dept])) {
        $deptAverages[$dept] = ['total' => 0, 'count' => 0];
    }
    $deptAverages[$dept]['total'] += $score;
    $deptAverages[$dept]['count']++;
}

usort($topStaffList, function($a, $b) { return $b['AvgScore'] <=> $a['AvgScore']; }); // Highest first
usort($atRiskStaffList, function($a, $b) { return $a['AvgScore'] <=> $b['AvgScore']; }); // Lowest first

// Limit both lists to the top 5 only
$topStaffList = array_slice($topStaffList, 0, 5);
$atRiskStaffList = array_slice($atRiskStaffList, 0, 5);

$companyAvg = $totalAnalyzed > 0 ? round($sumScores / $totalAnalyzed, 2) : 0;

// Bar Chart
$barStmt = $pdo->prepare("
    SELECT d.DepName, AVG(ks.Score) as AvgScore
    FROM KPIScore ks
    JOIN Staff st ON ks.StaffID = st.StaffID
    JOIN Department d ON st.DepID = d.DepID
    WHERE ks.Year = ?
    GROUP BY d.DepID, d.DepName
    ORDER BY AvgScore DESC
");
$barStmt->execute([$selectedYear]);
$barDataRaw = $barStmt->fetchAll(PDO::FETCH_ASSOC);

$chartDeptLabels = [];
$chartDeptData = [];
$lowestDept = '';
$lowestDeptScore = 5.0;

foreach ($barDataRaw as $row) {
    $chartDeptLabels[] = $row['DepName'];
    $chartDeptData[] = round($row['AvgScore'], 2);
    
    if (round($row['AvgScore'], 2) < $lowestDeptScore) {
        $lowestDeptScore = round($row['AvgScore'], 2);
        $lowestDept = $row['DepName'];
    }
}

// Historical trend 
$trendQuery = "
    SELECT ks.Year, AVG(ks.Score) as YearlyAvg 
    FROM KPIScore ks 
    JOIN Staff st ON ks.StaffID = st.StaffID
    WHERE 1=1
";
$trendParams = [];

if (!empty($selectedDept)) {
    $trendQuery .= " AND st.DepID = ?";
    $trendParams[] = $selectedDept;
}
$trendQuery .= " GROUP BY ks.Year ORDER BY ks.Year ASC";

$trendStmt = $pdo->prepare($trendQuery);
$trendStmt->execute($trendParams);
$trendDataRaw = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$chartTrendLabels = [];
$chartTrendData = [];
foreach ($trendDataRaw as $row) {
    $chartTrendLabels[] = $row['Year'];
    $chartTrendData[] = round($row['YearlyAvg'], 2);
}

// Radar Chart 
$radarQuery = "
    SELECT g.GroupName, AVG(ks.Score) as AvgScore
    FROM KPIScore ks
    JOIN KPI k ON ks.KPIID = k.KPIID
    JOIN KPIGroup g ON k.GroupID = g.GroupID
    JOIN Staff st ON ks.StaffID = st.StaffID
    WHERE ks.Year = ?
";
$radarParams = [$selectedYear];

if (!empty($selectedDept)) {
    $radarQuery .= " AND st.DepID = ?";
    $radarParams[] = $selectedDept;
}

$radarQuery .= " GROUP BY g.GroupID, g.GroupName ORDER BY g.GroupID ASC";
$radarStmt = $pdo->prepare($radarQuery);
$radarStmt->execute($radarParams);
$radarDataRaw = $radarStmt->fetchAll(PDO::FETCH_ASSOC);

$radarLabels = [];
$radarScores = [];
foreach($radarDataRaw as $row) {
    $cleanName = preg_replace('/ \([0-9]+%\)/', '', $row['GroupName']);
    
    $labelIndex = array_search(trim($cleanName), $radarLabels);
    if ($labelIndex !== false) {
        $radarScores[$labelIndex] = round(($radarScores[$labelIndex] + $row['AvgScore']) / 2, 2);
    } else {
        $radarLabels[] = trim($cleanName);
        $radarScores[] = round($row['AvgScore'], 2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; padding-top: 20px; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 10px 20px; margin-bottom: 5px; border-radius: 4px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.1); }
        .insight-badge { font-size: 0.9rem; padding: 8px 12px; border-radius: 6px; font-weight: bold; }
        .list-group-item { border-left: none; border-right: none; padding: 15px 20px; }
        .list-group-item:first-child { border-top: none; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block sidebar">
            <h4 class="text-center mb-4">KPI System</h4>
            <a href="index.php">Dashboard</a>
            <a href="analytics.php" class="active">Analytics</a>
            <a href="reports.php">Reports</a>
            <a href="staff.php">Staff Directory</a>
            <a href="kpi_management.php">KPI Management</a>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4 border-bottom">
                <h1 class="h2">KPI Analytics</h1>
                <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Export Report</button>
            </div>

            <?php 
                $showSearch = false;
                include 'filter_bar.php'; 
            ?>

            <h5 class="mb-3 text-muted">KPI Overview (<?= htmlspecialchars($selectedYear) ?>)</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card p-3 h-100" style="border-top: 4px solid #0d6efd;">
                        <h6 class="text-muted mb-1"><i class="bi bi-bar-chart-fill"></i> Avg KPI Score</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= number_format($companyAvg, 2) ?> <span class="fs-6 text-muted">/ 5.0</span></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100" style="border-top: 4px solid #6c757d;">
                        <h6 class="text-muted mb-1"><i class="bi bi-people-fill"></i> Total Analyzed</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= $totalAnalyzed ?> <span class="fs-6 text-muted">Staff</span></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100 bg-success bg-opacity-10" style="border-top: 4px solid #198754;">
                        <h6 class="text-success mb-1"><i class="bi bi-trophy-fill"></i> Top Performers (≥4.0)</h6>
                        <h2 class="fw-bold mb-0 text-success"><?= $topPerformers ?> <span class="fs-6">Staff</span></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100 bg-danger bg-opacity-10" style="border-top: 4px solid #dc3545;">
                        <h6 class="text-danger mb-1"><i class="bi bi-exclamation-triangle-fill"></i> At-Risk Staff (<3.0)</h6>
                        <h2 class="fw-bold mb-0 text-danger"><?= $atRiskStaff ?> <span class="fs-6">Staff</span></h2>
                    </div>
                </div>
            </div>

            <?php
            // get department name if filtered
            $contextName = "Company-Wide";
            if (!empty($selectedDept)) {
                $deptNameStmt = $pdo->prepare("SELECT DepName FROM Department WHERE DepID = ?");
                $deptNameStmt->execute([$selectedDept]);
                $contextName = $deptNameStmt->fetchColumn() . " Department";
            }

            // get previous year to calculate trend
            $prevYear = $selectedYear - 1;
            $prevQuery = "SELECT AVG(ks.Score) FROM KPIScore ks JOIN Staff st ON ks.StaffID = st.StaffID WHERE ks.Year = ?";
            $prevParams = [$prevYear];
            if (!empty($selectedDept)) {
                $prevQuery .= " AND st.DepID = ?";
                $prevParams[] = $selectedDept;
            }
            $prevStmt = $pdo->prepare($prevQuery);
            $prevStmt->execute($prevParams);
            $prevAvg = $prevStmt->fetchColumn();
            $prevAvg = $prevAvg ? round($prevAvg, 2) : null;

            $strongestCategory = '';
            $weakestCategory = '';
            $maxScore = 0;
            $minScore = 5;
            if (!empty($radarScores)) {
                $maxScore = max($radarScores);
                $minScore = min($radarScores);
                $strongestCategory = $radarLabels[array_search($maxScore, $radarScores)];
                $weakestCategory = $radarLabels[array_search($minScore, $radarScores)];
            }
            ?>
            <div class="card p-4 mb-4 bg-white shadow-sm border-0" style="border-left: 5px solid #6f42c1 !important;">
                <h5 class="mb-3 fw-bold text-dark"><i class="bi bi-robot text-purple" style="color: #6f42c1;"></i> Automated Executive Summary</h5>
                <p class="mb-2 fs-6 text-dark" style="line-height: 1.6;">
                    For the <strong><?= htmlspecialchars($contextName) ?></strong> evaluation period of <?= htmlspecialchars($selectedYear) ?>, the overall performance average stands at <strong><?= number_format($companyAvg, 2) ?> / 5.0</strong>. 
                    
                    <?php if ($prevAvg): ?>
                        <?php if ($companyAvg > $prevAvg): ?>
                            <span class="text-success fw-bold">This represents a consistent improvement</span> from last year's average of <?= number_format($prevAvg, 2) ?>.
                        <?php elseif ($companyAvg < $prevAvg): ?>
                            <span class="text-danger fw-bold">This indicates a performance decline</span> compared to last year's average of <?= number_format($prevAvg, 2) ?>, requiring managerial review.
                        <?php else: ?>
                            Performance has remained completely stable compared to last year.
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
                
                <?php if ($strongestCategory && $weakestCategory && $strongestCategory !== $weakestCategory): ?>
                <p class="mb-0 fs-6 text-dark" style="line-height: 1.6;">
                    Analysis of the KPI categories highlights a distinct strength in <strong><?= htmlspecialchars($strongestCategory) ?></strong> (<?= number_format($maxScore, 2) ?>). However, the data reveals repeated underperformance in <strong><?= htmlspecialchars($weakestCategory) ?></strong> (<?= number_format($minScore, 2) ?>). It is highly recommended that upcoming training and development resources be redirected toward this specific weakness.
                </p>
                <?php endif; ?>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card h-100" style="border-top: 4px solid #198754;">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h6 class="fw-bold text-success mb-0"><i class="bi bi-star-fill me-2"></i> Top Performers (Top 5)</h6>
                        </div>
                        <div class="card-body p-0 mt-2">
                            <?php if (empty($topStaffList)): ?>
                                <p class="text-muted text-center py-4 mb-0">No staff met the 4.0 threshold.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach($topStaffList as $staff): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($staff['Name']) ?> (<?= htmlspecialchars($staff['StaffID']) ?>)</strong>
                                                <div class="small text-muted"><?= htmlspecialchars($staff['DepName'] ?? 'Unassigned') ?> Department</div>
                                            </div>
                                            <span class="badge bg-success rounded-pill fs-6"><?= number_format($staff['AvgScore'], 2) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100" style="border-top: 4px solid #dc3545;">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h6 class="fw-bold text-danger mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Action Required (Bottom 5)</h6>
                        </div>
                        <div class="card-body p-0 mt-2">
                            <?php if (empty($atRiskStaffList)): ?>
                                <p class="text-muted text-center py-4 mb-0">No staff are currently at risk.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach($atRiskStaffList as $staff): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($staff['Name']) ?> (<?= htmlspecialchars($staff['StaffID']) ?>)</strong>
                                                <div class="small text-muted"><?= htmlspecialchars($staff['DepName'] ?? 'Unassigned') ?> Department</div>
                                            </div>
                                            <span class="badge bg-danger rounded-pill fs-6"><?= number_format($staff['AvgScore'], 2) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-radar text-primary"></i> Category Analysis</h6>
                        <?php if (count($radarLabels) < 3): ?>
                            <div class="text-center text-muted mt-5"><i class="bi bi-inbox fs-1"></i><p>Not enough categories to draw web.</p></div>
                        <?php else: ?>
                            <div style="position: relative; height: 300px; width: 100%;">
                                <canvas id="radarChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card p-4 h-100">
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-graph-up text-success"></i> KPI Trend Over Time</h6>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-pie-chart-fill text-warning"></i> Staff Distribution</h6>
                        <div style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center; align-items: center;">
                            <canvas id="distributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card p-4 h-100">
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-bar-chart-fill text-info"></i> Company-Wide Department Comparison</h6>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="deptChart"></canvas>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<script>
// Radar chart
<?php if (count($radarLabels) >= 3): ?>
new Chart(document.getElementById('radarChart'), {
    type: 'radar',
    data: {
        labels: <?= json_encode($radarLabels) ?>,
        datasets: [{
            label: 'Average Score',
            data: <?= json_encode($radarScores) ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.2)',
            borderColor: 'rgba(13, 110, 253, 1)',
            pointBackgroundColor: 'rgba(13, 110, 253, 1)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { 
            r: { 
                min: 0, 
                max: 5, 
                ticks: { stepSize: 1 } 
            } 
        },
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>

// Trend line chart
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartTrendLabels) ?>,
        datasets: [{
            label: 'Average Score',
            data: <?= json_encode($chartTrendData) ?>,
            borderColor: '#2c3e50',
            backgroundColor: 'rgba(44, 62, 80, 0.1)',
            borderWidth: 3,
            tension: 0.3, 
            fill: true,
            pointBackgroundColor: '#0d6efd',
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            y: { 
                min: 0, 
                max: 5 
            } 
        }
    }
});

// Performance distribution doughnut
new Chart(document.getElementById('distributionChart'), {
    type: 'doughnut',
    data: {
        labels: ['Top Performers', 'Average', 'At-Risk'],
        datasets: [{
            data: [
                <?= $topPerformers ?>, 
                <?= $totalAnalyzed - $topPerformers - $atRiskStaff ?>, 
                <?= $atRiskStaff ?>
            ],
            backgroundColor: ['#198754', '#e9ecef', '#dc3545'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '75%', 
        plugins: { legend: { position: 'bottom' } }
    }
});

// Department bar chart
new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDeptLabels) ?>,
        datasets: [{
            label: 'Avg Score',
            data: <?= json_encode($chartDeptData) ?>,
            backgroundColor: <?= json_encode($chartDeptData) ?>.map(score => score >= 4 ? '#198754' : (score < 3 ? '#dc3545' : '#6c757d')),
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            y: { 
                min: 0, 
                max: 5 
            } 
        }
    }
});
</script>

</body>
</html>
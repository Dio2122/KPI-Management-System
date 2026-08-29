<?php
// reports.php
require 'db.php';

// Variables from the filter bar
$selectedDept = $_GET['department'] ?? '';
$selectedYear = $_GET['year'] ?? date('Y');
$searchQuery  = trim($_GET['search'] ?? '');

// Fetch aLL staff for the selected department or year
$summaryQuery = "
    SELECT 
        s.StaffID, 
        ROUND(AVG(k.Score), 2) as OverallScore
    FROM Staff s
    LEFT JOIN KPIScore k ON s.StaffID = k.StaffID AND k.Year = ?
    WHERE 1=1
";
$summaryParams = [$selectedYear];

if (!empty($selectedDept)) {
    $summaryQuery .= " AND s.DepID = ?";
    $summaryParams[] = $selectedDept;
}
$summaryQuery .= " GROUP BY s.StaffID";

$summaryStmt = $pdo->prepare($summaryQuery);
$summaryStmt->execute($summaryParams);
$allEmployeesForSummary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

$totalEvaluated = 0;
$sumScores = 0;
$pendingEvals = 0;

// Do the math on all employees
foreach ($allEmployeesForSummary as $emp) {
    if ($emp['OverallScore']) {
        $totalEvaluated++;
        $sumScores += $emp['OverallScore'];
    } else {
        $pendingEvals++;
    }
}

$companyAvg = $totalEvaluated > 0 ? round($sumScores / $totalEvaluated, 2) : 0;

// Safety variables for your HTML
$companyAverage = $companyAvg;
$pendingEvaluations = $pendingEvals;

$tableQuery = "
    SELECT 
        s.StaffID, 
        s.Name, 
        d.DepName,
        ROUND(AVG(k.Score), 2) as OverallScore
    FROM Staff s
    LEFT JOIN Department d ON s.DepID = d.DepID
    LEFT JOIN KPIScore k ON s.StaffID = k.StaffID AND k.Year = ?
    WHERE 1=1
";
$tableParams = [$selectedYear];

// Apply department filter
if (!empty($selectedDept)) {
    $tableQuery .= " AND s.DepID = ?";
    $tableParams[] = $selectedDept;
}

// Apply search filter
if (!empty($searchQuery)) {
    $tableQuery .= " AND (s.Name LIKE ? OR s.StaffID LIKE ?)";
    $searchWildcard = "%" . $searchQuery . "%";
    $tableParams[] = $searchWildcard;
    $tableParams[] = $searchWildcard;
}

$tableQuery .= " GROUP BY s.StaffID, s.Name, d.DepName ORDER BY s.StaffID ASC";

$tableStmt = $pdo->prepare($tableQuery);
$tableStmt->execute($tableParams);

$employees = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; padding-top: 20px; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 10px 20px; margin-bottom: 5px; border-radius: 4px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block sidebar">
            <h4 class="text-center mb-4">KPI System</h4>
            <a href="index.php">Dashboard</a>
            <a href="analytics.php">Analytics</a>
            <a href="reports.php" class="active">Reports</a>
            <a href="staff.php">Staff Directory</a>
            <a href="kpi_management.php">KPI Management</a>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4 border-bottom">
                <h1 class="h2">Evaluation Reports</h1>
                
                <a href="export.php?year=<?= htmlspecialchars($selectedYear) ?>&department=<?= htmlspecialchars($selectedDept) ?>" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Export (<?= htmlspecialchars($selectedYear) ?>)
                </a>
            </div>
			<div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center" style="border-radius: 12px; border-left: 5px solid #0d6efd !important;">
                        <div class="me-3">
                            <i class="bi bi-graph-up-arrow text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 fw-bold" style="font-size: 0.85rem; text-transform: uppercase;">Company Average (<?= htmlspecialchars($selectedYear) ?>)</h6>
                            <h3 class="mb-0 fw-bold <?= $companyAvg >= 4 ? 'text-success' : 'text-primary' ?>"><?= number_format($companyAvg, 2) ?> <span class="fs-6 text-muted">/ 5.0</span></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center" style="border-radius: 12px; border-left: 5px solid #198754 !important;">
                        <div class="me-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 fw-bold" style="font-size: 0.85rem; text-transform: uppercase;">Evaluated Staff</h6>
                            <h3 class="mb-0 fw-bold text-dark"><?= $totalEvaluated ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center" style="border-radius: 12px; border-left: 5px solid #ffc107 !important;">
                        <div class="me-3">
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 fw-bold" style="font-size: 0.85rem; text-transform: uppercase;">Pending Evaluations</h6>
                            <h3 class="mb-0 fw-bold text-dark"><?= $pendingEvals ?></h3>
                        </div>
                    </div>
                </div>
            </div>
			
            <?php include 'filter_bar.php'; ?>

            <div class="card p-4">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Overall Score (<?= htmlspecialchars($selectedYear) ?>)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($employees as $emp): ?>
                        <tr>
                            <td><?= htmlspecialchars($emp['StaffID']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($emp['Name']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($emp['DepName'] ?? 'Unassigned') ?></span></td>
                            
                            <td>
                                <?php if($emp['OverallScore']): ?>
                                    <strong class="<?= $emp['OverallScore'] >= 4 ? 'text-success' : ($emp['OverallScore'] < 3 ? 'text-danger' : '') ?>">
                                        <?= htmlspecialchars($emp['OverallScore']) ?> / 5.0
                                    </strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if($emp['OverallScore']): ?>
                                    <span class="badge bg-success">Evaluated</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($emp['OverallScore']): ?>
                                    <a href="view_report.php?staff_id=<?= $emp['StaffID'] ?>&year=<?= htmlspecialchars($selectedYear) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-text"></i> View Full Report</a>
                                <?php else: ?>
                                    <a href="add_kpi_log.php?staff_id=<?= $emp['StaffID'] ?>" class="btn btn-sm btn-outline-warning">Evaluate Now</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($employees)): ?>
                            <tr><td colspan="6" class="text-center">No staff found matching the filter.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="table_sort.js"></script>

</body>
</html>
</body>
</html>
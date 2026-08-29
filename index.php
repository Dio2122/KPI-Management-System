<?php
// index.php
require 'db.php';

$currentYear = date('Y');

$staffCount = $pdo->query("SELECT COUNT(*) FROM Staff")->fetchColumn();
$kpiCount = $pdo->query("SELECT COUNT(*) FROM KPI")->fetchColumn();

// Calculation for evaluation progress
$evaluatedCountStmt = $pdo->prepare("SELECT COUNT(DISTINCT StaffID) FROM KPIScore WHERE Year = ?");
$evaluatedCountStmt->execute([$currentYear]);
$evaluatedCount = $evaluatedCountStmt->fetchColumn();

$pendingCount = $staffCount - $evaluatedCount;

// Calculate total average KPI
$avgKpiStmt = $pdo->prepare("SELECT AVG(Score) FROM KPIScore WHERE Year = ?");
$avgKpiStmt->execute([$currentYear]);
$companyAvg = $avgKpiStmt->fetchColumn();
$companyAvg = $companyAvg ? round($companyAvg, 2) : 0;

$avgPercentage = ($companyAvg / 5.0) * 100;

// Fetch "Action Required" list
$pendingStaffStmt = $pdo->prepare("
    SELECT s.StaffID, s.Name, d.DepName, p.PosName 
    FROM Staff s
    LEFT JOIN Department d ON s.DepID = d.DepID
    LEFT JOIN `Position` p ON s.PosID = p.PosID
    WHERE s.StaffID NOT IN (SELECT DISTINCT StaffID FROM KPIScore WHERE Year = ?)
    LIMIT 5
");
$pendingStaffStmt->execute([$currentYear]);
$pendingStaff = $pendingStaffStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate department Average
$deptAvgStmt = $pdo->prepare("
    SELECT d.DepName, AVG(ks.Score) as AvgScore
    FROM KPIScore ks
    JOIN Staff s ON ks.StaffID = s.StaffID
    JOIN Department d ON s.DepID = d.DepID
    WHERE ks.Year = ?
    GROUP BY d.DepID, d.DepName
    ORDER BY AvgScore DESC
");
$deptAvgStmt->execute([$currentYear]);
$deptAverages = $deptAvgStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; padding-top: 20px; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 10px 20px; margin-bottom: 5px; border-radius: 4px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.1); }
        .action-btn { padding: 15px; font-size: 1.1rem; border-radius: 10px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block sidebar">
            <h4 class="text-center mb-4">KPI System</h4>
            <a href="index.php" class="active">Dashboard</a>
            <a href="analytics.php">Analytics</a>
            <a href="reports.php">Reports</a>
            <a href="staff.php">Staff Directory</a>
            <a href="kpi_management.php">KPI Management</a>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded shadow-sm" style="border-left: 5px solid #0d6efd;">
                <div>
                    <h2 class="fw-bold mb-1">Welcome back, Admin</h2>
                    <p class="text-muted mb-0">Here is what is happening in your KPI System for <strong><?= $currentYear ?></strong>.</p>
                </div>
                <div class="text-end text-muted">
                    <h5 class="mb-0"><i class="bi bi-calendar3"></i> <?= date('F j, Y') ?></h5>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card p-3 h-100 d-flex flex-row align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                            <i class="bi bi-people-fill fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 text-uppercase" style="font-size: 0.8rem;">Total Staff</h6>
                            <h3 class="fw-bold mb-0"><?= $staffCount ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100 d-flex flex-row align-items-center">
                        <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                            <i class="bi bi-check2-circle fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 text-uppercase" style="font-size: 0.8rem;">Evaluated (<?= $currentYear ?>)</h6>
                            <h3 class="fw-bold mb-0"><?= $evaluatedCount ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100 d-flex flex-row align-items-center">
                        <div class="bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                            <i class="bi bi-hourglass-split fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 text-uppercase" style="font-size: 0.8rem;">Pending Evaluations</h6>
                            <h3 class="fw-bold mb-0"><?= $pendingCount ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 h-100 d-flex flex-row align-items-center">
                        <div class="bg-secondary bg-opacity-10 text-secondary rounded p-3 me-3">
                            <i class="bi bi-ui-checks-grid fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 text-uppercase" style="font-size: 0.8rem;">Active KPIs</h6>
                            <h3 class="fw-bold mb-0"><?= $kpiCount ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-7">
                    <div class="card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-circle-fill"></i> Action Required</h5>
                            <span class="badge bg-danger rounded-pill"><?= count($pendingStaff) ?> Priority</span>
                        </div>
                        <p class="text-muted small">The following staff members have not received their <?= $currentYear ?> performance evaluation yet.</p>
                        
                        <?php if (empty($pendingStaff)): ?>
                            <div class="alert alert-success text-center py-4 mt-3">
                                <i class="bi bi-emoji-smile fs-2 d-block mb-2"></i>
                                <h6 class="mb-0">All caught up!</h6>
                                <p class="mb-0 small">Every staff member has been evaluated for <?= $currentYear ?>.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Staff Name</th>
                                            <th>Department</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($pendingStaff as $emp): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($emp['Name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($emp['PosName'] ?? 'No Position') ?></small>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($emp['DepName'] ?? 'Unassigned') ?></span></td>
                                            <td class="text-end">
                                                <a href="add_kpi_log.php?staff_id=<?= $emp['StaffID'] ?>" class="btn btn-sm btn-outline-success">
                                                    Evaluate Now
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($pendingCount > 5): ?>
                                <div class="text-center mt-2">
                                    <a href="staff.php" class="text-decoration-none small text-primary fw-bold">View all <?= $pendingCount ?> pending staff <i class="bi bi-arrow-right"></i></a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-5">
                    
                    <div class="card p-4 mb-4">
                        <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-graph-up-arrow text-primary"></i> <?= $currentYear ?> Company Average</h5>
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <span class="text-muted fw-bold">Overall Performance</span>
                            <span class="fs-4 fw-bold text-dark"><?= number_format($companyAvg, 2) ?> <span class="fs-6 text-muted">/ 5.0</span></span>
                        </div>
                        <div class="progress" style="height: 12px;">
                            <div class="progress-bar <?= $companyAvg >= 4 ? 'bg-success' : ($companyAvg < 3 ? 'bg-danger' : 'bg-primary') ?>" 
                                 role="progressbar" 
                                 style="width: <?= $avgPercentage ?>%;" 
                                 aria-valuenow="<?= $avgPercentage ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <div class="card p-4">
                        <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-diagram-3-fill text-info"></i> Department Breakdown</h5>
                        
                        <?php if (empty($deptAverages)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                <small>No department data for <?= $currentYear ?> yet.</small>
                            </div>
                        <?php else: ?>
                            <div class="d-grid gap-3">
                                <?php foreach($deptAverages as $dept): 
                                    $score = round($dept['AvgScore'], 2);
                                    $percent = ($score / 5.0) * 100;
                                    // Smart colors: Green if >= 4, Red if < 3, Blue otherwise
                                    $barColor = $score >= 4 ? 'bg-success' : ($score < 3 ? 'bg-danger' : 'bg-primary');
                                ?>
                                    <div>
                                        <div class="d-flex justify-content-between align-items-end mb-1">
                                            <span class="text-muted fw-bold small text-uppercase"><?= htmlspecialchars($dept['DepName']) ?></span>
                                            <span class="fw-bold text-dark small"><?= number_format($score, 2) ?></span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar <?= $barColor ?>" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
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

</body>
</html>
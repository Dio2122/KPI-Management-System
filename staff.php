<?php
// staff.php
require 'db.php';

// Prepare variables based on the filter bar
$selectedDept = $_GET['department'] ?? '';
$searchQuery  = $_GET['search'] ?? '';

$query = "SELECT s.StaffID, s.Name, d.DepName, p.PosName 
          FROM Staff s 
          LEFT JOIN Department d ON s.DepID = d.DepID 
          LEFT JOIN `Position` p ON s.PosID = p.PosID
          WHERE 1=1"; 
$params = [];

// Fetch data for summary cards
$totalStaffStmt = $pdo->query("SELECT COUNT(*) FROM Staff");
$totalStaffCount = $totalStaffStmt->fetchColumn();

$deptCountsStmt = $pdo->query("
    SELECT d.DepName, COUNT(s.StaffID) as Total
    FROM Department d
    LEFT JOIN Staff s ON d.DepID = s.DepID
    GROUP BY d.DepID
    ORDER BY Total DESC
    LIMIT 3
");
$deptCounts = $deptCountsStmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($selectedDept)) {
    $query .= " AND s.DepID = ?";
    $params[] = $selectedDept;
}

if (!empty($searchQuery)) {
    $query .= " AND (s.Name LIKE ? OR s.StaffID = ?)";
    $params[] = "%" . $searchQuery . "%"; 
    $params[] = $searchQuery;
}

$query .= " ORDER BY s.StaffID ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Directory - KPI System</title>
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
            <a href="reports.php">Reports</a>
            <a href="staff.php" class="active">Staff Directory</a>
            <a href="kpi_management.php">KPI Management</a>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4 border-bottom">
                <h1 class="h2">Staff Directory</h1>
                <a href="add_kpi_log.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add KPI Evaluation</a>
            </div>
			<div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center" style="border-radius: 12px;">
                        <div class="bg-primary text-white rounded p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 fw-bold" style="font-size: 0.85rem; text-transform: uppercase;">Total Staff</h6>
                            <h3 class="mb-0 fw-bold text-dark"><?= $totalStaffCount ?></h3>
                        </div>
                    </div>
                </div>
                
                <?php foreach($deptCounts as $dc): ?>
                <div class="col-md-3">
                    <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center" style="border-radius: 12px;">
                        <div class="bg-light text-primary rounded p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-diagram-3-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 fw-bold" style="font-size: 0.85rem; text-transform: uppercase;"><?= htmlspecialchars($dc['DepName']) ?></h6>
                            <h3 class="mb-0 fw-bold text-dark"><?= $dc['Total'] ?></h3>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php 
				$showYear = false;
				include 'filter_bar.php'; 
			?>

            <div class="card p-4">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($employees as $emp): ?>
                        <tr>
                            <td><?= htmlspecialchars($emp['StaffID']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($emp['Name']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($emp['DepName'] ?? 'Unassigned') ?></span></td>
                            <td><?= htmlspecialchars($emp['PosName'] ?? 'Unassigned') ?></td>
                            <td>
                                <a href="profile.php?staff_id=<?= $emp['StaffID'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-person-lines-fill"></i> View Profile
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="staffModal<?= $emp['StaffID'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title"><i class="bi bi-person-badge"></i> <?= htmlspecialchars($emp['Name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <strong>Department:</strong> <?= htmlspecialchars($emp['DepName'] ?? 'N/A') ?><br>
                                            <strong>Position:</strong> <?= htmlspecialchars($emp['PosName'] ?? 'N/A') ?>
                                        </div>
                                        <div class="alert alert-info py-2">
                                            <i class="bi bi-info-circle"></i> To view detailed performance history, go to the <strong>Reports</strong> tab.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <a href="add_kpi_log.php?staff_id=<?= $emp['StaffID'] ?>" class="btn btn-primary btn-sm">Evaluate Staff</a>
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($employees)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No staff found for this department.</td></tr>
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
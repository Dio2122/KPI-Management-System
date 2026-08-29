<?php
// kpi_management.php
require 'db.php';

$message = '';

// KPI deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_kpi'])) {
    $kpiID = $_POST['kpi_id'];
    
    try {
        $delStmt = $pdo->prepare("DELETE FROM KPI WHERE KPIID = ?");
        $delStmt->execute([$kpiID]);
        $message = "<div class='alert alert-success shadow-sm mb-4'><i class='bi bi-check-circle-fill'></i> KPI successfully deleted.</div>";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $message = "<div class='alert alert-danger shadow-sm mb-4'><i class='bi bi-shield-lock-fill'></i> <strong>Action Denied:</strong> You cannot delete this KPI because it is already tied to existing staff evaluations.</div>";
        } else {
            $message = "<div class='alert alert-danger shadow-sm mb-4'><i class='bi bi-exclamation-triangle-fill'></i> Error deleting KPI: " . $e->getMessage() . "</div>";
        }
    }
}

// Fetch all active KPIs
$kpiStmt = $pdo->query("
    SELECT k.KPIID, k.KPICode, k.Description, g.GroupName, g.Weightage, s.SectionName 
    FROM KPI k 
    JOIN KPIGroup g ON k.GroupID = g.GroupID 
    JOIN Section s ON g.SectionID = s.SectionID 
    ORDER BY 
        s.SectionName ASC, 
        CASE WHEN s.SectionName = 'Section 1' THEN 1 ELSE g.GroupID END ASC, 
        LENGTH(k.KPICode) ASC, 
        k.KPICode ASC
");
$kpis = $kpiStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPI Master List - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; padding-top: 20px; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 10px 20px; margin-bottom: 5px; border-radius: 4px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
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
            <a href="staff.php">Staff Directory</a>
            <a href="kpi_management.php" class="active">KPI Management</a>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4 border-bottom">
                <h1 class="h2">KPI Master List</h1>
                <a href="add_kpi.php" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg"></i> Add New KPI</a>
            </div>

            <?= $message ?>

            <div class="card p-4">
                <h5 class="fw-bold mb-4 text-muted"><i class="bi bi-list-ul"></i> Current KPI Master List</h5>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle border mb-0">
                        <tbody>
                            <?php 
                            $currentSection = '';
                            $currentGroup = '';
                            
                            foreach($kpis as $kpi):
                                if ($currentSection != $kpi['SectionName']): 
                                    $currentSection = $kpi['SectionName'];
                            ?>
                                <tr class="table-dark">
                                    <td colspan="3" class="text-uppercase fw-bold text-white fs-6 py-3">
                                        <i class="bi bi-folder2-open me-2"></i> <?= htmlspecialchars($currentSection) ?>
                                    </td>
                                </tr>
                            <?php 
                                endif; 
                                
                                if ($currentGroup != $kpi['GroupName']): 
                                    $currentGroup = $kpi['GroupName'];
                                    $weightPercent = $kpi['Weightage'] * 100;
                                    
                                    $displayGroupName = preg_replace('/ \([0-9]+%\)/', '', $currentGroup);
                            ?>
                                <tr class="table-secondary">
                                    <td colspan="2" class="fw-bold text-dark" style="padding-left: 25px;">
                                        <i class="bi bi-diagram-3 me-2"></i> <?= htmlspecialchars(trim($displayGroupName)) ?>
                                    </td>
                                    <td class="fw-bold text-dark text-end pe-4 text-nowrap" style="width: 15%;">
                                        Weightage: <?= number_format($weightPercent, 0) ?>%
                                    </td>
                                </tr>
                            <?php 
                                endif; 
                            ?>
                                <tr>
                                    <td style="width: 20%; padding-left: 50px;">
                                        <strong><?= htmlspecialchars($kpi['KPICode']) ?></strong>
                                    </td>
                                    <td style="width: 70%;">
                                        <?= htmlspecialchars($kpi['Description']) ?>
                                    </td>
                                    <td style="width: 10%;" class="text-end pe-3">
                                        
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this KPI?');">
                                            <input type="hidden" name="kpi_id" value="<?= $kpi['KPIID'] ?>">
                                            <button type="submit" name="delete_kpi" class="btn btn-sm btn-outline-danger" title="Delete KPI">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>
    </div>
</div>

</body>
</html>
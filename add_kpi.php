<?php
// add_kpi.php
require 'db.php';

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_kpi'])) {
    $kpiCode = trim($_POST['kpi_code']);
    $description = trim($_POST['description']);
    $groupID = $_POST['group_id'];

    if (!empty($kpiCode) && !empty($description) && !empty($groupID)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO KPI (KPICode, Description, GroupID) VALUES (?, ?, ?)");
            $stmt->execute([$kpiCode, $description, $groupID]);
            $message = "<div class='alert alert-success shadow-sm'><i class='bi bi-check-circle-fill'></i> KPI successfully added to the Master List!</div>";
        } catch (PDOException $e) {
            // Check for duplicate KPICode error
            if ($e->getCode() == 23000) {
                $message = "<div class='alert alert-danger shadow-sm'><i class='bi bi-exclamation-triangle-fill'></i> Error: A KPI with that Code already exists.</div>";
            } else {
                $message = "<div class='alert alert-danger shadow-sm'><i class='bi bi-exclamation-triangle-fill'></i> Error saving KPI: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-warning shadow-sm'><i class='bi bi-info-circle-fill'></i> Please fill in all fields.</div>";
    }
}

// Fetch all KPI Groups for the dropdown
$groupStmt = $pdo->query("
    SELECT g.GroupID, g.GroupName, s.SectionName 
    FROM KPIGroup g 
    JOIN Section s ON g.SectionID = s.SectionID 
    ORDER BY s.SectionName ASC, g.GroupName ASC
");
$groups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New KPI - KPI System</title>
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
                <h1 class="h2">Add New KPI</h1>
                <a href="kpi_management.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Master List</a>
            </div>

            <?= $message ?>

            <div class="card p-4 mx-auto mt-4" style="max-width: 600px;">
                <h5 class="fw-bold mb-4 border-bottom pb-3"><i class="bi bi-node-plus text-primary"></i> KPI Details</h5>
                
                <form method="POST" action="">
                    
                    <div class="mb-3">
                        <label for="group_id" class="form-label fw-bold text-muted">Assign to Group <span class="text-danger">*</span></label>
                        <select name="group_id" id="group_id" class="form-select" required>
                            <option value="">-- Select Section & Group --</option>
                            <?php foreach($groups as $group): ?>
                                <option value="<?= htmlspecialchars($group['GroupID']) ?>">
                                    <?= htmlspecialchars($group['SectionName']) ?> &rarr; <?= htmlspecialchars($group['GroupName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">This determines the weightage for this new KPI.</div>
                    </div>

                    <div class="mb-3">
                        <label for="kpi_code" class="form-label fw-bold text-muted">KPI Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="kpi_code" name="kpi_code" placeholder="" required>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-bold text-muted">Metric Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter the metric to be evaluated..." required></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="submit_kpi" class="btn btn-primary btn-lg fw-bold">
                            <i class="bi bi-save"></i> Save New KPI
                        </button>
                    </div>

                </form>
            </div>

        </main>
    </div>
</div>

</body>
</html>
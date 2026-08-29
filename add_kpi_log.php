<?php
// add_kpi_log.php
require 'db.php';

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_evaluation'])) {
    $staffID = $_POST['staff_id'];
    $year = $_POST['year'];
    
    // Capture the feedback fields
    $comment = trim($_POST['comment'] ?? '');
    $recommendation = trim($_POST['recommendation'] ?? '');
    
    try {
        // Ensures scores and feedback both save
        $pdo->beginTransaction();
        
        // Save the numerical KPI Scores
        $insertScoreStmt = $pdo->prepare("INSERT INTO KPIScore (StaffID, KPIID, Year, Score) VALUES (?, ?, ?, ?)");
        
        if(isset($_POST['scores']) && is_array($_POST['scores'])) {
            foreach($_POST['scores'] as $kpiID => $score) {
                if($score !== '') {
                    $insertScoreStmt->execute([$staffID, $kpiID, $year, $score]);
                }
            }
        }
        
        // Save the supervisor comments and recommendations
        if ($comment !== '' || $recommendation !== '') {
            $insertFeedbackStmt = $pdo->prepare("INSERT INTO feedback (StaffID, Year, Comment, Recommendation) VALUES (?, ?, ?, ?)");
            $insertFeedbackStmt->execute([$staffID, $year, $comment, $recommendation]);
        }
        
        $pdo->commit();
        $message = "<div class='alert alert-success'><i class='bi bi-check-circle'></i> Evaluation and feedback for $year saved successfully!</div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'>Error saving evaluation: " . $e->getMessage() . "</div>";
    }
}

// Fetch staff for the dropdown
$staffStmt = $pdo->query("SELECT StaffID, Name FROM Staff ORDER BY Name ASC");
$staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all active KPIs for evaluation
$kpiStmt = $pdo->query("
    SELECT k.KPIID, k.KPICode, k.Description, g.GroupName, s.SectionName 
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

$preselectedStaff = $_GET['staff_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Evaluate Staff - KPI System</title>
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
            <a href="staff.php">Staff Directory</a>
            <a href="kpi_management.php">KPI Management</a>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4 border-bottom">
                <h1 class="h2">Add KPI Evaluation Log</h1>
                <a href="staff.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Staff</a>
            </div>

            <?= $message ?>

            <div class="card p-4 mx-auto" style="max-width: 900px;">
                <form method="POST" action="">
                    
                    <div class="row mb-4 p-3 bg-light rounded border">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Employee</label>
                            <select name="staff_id" class="form-select" required>
                                <option value="">-- Choose Employee --</option>
                                <?php foreach($staffList as $staff): ?>
                                    <option value="<?= $staff['StaffID'] ?>" <?= $preselectedStaff == $staff['StaffID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($staff['Name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Evaluation Year</label>
                            <select name="year" class="form-select" required>
                                <?php 
                                $currentYear = date('Y');
                                for ($y = 2022; $y <= $currentYear + 1; $y++): 
                                ?>
                                    <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary"><i class="bi bi-clipboard-data"></i> Performance Metrics (Score 1-5)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 15%">Code</th>
                                    <th style="width: 65%">Description</th>
                                    <th style="width: 20%" class="text-center">Score (1-5)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $currentSection = '';
                                $currentGroup = '';
                                $printedCompetency = false;
                                
                                foreach($kpis as $kpi): 
                                    if ($currentSection != $kpi['SectionName']): 
                                        $currentSection = $kpi['SectionName'];
                                ?>
                                    <tr class="table-secondary">
                                        <td colspan="3" class="text-uppercase fw-bold text-dark fs-6 py-3">
                                            <i class="bi bi-folder2-open"></i> <?= htmlspecialchars($currentSection) ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endif; 
                                    
                                    if ($currentSection == 'Section 1') {
                                        if (!$printedCompetency) {
                                            echo '
                                            <tr class="table-light">
                                                <td colspan="3" class="fw-bold text-primary" style="padding-left: 25px;">
                                                    <i class="bi bi-diagram-3"></i> Competency
                                                </td>
                                            </tr>';
                                            $printedCompetency = true;
                                        }
                                    } else {
                                        if ($currentGroup != $kpi['GroupName']): 
                                            $currentGroup = $kpi['GroupName'];
                                ?>
                                            <tr class="table-light">
                                                <td colspan="3" class="fw-bold text-primary" style="padding-left: 25px;">
                                                    <i class="bi bi-diagram-3"></i> <?= htmlspecialchars($currentGroup) ?>
                                                </td>
                                            </tr>
                                <?php 
                                        endif;
                                    }
                                ?>
                                <tr>
                                    <td style="padding-left: 40px;"><strong><?= htmlspecialchars($kpi['KPICode']) ?></strong></td>
                                    <td><?= htmlspecialchars($kpi['Description']) ?></td>
                                    <td>
                                        <input type="number" name="scores[<?= $kpi['KPIID'] ?>]" class="form-control text-center mx-auto" min="1" max="5" placeholder="-" style="max-width: 80px;">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5 class="mt-5 mb-3 text-primary"><i class="bi bi-chat-left-text"></i> Supervisor Feedback & Development</h5>
                    <div class="row g-4 bg-light p-4 rounded border mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Supervisor Comments</label>
                            <textarea name="comment" class="form-control" rows="4" placeholder=""></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Training & Development Recommendations</label>
                            <textarea name="recommendation" class="form-control" rows="4" placeholder=""></textarea>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" name="submit_evaluation" class="btn btn-success btn-lg px-5">
                            <i class="bi bi-save"></i> Save Complete Evaluation
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>
</div>
</body>
</html>
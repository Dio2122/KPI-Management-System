<?php
// view_report.php
require 'db.php';

if (!isset($_GET['staff_id']) || !isset($_GET['year'])) {
    die("<div class='alert alert-danger m-4'>Error: Missing Staff ID or Year.</div>");
}

$staffID = $_GET['staff_id'];
$year = $_GET['year'];

// Fetch staff details
$staffStmt = $pdo->prepare("
    SELECT s.Name, d.DepName, p.PosName 
    FROM Staff s 
    LEFT JOIN Department d ON s.DepID = d.DepID 
    LEFT JOIN `Position` p ON s.PosID = p.PosID 
    WHERE s.StaffID = ?
");
$staffStmt->execute([$staffID]);
$staff = $staffStmt->fetch(PDO::FETCH_ASSOC);

// Fetch current year scores
$scoreStmt = $pdo->prepare("
    SELECT k.KPICode, k.Description, g.GroupName, g.Weightage, s.SectionName, ks.Score
    FROM KPIScore ks
    JOIN KPI k ON ks.KPIID = k.KPIID
    JOIN KPIGroup g ON k.GroupID = g.GroupID
    JOIN Section s ON g.SectionID = s.SectionID
    WHERE ks.StaffID = ? AND ks.Year = ?
    ORDER BY 
        s.SectionName ASC, 
        CASE WHEN s.SectionName = 'Section 1' THEN 1 ELSE g.GroupID END ASC, 
        LENGTH(k.KPICode) ASC, 
        k.KPICode ASC
");
$scoreStmt->execute([$staffID, $year]);
$rawScores = $scoreStmt->fetchAll(PDO::FETCH_ASSOC);

$reportData = ['Section 1' => [], 'Section 2' => []];
foreach ($rawScores as $row) {
    $sec = $row['SectionName'];
    $grp = $row['GroupName'];
    if (!isset($reportData[$sec][$grp])) {
        $reportData[$sec][$grp] = ['weightage' => $row['Weightage'], 'kpis' => []];
    }
    $reportData[$sec][$grp]['kpis'][] = $row;
}

// Fetch feedback
$feedbackStmt = $pdo->prepare("SELECT Comment, Recommendation FROM feedback WHERE StaffID = ? AND Year = ?");
$feedbackStmt->execute([$staffID, $year]);
$feedback = $feedbackStmt->fetch(PDO::FETCH_ASSOC);

// Fetch previous year data for growth calculation
$prevYear = $year - 1;
$prevStmt = $pdo->prepare("
    SELECT g.GroupName, g.Weightage, s.SectionName, ks.Score
    FROM KPIScore ks
    JOIN KPI k ON ks.KPIID = k.KPIID
    JOIN KPIGroup g ON k.GroupID = g.GroupID
    JOIN Section s ON g.SectionID = s.SectionID
    WHERE ks.StaffID = ? AND ks.Year = ?
");
$prevStmt->execute([$staffID, $prevYear]);
$prevRawScores = $prevStmt->fetchAll(PDO::FETCH_ASSOC);

$prevScore = null;

if (count($prevRawScores) > 0) {
    $pSec1Total = 0;
    $pSec2Groups = [];
    
    foreach ($prevRawScores as $row) {
        if ($row['SectionName'] == 'Section 1') {
            $pSec1Total += $row['Score'] * $row['Weightage'];
        } else {
            if (!isset($pSec2Groups[$row['GroupName']])) {
                $pSec2Groups[$row['GroupName']] = ['sum' => 0, 'count' => 0, 'weight' => $row['Weightage']];
            }
            $pSec2Groups[$row['GroupName']]['sum'] += $row['Score'];
            $pSec2Groups[$row['GroupName']]['count']++;
        }
    }
    
    $pSec2Total = 0;
    foreach ($pSec2Groups as $g) {
        $pSec2Total += ($g['sum'] / $g['count']) * $g['weight'];
    }
    $prevScore = $pSec1Total + $pSec2Total;
}

$section1Total = 0;
$section2Total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Performance Report: <?= htmlspecialchars($staff['Name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-custom { border-collapse: collapse; width: 100%; margin-bottom: 20px; background-color: white; border: 1px solid #dee2e6; }
        .table-custom th, .table-custom td { border: 1px solid #dee2e6; padding: 10px 12px; vertical-align: middle; }
        .header-blue { background-color: #1f4e79 !important; color: white !important; font-weight: bold; }
        .header-light { background-color: #e9ecef !important; font-weight: bold; text-align: center; }
        .total-yellow { background-color: #fff2cc !important; font-weight: bold; }
        .total-green { background-color: #e2efda !important; font-weight: bold; font-size: 1.1rem; }
        .center-text { text-align: center; }
        @media print {
            .no-print { display: none !important; }
            body { background-color: white; }
            .card { box-shadow: none; border: none; }
            * { -webkit-print-color-adjust: exact !important; color-adjust: exact !important; }
        }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 1000px;">
    
    <div class="d-flex justify-content-between mb-4 no-print">
        <a href="reports.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Reports</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print Report</button>
    </div>

    <div class="card p-4 p-md-5 mb-4">
        
        <div class="mb-4">
            <h3 class="text-uppercase fw-bold mb-0">Annual Performance Evaluation</h3>
            <p class="text-muted mb-3">Evaluation Year: <strong><?= htmlspecialchars($year) ?></strong></p>
            
            <table class="table table-bordered table-sm w-50">
                <tr><td class="bg-light fw-bold" style="width: 30%;">Name</td><td><?= htmlspecialchars($staff['Name']) ?></td></tr>
                <tr><td class="bg-light fw-bold">Department</td><td><?= htmlspecialchars($staff['DepName'] ?? '-') ?></td></tr>
                <tr><td class="bg-light fw-bold">Position</td><td><?= htmlspecialchars($staff['PosName'] ?? '-') ?></td></tr>
            </table>
        </div>

        <table class="table-custom">
            <thead>
                <tr><td colspan="4" class="header-blue">SECTION 1: Core Competencies (25%)</td></tr>
                <tr>
                    <th class="header-light" style="width: 40%; text-align: left;">Competency</th>
                    <th class="header-light" style="width: 20%;">Weight</th>
                    <th class="header-light" style="width: 20%;">Score (1-5)</th>
                    <th class="header-light" style="width: 20%;">Weighted Score</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (isset($reportData['Section 1'])) {
                    foreach ($reportData['Section 1'] as $groupName => $groupData) {
                        foreach ($groupData['kpis'] as $kpi) {
                            $weightPercent = $groupData['weightage'] * 100;
                            $weightedScore = $kpi['Score'] * $groupData['weightage'];
                            $section1Total += $weightedScore;
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($kpi['Description']) . "</td>";
                            echo "<td class='center-text'>" . number_format($weightPercent, 1) . "%</td>";
                            echo "<td class='center-text'>" . htmlspecialchars($kpi['Score']) . ".0</td>";
                            echo "<td class='center-text'>" . number_format($weightedScore, 2) . "</td>";
                            echo "</tr>";
                        }
                    }
                }
                ?>
                <tr>
                    <td colspan="3" class="total-yellow">Section 1 Total</td>
                    <td class="total-yellow center-text"><?= number_format($section1Total, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <table class="table-custom">
            <thead>
                <tr><td colspan="6" class="header-blue">SECTION 2: KPI Achievement (75%)</td></tr>
                <tr>
                    <th class="header-light" style="width: 20%;">KPI Group</th>
                    <th class="header-light" style="width: 35%;">Measurable Target</th>
                    <th class="header-light" style="width: 10%;">Rating (1-5)</th>
                    <th class="header-light" style="width: 12.5%;">Overall KPI Score (avg)</th>
                    <th class="header-light" style="width: 10%;">Weight</th>
                    <th class="header-light" style="width: 12.5%;">Weighted Score</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (isset($reportData['Section 2'])) {
                    foreach ($reportData['Section 2'] as $groupName => $groupData) {
                        $kpiCount = count($groupData['kpis']);
                        $groupScoreSum = 0;
                        
                        foreach ($groupData['kpis'] as $kpi) { $groupScoreSum += $kpi['Score']; }
                        $avgScore = $kpiCount > 0 ? ($groupScoreSum / $kpiCount) : 0;
                        
                        $weightPercent = $groupData['weightage'] * 100;
                        $weightedScore = $avgScore * $groupData['weightage'];
                        $section2Total += $weightedScore;

                        $isFirstRow = true;
                        foreach ($groupData['kpis'] as $kpi) {
                            echo "<tr>";
                            if ($isFirstRow) echo "<td rowspan='{$kpiCount}'>" . htmlspecialchars($groupName) . "</td>";
                            echo "<td>" . htmlspecialchars($kpi['Description']) . "</td>";
                            echo "<td class='center-text'>" . htmlspecialchars($kpi['Score']) . "</td>";
                            if ($isFirstRow) {
                                echo "<td rowspan='{$kpiCount}' class='center-text'>" . number_format($avgScore, 2) . "</td>";
                                echo "<td rowspan='{$kpiCount}' class='center-text'>" . number_format($weightPercent, 1) . "%</td>";
                                echo "<td rowspan='{$kpiCount}' class='center-text'>" . number_format($weightedScore, 2) . "</td>";
                                $isFirstRow = false;
                            }
                            echo "</tr>";
                        }
                    }
                }
                ?>
                <tr>
                    <td colspan="5" class="total-yellow">Section 2 Total</td>
                    <td class="total-yellow center-text"><?= number_format($section2Total, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <?php $finalScore = $section1Total + $section2Total; ?>
        <table class="table-custom mb-5">
            <tr>
                <td class="total-green d-flex align-items-center" style="width: 87.5%; text-transform: uppercase; border-right: none;">
                    Final Performance Score (1-5 Scale)
                    
                    <?php if ($prevScore !== null): ?>
                        <?php 
                        $diff = $finalScore - $prevScore; 
                        if ($diff > 0): 
                        ?>
                            <span class="badge bg-success ms-3 shadow-sm" style="font-size: 0.85rem;"><i class="bi bi-graph-up-arrow"></i> +<?= number_format($diff, 2) ?> vs <?= $prevYear ?></span>
                        <?php elseif ($diff < 0): ?>
                            <span class="badge bg-danger ms-3 shadow-sm" style="font-size: 0.85rem;"><i class="bi bi-graph-down-arrow"></i> <?= number_format($diff, 2) ?> vs <?= $prevYear ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-3 shadow-sm" style="font-size: 0.85rem;"><i class="bi bi-dash-circle"></i> Maintained vs <?= $prevYear ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-3 shadow-sm" style="font-size: 0.85rem;"><i class="bi bi-info-circle"></i> First Year Data</span>
                    <?php endif; ?>

                </td>
                <td class="total-green center-text" style="width: 12.5%;"><?= number_format($finalScore, 2) ?></td>
            </tr>
        </table>

        <h5 class="mb-3 border-bottom pb-2 fw-bold text-uppercase" style="color: #1f4e79;">Supervisor Feedback & Development</h5>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="p-3 rounded border h-100" style="background-color: #f8f9fa;">
                    <h6 class="fw-bold text-dark border-bottom pb-2">Overall Comments</h6>
                    <p class="mb-0 text-dark mt-2" style="white-space: pre-wrap; font-size: 0.95rem;"><?= htmlspecialchars($feedback['Comment'] ?? 'No comments provided.') ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 rounded border h-100" style="background-color: #f8f9fa;">
                    <h6 class="fw-bold text-dark border-bottom pb-2">Training & Recommendations</h6>
                    <p class="mb-0 text-dark mt-2" style="white-space: pre-wrap; font-size: 0.95rem;"><?= htmlspecialchars($feedback['Recommendation'] ?? 'No recommendations provided.') ?></p>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>
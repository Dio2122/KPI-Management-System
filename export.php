<?php
// export.php
require 'db.php';

// Gather the filters
$selectedDept = $_GET['department'] ?? '';
$selectedYear = $_GET['year'] ?? date('Y');
$searchQuery  = $_GET['search'] ?? '';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="KPI_Performance_Report_' . $selectedYear . '.csv"');

// Open the "output" stream
$output = fopen('php://output', 'w');

// Write the column headers for the Excel file
fputcsv($output, ['Staff ID', 'Name', 'Department', 'Position', 'Overall Score', 'Status', 'Supervisor Comments', 'Recommendations']);

// Build the smart query
$query = "
    SELECT 
        s.StaffID, 
        s.Name, 
        d.DepName,
        p.PosName,
        ROUND(AVG(k.Score), 2) as OverallScore,
        f.Comment,
        f.Recommendation
    FROM Staff s
    LEFT JOIN Department d ON s.DepID = d.DepID
    LEFT JOIN `Position` p ON s.PosID = p.PosID
    LEFT JOIN KPIScore k ON s.StaffID = k.StaffID AND k.Year = ?
    LEFT JOIN feedback f ON s.StaffID = f.StaffID AND f.Year = ?
    WHERE 1=1
";
$params = [$selectedYear, $selectedYear];

// Apply department filter
if (!empty($selectedDept)) {
    $query .= " AND s.DepID = ?";
    $params[] = $selectedDept;
}

// Apply search filter
if (!empty($searchQuery)) {
    $query .= " AND (s.Name LIKE ? OR s.StaffID = ?)";
    $params[] = "%" . $searchQuery . "%";
    $params[] = $searchQuery;
}

// Group everything to get one row per staff member
$query .= " GROUP BY s.StaffID, s.Name, d.DepName, p.PosName, f.Comment, f.Recommendation ORDER BY s.Name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);

// Loop through the database results and write them to the CSV
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Determine Status
    if ($row['OverallScore']) {
        $status = 'Evaluated';
        $score = $row['OverallScore'];
    } else {
        $status = 'Pending';
        $score = '-';
    }

    // Write the row to the file
    fputcsv($output, [
        $row['StaffID'],
        $row['Name'],
        $row['DepName'] ?? 'Unassigned',
        $row['PosName'] ?? 'Unassigned',
        $score,
        $status,
        $row['Comment'] ?? 'No comments.',
        $row['Recommendation'] ?? 'No recommendations.'
    ]);
}

// Close the file stream
fclose($output);
exit();
?>
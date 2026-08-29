<?php
// filter_bar.php

// Check parent page instructions
$showSearch = isset($showSearch) ? $showSearch : true;
$showYear = isset($showYear) ? $showYear : true;

// Grid math for Bootstrap's 12-column layout
if ($showSearch && $showYear) {
    $searchCol = 'col-md-4';
    $deptCol = 'col-md-3';
    $yearCol = 'col-md-3';
} elseif (!$showSearch && $showYear) {
    // If no search, stretch Dept and Year
    $deptCol = 'col-md-5';
    $yearCol = 'col-md-5';
} elseif ($showSearch && !$showYear) {
    // If no year, stretch Search and Dept
    $searchCol = 'col-md-5';
    $deptCol = 'col-md-5';
} else {
    // If both are hidden, let Dept take up all the space
    $deptCol = 'col-md-10';
}

// Fetch departments for the dropdown
$deptStmt = $pdo->query("SELECT * FROM Department ORDER BY DepName ASC");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct years
if ($showYear) {
    $yearStmt = $pdo->query("SELECT DISTINCT Year FROM KPIScore ORDER BY Year DESC");
    $years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);
    if(empty($years)) { 
        $years = [['Year' => date('Y')], ['Year' => date('Y') - 1]]; 
    }
}

// Get current selected filters from the URL
$selectedDept = $_GET['department'] ?? '';
$selectedYear = $_GET['year'] ?? date('Y');
$searchQuery  = $_GET['search'] ?? ''; 
?>

<div class="bg-white p-3 rounded mb-4 shadow-sm" style="border: 1px solid #e9ecef;">
    <form method="GET" action="">
        <div class="row align-items-end g-2">
            
            <?php if ($showSearch): ?>
            <div class="<?= $searchCol ?>">
                <label for="search" class="form-label text-muted fw-bold mb-1"><i class="bi bi-search"></i> Search Staff</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search by Name or ID..." value="<?= htmlspecialchars($searchQuery) ?>">
            </div>
            <?php endif; ?>

            <div class="<?= $deptCol ?>">
                <label for="department" class="form-label text-muted fw-bold mb-1"><i class="bi bi-building"></i> Department</label>
                <select class="form-select" id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['DepID']) ?>" <?= $selectedDept == $dept['DepID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['DepName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($showYear): ?>
            <div class="<?= $yearCol ?>">
                <label for="year" class="form-label text-muted fw-bold mb-1"><i class="bi bi-calendar"></i> Year</label>
                <select class="form-select" id="year" name="year">
                    <?php foreach($years as $y): ?>
                        <option value="<?= htmlspecialchars($y['Year']) ?>" <?= $selectedYear == $y['Year'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($y['Year']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <?= $showSearch ? 'Search' : 'Apply Filters' ?>
                </button>
            </div>
            
        </div>
    </form>
</div>
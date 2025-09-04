<?php
// view.php - Read-Only Building Finance Report
session_start();

// Configuration
$dataFile = 'building_data.json';
$floors = ['أرضي', 'أول', 'ثاني', 'ثالث', 'رابع', 'خامس'];
$apartments = [1, 2, 3];

// Load data from JSON file
function loadData($file, $default = []) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return $data ? $data : $default;
    }
    return $default;
}

// Load existing data
$buildingData = loadData($dataFile, [
    'balance' => 0,
    'payments' => [],
    'expenses' => [],
    'history' => [],
    'apartments' => [],
    'apartment_accounts' => []
]);

// Get current month and year
$currentYear = $_GET['year'] ?? date('Y');
$currentMonthNum = $_GET['month'] ?? date('m');
$currentMonth = $currentYear . '-' . str_pad($currentMonthNum, 2, '0', STR_PAD_LEFT);

// Calculate total statistics
$totalIncome = 0;
$totalExpensesAll = 0;

foreach ($buildingData['payments'] as $monthData) {
    foreach ($monthData as $payment) {
        if ($payment['paid']) $totalIncome += $payment['amount'];
    }
}

foreach ($buildingData['expenses'] as $monthExpenses) {
    foreach ($monthExpenses as $expense) {
        $totalExpensesAll += $expense['amount'];
    }
}

$finalBalance = $totalIncome - $totalExpensesAll;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير مالية العمارة - عرض فقط</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" type="text/css" />
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
        }
    </style>
</head>
<body class="bg-base-200">
    <!-- Header -->
    <div class="navbar bg-primary text-primary-content no-print">
        <div class="flex-1">
            <h1 class="btn btn-ghost text-xl">📊 تقرير مالية العمارة</h1>
        </div>
        <div class="flex-none gap-2">
            <div class="stat">
                <div class="stat-title text-primary-content/70">الرصيد الحالي</div>
                <div class="stat-value text-lg"><?= number_format($buildingData['balance']) ?> جنيه</div>
            </div>
            <!-- <button onclick="window.print()" class="btn btn-accent">🖨️ طباعة</button> -->
        </div>
    </div>

    <div class="container mx-auto p-4 max-w-7xl">
        <!-- Date and Navigation -->
        <div class="card bg-base-100 shadow-xl mb-6 no-print">
            <div class="card-body">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <select class="select select-md select-bordered" onchange="changeYear(this.value)">
                            <?php
                            for ($year = 2023; $year <= 2030; $year++) {
                                $selected = ($year == $currentYear) ? 'selected' : '';
                                echo "<option value='{$year}' {$selected}>{$year}</option>";
                            }
                            ?>
                        </select>
                        <select class="select select-bordered" onchange="changeMonth(this.value)">
                            <?php
                            $months = [
                                '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس',
                                '04' => 'إبريل', '05' => 'مايو', '06' => 'يونيو',
                                '07' => 'يوليو', '08' => 'أغسطس', '09' => 'سبتمبر',
                                '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر'
                            ];
                            foreach ($months as $value => $text) {
                                $selected = ($value === $currentMonthNum) ? 'selected' : '';
                                echo "<option value='{$value}' {$selected}>{$text}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="text-lg font-bold">
                        تقرير <?= $months[$currentMonthNum] ?> <?= $currentYear ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Summary -->
        <div class="stats shadow w-full mb-6">
            <div class="stat">
                <div class="stat-figure text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="stat-title">إجمالي الإيرادات</div>
                <div class="stat-value text-success"><?= number_format($totalIncome) ?></div>
                <div class="stat-desc">جنيه مصري</div>
            </div>
            
            <div class="stat">
                <div class="stat-figure text-error">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                </div>
                <div class="stat-title">إجمالي المصاريف</div>
                <div class="stat-value text-error"><?= number_format($totalExpensesAll) ?></div>
                <div class="stat-desc">جنيه مصري</div>
            </div>
            
            <div class="stat">
                <div class="stat-figure text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
                <div class="stat-title">الرصيد الإجمالي</div>
                <div class="stat-value text-primary"><?= number_format($finalBalance) ?></div>
                <div class="stat-desc">جنيه مصري</div>
            </div>
        </div>

        <!-- Monthly Payments Report -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">💰 تقرير مدفوعات <?= $months[$currentMonthNum] ?> <?= $currentYear ?></h2>
                
                <?php
                $monthPayments = $buildingData['payments'][$currentMonth] ?? [];
                $totalRequired = 0;
                $totalCollected = 0;
                
                // Calculate totals
                foreach ($floors as $floorIndex => $floor) {
                    foreach ($apartments as $apt) {
                        $apartmentId = "{$floorIndex}-{$apt}";
                        $aptConfig = $buildingData['apartments'][$apartmentId] ?? ['monthly_payment' => 200, 'active' => true];
                        
                        if ($aptConfig['active']) {
                            $totalRequired += $aptConfig['monthly_payment'];
                        }
                    }
                }
                
                foreach ($monthPayments as $payment) {
                    if ($payment['paid']) $totalCollected += $payment['amount'];
                }
                $totalRemaining = $totalRequired - $totalCollected;
                ?>
                
                <!-- Monthly Summary -->
                <div class="stats shadow mb-4">
                    <div class="stat">
                        <div class="stat-title">المطلوب</div>
                        <div class="stat-value text-info"><?= number_format($totalRequired) ?></div>
                        <div class="stat-desc">جنيه</div>
                    </div>
                    <div class="stat">
                        <div class="stat-title">المحصل</div>
                        <div class="stat-value text-success"><?= number_format($totalCollected) ?></div>
                        <div class="stat-desc">جنيه</div>
                    </div>
                    <div class="stat">
                        <div class="stat-title">المتبقي</div>
                        <div class="stat-value text-warning"><?= number_format($totalRemaining) ?></div>
                        <div class="stat-desc">جنيه</div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>الدور</th>
                                <th>رقم الشقة</th>
                                <th>اسم المالك</th>
                                <th>المبلغ المطلوب</th>
                                <th>حالة الدفع</th>
                                <th>تاريخ الدفع</th>
                                <th>رصيد الحساب</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($floors as $floorIndex => $floor): ?>
                                <?php foreach ($apartments as $apt): ?>
                                    <?php
                                    $apartmentId = "{$floorIndex}-{$apt}";
                                    $payment = $monthPayments[$apartmentId] ?? null;
                                    $isPaid = $payment && $payment['paid'];
                                    
                                    $aptConfig = $buildingData['apartments'][$apartmentId] ?? [
                                        'name' => '', 
                                        'monthly_payment' => 200, 
                                        'active' => true
                                    ];
                                    
                                    $accountBalance = $buildingData['apartment_accounts'][$apartmentId] ?? 0;
                                    
                                    if (!$aptConfig['active']) {
                                        echo "<tr class='opacity-50'>";
                                        echo "<td>{$floor}</td>";
                                        echo "<td>{$apt}</td>";
                                        echo "<td class='text-gray-500'>شقة فارغة</td>";
                                        echo "<td class='text-gray-500'>-</td>";
                                        echo "<td><div class='badge badge-neutral'>غير نشط</div></td>";
                                        echo "<td>-</td>";
                                        echo "<td>-</td>";
                                        echo "</tr>";
                                        continue;
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $floor ?></td>
                                        <td><?= $apt ?></td>
                                        <td><?= htmlspecialchars($aptConfig['name'] ?: 'غير محدد') ?></td>
                                        <td><?= number_format($aptConfig['monthly_payment']) ?> جنيه</td>
                                        <td>
                                            <div class="badge <?= $isPaid ? 'badge-success' : 'badge-error' ?>">
                                                <?= $isPaid ? '✅ تم الدفع' : '❌ لم يتم الدفع' ?>
                                            </div>
                                        </td>
                                        <td><?= $isPaid ? $payment['date'] : '-' ?></td>
                                        <td>
                                            <span class="font-bold <?= $accountBalance > 0 ? 'text-success' : ($accountBalance < 0 ? 'text-error' : '') ?>">
                                                <?= number_format($accountBalance) ?> جنيه
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Monthly Expenses Report -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">📋 مصاريف <?= $months[$currentMonthNum] ?> <?= $currentYear ?></h2>
                
                <?php
                $monthExpenses = $buildingData['expenses'][$currentMonth] ?? [];
                $totalExpenses = 0;
                ?>
                
                <?php if (empty($monthExpenses)): ?>
                    <div class="alert alert-info">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>لا توجد مصاريف مسجلة لهذا الشهر</span>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الوصف</th>
                                    <th>النوع</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthExpenses as $expense): ?>
                                    <?php $totalExpenses += $expense['amount']; ?>
                                    <tr>
                                        <td><?= $expense['date'] ?></td>
                                        <td><?= htmlspecialchars($expense['description']) ?></td>
                                        <td>
                                            <?php
                                            $types = [
                                                'water' => '💧 مياه',
                                                'electricity' => '⚡ كهرباء',
                                                'garage' => '🚗 جراج',
                                                'stairs' => '🧹 سلالم',
                                                'elevator' => '🛗 أسانسير',
                                                'other' => '📝 أخرى'
                                            ];
                                            echo $types[$expense['type']] ?? '📝 أخرى';
                                            ?>
                                        </td>
                                        <td class="font-bold text-error"><?= number_format($expense['amount']) ?> جنيه</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-base-200">
                                    <th colspan="3">إجمالي المصاريف:</th>
                                    <th class="text-error font-bold text-lg"><?= number_format($totalExpenses) ?> جنيه</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Apartment Accounts Summary -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">💳 ملخص حسابات الشقق</h2>
                
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>الشقة</th>
                                <th>اسم المالك</th>
                                <th>المبلغ الشهري</th>
                                <th>رصيد الحساب</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalPositiveBalance = 0;
                            $totalNegativeBalance = 0;
                            ?>
                            <?php foreach ($floors as $floorIndex => $floor): ?>
                                <?php foreach ($apartments as $apt): ?>
                                    <?php
                                    $apartmentId = "{$floorIndex}-{$apt}";
                                    $aptConfig = $buildingData['apartments'][$apartmentId] ?? [
                                        'name' => '', 
                                        'monthly_payment' => 200, 
                                        'active' => true
                                    ];
                                    $accountBalance = $buildingData['apartment_accounts'][$apartmentId] ?? 0;
                                    
                                    if ($accountBalance > 0) $totalPositiveBalance += $accountBalance;
                                    if ($accountBalance < 0) $totalNegativeBalance += abs($accountBalance);
                                    ?>
                                    <tr class="<?= !$aptConfig['active'] ? 'opacity-50' : '' ?>">
                                        <td><?= $floor ?> - <?= $apt ?></td>
                                        <td><?= htmlspecialchars($aptConfig['name'] ?: 'غير محدد') ?></td>
                                        <td><?= $aptConfig['active'] ? number_format($aptConfig['monthly_payment']) . ' جنيه' : 'غير نشط' ?></td>
                                        <td>
                                            <span class="font-bold <?= $accountBalance > 0 ? 'text-success' : ($accountBalance < 0 ? 'text-error' : '') ?>">
                                                <?= number_format($accountBalance) ?> جنيه
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$aptConfig['active']): ?>
                                                <div class="badge badge-neutral">غير نشط</div>
                                            <?php elseif ($accountBalance > 0): ?>
                                                <div class="badge badge-success">رصيد إضافي</div>
                                            <?php elseif ($accountBalance < 0): ?>
                                                <div class="badge badge-error">عليه مستحقات</div>
                                            <?php else: ?>
                                                <div class="badge badge-ghost">متوازن</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-base-200">
                                <th colspan="2">الإجمالي:</th>
                                <th>-</th>
                                <th>
                                    <span class="text-success">+<?= number_format($totalPositiveBalance) ?></span> / 
                                    <span class="text-error">-<?= number_format($totalNegativeBalance) ?></span>
                                </th>
                                <th>جنيه</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Transaction History -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">📅 آخر العمليات المالية</h2>
                
                <?php
                $recentHistory = array_slice(array_reverse($buildingData['history']), 0, 20);
                ?>
                
                <?php if (empty($recentHistory)): ?>
                    <div class="alert alert-info">
                        <span>لا توجد عمليات مسجلة</span>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الوصف</th>
                                    <th>النوع</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentHistory as $item): ?>
                                    <tr>
                                        <td><?= $item['date'] ?></td>
                                        <td><?= htmlspecialchars($item['description']) ?></td>
                                        <td>
                                            <div class="badge <?= $item['type'] === 'income' ? 'badge-success' : 'badge-error' ?>">
                                                <?= $item['type'] === 'income' ? '📈 إيراد' : '📉 مصروف' ?>
                                            </div>
                                        </td>
                                        <td class="font-bold <?= $item['type'] === 'income' ? 'text-success' : 'text-error' ?>">
                                            <?= $item['type'] === 'income' ? '+' : '-' ?><?= number_format($item['amount']) ?> جنيه
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center text-base-content/60 py-4">
            <p>تم إنشاء التقرير في: <?= date('Y-m-d H:i:s') ?></p>
            <p>نظام إدارة مالية العمارة</p>
        </div>
    </div>

    <script>
        function changeMonth(month) {
            const year = '<?= $currentYear ?>';
            window.location.href = '?year=' + year + '&month=' + month;
        }

        function changeYear(year) {
            const month = '<?= $currentMonthNum ?>';
            window.location.href = '?year=' + year + '&month=' + month;
        }
    </script>
</body>
</html>
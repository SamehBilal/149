<?php
// index.php
session_start();

// Configuration
$dataFile = 'building_data.json';
$floors = ['أرضي', 'أول', 'ثاني', 'ثالث', 'رابع', 'خامس'];
$apartments = [1, 2, 3];

// Custom apartment configurations
$apartmentConfig = [
    '0-1' => ['name' => '', 'monthly_payment' => 100, 'active' => true],  // أرضي رقم 1 - 100 جنيه
    '0-2' => ['name' => '', 'monthly_payment' => 0, 'active' => false],   // أرضي رقم 2 - فارغة
    '0-3' => ['name' => '', 'monthly_payment' => 200, 'active' => true],  // باقي الشقق - 200 جنيه
    '1-1' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '1-2' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '1-3' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '2-1' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '2-2' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '2-3' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '3-1' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '3-2' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '3-3' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '4-1' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '4-2' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '4-3' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '5-1' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '5-2' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
    '5-3' => ['name' => '', 'monthly_payment' => 200, 'active' => true],
];

// Initialize data structure
$defaultData = [
    'balance' => 0,
    'payments' => [],
    'expenses' => [],
    'history' => [],
    'apartments' => $apartmentConfig,
    'apartment_accounts' => []
];

// Load data from JSON file
function loadData($file, $default) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return $data ? $data : $default;
    }
    return $default;
}

// Save data to JSON file
function saveData($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Load existing data
$buildingData = loadData($dataFile, $defaultData);

// Ensure apartment configurations exist
if (!isset($buildingData['apartments'])) {
    $buildingData['apartments'] = $apartmentConfig;
    saveData($dataFile, $buildingData);
}

// Ensure apartment accounts exist
if (!isset($buildingData['apartment_accounts'])) {
    $buildingData['apartment_accounts'] = [];
    saveData($dataFile, $buildingData);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'mark_paid':
            $month = $_POST['month'];
            $apartmentId = $_POST['apartment_id'];
            $floor = $_POST['floor'];
            $apt = $_POST['apartment'];
            $date = date('Y-m-d');
            
            // Get apartment payment amount
            $paymentAmount = $buildingData['apartments'][$apartmentId]['monthly_payment'] ?? 200;
            
            if (!isset($buildingData['payments'][$month])) {
                $buildingData['payments'][$month] = [];
            }
            
            $buildingData['payments'][$month][$apartmentId] = [
                'paid' => true,
                'amount' => $paymentAmount,
                'date' => $date,
                'floor' => $floor,
                'apartment' => $apt
            ];
            
            $buildingData['balance'] += $paymentAmount;
            
            $buildingData['history'][] = [
                'type' => 'income',
                'description' => "دفع شقة {$apt} - {$floor}",
                'amount' => $paymentAmount,
                'date' => $date,
                'timestamp' => time()
            ];
            
            if (saveData($dataFile, $buildingData)) {
                $response = ['success' => true, 'message' => 'تم تسجيل الدفع بنجاح'];
            }
            break;
            
        case 'mark_unpaid':
            $month = $_POST['month'];
            $apartmentId = $_POST['apartment_id'];
            
            if (isset($buildingData['payments'][$month][$apartmentId])) {
                $paymentAmount = $buildingData['payments'][$month][$apartmentId]['amount'];
                $buildingData['balance'] -= $paymentAmount;
                unset($buildingData['payments'][$month][$apartmentId]);
                
                if (saveData($dataFile, $buildingData)) {
                    $response = ['success' => true, 'message' => 'تم إلغاء الدفع'];
                }
            }
            break;
            
        case 'add_expense':
            $month = $_POST['month'];
            $type = $_POST['expense_type'];
            $amount = floatval($_POST['amount']);
            $description = $_POST['description'] ?: getExpenseTypeName($type);
            $date = $_POST['date'] ?: date('Y-m-d');
            
            if ($amount > 0) {
                $expense = [
                    'id' => time() . rand(1000, 9999),
                    'type' => $type,
                    'amount' => $amount,
                    'description' => $description,
                    'date' => $date
                ];
                
                if (!isset($buildingData['expenses'][$month])) {
                    $buildingData['expenses'][$month] = [];
                }
                
                $buildingData['expenses'][$month][] = $expense;
                $buildingData['balance'] -= $amount;
                
                $buildingData['history'][] = [
                    'type' => 'expense',
                    'description' => $description,
                    'amount' => $amount,
                    'date' => $date,
                    'timestamp' => time()
                ];
                
                if (saveData($dataFile, $buildingData)) {
                    $response = ['success' => true, 'message' => 'تم إضافة المصروف بنجاح'];
                }
            }
            break;
            
        case 'remove_expense':
            $month = $_POST['month'];
            $expenseId = $_POST['expense_id'];
            
            if (isset($buildingData['expenses'][$month])) {
                foreach ($buildingData['expenses'][$month] as $key => $expense) {
                    if ($expense['id'] == $expenseId) {
                        $buildingData['balance'] += $expense['amount'];
                        unset($buildingData['expenses'][$month][$key]);
                        $buildingData['expenses'][$month] = array_values($buildingData['expenses'][$month]);
                        break;
                    }
                }
                
                if (saveData($dataFile, $buildingData)) {
                    $response = ['success' => true, 'message' => 'تم حذف المصروف'];
                }
            }
            break;
            
        case 'update_apartment':
            $apartmentId = $_POST['apartment_id'];
            $name = $_POST['name'];
            $monthlyAmount = floatval($_POST['monthly_amount']);
            $active = isset($_POST['active']) ? true : false;
            
            $buildingData['apartments'][$apartmentId]['name'] = $name;
            $buildingData['apartments'][$apartmentId]['monthly_payment'] = $monthlyAmount;
            $buildingData['apartments'][$apartmentId]['active'] = $active;
            
            if (saveData($dataFile, $buildingData)) {
                $response = ['success' => true, 'message' => 'تم تحديث بيانات الشقة'];
            }
            break;
            
        case 'update_account':
            $apartmentId = $_POST['apartment_id'];
            $amount = floatval($_POST['amount']);
            $description = $_POST['description'];
            $type = $_POST['type']; // 'add' or 'deduct'
            
            if (!isset($buildingData['apartment_accounts'][$apartmentId])) {
                $buildingData['apartment_accounts'][$apartmentId] = 0;
            }
            
            if ($type === 'add') {
                $buildingData['apartment_accounts'][$apartmentId] += $amount;
                $buildingData['balance'] += $amount;
            } else {
                $buildingData['apartment_accounts'][$apartmentId] -= $amount;
                $buildingData['balance'] -= $amount;
            }
            
            $buildingData['history'][] = [
                'type' => $type === 'add' ? 'income' : 'expense',
                'description' => $description . " - حساب شقة " . $apartmentId,
                'amount' => $amount,
                'date' => date('Y-m-d'),
                'timestamp' => time()
            ];
            
            if (saveData($dataFile, $buildingData)) {
                $response = ['success' => true, 'message' => 'تم تحديث حساب الشقة'];
            }
            break;
    }
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit;
}

function getExpenseTypeName($type) {
    $types = [
        'water' => 'فاتورة المياه',
        'electricity' => 'فاتورة الكهرباء',
        'garage' => 'تنظيف السلالم والجراج',
        'elevator' => 'صيانة الأسانسير',
        'other' => 'مصروف إضافي'
    ];
    return $types[$type] ?? 'مصروف';
}

// Get current month and year
$currentYear = $_GET['year'] ?? date('Y');
$currentMonthNum = $_GET['month'] ?? date('m');
$currentMonth = $currentYear . '-' . str_pad($currentMonthNum, 2, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة مالية العمارة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" type="text/css" />
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    </style>
</head>
<body class="bg-base-200">
    <!-- Header -->
    <div class="navbar bg-primary text-primary-content">
        <div class="flex-1">
            <h1 class="btn btn-ghost text-xl">🏢 نظام إدارة مالية العمارة</h1>
        </div>
        <div class="flex-none">
            <div class="stat">
                <div class="stat-title text-primary-content/70">الرصيد الحالي</div>
                <div class="stat-value text-lg"><?= number_format($buildingData['balance']) ?> جنيه</div>
            </div>
        </div>
    </div>

    <div class="container mx-auto p-4 max-w-7xl">
        <!-- Month and Year Selector -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">اختر السنة:</span>
                        </label>
                        <select class="select select-bordered" onchange="changeYear(this.value)">
                            <?php
                            for ($year = 2023; $year <= 2030; $year++) {
                                $selected = ($year == $currentYear) ? 'selected' : '';
                                echo "<option value='{$year}' {$selected}>{$year}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">اختر الشهر:</span>
                        </label>
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
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">إعدادات الشقق:</span>
                        </label>
                        <button class="btn btn-outline" onclick="showTab('settings')">⚙️ إعدادات الشقق</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs tabs-boxed mb-6">
            <a class="tab tab-active" onclick="showTab('payments')">💰 المدفوعات</a>
            <a class="tab" onclick="showTab('expenses')">📋 المصاريف</a>
            <a class="tab" onclick="showTab('summary')">📊 الملخص</a>
            <a class="tab" onclick="showTab('settings')">⚙️ إعدادات الشقق</a>
            <a class="tab" onclick="showTab('accounts')">💳 حسابات الشقق</a>
        </div>

        <!-- Payments Tab -->
        <div id="payments" class="tab-content">
            <?php
            $monthPayments = $buildingData['payments'][$currentMonth] ?? [];
            $totalRequired = 0;
            $totalCollected = 0;
            
            // Calculate totals based on active apartments and their custom payments
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

            <!-- Summary Stats -->
            <div class="stats shadow mb-6 w-full">
                <div class="stat">
                    <div class="stat-title">المطلوب</div>
                    <div class="stat-value text-primary"><?= number_format($totalRequired) ?></div>
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

            <!-- Payments Table -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>الدور</th>
                                    <th>رقم الشقة</th>
                                    <th>اسم المالك</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الدفع</th>
                                    <th>رصيد الحساب</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($floors as $floorIndex => $floor): ?>
                                    <?php foreach ($apartments as $apt): ?>
                                        <?php
                                        $apartmentId = "{$floorIndex}-{$apt}";
                                        $payment = $monthPayments[$apartmentId] ?? null;
                                        $isPaid = $payment && $payment['paid'];
                                        
                                        // Get apartment configuration
                                        $aptConfig = $buildingData['apartments'][$apartmentId] ?? [
                                            'name' => '', 
                                            'monthly_payment' => 200, 
                                            'active' => true
                                        ];
                                        
                                        // Get apartment account balance
                                        $accountBalance = $buildingData['apartment_accounts'][$apartmentId] ?? 0;
                                        
                                        // Skip inactive apartments (like أرضي 2)
                                        if (!$aptConfig['active']) {
                                            echo "<tr class='opacity-50'>";
                                            echo "<td>{$floor}</td>";
                                            echo "<td>{$apt}</td>";
                                            echo "<td class='text-gray-500'>شقة فارغة</td>";
                                            echo "<td class='text-gray-500'>-</td>";
                                            echo "<td><div class='badge badge-neutral'>غير نشط</div></td>";
                                            echo "<td>-</td>";
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
                                            <td><?= $aptConfig['monthly_payment'] ?> جنيه</td>
                                            <td>
                                                <div class="badge <?= $isPaid ? 'badge-success' : 'badge-error' ?>">
                                                    <?= $isPaid ? 'تم الدفع' : 'لم يتم الدفع' ?>
                                                </div>
                                            </td>
                                            <td><?= $isPaid ? $payment['date'] : '-' ?></td>
                                            <td>
                                                <span class="<?= $accountBalance > 0 ? 'text-success' : ($accountBalance < 0 ? 'text-error' : '') ?>">
                                                    <?= number_format($accountBalance) ?> جنيه
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!$isPaid): ?>
                                                    <button class="btn btn-success btn-sm" 
                                                            onclick="markAsPaid('<?= $currentMonth ?>', '<?= $apartmentId ?>', '<?= $floor ?>', <?= $apt ?>)">
                                                        تم الدفع
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-error btn-sm" 
                                                            onclick="markAsUnpaid('<?= $currentMonth ?>', '<?= $apartmentId ?>')">
                                                        إلغاء الدفع
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Tab -->
        <div id="expenses" class="tab-content" style="display: none;">
            <!-- Add Expense Form -->
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title">إضافة مصروف جديد</h2>
                    <form onsubmit="addExpense(event)" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="form-control">
                                <label class="label">نوع المصروف</label>
                                <select name="expense_type" class="select select-bordered" required>
                                    <option value="water">فاتورة المياه</option>
                                    <option value="electricity">فاتورة الكهرباء</option>
                                    <option value="garage">تنظيف السلالم والجراج</option>
                                    <option value="elevator">صيانة الأسانسير</option>
                                    <option value="other">مصروف إضافي</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label">المبلغ (جنيه)</label>
                                <input type="number" name="amount" class="input input-bordered" required>
                            </div>
                            <div class="form-control">
                                <label class="label">الوصف</label>
                                <input type="text" name="description" class="input input-bordered">
                            </div>
                            <div class="form-control">
                                <label class="label">التاريخ</label>
                                <input type="date" name="date" class="input input-bordered" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">إضافة مصروف</button>
                    </form>
                </div>
            </div>

            <!-- Expenses List -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">مصاريف <?= $months[$currentMonthNum] ?> <?= $currentYear ?></h2>
                    <?php
                    $monthExpenses = $buildingData['expenses'][$currentMonth] ?? [];
                    $totalExpenses = 0;
                    ?>
                    
                    <?php if (empty($monthExpenses)): ?>
                        <div class="alert">
                            <span>لا توجد مصاريف لهذا الشهر</span>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($monthExpenses as $expense): ?>
                                <?php $totalExpenses += $expense['amount']; ?>
                                <div class="flex items-center justify-between p-4 bg-base-200 rounded-lg">
                                    <div>
                                        <div class="font-semibold"><?= htmlspecialchars($expense['description']) ?></div>
                                        <div class="text-sm text-base-content/70"><?= $expense['date'] ?></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-error"><?= number_format($expense['amount']) ?> جنيه</span>
                                        <button class="btn btn-error btn-sm" 
                                                onclick="removeExpense('<?= $currentMonth ?>', '<?= $expense['id'] ?>')">
                                            حذف
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="stat bg-base-200 rounded-lg mt-4">
                            <div class="stat-title">إجمالي المصاريف</div>
                            <div class="stat-value text-error"><?= number_format($totalExpenses) ?> جنيه</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Summary Tab -->
        <div id="summary" class="tab-content" style="display: none;">
            <?php
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
            
            <div class="stats shadow w-full mb-6">
                <div class="stat">
                    <div class="stat-title">إجمالي الإيرادات</div>
                    <div class="stat-value text-success"><?= number_format($totalIncome) ?></div>
                    <div class="stat-desc">جنيه</div>
                </div>
                <div class="stat">
                    <div class="stat-title">إجمالي المصاريف</div>
                    <div class="stat-value text-error"><?= number_format($totalExpensesAll) ?></div>
                    <div class="stat-desc">جنيه</div>
                </div>
                <div class="stat">
                    <div class="stat-title">الرصيد النهائي</div>
                    <div class="stat-value text-primary"><?= number_format($finalBalance) ?></div>
                    <div class="stat-desc">جنيه</div>
                </div>
            </div>

            <!-- Recent History -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">آخر العمليات</h2>
                    <?php
                    $recentHistory = array_slice(array_reverse($buildingData['history']), 0, 10);
                    ?>
                    
                    <?php if (empty($recentHistory)): ?>
                        <div class="alert">
                            <span>لا توجد عمليات مسجلة</span>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($recentHistory as $item): ?>
                                <div class="flex items-center justify-between p-4 bg-base-200 rounded-lg">
                                    <div>
                                        <div class="font-semibold"><?= htmlspecialchars($item['description']) ?></div>
                                        <div class="text-sm text-base-content/70"><?= $item['date'] ?></div>
                                    </div>
                                    <span class="font-bold <?= $item['type'] === 'income' ? 'text-success' : 'text-error' ?>">
                                        <?= $item['type'] === 'income' ? '+' : '-' ?><?= number_format($item['amount']) ?> جنيه
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Apartment Settings Tab -->
        <div id="settings" class="tab-content" style="display: none;">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">⚙️ إعدادات الشقق</h2>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>الدور</th>
                                    <th>رقم الشقة</th>
                                    <th>اسم المالك</th>
                                    <th>المبلغ الشهري</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($floors as $floorIndex => $floor): ?>
                                    <?php foreach ($apartments as $apt): ?>
                                        <?php
                                        $apartmentId = "{$floorIndex}-{$apt}";
                                        $aptConfig = $buildingData['apartments'][$apartmentId] ?? [
                                            'name' => '', 
                                            'monthly_payment' => 200, 
                                            'active' => true
                                        ];
                                        ?>
                                        <tr>
                                            <td><?= $floor ?></td>
                                            <td><?= $apt ?></td>
                                            <td>
                                                <input type="text" 
                                                       class="input input-bordered input-sm" 
                                                       value="<?= htmlspecialchars($aptConfig['name']) ?>"
                                                       id="name-<?= $apartmentId ?>">
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="input input-bordered input-sm w-20" 
                                                       value="<?= $aptConfig['monthly_payment'] ?>"
                                                       id="payment-<?= $apartmentId ?>">
                                            </td>
                                            <td>
                                                <input type="checkbox" 
                                                       class="checkbox checkbox-success" 
                                                       <?= $aptConfig['active'] ? 'checked' : '' ?>
                                                       id="active-<?= $apartmentId ?>">
                                            </td>
                                            <td>
                                                <button class="btn btn-primary btn-sm" 
                                                        onclick="updateApartment('<?= $apartmentId ?>')">
                                                    حفظ
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Apartment Accounts Tab -->
        <div id="accounts" class="tab-content" style="display: none;">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">💳 حسابات الشقق</h2>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>الشقة</th>
                                    <th>اسم المالك</th>
                                    <th>رصيد الحساب</th>
                                    <th>إضافة/خصم مبلغ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($floors as $floorIndex => $floor): ?>
                                    <?php foreach ($apartments as $apt): ?>
                                        <?php
                                        $apartmentId = "{$floorIndex}-{$apt}";
                                        $aptConfig = $buildingData['apartments'][$apartmentId] ?? ['name' => '', 'active' => true];
                                        $accountBalance = $buildingData['apartment_accounts'][$apartmentId] ?? 0;
                                        
                                        // Skip inactive apartments
                                        if (!$aptConfig['active']) continue;
                                        ?>
                                        <tr>
                                            <td><?= $floor ?> - <?= $apt ?></td>
                                            <td><?= htmlspecialchars($aptConfig['name'] ?: 'غير محدد') ?></td>
                                            <td>
                                                <span class="font-bold <?= $accountBalance > 0 ? 'text-success' : ($accountBalance < 0 ? 'text-error' : '') ?>">
                                                    <?= number_format($accountBalance) ?> جنيه
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <input type="number" 
                                                           class="input input-bordered input-sm w-24" 
                                                           placeholder="المبلغ"
                                                           id="amount-<?= $apartmentId ?>">
                                                    <input type="text" 
                                                           class="input input-bordered input-sm" 
                                                           placeholder="الوصف"
                                                           id="desc-<?= $apartmentId ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <button class="btn btn-success btn-sm" 
                                                            onclick="updateAccount('<?= $apartmentId ?>', 'add')">
                                                        إضافة
                                                    </button>
                                                    <button class="btn btn-error btn-sm" 
                                                            onclick="updateAccount('<?= $apartmentId ?>', 'deduct')">
                                                        خصم
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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

        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('tab-active');
            });
            
            // Show selected tab
            document.getElementById(tabName).style.display = 'block';
            event.target.classList.add('tab-active');
        }

        // Initialize first tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('payments').style.display = 'block';
        });

        function markAsPaid(month, apartmentId, floor, apt) {
            const formData = new FormData();
            formData.append('action', 'mark_paid');
            formData.append('month', month);
            formData.append('apartment_id', apartmentId);
            formData.append('floor', floor);
            formData.append('apartment', apt);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('حدث خطأ: ' + data.message);
                }
            });
        }

        function markAsUnpaid(month, apartmentId) {
            const formData = new FormData();
            formData.append('action', 'mark_unpaid');
            formData.append('month', month);
            formData.append('apartment_id', apartmentId);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('حدث خطأ: ' + data.message);
                }
            });
        }

        function addExpense(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'add_expense');
            formData.append('month', '<?= $currentMonth ?>');
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('حدث خطأ: ' + data.message);
                }
            });
        }

        function removeExpense(month, expenseId) {
            if (confirm('هل أنت متأكد من حذف هذا المصروف؟')) {
                const formData = new FormData();
                formData.append('action', 'remove_expense');
                formData.append('month', month);
                formData.append('expense_id', expenseId);
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('حدث خطأ: ' + data.message);
                    }
                });
            }
        }

        function updateApartment(apartmentId) {
            const name = document.getElementById('name-' + apartmentId).value;
            const monthlyAmount = document.getElementById('payment-' + apartmentId).value;
            const active = document.getElementById('active-' + apartmentId).checked;
            
            const formData = new FormData();
            formData.append('action', 'update_apartment');
            formData.append('apartment_id', apartmentId);
            formData.append('name', name);
            formData.append('monthly_amount', monthlyAmount);
            if (active) formData.append('active', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم تحديث بيانات الشقة بنجاح');
                    location.reload();
                } else {
                    alert('حدث خطأ: ' + data.message);
                }
            });
        }

        function updateAccount(apartmentId, type) {
            const amount = document.getElementById('amount-' + apartmentId).value;
            const description = document.getElementById('desc-' + apartmentId).value;
            
            if (!amount || amount <= 0) {
                alert('يرجى إدخال مبلغ صحيح');
                return;
            }
            
            if (!description) {
                alert('يرجى إدخال وصف للعملية');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_account');
            formData.append('apartment_id', apartmentId);
            formData.append('amount', amount);
            formData.append('description', description);
            formData.append('type', type);
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم تحديث حساب الشقة بنجاح');
                    location.reload();
                } else {
                    alert('حدث خطأ: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
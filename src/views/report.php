<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$patientId = $userId; // For common case

// 1. Overall Summary
$summarySql = "
    SELECT 
        COUNT(*) as total_prescribed,
        SUM(CASE WHEN status = 'taken' THEN 1 ELSE 0 END) as total_taken,
        SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as total_missed
    FROM doses
    WHERE patient_id = ? AND DATE(scheduled_datetime) <= CURDATE()
";
$stmt = $conn->prepare($summarySql);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$prescribed = $summary['total_prescribed'] ?: 0;
$taken = $summary['total_taken'] ?: 0;
$missed = $summary['total_missed'] ?: 0;
$adherence = $prescribed > 0 ? round(($taken / $prescribed) * 100) : 0;

// 2. Per-Medicine Data (for Bar Chart)
$medSql = "
    SELECT 
        COALESCE(pm.medicine_name, m.name) as medicine_name,
        SUM(CASE WHEN d.status = 'taken' THEN 1 ELSE 0 END) as taken,
        SUM(CASE WHEN d.status = 'missed' THEN 1 ELSE 0 END) as missed,
        COUNT(*) as total
    FROM doses d
    LEFT JOIN prescription_medicines pm ON d.prescription_medicine_id = pm.id
    LEFT JOIN medicines m ON d.manual_medicine_id = m.id
    WHERE d.patient_id = ? AND DATE(d.scheduled_datetime) <= CURDATE()
    GROUP BY medicine_name
";
$stmt = $conn->prepare($medSql);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$medResults = $stmt->get_result();
$medicines = [];
while ($row = $medResults->fetch_assoc()) {
    $medicines[] = $row;
}
$stmt->close();

// 3. Daily Trend (Line Chart - Last 7 Days)
$trendSql = "
    SELECT 
        DATE(scheduled_datetime) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'taken' THEN 1 ELSE 0 END) as taken
    FROM doses
    WHERE patient_id = ? AND scheduled_datetime BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
    GROUP BY DATE(scheduled_datetime)
    ORDER BY date ASC
";
$stmt = $conn->prepare($trendSql);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$trendResults = $stmt->get_result();
$trendData = [];
while ($row = $trendResults->fetch_assoc()) {
    $row['adherence'] = round(($row['taken'] / $row['total']) * 100);
    $trendData[] = $row;
}
$stmt->close();

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sushrusha - Adherence Report</title>
    <link href="../../public/assets/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-[#f6f7f8] text-[#0d141b] min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-black tracking-tight">Health Adherence Report</h1>
            <button onclick="window.history.back()" class="flex items-center gap-2 text-slate-500 hover:text-primary font-medium">
                <span class="material-symbols-outlined">arrow_back</span> Back to Dashboard
            </button>
        </div>

        <!-- Alert Banner (Adherence < 70%) -->
        <?php if ($adherence < 70 && $prescribed > 0): ?>
        <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-4 text-red-800 animate-pulse">
            <span class="material-symbols-outlined text-3xl">warning</span>
            <div>
                <p class="font-bold">Low Adherence Alert!</p>
                <p class="text-sm">Your overall adherence is <?php echo $adherence; ?>%. Please ensure you take your medications on time.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <p class="text-slate-500 text-sm font-bold uppercase tracking-wider mb-2">Total Prescribed</p>
                <p class="text-4xl font-black text-slate-900"><?php echo $prescribed; ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <p class="text-green-500 text-sm font-bold uppercase tracking-wider mb-2">Total Taken</p>
                <p class="text-4xl font-black text-green-600"><?php echo $taken; ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <p class="text-red-500 text-sm font-bold uppercase tracking-wider mb-2">Total Missed</p>
                <p class="text-4xl font-black text-red-600"><?php echo $missed; ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl border-2 border-primary shadow-lg shadow-primary/10">
                <p class="text-primary text-sm font-bold uppercase tracking-wider mb-2">Overall Adherence</p>
                <p class="text-4xl font-black text-primary"><?php echo $adherence; ?>%</p>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Overall Pie Chart -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center">
                <h3 class="text-lg font-bold mb-6 self-start">Overall Adherence Ratio</h3>
                <div class="w-full max-w-[300px]">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <!-- Per-Medicine Bar Chart -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold mb-6">Medication Performance</h3>
                <canvas id="barChart"></canvas>
            </div>

            <!-- Trend Line Chart -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 md:col-span-2">
                <h3 class="text-lg font-bold mb-6">7-Day Adherence Trend</h3>
                <div class="h-[300px]">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pie Chart
        new Chart(document.getElementById('pieChart'), {
            type: 'doughnut',
            data: {
                labels: ['Taken', 'Missed'],
                datasets: [{
                    data: [<?php echo $taken; ?>, <?php echo $missed; ?>],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
        });

        // Bar Chart
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($medicines, 'medicine_name')); ?>,
                datasets: [
                    {
                        label: 'Taken',
                        data: <?php echo json_encode(array_column($medicines, 'taken')); ?>,
                        backgroundColor: '#10b981'
                    },
                    {
                        label: 'Missed',
                        data: <?php echo json_encode(array_column($medicines, 'missed')); ?>,
                        backgroundColor: '#ef4444'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });

        // Line Chart
        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trendData, 'date')); ?>,
                datasets: [{
                    label: 'Adherence %',
                    data: <?php echo json_encode(array_column($trendData, 'adherence')); ?>,
                    borderColor: '#137fec',
                    backgroundColor: 'rgba(19, 127, 236, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '#137fec'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
                }
            }
        });
    </script>
</body>
</html>

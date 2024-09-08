<?php
session_start();

include 'config.php';

// Login logic
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare statement to avoid SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        echo "Username atau password salah!";
    }
}

// Logout logic
if (isset($_POST['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: login.php"); // Redirect to login page
    exit;
}

// Query for Pie Chart: Gender Distribution
$sql_gender = "SELECT jenis_kelamin, COUNT(*) as jumlah FROM tbl_kjp GROUP BY jenis_kelamin";
$result_gender = $conn->query($sql_gender);

$genders = [];
$gender_values = [];

if ($result_gender->num_rows > 0) {
    while ($row = $result_gender->fetch_assoc()) {
        $genders[] = $row['jenis_kelamin'];
        $gender_values[] = $row['jumlah'];
    }
}

// Query for Bar Chart: Recipients per Kecamatan
$sql_kecamatan = "SELECT kecamatan, COUNT(*) as jumlah FROM tbl_kjp GROUP BY kecamatan";
$result_kecamatan = $conn->query($sql_kecamatan);

$kecamatan = [];
$kecamatan_values = [];

if ($result_kecamatan->num_rows > 0) {
    while ($row = $result_kecamatan->fetch_assoc()) {
        $kecamatan[] = $row['kecamatan'];
        $kecamatan_values[] = $row['jumlah'];
    }
}

// Query for Line Chart: Recipients per Class
$sql_kelas = "SELECT kelas, COUNT(*) as jumlah FROM tbl_kjp GROUP BY kelas";
$result_kelas = $conn->query($sql_kelas);

$kelas = [];
$kelas_values = [];

if ($result_kelas->num_rows > 0) {
    while ($row = $result_kelas->fetch_assoc()) {
        $kelas[] = $row['kelas'];
        $kelas_values[] = $row['jumlah'];
    }
}

// Query for Top School by Recipients
$sql_sekolah = "SELECT nama_sekolah, COUNT(*) as jumlah FROM tbl_kjp GROUP BY nama_sekolah ORDER BY jumlah DESC LIMIT 1";
$result_sekolah = $conn->query($sql_sekolah);

$sekolah_terbanyak = "";
$jumlah_terbanyak = 0;

if ($result_sekolah->num_rows > 0) {
    $row = $result_sekolah->fetch_assoc();
    $sekolah_terbanyak = $row['nama_sekolah'];
    $jumlah_terbanyak = $row['jumlah'];
}

// Query for Total Schools
$sql_total_sekolah = "SELECT COUNT(DISTINCT nama_sekolah) as total_sekolah FROM tbl_kjp";
$result_total_sekolah = $conn->query($sql_total_sekolah);

$total_sekolah = 0;

if ($result_total_sekolah->num_rows > 0) {
    $row = $result_total_sekolah->fetch_assoc();
    $total_sekolah = $row['total_sekolah'];
}

// Query for Top 10 Schools by Recipients
$sql_top_sekolah = "SELECT nama_sekolah, COUNT(*) as jumlah FROM tbl_kjp GROUP BY nama_sekolah ORDER BY jumlah DESC LIMIT 10";
$result_top_sekolah = $conn->query($sql_top_sekolah);

$top_sekolah = [];
$top_sekolah_values = [];

if ($result_top_sekolah->num_rows > 0) {
    while ($row = $result_top_sekolah->fetch_assoc()) {
        $top_sekolah[] = $row['nama_sekolah'];
        $top_sekolah_values[] = $row['jumlah'];
    }
}

// Query for Recipients Based on School Level (SD, SMP, SMA)
$sql_jenjang = "SELECT CASE 
                    WHEN kelas BETWEEN 1 AND 6 THEN 'SD' 
                    WHEN kelas BETWEEN 7 AND 9 THEN 'SMP' 
                    WHEN kelas BETWEEN 10 AND 12 THEN 'SMA' 
                END as jenjang, COUNT(*) as jumlah FROM tbl_kjp GROUP BY jenjang";
$result_jenjang = $conn->query($sql_jenjang);

$jenjang = [];
$jenjang_values = [];

if ($result_jenjang->num_rows > 0) {
    while ($row = $result_jenjang->fetch_assoc()) {
        $jenjang[] = $row['jenjang'];
        $jenjang_values[] = $row['jumlah'];
    }
}

// Query for Recipients Based on Kecamatan
$sql_kecamatan_dist = "SELECT kecamatan, COUNT(*) as jumlah FROM tbl_kjp GROUP BY kecamatan ORDER BY jumlah DESC";
$result_kecamatan_dist = $conn->query($sql_kecamatan_dist);

$kecamatan_dist = [];
$kecamatan_dist_values = [];

if ($result_kecamatan_dist->num_rows > 0) {
    while ($row = $result_kecamatan_dist->fetch_assoc()) {
        $kecamatan_dist[] = $row['kecamatan'];
        $kecamatan_dist_values[] = $row['jumlah'];
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kartu Jakarta Pintar Plus</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f4f4f9;
            margin: 0;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .chart-container, .map-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 15px;
        }
        .info-container {
            text-align: center;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 20px;
        }
        .map-container {
            height: 400px;
        }
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <h1>Dashboard Kartu Jakarta Pintar Plus</h1>

    <div class="info-container">
        <h3>Sekolah dengan Penerima KJP Plus Terbanyak: <?php echo $sekolah_terbanyak; ?> (<?php echo $jumlah_terbanyak; ?> penerima)</h3>
        <h3>Total Sekolah: <?php echo $total_sekolah; ?></h3>
    </div>

    <div class="dashboard-container">
        <div class="chart-container">
            <canvas id="pieChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="barChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="lineChart"></canvas>
        </div>

        <div id="map" class="map-container"></div>
        
        <div class="chart-container">
            <canvas id="top10SekolahChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="jenjangChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="kecamatanDistChart"></canvas>
        </div>
    </div>

    <!-- Logout Form -->
    <form method="POST" action="">
        <button type="submit" name="logout" style="position: fixed; top: 20px; right: 20px;">Logout</button>
    </form>

    <script>
        // Pie Chart for Gender Distribution
        var ctxPie = document.getElementById('pieChart').getContext('2d');
        var pieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($genders); ?>,
                datasets: [{
                    data: <?php echo json_encode($gender_values); ?>,
                    backgroundColor: ['#ff6384', '#36a2eb', '#cc65fe'],
                    hoverBackgroundColor: ['#ff6384', '#36a2eb', '#cc65fe']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Bar Chart for Recipients per Kecamatan
        var ctxBar = document.getElementById('barChart').getContext('2d');
        var barChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($kecamatan); ?>,
                datasets: [{
                    label: 'Jumlah Penerima',
                    data: <?php echo json_encode($kecamatan_values); ?>,
                    backgroundColor: '#36a2eb',
                    borderColor: '#36a2eb',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Line Chart for Recipients per Class
        var ctxLine = document.getElementById('lineChart').getContext('2d');
        var lineChart = new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($kelas); ?>,
                datasets: [{
                    label: 'Jumlah Penerima',
                    data: <?php echo json_encode($kelas_values); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Top 10 Schools by Recipients
        var ctxTop10 = document.getElementById('top10SekolahChart').getContext('2d');
        var top10SekolahChart = new Chart(ctxTop10, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($top_sekolah); ?>,
                datasets: [{
                    label: 'Jumlah Penerima',
                    data: <?php echo json_encode($top_sekolah_values); ?>,
                    backgroundColor: '#ff6384',
                    borderColor: '#ff6384',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Chart for Recipients Based on School Level
        var ctxJenjang = document.getElementById('jenjangChart').getContext('2d');
        var jenjangChart = new Chart(ctxJenjang, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($jenjang); ?>,
                datasets: [{
                    data: <?php echo json_encode($jenjang_values); ?>,
                    backgroundColor: ['#ffce56', '#36a2eb', '#ff6384'],
                    hoverBackgroundColor: ['#ffce56', '#36a2eb', '#ff6384']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Chart for Recipients Based on Kecamatan Distribution
        var ctxKecamatanDist = document.getElementById('kecamatanDistChart').getContext('2d');
        var kecamatanDistChart = new Chart(ctxKecamatanDist, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($kecamatan_dist); ?>,
                datasets: [{
                    label: 'Jumlah Penerima',
                    data: <?php echo json_encode($kecamatan_dist_values); ?>,
                    backgroundColor: '#cc65fe',
                    borderColor: '#cc65fe',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Leaflet Map
        var map = L.map('map').setView([-6.2, 106.816666], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
        }).addTo(map);

        L.marker([-6.2, 106.816666]).addTo(map)
            .bindPopup('Jakarta')
            .openPopup();
    </script>
</body>
</html>

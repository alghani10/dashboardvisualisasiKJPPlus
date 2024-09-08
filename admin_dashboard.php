<?php
session_start();
include 'config.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}


$limit = 10;


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;


$total_query = "SELECT COUNT(*) as total FROM tbl_kjp";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total = $total_row['total'];


$sql = "SELECT nama_siswa, nama_sekolah, alamat FROM tbl_kjp LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid black;
        }
    </style>
</head>
<body>
    <h2>Data Siswa Penerima KJP Plus</h2>
    <table border="1">
        <tr>
            <th>Nama Siswa</th>
            <th>Nama Sekolah</th>
            <th>Alamat</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td><?php echo $row['nama_siswa']; ?></td>
                <td><?php echo $row['nama_sekolah']; ?></td>
                <td><?php echo $row['alamat']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- Pagination Links -->
    <div>
        <?php for ($i = 1; $i <= ceil($total / $limit); $i++) : ?>
            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>

    <p><a href="logout.php">Logout</a></p>
</body>
</html>

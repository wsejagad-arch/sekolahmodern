<?php
include 'koneksi.php';

header('Content-Type: application/json');

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'search_students':
        searchStudents();
        break;

    case 'get_students_by_class':
        $kelas = $_GET['kelas'] ?? '';
        getStudentsByClass($kelas);
        break;

    default:
        // Legacy functionality for backward compatibility
        $kelas = $_GET['kelas'] ?? '';
        if (!empty($kelas)) {
            getStudentsByClass($kelas);
        } else {
            echo json_encode(['error' => 'Invalid action or missing parameters']);
        }
        break;
}

function searchStudents() {
    global $conn;

    $query = trim($_POST['query'] ?? '');

    if (empty($query) || strlen($query) < 2) {
        echo json_encode(['success' => false, 'message' => 'Query too short']);
        return;
    }

    // Search for students by name or student ID
    $sql = "SELECT no_induk, nama_siswa, kelas FROM tbl_siswa
            WHERE nama_siswa LIKE ? OR no_induk LIKE ?
            ORDER BY nama_siswa
            LIMIT 10";

    $stmt = mysqli_prepare($conn, $sql);
    $searchTerm = "%$query%";
    mysqli_stmt_bind_param($stmt, 'ss', $searchTerm, $searchTerm);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }

    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
}

function getStudentsByClass($kelas) {
    global $conn;

    if (empty($kelas)) {
        echo json_encode(['error' => 'Kelas parameter is required']);
        return;
    }

    $sql = "SELECT * FROM tbl_data WHERE kelas = ? AND no_induk = '5555'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $kelas);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode($data);
}
?>


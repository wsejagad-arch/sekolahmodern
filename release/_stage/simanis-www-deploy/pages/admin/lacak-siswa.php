<?php
// Access control is now handled in home.php
if (!isset($conn) || !($conn instanceof mysqli)) {
    require __DIR__ . '/../../koneksi.php';
}
$lembaga = data_lembaga();

// Handle search
$searchResults = [];
$searchQuery = "";
$selectedStudent = null;

if (isset($_POST['search_student'])) {
    $searchQuery = mysqli_real_escape_string($conn, $_POST['search_query']);

    if (!empty($searchQuery)) {
        // Search for students by name or student ID
        $sql = "SELECT * FROM tbl_siswa
                WHERE nama_siswa LIKE '%$searchQuery%'
                OR no_induk LIKE '%$searchQuery%'
                ORDER BY nama_siswa LIMIT 10";

        $result = mysqli_query($conn, $sql);
        if ($result !== false) {
            while ($row = mysqli_fetch_assoc($result)) {
                $searchResults[] = $row;
            }
        }
    }
}

if (isset($_GET['student_id'])) {
    $studentId = mysqli_real_escape_string($conn, $_GET['student_id']);

    // Get student details
    $sql = "SELECT * FROM tbl_siswa WHERE no_induk = '$studentId'";
    $result = mysqli_query($conn, $sql);
    $selectedStudent = ($result !== false) ? mysqli_fetch_assoc($result) : null;

    if ($selectedStudent) {
        // Get student's subject assignments, teachers, and attendance from journals
        $sql = "SELECT
                    ma.nama_mapel,
                    g.nama_guru,
                    mt.tanggal,
                    mt.absen,
                    mt.kelas
                FROM tbl_mapel_ampu ma
                LEFT JOIN tbl_guru g ON ma.no_induk = g.no_induk
                LEFT JOIN tbl_materi mt ON ma.id_mapel = mt.id_mapel
                WHERE ma.kelas = '{$selectedStudent['kelas']}'
                ORDER BY mt.tanggal DESC";

        $result = mysqli_query($conn, $sql);
        $studentData = [];
        if ($result !== false) {
            while ($row = mysqli_fetch_assoc($result)) {
                $studentData[] = $row;
            }
        }

        // If no data found with exact match, try LIKE match
        if (empty($studentData)) {
            $sql = "SELECT
                        ma.nama_mapel,
                        g.nama_guru,
                        mt.tanggal,
                        mt.absen,
                        mt.kelas
                    FROM tbl_mapel_ampu ma
                    LEFT JOIN tbl_guru g ON ma.no_induk = g.no_induk
                    LEFT JOIN tbl_materi mt ON ma.id_mapel = mt.id_mapel
                    WHERE ma.kelas LIKE '%{$selectedStudent['kelas']}%'
                    ORDER BY mt.tanggal DESC";

            $result = mysqli_query($conn, $sql);
            $studentData = [];
            if ($result !== false) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $studentData[] = $row;
                }
            }
        }

        // Debug: Log the data
        error_log("Student data for {$selectedStudent['nama_siswa']}: " . count($studentData) . " records");
        if (count($studentData) > 0) {
            error_log("Sample data: " . json_encode($studentData[0]));
        }

        // Get student's violations separately
        $sqlViolations = "SELECT
                            tanggal_pelanggaran,
                            deskripsi_pelanggaran,
                            tindakan_yang_diambil,
                            kategori_pelanggaran,
                            status_pelanggaran
                         FROM tbl_pelanggaran
                         WHERE no_induk = '$studentId'
                         ORDER BY tanggal_pelanggaran DESC";

        $resultViolations = mysqli_query($conn, $sqlViolations);
        $violations = [];
        if ($resultViolations !== false) {
            while ($row = mysqli_fetch_assoc($resultViolations)) {
                $violations[] = $row;
            }
        } else {
            error_log("Query violations failed: " . mysqli_error($conn));
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Lacak Siswa</h1>
    </div>

    <!-- Search Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pencarian Siswa</h6>
        </div>
        <div class="card-body">
            <form method="post" class="mb-3">
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" name="search_query" class="form-control"
                            placeholder="Cari siswa berdasarkan nama atau nomor induk..."
                            value="<?php echo htmlspecialchars($searchQuery); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="search_student" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Cari Siswa
                        </button>
                    </div>
                </div>
            </form>

            <?php if (!empty($searchResults)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>No Induk</th>
                                <th>Kelas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($searchResults as $student): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($student['nama_siswa']); ?></td>
                                    <td><?php echo htmlspecialchars($student['no_induk']); ?></td>
                                    <td><?php echo htmlspecialchars($student['kelas']); ?></td>
                                    <td>
                                        <a href="?page=lacak-siswa&student_id=<?php echo urlencode($student['no_induk']); ?>"
                                            class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Lacak
                                        </a>
                                        <a href="detail-profil-siswa.php?no_induk=<?php echo urlencode($student['no_induk']); ?>"
                                            class="btn btn-sm btn-outline-primary ml-1">
                                            <i class="fas fa-id-card"></i> Profil & Izin Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($selectedStudent): ?>
        <!-- Student Details -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Detail Siswa: <?php echo htmlspecialchars($selectedStudent['nama_siswa']); ?>
                    (<?php echo htmlspecialchars($selectedStudent['no_induk']); ?>)
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <a href="detail-profil-siswa.php?no_induk=<?php echo urlencode($selectedStudent['no_induk']); ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user-cog"></i> Buka Profil & Atur Izin Edit
                    </a>
                </div>
                <?php
                // Calculate attendance statistics
                $totalHadir = 0;
                $totalSakit = 0;
                $totalIzin = 0;
                $totalAlfa = 0;
                $totalTelat = 0;
                $totalRecords = 0;

                foreach ($studentData as $data) {
                    $totalRecords++;
                    $attendance = 'Hadir'; // Default
                    if (!empty($data['absen'])) {
                        $absenParts = explode(', ', $data['absen']);
                        foreach ($absenParts as $part) {
                            if (strpos($part, $selectedStudent['nama_siswa']) !== false) {
                                $statusParts = explode(' : ', $part);
                                if (count($statusParts) > 1) {
                                    $attendance = trim($statusParts[1]);
                                }
                                break;
                            }
                        }
                    }

                    switch (strtolower($attendance)) {
                        case 'hadir':
                            $totalHadir++;
                            break;
                        case 'sakit':
                            $totalSakit++;
                            break;
                        case 'izin':
                            $totalIzin++;
                            break;
                        case 'alfa':
                        case 'alpha':
                        case 'absent':
                            $totalAlfa++;
                            break;
                        case 'telat':
                        case 'late':
                            $totalTelat++;
                            break;
                    }
                }
                ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card border-left-primary shadow h-100 py-3">
                            <div class="card-body">
                                <h6 class="card-title text-primary font-weight-bold mb-3">
                                    <i class="fas fa-user-graduate mr-2"></i>Informasi Siswa
                                </h6>
                                <div class="row">
                                    <div class="col-5 text-left pr-1">
                                        <strong>Nama</strong>
                                    </div>
                                    <div class="col-1 text-center px-0">
                                        <strong>:</strong>
                                    </div>
                                    <div class="col-6">
                                        <?php echo htmlspecialchars($selectedStudent['nama_siswa']); ?>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-5 text-left pr-1">
                                        <strong>No Induk</strong>
                                    </div>
                                    <div class="col-1 text-center px-0">
                                        <strong>:</strong>
                                    </div>
                                    <div class="col-6">
                                        <?php echo htmlspecialchars($selectedStudent['no_induk']); ?>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-5 text-left pr-1">
                                        <strong>Kelas</strong>
                                    </div>
                                    <div class="col-1 text-center px-0">
                                        <strong>:</strong>
                                    </div>
                                    <div class="col-6">
                                        <?php echo htmlspecialchars($selectedStudent['kelas']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-left-success shadow h-100 py-3">
                            <div class="card-body">
                                <h6 class="card-title text-success font-weight-bold mb-3">
                                    <i class="fas fa-chart-bar mr-2"></i>Statistik Kehadiran
                                </h6>
                                <div class="row">
                                    <div class="col-4">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                            Total Hadir
                                                        </div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                            <?php echo $totalHadir; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                            Total Sakit
                                                        </div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                            <?php echo $totalSakit; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-thermometer-half fa-2x text-warning"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                            Total Izin
                                                        </div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                            <?php echo $totalIzin; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-envelope fa-2x text-info"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-4">
                                        <div class="card border-left-danger shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                            Total Alfa
                                                        </div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                            <?php echo $totalAlfa; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card border-left-secondary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                                            Total Telat
                                                        </div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                            <?php echo $totalTelat; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-clock fa-2x text-secondary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                            Total Rekaman
                                                        </div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                            <?php echo $totalRecords; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="studentTrackingTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru</th>
                                <th>Tanggal</th>
                                <th>Kehadiran</th>
                                <th>Catatan Pelanggaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $hasData = false;

                            // Display journal/attendance data
                            foreach ($studentData as $data):
                                $hasData = true;
                                // Parse attendance data to check if student is present
                                $attendance = 'Hadir';
                                if (!empty($data['absen'])) {
                                    $absenParts = explode(', ', $data['absen']);
                                    foreach ($absenParts as $part) {
                                        if (strpos($part, $selectedStudent['nama_siswa']) !== false) {
                                            $statusParts = explode(' : ', $part);
                                            if (count($statusParts) > 1) {
                                                $attendance = trim($statusParts[1]);
                                            }
                                            break;
                                        }
                                    }
                                }

                                // Find violation for this date if any
                                $violationNote = '';
                                foreach ($violations as $violation) {
                                    if ($violation['tanggal_pelanggaran'] == $data['tanggal']) {
                                        $violationNote = $violation['kategori_pelanggaran'] . ': ' . $violation['deskripsi_pelanggaran'];
                                        if (!empty($violation['tindakan_yang_diambil'])) {
                                            $violationNote .= ' | Tindakan: ' . $violation['tindakan_yang_diambil'];
                                        }
                                        break;
                                    }
                                }
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($data['nama_mapel'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($data['nama_guru'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($data['tanggal'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $attendance == 'Hadir' ? 'success' : 'danger' ?>">
                                            <?php echo htmlspecialchars($attendance); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($violationNote)): ?>
                                            <span class="text-danger"><?php echo htmlspecialchars($violationNote); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php
                            // Display violations that don't have corresponding journal entries
                            foreach ($violations as $violation):
                                $found = false;
                                foreach ($studentData as $data) {
                                    if ($data['tanggal'] == $violation['tanggal_pelanggaran']) {
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found):
                                    $hasData = true;
                                    $violationNote = $violation['kategori_pelanggaran'] . ': ' . $violation['deskripsi_pelanggaran'];
                                    if (!empty($violation['tindakan_yang_diambil'])) {
                                        $violationNote .= ' | Tindakan: ' . $violation['tindakan_yang_diambil'];
                                    }
                            ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><span class="text-muted">-</span></td>
                                        <td><span class="text-muted">-</span></td>
                                        <td><?php echo htmlspecialchars($violation['tanggal_pelanggaran']); ?></td>
                                        <td><span class="text-muted">-</span></td>
                                        <td>
                                            <span class="text-danger"><?php echo htmlspecialchars($violationNote); ?></span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if (!$hasData): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        Tidak ada data untuk siswa ini
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <a href="?page=lacak-siswa" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Pencarian
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function() {
        $('#studentTrackingTable').DataTable({
            "pageLength": 25,
            "order": [
                [3, "desc"]
            ], // Sort by date descending
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data yang ditemukan",
                "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                "infoEmpty": "Tidak ada data tersedia",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });

        // Autocomplete functionality for student search
        $(document).ready(function() {
            const searchInput = $('input[name="search_query"]');
            let dropdownContainer = null;
            let currentFocus = -1;
            let searchTimeout = null;

            // Create dropdown container
            function createDropdownContainer() {
                if (!dropdownContainer) {
                    dropdownContainer = $('<div class="autocomplete-dropdown"></div>');
                    dropdownContainer.css({
                        'position': 'absolute',
                        'background': 'white',
                        'border': '1px solid #ccc',
                        'border-radius': '4px',
                        'box-shadow': '0 2px 4px rgba(0,0,0,0.1)',
                        'max-height': '200px',
                        'overflow-y': 'auto',
                        'z-index': '1000',
                        'width': searchInput.outerWidth() + 'px',
                        'display': 'none'
                    });
                    searchInput.parent().css('position', 'relative').append(dropdownContainer);
                }
                return dropdownContainer;
            }

            // Show dropdown with results
            function showDropdown(results) {
                const container = createDropdownContainer();
                container.empty();

                if (results.length === 0) {
                    container.html(`
                    <div class="autocomplete-no-results">
                        <span class="text-muted">Tidak ada siswa ditemukan</span>
                    </div>
                `);
                    container.show();
                    return;
                }

                results.forEach(function(student, index) {
                    const item = $('<div class="autocomplete-item"></div>');
                    item.css({
                        'padding': '8px 12px',
                        'cursor': 'pointer',
                        'border-bottom': '1px solid #eee'
                    });

                    item.html(`<strong>${student.nama_siswa}</strong> (${student.kelas})`);
                    item.data('student', student);

                    item.hover(
                        function() {
                            $(this).css('background-color', '#f8f9fa');
                        },
                        function() {
                            $(this).css('background-color', 'white');
                        }
                    );

                    item.click(function() {
                        const selectedStudent = $(this).data('student');
                        searchInput.val(selectedStudent.nama_siswa);
                        container.hide();
                        // Auto-submit the form or redirect to student details
                        window.location.href = `?page=lacak-siswa&student_id=${selectedStudent.no_induk}`;
                    });

                    container.append(item);
                });

                container.show();
            }

            // Hide dropdown
            function hideDropdown() {
                if (dropdownContainer) {
                    dropdownContainer.hide();
                }
                currentFocus = -1;
            }

            // Show loading indicator
            function showLoading() {
                const container = createDropdownContainer();
                container.empty();
                container.html(`
                <div class="autocomplete-loading">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <span class="ml-2">Mencari siswa...</span>
                </div>
            `);
                container.show();
            }

            // Hide loading indicator
            function hideLoading() {
                // This will be called when showDropdown is called
            }

            // Handle input events with debouncing
            searchInput.on('input', function() {
                const query = $(this).val().trim();

                // Clear previous timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                if (query.length < 2) {
                    hideDropdown();
                    return;
                }

                // Set new timeout for debounced search
                searchTimeout = setTimeout(function() {
                    // Show loading indicator
                    showLoading();

                    // Fetch student data via AJAX
                    $.ajax({
                        url: 'get_data.php',
                        method: 'POST',
                        data: {
                            action: 'search_students',
                            query: query
                        },
                        success: function(response) {
                            try {
                                const data = JSON.parse(response);
                                if (data.success && data.students) {
                                    showDropdown(data.students);
                                } else {
                                    showDropdown([]); // Show empty results
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                showDropdown([]); // Show empty results on error
                            }
                        },
                        error: function() {
                            console.error('Error fetching student data');
                            showDropdown([]); // Show empty results on error
                        }
                    });
                }, 300); // 300ms delay
            });

            // Handle keyboard navigation
            searchInput.on('keydown', function(e) {
                if (!dropdownContainer || !dropdownContainer.is(':visible')) return;

                const items = dropdownContainer.find('.autocomplete-item');

                if (e.keyCode === 40) { // Down arrow
                    e.preventDefault();
                    currentFocus = currentFocus < items.length - 1 ? currentFocus + 1 : 0;
                    updateFocus();
                } else if (e.keyCode === 38) { // Up arrow
                    e.preventDefault();
                    currentFocus = currentFocus > 0 ? currentFocus - 1 : items.length - 1;
                    updateFocus();
                } else if (e.keyCode === 13) { // Enter
                    e.preventDefault();
                    if (currentFocus >= 0 && currentFocus < items.length) {
                        items.eq(currentFocus).click();
                    }
                } else if (e.keyCode === 27) { // Escape
                    hideDropdown();
                }
            });

            function updateFocus() {
                const items = dropdownContainer.find('.autocomplete-item');
                items.removeClass('autocomplete-item-focused').css('background-color', 'white');
                if (currentFocus >= 0 && currentFocus < items.length) {
                    items.eq(currentFocus).addClass('autocomplete-item-focused').css('background-color', '#f8f9fa');
                }
            }

            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!searchInput.is(e.target) && !dropdownContainer.is(e.target) && dropdownContainer.has(e.target).length === 0) {
                    hideDropdown();
                }
            });

            // Hide dropdown on form submit
            $('form').on('submit', function() {
                hideDropdown();
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
            });
        });

    });
</script>
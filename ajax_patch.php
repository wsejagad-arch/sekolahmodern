    if ($action === 'perangkat_load_drive') {
        $folders = [];
        $qFolder = mysqli_query($conn, "SELECT id, nama_file FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND tipe_dokumen='perangkat_folder' ORDER BY nama_file ASC");
        while ($rF = mysqli_fetch_assoc($qFolder)) {
            $folderId = $rF['id'];
            $files = [];
            $qFile = mysqli_query($conn, "SELECT id, tipe_dokumen, nama_file, label, created_at FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND sumber_id='$folderId' AND tipe_dokumen IN ('modul', 'atp') ORDER BY created_at DESC");
            while ($rFile = mysqli_fetch_assoc($qFile)) {
                $files[] = $rFile;
            }
            $folders[] = [
                'id' => $folderId,
                'nama' => $rF['nama_file'],
                'files' => $files
            ];
        }
        echo json_encode(['status'=>'success', 'folders'=>$folders]);
        exit;
    }

    if ($action === 'perangkat_create_folder') {
        $nama = trim($_POST['nama_folder'] ?? '');
        if (empty($nama)) {
            echo json_encode(['status'=>'error', 'message'=>'Nama folder tidak boleh kosong']);
            exit;
        }
        $namaEsc = mysqli_real_escape_string($conn, $nama);
        // Check exist
        $qC = mysqli_query($conn, "SELECT id FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND tipe_dokumen='perangkat_folder' AND nama_file='$namaEsc'");
        if (mysqli_num_rows($qC) > 0) {
            echo json_encode(['status'=>'error', 'message'=>'Folder sudah ada']);
            exit;
        }
        $q = mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label) VALUES ('$nipEsc', 'perangkat_folder', 'root', '$namaEsc', 'Folder Kelas')");
        if ($q) {
            echo json_encode(['status'=>'success']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>mysqli_error($conn)]);
        }
        exit;
    }

    if ($action === 'perangkat_delete_folder') {
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE id=$id AND no_induk_guru='$nipEsc' AND tipe_dokumen='perangkat_folder'");
        mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE sumber_id='$id' AND no_induk_guru='$nipEsc'");
        echo json_encode(['status'=>'success']);
        exit;
    }

    if ($action === 'perangkat_delete_file') {
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE id=$id AND no_induk_guru='$nipEsc'");
        echo json_encode(['status'=>'success']);
        exit;
    }

    if ($action === 'perangkat_save_ai') {
        $kelas = trim($_POST['kelas'] ?? '');
        $tipe = $_POST['tipe'] ?? ''; // modul or atp
        $label = $_POST['label'] ?? '';
        $htmlContent = $_POST['html'] ?? '';
        
        $kelasEsc = mysqli_real_escape_string($conn, $kelas);
        
        // Ensure folder exists
        $qFolder = mysqli_query($conn, "SELECT id FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND tipe_dokumen='perangkat_folder' AND nama_file='Kelas $kelasEsc'");
        if (mysqli_num_rows($qFolder) == 0) {
            mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label) VALUES ('$nipEsc', 'perangkat_folder', 'root', 'Kelas $kelasEsc', 'Folder Kelas')");
            $folderId = mysqli_insert_id($conn);
        } else {
            $row = mysqli_fetch_assoc($qFolder);
            $folderId = $row['id'];
        }
        
        $dataJson = mysqli_real_escape_string($conn, json_encode(['htmlContent' => $htmlContent]));
        $tipeEsc = mysqli_real_escape_string($conn, $tipe);
        $labelEsc = mysqli_real_escape_string($conn, $label);
        $namaFile = ($tipe === 'modul' ? 'Modul Ajar' : 'ATP') . " - " . $labelEsc;
        
        $q = mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label, data_json) VALUES ('$nipEsc', '$tipeEsc', '$folderId', '$namaFile', '$labelEsc', '$dataJson')");
        if ($q) {
            echo json_encode(['status'=>'success']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>mysqli_error($conn)]);
        }
        exit;
    }

<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

$success = '';
$error = '';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if($id === $_SESSION['admin_id']) {
        $error = "Anda tidak dapat menghapus akun Anda sendiri!";
    } else {
        $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()) {
            $success = "Admin berhasil dihapus!";
        } else {
            $error = "Gagal menghapus admin!";
        }
    }
}

// Handle Add / Edit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if($action === 'add') {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];

        $check = $conn->prepare("SELECT id FROM admin WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if($check->get_result()->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $stmt = $conn->prepare("INSERT INTO admin (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $password, $role);
            if($stmt->execute()) {
                $success = "Admin baru berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan admin!";
            }
        }
    } elseif($action === 'edit_password') {
        $id = (int)$_POST['id'];
        if(!empty($_POST['new_password'])) {
            $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password, $id);
            if($stmt->execute()) {
                $success = "Password berhasil diubah!";
            } else {
                $error = "Gagal mengubah password!";
            }
        }
    }
}

// Get all admins
$admins = $conn->query("SELECT * FROM admin ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; }
        .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.875rem; }
        .badge-superadmin { background: #fee2e2; color: #ef4444; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-author { background: #dcfce3; color: #22c55e; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 12px; width: 100%; max-width: 500px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1 style="font-size: 1.5rem;">Kelola Admin</h1>
                <button onclick="openModal('addModal')" class="btn" style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-plus"></i> Tambah Admin
                </button>
            </div>

            <?php if($success): ?>
                <div style="background: #dcfce3; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role / Peran</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($admin = $admins->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $admin['id'] ?></td>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($admin['username']) ?></td>
                                    <td>
                                        <span class="badge-<?= isset($admin['role']) && $admin['role'] == 'author' ? 'author' : 'superadmin' ?>">
                                            <?= isset($admin['role']) && $admin['role'] == 'author' ? 'Admin Posting' : 'Superadmin' ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                        <button onclick="editPassword(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['username']) ?>')" class="btn btn-sm" style="background: #f59e0b;">
                                            <i class="fas fa-key"></i> Ubah Password
                                        </button>
                                        <?php if($admin['id'] !== $_SESSION['admin_id']): ?>
                                            <a href="?delete=<?= $admin['id'] ?>" class="btn btn-sm" style="background: #ef4444;" onclick="return confirm('Yakin ingin menghapus admin ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Admin -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;">Tambah Admin Baru</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Role / Peran</label>
                    <select name="role" class="form-control" required>
                        <option value="author">Admin Posting (Hanya bisa posting)</option>
                        <option value="superadmin">Superadmin (Akses Penuh)</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn" style="background: #cbd5e1; color: #334155;" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ubah Password -->
    <div id="editPassModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;">Ubah Password <span id="edit_username" style="color:var(--primary);"></span></h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_password">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Password Baru</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn" style="background: #cbd5e1; color: #334155;" onclick="closeModal('editPassModal')">Batal</button>
                    <button type="submit" class="btn" style="background: #f59e0b;">Ubah Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        function editPassword(id, username) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').innerText = username;
            openModal('editPassModal');
        }
    </script>
</body>
</html>

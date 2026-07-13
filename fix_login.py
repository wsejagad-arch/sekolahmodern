import re

with open('login_action.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update verify_password signature and logic
old_verify = """function verify_password(string $rawPassword, string $storedHash): bool
{
	// Check if hash is bcrypt format (starts with $2a$, $2b$, or $2y$)
	if (preg_match('/^\$2[aby]\$/', $storedHash)) {
		return password_verify($rawPassword, $storedHash);
	}

	// Otherwise treat as MD5 hash
	return hash_equals(md5($rawPassword), $storedHash);
}"""

new_verify = """function verify_password(string $rawPassword, string $storedHash, string $noInduk = ''): bool
{
	// Check if hash is bcrypt format (starts with $2a$, $2b$, or $2y$)
	if (preg_match('/^\$2[aby]\$/', $storedHash)) {
		return password_verify($rawPassword, $storedHash);
	}

	// Otherwise treat as MD5 hash
	if (hash_equals(md5($rawPassword), $storedHash)) return true;
	
	// Fallback for default passwords: allow '12345' or NISN/NIP interchangeably for MD5 hashes
	if ($noInduk !== '') {
	    if ($rawPassword === '12345' && hash_equals(md5($noInduk), $storedHash)) return true;
	    if ($rawPassword === $noInduk && hash_equals(md5('12345'), $storedHash)) return true;
	}
	
	return false;
}"""
content = content.replace(old_verify, new_verify)

# 2. Remove Guru auto-correct
old_guru_repair = """		// Auto-repair missing tbl_pengguna entry
		if (empty($user['password'])) {
			$no_induk = mysqli_real_escape_string($conn, $user['no_induk']);
			$hashnip = md5('12345');
			$akses = 2; // Guru
			mysqli_query($conn, "INSERT IGNORE INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$no_induk','$hashnip','$akses')");
			$user['password'] = $hashnip; // Set password so verify_password doesn't fail
		} elseif ($user['password'] === md5($user['no_induk'])) {
			$no_induk = mysqli_real_escape_string($conn, $user['no_induk']);
			$hashnip = md5('12345');
			mysqli_query($conn, "UPDATE tbl_pengguna SET password='$hashnip' WHERE no_induk='$no_induk'");
			$user['password'] = $hashnip; // Set updated password
		}"""
new_guru_repair = """		// Auto-repair missing tbl_pengguna entry
		if (empty($user['password'])) {
			$no_induk = mysqli_real_escape_string($conn, $user['no_induk']);
			$hashnip = md5('12345');
			$akses = 2; // Guru
			mysqli_query($conn, "INSERT IGNORE INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$no_induk','$hashnip','$akses')");
			$user['password'] = $hashnip; // Set password so verify_password doesn't fail
		}"""
content = content.replace(old_guru_repair, new_guru_repair)

# 3. Remove Siswa auto-correct
old_siswa_repair = """		// Auto-repair missing tbl_pengguna entry
		if (empty($user['password'])) {
			$no_induk = mysqli_real_escape_string($conn, $user['no_induk']);
			$hashnip = md5('12345');
			$akses = 3; // Siswa
			mysqli_query($conn, "INSERT IGNORE INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$no_induk','$hashnip','$akses')");
			$user['password'] = $hashnip; // Set password so verify_password doesn't fail
		} elseif ($user['password'] === md5($user['no_induk'])) {
			$no_induk = mysqli_real_escape_string($conn, $user['no_induk']);
			$hashnip = md5('12345');
			mysqli_query($conn, "UPDATE tbl_pengguna SET password='$hashnip' WHERE no_induk='$no_induk'");
			$user['password'] = $hashnip; // Set updated password
		}"""
new_siswa_repair = """		// Auto-repair missing tbl_pengguna entry
		if (empty($user['password'])) {
			$no_induk = mysqli_real_escape_string($conn, $user['no_induk']);
			$hashnip = md5('12345');
			$akses = 3; // Siswa
			mysqli_query($conn, "INSERT IGNORE INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$no_induk','$hashnip','$akses')");
			$user['password'] = $hashnip; // Set password so verify_password doesn't fail
		}"""
content = content.replace(old_siswa_repair, new_siswa_repair)

# 4. Replace verify_password calls for admin
content = re.sub(r"verify_password\(\$passwordRaw,\s*\$user\['password'\]\)", "verify_password($passwordRaw, $user['password'], $user['no_induk'] ?? '')", content)
# 5. Replace verify_password calls for guru
content = re.sub(r"verify_password\(\$passwordRaw,\s*\$guru\['password'\]\)", "verify_password($passwordRaw, $guru['password'], $guru['no_induk'] ?? '')", content)
# 6. Replace verify_password calls for siswa
content = re.sub(r"verify_password\(\$passwordRaw,\s*\$siswa\['password'\]\)", "verify_password($passwordRaw, $siswa['password'], $siswa['no_induk'] ?? '')", content)

with open('login_action.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Done")

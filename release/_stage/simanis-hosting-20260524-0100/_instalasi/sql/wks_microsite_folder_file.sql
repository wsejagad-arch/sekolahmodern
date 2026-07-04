-- WKS Microsite, Folder, dan File SQL
-- Jalankan pada database jurnal jika ingin membuat tabel secara manual.

CREATE TABLE IF NOT EXISTS tbl_wks_microsite (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    unit VARCHAR(30) NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT DEFAULT NULL,
    microsite_url VARCHAR(500) DEFAULT NULL,
    folder_url VARCHAR(500) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(50) DEFAULT NULL,
    updated_by VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_unit_active (unit, is_active),
    KEY idx_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tbl_wks_folder (
    id_folder INT UNSIGNED NOT NULL AUTO_INCREMENT,
    unit VARCHAR(30) NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    folder_name VARCHAR(180) NOT NULL,
    folder_url VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(50) DEFAULT NULL,
    updated_by VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_folder),
    KEY idx_unit_active (unit, is_active),
    KEY idx_parent (parent_id),
    KEY idx_sort (sort_order, id_folder),
    CONSTRAINT fk_wks_folder_parent
        FOREIGN KEY (parent_id) REFERENCES tbl_wks_folder (id_folder)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tbl_wks_file (
    id_file INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_folder INT UNSIGNED DEFAULT NULL,
    unit VARCHAR(30) NOT NULL,
    file_title VARCHAR(180) NOT NULL,
    file_url VARCHAR(500) DEFAULT NULL,
    file_type VARCHAR(60) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(50) DEFAULT NULL,
    updated_by VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_file),
    KEY idx_folder (id_folder),
    KEY idx_unit_active (unit, is_active),
    KEY idx_sort (sort_order, id_file),
    CONSTRAINT fk_wks_file_folder
        FOREIGN KEY (id_folder) REFERENCES tbl_wks_folder (id_folder)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tbl_wks_microsite
    (unit, title, description, microsite_url, folder_url, sort_order, is_active)
SELECT 'kurikulum', 'WKS Kurikulum', 'Program akademik, perangkat ajar, jadwal pembelajaran, asesmen, dan pengembangan kurikulum.', '', '', 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_wks_microsite WHERE unit = 'kurikulum'
);

INSERT INTO tbl_wks_microsite
    (unit, title, description, microsite_url, folder_url, sort_order, is_active)
SELECT 'kesiswaan', 'WKS Kesiswaan', 'Program pembinaan siswa, ketertiban, organisasi, prestasi, dan layanan karakter.', '', '', 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_wks_microsite WHERE unit = 'kesiswaan'
);

INSERT INTO tbl_wks_microsite
    (unit, title, description, microsite_url, folder_url, sort_order, is_active)
SELECT 'humas', 'WKS Humas', 'Publikasi sekolah, kemitraan, dokumentasi kegiatan, dan komunikasi eksternal.', '', '', 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_wks_microsite WHERE unit = 'humas'
);

INSERT INTO tbl_wks_microsite
    (unit, title, description, microsite_url, folder_url, sort_order, is_active)
SELECT 'sarpras', 'WKS Sarpras', 'Inventaris, perawatan fasilitas, ruang belajar, dan kebutuhan sarana prasarana.', '', '', 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_wks_microsite WHERE unit = 'sarpras'
);

INSERT INTO tbl_wks_folder
    (unit, folder_name, folder_url, description, sort_order, is_active)
SELECT 'kurikulum', 'Folder WKS Kurikulum', '', 'Folder utama dokumen akademik, perangkat ajar, jadwal, dan asesmen kurikulum.', 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_wks_folder WHERE unit = 'kurikulum' AND folder_name = 'Folder WKS Kurikulum'
);

INSERT INTO tbl_wks_folder
    (unit, folder_name, folder_url, description, sort_order, is_active)
SELECT 'kesiswaan', 'Folder WKS Kesiswaan', '', 'Folder utama dokumen dan program WKS Kesiswaan.', 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_wks_folder WHERE unit = 'kesiswaan' AND folder_name = 'Folder WKS Kesiswaan'
);

INSERT INTO tbl_wks_folder
    (unit, folder_name, folder_url, description, sort_order, is_active)
SELECT 'humas', 'Folder WKS Humas', '', 'Folder utama dokumen publikasi, kemitraan, dan humas.', 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_wks_folder WHERE unit = 'humas' AND folder_name = 'Folder WKS Humas'
);

INSERT INTO tbl_wks_folder
    (unit, folder_name, folder_url, description, sort_order, is_active)
SELECT 'sarpras', 'Folder WKS Sarpras', '', 'Folder utama dokumen sarana prasarana dan inventaris.', 10, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_wks_folder WHERE unit = 'sarpras' AND folder_name = 'Folder WKS Sarpras'
);

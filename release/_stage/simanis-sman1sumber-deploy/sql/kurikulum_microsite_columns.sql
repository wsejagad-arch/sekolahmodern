-- Kurikulum Microsite SQL (create/upgrade)
-- Jalankan pada database jurnal

CREATE TABLE IF NOT EXISTS tbl_kurikulum_menu (
    id_menu INT AUTO_INCREMENT PRIMARY KEY,
    menu_key VARCHAR(120) NOT NULL,
    menu_title VARCHAR(150) NOT NULL,
    menu_url VARCHAR(255) NOT NULL,
    menu_icon VARCHAR(60) NOT NULL DEFAULT 'bi-link-45deg',
    icon_type VARCHAR(20) NOT NULL DEFAULT 'bootstrap',
    icon_image_path VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    open_in_new_tab TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(50) NOT NULL,
    updated_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_menu_key (menu_key),
    INDEX idx_sort_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Untuk database lama yang tabelnya sudah ada tapi kolom baru belum ada:
ALTER TABLE tbl_kurikulum_menu ADD COLUMN icon_type VARCHAR(20) NOT NULL DEFAULT 'bootstrap' AFTER menu_icon;
ALTER TABLE tbl_kurikulum_menu ADD COLUMN icon_image_path VARCHAR(255) NULL AFTER icon_type;
ALTER TABLE tbl_kurikulum_menu ADD COLUMN open_in_new_tab TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active;

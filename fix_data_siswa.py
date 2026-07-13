import sys

file_path = 'c:/xampp/htdocs/jurnal/data-siswa.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace export-siswa.php
content = content.replace('href="export-siswa.php"', 'href="<?= asset_url(\'export-siswa.php\') ?>"')
# Replace detail-profil-siswa.php
content = content.replace('href="detail-profil-siswa.php?', 'href="<?= asset_url(\'detail-profil-siswa.php\') ?>?')
# Replace delete-siswa.php
content = content.replace('href="delete-siswa.php?', 'href="<?= asset_url(\'delete-siswa.php\') ?>?')

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("data-siswa.php updated.")

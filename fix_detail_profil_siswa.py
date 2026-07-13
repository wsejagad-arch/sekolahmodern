import sys

file_path = 'c:/xampp/htdocs/jurnal/detail-profil-siswa.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace home.php
content = content.replace('href="home.php?page=', 'href="<?= asset_url(\'home.php\') ?>?page=')
# Replace detail-profil-siswa.php
content = content.replace('href="detail-profil-siswa.php?', 'href="<?= asset_url(\'detail-profil-siswa.php\') ?>?')

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("detail-profil-siswa.php updated.")

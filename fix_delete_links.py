import sys

file_path = 'c:/xampp/htdocs/jurnal/detail-guru.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace delete-all-jadwal.php
content = content.replace('href="delete-all-jadwal.php?', 'href="<?= asset_url(\'delete-all-jadwal.php\') ?>?')
# Replace delete-mapel-guru.php
content = content.replace('href="delete-mapel-guru.php?', 'href="<?= asset_url(\'delete-mapel-guru.php\') ?>?')

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("detail-guru.php updated.")

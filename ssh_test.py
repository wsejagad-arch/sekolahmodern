import paramiko
import sys

host = '203.175.125.118'
port = 26035
username = 'pintarhub'
password = 'wahyu123'

try:
    print(f"Mencoba terhubung ke {host}:{port}...")
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(host, port=port, username=username, password=password, timeout=10)
    print("Berhasil terhubung!")
    
    print("\n--- Menjalankan perintah 'uname -a' dan 'ls -la' ---")
    stdin, stdout, stderr = client.exec_command('uname -a && pwd && ls -la')
    print(stdout.read().decode('utf-8'))
    
    err = stderr.read().decode('utf-8')
    if err:
        print("Error:", err)
        
    client.close()
except Exception as e:
    print("Gagal terhubung:", e)

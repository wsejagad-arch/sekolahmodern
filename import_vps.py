import paramiko
import os
import sys

host = '203.175.125.118'
port = 26035
username = 'ubuntu'
password = 'wahyu123'
local_file = 'smasumb1_simanis.sql'
remote_file = '/home/ubuntu/smasumb1_simanis.sql'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

try:
    print(f"Connecting to VPS...")
    client.connect(host, port=port, username=username, password=password, timeout=15)
    
    # SFTP Upload
    print(f"Uploading {local_file} to VPS...")
    sftp = client.open_sftp()
    sftp.put(local_file, remote_file)
    sftp.close()
    print("Upload complete.")
    
    # Import Database
    print("Importing database into 'sijurnal'...")
    # Import using the newly created database user
    cmd = f"mysql -u vps_jurnal -p'WahyuJurnal123!' sijurnal < {remote_file}"
    stdin, stdout, stderr = client.exec_command(cmd)
    
    exit_status = stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    
    if exit_status != 0:
        print(f"Error importing database:\n{err}")
        sys.exit(1)
        
    print("Database imported successfully!")
    
    # Cleanup
    client.exec_command(f"rm {remote_file}")
    
except Exception as e:
    print(f"Failed: {e}")
finally:
    client.close()

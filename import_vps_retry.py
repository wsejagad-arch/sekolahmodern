import paramiko
import sys

host = '203.175.125.118'
port = 26035
username = 'ubuntu'
password = 'wahyu123'
remote_file = '/home/ubuntu/smasumb1_simanis.sql'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

def run_sudo(cmd):
    stdin, stdout, stderr = client.exec_command(f"sudo -S {cmd}")
    stdin.write(password + '\n')
    stdin.flush()
    exit_status = stdout.channel.recv_exit_status() 
    return exit_status, stdout.read().decode().strip(), stderr.read().decode().strip()

try:
    print(f"Connecting to VPS...")
    client.connect(host, port=port, username=username, password=password, timeout=15)
    
    # Enable log_bin_trust_function_creators
    run_sudo("mysql -e \"SET GLOBAL log_bin_trust_function_creators = 1;\"")
    
    # Drop and recreate database to start fresh
    print("Resetting 'sijurnal' database...")
    run_sudo("mysql -e \"DROP DATABASE IF EXISTS sijurnal; CREATE DATABASE sijurnal;\"")
    
    # Import Database
    print("Importing database into 'sijurnal'...")
    cmd = f"mysql -u vps_jurnal -p'WahyuJurnal123!' sijurnal < {remote_file}"
    stdin, stdout, stderr = client.exec_command(cmd)
    
    exit_status = stdout.channel.recv_exit_status()
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    
    if exit_status != 0:
        print(f"Error importing database:\n{err}")
        sys.exit(1)
        
    print("Database imported successfully!")
    
except Exception as e:
    print(f"Failed: {e}")
finally:
    client.close()

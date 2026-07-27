import paramiko
import sys

host = '203.175.125.118'
port = 26035
username = 'ubuntu'
password = 'wahyu123'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

def run_sudo(cmd):
    print(f"Executing: {cmd}")
    # Use -S to read password from stdin
    stdin, stdout, stderr = client.exec_command(f"sudo -S {cmd}")
    stdin.write(password + '\n')
    stdin.flush()
    
    exit_status = stdout.channel.recv_exit_status() 
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    
    if exit_status != 0:
        print(f"Error executing {cmd}:\n{err}")
        sys.exit(1)
    print("Success.")

try:
    print(f"Connecting to VPS...")
    client.connect(host, port=port, username=username, password=password, timeout=15)
    
    commands = [
        "apt-get update -y",
        "DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server",
        "sed -i 's/bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf",
        "systemctl restart mysql",
        "mysql -e \"CREATE DATABASE IF NOT EXISTS sijurnal;\"",
        "mysql -e \"CREATE USER IF NOT EXISTS 'vps_jurnal'@'%' IDENTIFIED BY 'WahyuJurnal123!';\"",
        "mysql -e \"GRANT ALL PRIVILEGES ON sijurnal.* TO 'vps_jurnal'@'%';\"",
        "mysql -e \"FLUSH PRIVILEGES;\""
    ]
    
    for cmd in commands:
        run_sudo(cmd)

    print("\nVPS Database setup is COMPLETE.")
    print("Database: sijurnal")
    print("User: vps_jurnal")
    print("Password: WahyuJurnal123!")

except Exception as e:
    print(f"Failed: {e}")
finally:
    client.close()

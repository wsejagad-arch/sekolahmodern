import paramiko
import sys

host = '203.175.125.118'
port = 26035
username = 'ubuntu'
password = 'wahyu123'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

try:
    print(f"Connecting to {host}:{port} as {username}...")
    client.connect(host, port=port, username=username, password=password, timeout=10)
    print("Connected successfully!")
    
    # Check OS
    stdin, stdout, stderr = client.exec_command("cat /etc/os-release | grep PRETTY_NAME")
    print(f"OS: {stdout.read().decode('utf-8').strip()}")
    
    # Check MySQL
    stdin, stdout, stderr = client.exec_command("mysql -V")
    mysql_v = stdout.read().decode('utf-8').strip()
    if mysql_v:
        print(f"MySQL is installed: {mysql_v}")
        # Try to connect to MySQL as root without password
        stdin, stdout, stderr = client.exec_command("sudo mysql -e 'SHOW DATABASES;'")
        dbs = stdout.read().decode('utf-8').strip()
        if dbs:
            print(f"Databases:\n{dbs}")
        else:
            print("Could not list databases (might require password).")
    else:
        print("MySQL is NOT installed.")
        
except Exception as e:
    print(f"Failed to connect: {e}")
finally:
    client.close()

import paramiko
import sys

host = '203.175.125.118'
port = 26035
username = 'ubuntu'
password = 'wahyu123'

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
    
    print("Opening port 3306 in firewall...")
    status, out, err = run_sudo("ufw allow 3306/tcp")
    print(f"UFW Status:\n{out}\n{err}")
    
except Exception as e:
    print(f"Failed: {e}")
finally:
    client.close()

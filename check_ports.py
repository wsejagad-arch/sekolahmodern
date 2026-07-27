import paramiko

host = '203.175.125.118'
port = 26035
username = 'ubuntu'
password = 'wahyu123'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

try:
    print("Connecting...")
    client.connect(host, port=port, username=username, password=password, timeout=10)
    
    stdin, stdout, stderr = client.exec_command("sudo -S iptables -L -n")
    stdin.write(password + '\n')
    stdin.flush()
    print("Iptables:")
    print(stdout.read().decode())
    
    stdin, stdout, stderr = client.exec_command("sudo -S netstat -tlpn")
    print("Netstat:")
    print(stdout.read().decode())
    
except Exception as e:
    print(e)
finally:
    client.close()

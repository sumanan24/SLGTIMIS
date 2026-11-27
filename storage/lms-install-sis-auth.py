import os
import paramiko

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
token = ""
with open(os.path.join(root, ".env"), "r", encoding="utf-8") as handle:
    for line in handle:
        if line.startswith("LMS_AUTH_TOKEN="):
            token = line.split("=", 1)[1].strip()
if len(token) < 32:
    raise SystemExit("token missing")

def read(path):
    with open(os.path.join(root, path), "r", encoding="utf-8") as handle:
        return handle.read()

files = {
    "/tmp/slgti-moodle-same-password.php": read("storage/slgti-moodle-same-password.php"),
    "/tmp/auth_sis.php": read("storage/auth-sis/auth.php"),
    "/tmp/auth_sis_version.php": read("storage/auth-sis/version.php"),
    "/tmp/auth_sis_lang.php": read("storage/auth-sis/lang/en/auth_sis.php"),
    "/tmp/slgti-sis-auth.php": "<?php\nreturn array('url' => 'http://172.16.0.199/SLGTIMIS/api/lms-auth.php', 'token' => '%s');\n" % token,
}

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("172.16.1.230", username="ictadmin", password=os.environ["LMS_SSH_PW"], timeout=15, allow_agent=False, look_for_keys=False)
sftp = client.open_sftp()
for path, body in files.items():
    with sftp.file(path, "w") as remote:
        remote.write(body)
sftp.close()

command = """
set -e
install -d /usr/local/etc /var/www/moodle/auth/sis/lang/en
install -m 640 /tmp/slgti-sis-auth.php /usr/local/etc/slgti-sis-auth.php
chgrp www-data /usr/local/etc/slgti-sis-auth.php
install -m 644 /tmp/auth_sis.php /var/www/moodle/auth/sis/auth.php
install -m 644 /tmp/auth_sis_version.php /var/www/moodle/auth/sis/version.php
install -m 644 /tmp/auth_sis_lang.php /var/www/moodle/auth/sis/lang/en/auth_sis.php
install -m 755 /tmp/slgti-moodle-same-password.php /usr/local/sbin/slgti-moodle-same-password.php
rm -f /tmp/slgti-sis-auth.php /tmp/auth_sis.php /tmp/auth_sis_version.php /tmp/auth_sis_lang.php /tmp/slgti-moodle-same-password.php
php -l /var/www/moodle/auth/sis/auth.php
"""
stdin, stdout, stderr = client.exec_command("sudo -S -p '' bash -s", timeout=60)
stdin.write(os.environ["LMS_SSH_PW"] + "\n")
stdin.write(command)
stdin.channel.shutdown_write()
out = stdout.read().decode("utf-8", "replace")
err = stderr.read().decode("utf-8", "replace")
code = stdout.channel.recv_exit_status()
client.close()
for line in (out + "\n" + err).splitlines():
    lowered = line.lower()
    if "password" in lowered or "token" in lowered:
        continue
    print(line)
raise SystemExit(code)

"""Run one remote command with sudo. Password stays on stdin. Never printed."""
import os
import subprocess
import sys

password = os.environ.get("LMS_SSH_PW", "")
if not password:
    sys.stderr.write("missing LMS_SSH_PW\n")
    sys.exit(2)
remote = sys.argv[1]
cmd = [
    "ssh",
    "-i", "storage/lms-sync-key",
    "-o", "BatchMode=yes",
    "-o", "UserKnownHostsFile=storage/lms-known-hosts",
    "ictadmin@172.16.1.230",
    "sudo -S -p '' bash -c " + "'" + remote.replace("'", "'\\''") + "'",
]
proc = subprocess.run(cmd, input=password + "\n", text=True, capture_output=True)
sys.stdout.write(proc.stdout)
err = proc.stderr.replace(password, "")
sys.stderr.write(err)
sys.exit(proc.returncode)

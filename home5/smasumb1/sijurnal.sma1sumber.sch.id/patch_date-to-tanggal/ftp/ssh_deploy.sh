#!/bin/sh
# ssh_deploy.sh
# Usage:
# ./ssh_deploy.sh <user@host> <port> <path_to_private_key_or_-for-password> <local_zip_path> <remote_target_dir>
# Examples:
# ./ssh_deploy.sh user@host 22 ~/.ssh/id_rsa ./patch_date-to-tanggal.zip /home/user/public_html/sijurnal

set -e
if [ "$#" -lt 5 ]; then
  echo "Usage: $0 <user@host> <port> <key|- for password auth> <local_zip_path> <remote_target_dir>"
  exit 2
fi

REMOTE=$1
PORT=$2
KEY=$3
LOCAL_ZIP=$4
REMOTE_DIR=$5
ZIP_NAME=$(basename "$LOCAL_ZIP")
REMOTE_ZIP_PATH="$REMOTE_DIR/$ZIP_NAME"

echo "Uploading $LOCAL_ZIP to $REMOTE:$REMOTE_ZIP_PATH"
if [ "$KEY" = "-" ]; then
  scp -P "$PORT" "$LOCAL_ZIP" "$REMOTE:$REMOTE_ZIP_PATH"
else
  scp -i "$KEY" -P "$PORT" "$LOCAL_ZIP" "$REMOTE:$REMOTE_ZIP_PATH"
fi

echo "Upload complete. Extracting on remote host..."
if [ "$KEY" = "-" ]; then
  ssh -p "$PORT" "$REMOTE" "mkdir -p '$REMOTE_DIR' && cd '$REMOTE_DIR' && unzip -o '$ZIP_NAME' && echo 'EXTRACT_OK' || echo 'EXTRACT_FAIL'"
else
  ssh -i "$KEY" -p "$PORT" "$REMOTE" "mkdir -p '$REMOTE_DIR' && cd '$REMOTE_DIR' && unzip -o '$ZIP_NAME' && echo 'EXTRACT_OK' || echo 'EXTRACT_FAIL'"
fi

echo "Remote extraction finished. You should verify files on the server (cPanel File Manager or SSH)."
exit 0

#!/bin/zsh
cd "$(dirname "$0")"
TASK_PHP="$(command -v php)"
if [ -z "$TASK_PHP" ] && [ -x /opt/homebrew/bin/php ]; then TASK_PHP=/opt/homebrew/bin/php; fi
"$TASK_PHP" bin/local.php inbox
printf '\nThese are local test messages only. Press Return to close.\n'
read

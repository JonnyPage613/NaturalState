#!/bin/zsh
cd "$(dirname "$0")"
TASK_PHP="$(command -v php)"
if [ -z "$TASK_PHP" ] && [ -x /opt/homebrew/bin/php ]; then TASK_PHP=/opt/homebrew/bin/php; fi
if [ -z "$TASK_PHP" ]; then echo 'PHP 8.2 or newer is needed. See START HERE.md.'; read; exit 1; fi
open http://localhost:8080
"$TASK_PHP" bin/local.php

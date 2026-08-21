#!/bin/bash
ERRORS=0
TOTAL=0
for f in $(find -L wp-content/plugins/native-content-relationships -maxdepth 4 -name "*.php" -not -path "*/vendor/*" -not -path "*/tests/*" -not -path "*/node_modules/*"); do
    TOTAL=$((TOTAL + 1))
    result=$(php -l "$f" 2>&1)
    if [ $? -ne 0 ]; then
        echo "FAIL: $f"
        echo "  $result"
        ERRORS=$((ERRORS + 1))
    fi
done
echo "=== PHP Syntax Check ==="
echo "Files: $TOTAL | Errors: $ERRORS"

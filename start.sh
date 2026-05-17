#!/bin/bash
set -e

MYSQL_DATA=/tmp/mysql_data
MYSQL_RUN=/tmp/mysql_run
MYSQL_SOCK=$MYSQL_RUN/mysql.sock

mkdir -p "$MYSQL_DATA" "$MYSQL_RUN"

# Start MariaDB if not already running
if ! mysql --socket="$MYSQL_SOCK" -u root --connect-timeout=2 -e "SELECT 1;" 2>/dev/null; then
    echo "[startup] Starting MariaDB..."
    rm -f "$MYSQL_SOCK" "$MYSQL_RUN/mysql.pid"
    mysqld --no-defaults \
        --datadir="$MYSQL_DATA" \
        --socket="$MYSQL_SOCK" \
        --pid-file="$MYSQL_RUN/mysql.pid" \
        --port=3306 \
        --user=runner \
        --skip-grant-tables \
        --skip-networking=0 \
        --bind-address=127.0.0.1 \
        2>>"$MYSQL_RUN/mysql.log" &

    echo "[startup] Waiting for MariaDB..."
    for i in $(seq 1 30); do
        sleep 1
        if mysql --socket="$MYSQL_SOCK" -u root --connect-timeout=2 -e "SELECT 1;" 2>/dev/null; then
            echo "[startup] MariaDB ready after $i seconds"
            break
        fi
    done
fi

# Create database if not exists
mysql --socket="$MYSQL_SOCK" -u root -e "CREATE DATABASE IF NOT EXISTS aeterna_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" 2>&1

# Import schema if tables don't exist yet
TABLE_COUNT=$(mysql --socket="$MYSQL_SOCK" -u root aeterna_erp -e "SHOW TABLES;" 2>/dev/null | wc -l)
if [ "$TABLE_COUNT" -lt 5 ]; then
    echo "[startup] Importing database schema..."
    # Remove the SET AUTOCOMMIT=0 / START TRANSACTION wrapper since skip-grant-tables may cause issues
    mysql --socket="$MYSQL_SOCK" -u root aeterna_erp < /home/runner/workspace/if0_41872581_aeterna_erp.sql 2>&1 | tail -5
    echo "[startup] Schema imported."
else
    echo "[startup] Database already has $TABLE_COUNT tables, skipping import."
fi

echo "[startup] Starting PHP built-in server on 0.0.0.0:5000..."
exec php -S 0.0.0.0:5000 -t /home/runner/workspace /home/runner/workspace/router.php

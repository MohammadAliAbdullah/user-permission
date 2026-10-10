#!/usr/bin/env bash
# Apply RBAC migrations to bnsis_permission (idempotent — safe to re-run, additive only).
# Usage: ./database/run_migrations.sh [mysql-user]  (default: root)
set -euo pipefail
DB_USER="${1:-root}"
DIR="$(cd "$(dirname "$0")" && pwd)"
for f in "$DIR"/migrations/*.sql; do
  echo "==> Applying $(basename "$f")"
  mysql -u "$DB_USER" < "$f"
done
echo "All migrations applied to bnsis_permission."

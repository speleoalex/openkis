#!/usr/bin/env bash
# Back up the OpenKIS database and data folder (misc/: uploads, photos,
# surveys, table definitions) into ./backups/<timestamp>/.
# Restore: see README.md.
set -euo pipefail
cd "$(dirname "$0")"
set -a; . ./.env; set +a
TS=$(date +%Y%m%d-%H%M%S)
OUT="backups/$TS"
mkdir -p "$OUT"
docker compose exec -T db mariadb-dump -u root -p"$MARIADB_ROOT_PASSWORD" \
    --single-transaction --routines "$OPENKIS_DB_NAME" | gzip > "$OUT/db.sql.gz"
docker compose exec -T web tar -C /var/www/html -czf - misc extra/openkis_config.local.php > "$OUT/files.tar.gz"
echo "backup written to $OUT"
ls -lh "$OUT"

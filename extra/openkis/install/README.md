# OpenKIS install files

- `openkis_install.php` — included on every request by `extra/openkis.inc.php`.
  Copies the table definitions below into `misc/fndatabase/` when they are
  missing (never overwrites). The MySQL driver then creates the tables and adds
  missing columns automatically.
- `misc/fndatabase/ctl_*.php` — OpenKIS table definitions (caves, springs,
  surveys, photos, glaciers, ...). To ship a new field, add it here: existing
  installations pick it up on the next request (`ALTER TABLE ... ADD`).
- `misc/fndatabase/fn_groups/`, `fn_settings/`, `fn_users/` — legacy seeds,
  not installed automatically (groups are created from the section configs,
  settings and the admin user are set by the wizard).
- `demo_cave.sql` — one sample cave, import after the wizard:

  ```bash
  /opt/lampp/bin/mysql -u root openkis < extra/openkis/install/demo_cave.sql
  ```

Full installation instructions are in the top-level `README.md`.

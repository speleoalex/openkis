-- OpenKIS: secondary indexes for the most used and heaviest queries.
--
-- The finis MySQL driver creates every table with a primary key only, so
-- every other lookup is a full table scan. See INDEXES.md for the query
-- each index serves.
--
-- Safe to run more than once, on MySQL 5.7+ / MariaDB 10.2+: an index is
-- added only if its table and all its columns exist and no index with that
-- name is there yet. Tables are created by OpenKIS on first use, so run it
-- again after new sections have been used (or simply after every upgrade).
--
--   mariadb -u root -p openkis < sql/openkis_indexes.sql
--
-- The framework only ever ADDs columns, so it never drops these indexes.

DROP PROCEDURE IF EXISTS openkis_add_index;

DELIMITER //
CREATE PROCEDURE openkis_add_index(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_columns VARCHAR(255))
BEGIN
    DECLARE v_missing INT DEFAULT 0;
    DECLARE v_rest VARCHAR(255);
    DECLARE v_col VARCHAR(64);

    IF EXISTS (SELECT 1 FROM information_schema.tables
               WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics
                       WHERE table_schema = DATABASE() AND table_name = p_table
                         AND index_name = p_index) THEN
        SET v_rest = p_columns;
        WHILE v_rest <> '' DO
            SET v_col = TRIM(SUBSTRING_INDEX(v_rest, ',', 1));
            IF LOCATE(',', v_rest) > 0 THEN
                SET v_rest = SUBSTRING(v_rest, LOCATE(',', v_rest) + 1);
            ELSE
                SET v_rest = '';
            END IF;
            IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                           WHERE table_schema = DATABASE() AND table_name = p_table
                             AND column_name = v_col) THEN
                SET v_missing = v_missing + 1;
            END IF;
        END WHILE;
        IF v_missing = 0 THEN
            SET @openkis_sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (`',
                                      REPLACE(REPLACE(p_columns, ' ', ''), ',', '`,`'), '`)');
            PREPARE openkis_stmt FROM @openkis_sql;
            EXECUTE openkis_stmt;
            DEALLOCATE PREPARE openkis_stmt;
        END IF;
    END IF;
END //
DELIMITER ;

-- 1. Cave code: map/KML/GPX/JSON filter_code, links from photos, surveys,
--    attachments, fauna, springs, bibliography and other caves (FK target)
CALL openkis_add_index('ctl_caves',        'okx_code',         'code');
CALL openkis_add_index('ctl_artificials',  'okx_code',         'code');
CALL openkis_add_index('ctl_glacial',      'okx_code',         'code');

-- 2. Records linked to a cave / artificial cavity / glacial cave
--    (view footers, photo server, dbview navigation by codecave)
CALL openkis_add_index('ctl_photos',                  'okx_codecave',       'codecave');
CALL openkis_add_index('ctl_photos_artificials',      'okx_codeartificial', 'codeartificial');
CALL openkis_add_index('ctl_surveys',                 'okx_codecave',       'codecave');
CALL openkis_add_index('ctl_surveys_artificials',     'okx_codeartificial', 'codeartificial');
CALL openkis_add_index('ctl_surveys_glacial',         'okx_codeglacial',    'codeglacial');
CALL openkis_add_index('ctl_attachments',             'okx_codecave',       'codecave');
CALL openkis_add_index('ctl_attachments_artificials', 'okx_codeartificial', 'codeartificial');
CALL openkis_add_index('ctl_faunacave',               'okx_codecave',       'codecave');
CALL openkis_add_index('ctl_faunacave',               'okx_codeartificial', 'codeartificial');
CALL openkis_add_index('ctl_faunacave',               'okx_name',           'name');
CALL openkis_add_index('ctl_springs',                 'okx_codecave',       'codecave');

-- 3. Default list order (ORDER BY recordupdate DESC LIMIT n) of the sections
CALL openkis_add_index('ctl_caves',        'okx_recordupdate', 'recordupdate');
CALL openkis_add_index('ctl_artificials',  'okx_recordupdate', 'recordupdate');
CALL openkis_add_index('ctl_glacial',      'okx_recordupdate', 'recordupdate');
CALL openkis_add_index('ctl_springs',      'okx_recordupdate', 'recordupdate');
CALL openkis_add_index('ctl_bibliography', 'okx_recordupdate', 'recordupdate');

-- 4. Navigation groups (filter_<field> = value, and the per-value counts)
CALL openkis_add_index('ctl_caves',        'okx_regione',   'regione');
CALL openkis_add_index('ctl_caves',        'okx_provincia', 'provincia');
CALL openkis_add_index('ctl_caves',        'okx_comune',    'comune');
CALL openkis_add_index('ctl_caves',        'okx_areas',     'areas');
CALL openkis_add_index('ctl_artificials',  'okx_regione',   'regione');
CALL openkis_add_index('ctl_artificials',  'okx_provincia', 'provincia');
CALL openkis_add_index('ctl_artificials',  'okx_comune',    'comune');
CALL openkis_add_index('ctl_glacial',      'okx_glaciers',  'glaciers');
CALL openkis_add_index('ctl_bibliography', 'okx_year',      'year');
CALL openkis_add_index('ctl_bibliography', 'okx_province',  'province');
CALL openkis_add_index('ctl_bibliography', 'okx_zone',      'zone');
CALL openkis_add_index('ctl_springs',      'okx_username',  'username');

-- 5. Record history: WHERE id = x ORDER BY recordupdate DESC
CALL openkis_add_index('ctl_caves_versions',       'okx_id_recordupdate', 'id,recordupdate');
CALL openkis_add_index('ctl_artificials_versions', 'okx_id_recordupdate', 'id,recordupdate');
CALL openkis_add_index('ctl_glacial_versions',     'okx_id_recordupdate', 'id,recordupdate');

-- 6. Foreign-key targets that are not primary keys (one lookup per record
--    and per field when lists, views and exports show linked values)
CALL openkis_add_index('ctl_areas',     'okx_code',            'code');
CALL openkis_add_index('ctl_glaciers',  'okx_code',            'code');
CALL openkis_add_index('ctl_licenses',  'okx_name',            'name');
CALL openkis_add_index('ctl_fauna',     'okx_scientific_name', 'scientific_name');

-- 7. Framework tables read on every page or every login
CALL openkis_add_index('fn_notifications', 'okx_username_displayed', 'username,displayed');
CALL openkis_add_index('fn_userssessions', 'okx_username_varname',   'username,varname');
CALL openkis_add_index('fn_users',         'okx_email',              'email');

DROP PROCEDURE IF EXISTS openkis_add_index;

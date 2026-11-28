-- ---------------------------------------------------------------------------
-- MonsterList — every outstanding schema change, for Hostinger (v167).
--
-- SAFE TO RUN AS MANY TIMES AS YOU LIKE. Anything already applied is skipped
-- silently. If the whole thing has already run, running it again does nothing
-- and reports the same.
--
-- WHY IT LOOKS LIKE THIS
-- The repo's other migrations check themselves by reading information_schema,
-- which Hostinger denies to the database user (#1044). The previous version
-- of this file dropped that check and used plain ALTER statements, which then
-- stopped dead on #1060 Duplicate column name the moment one column already
-- existed. This version uses a tiny stored procedure that runs a statement
-- and swallows exactly three errors:
--
--     1060  duplicate column   - already added
--     1061  duplicate key      - already added
--     1091  can't drop         - nothing to drop
--
-- Nothing else is swallowed, so a real problem still stops it.
--
-- HOW TO RUN IT
-- phpMyAdmin -> SQL -> paste the whole file -> Go. Leave the Delimiter box
-- alone; the DELIMITER lines below handle it.
--
-- NOTHING HERE DELETES OR REWRITES DATA. Every statement only adds a column,
-- a key or a table. The procedure is removed again at the end.
--
-- IF 'CREATE PROCEDURE' IS DENIED on your host, use check-schema.sql instead
-- to see what is missing, and tell me — I will send plain statements for just
-- the missing pieces.
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS ml_try;

DELIMITER //
CREATE PROCEDURE ml_try(IN ddl TEXT)
BEGIN
  DECLARE CONTINUE HANDLER FOR 1060, 1061, 1091 BEGIN END;
  SET @s = ddl;
  PREPARE st FROM @s;
  EXECUTE st;
  DEALLOCATE PREPARE st;
END //
DELIMITER ;

-- == columns and keys =====================================================
CALL ml_try('ALTER TABLE users ADD COLUMN toutify_sub CHAR(26) DEFAULT NULL');   -- users.toutify_sub
CALL ml_try('ALTER TABLE users ADD COLUMN toutify_status VARCHAR(20) DEFAULT NULL');   -- users.toutify_status
CALL ml_try('ALTER TABLE users ADD COLUMN toutify_updated_at INT UNSIGNED DEFAULT NULL');   -- users.toutify_updated_at
CALL ml_try('ALTER TABLE users ADD COLUMN business_name VARCHAR(180) DEFAULT NULL');   -- users.business_name
CALL ml_try('ALTER TABLE users ADD COLUMN phone VARCHAR(40) DEFAULT NULL');   -- users.phone
CALL ml_try('ALTER TABLE users ADD COLUMN toutify_tools TEXT NULL');   -- users.toutify_tools
CALL ml_try('ALTER TABLE businesses ADD COLUMN toutify_website_id CHAR(26) DEFAULT NULL');   -- businesses.toutify_website_id
CALL ml_try('ALTER TABLE businesses ADD COLUMN toutify_updated_at INT UNSIGNED DEFAULT NULL');   -- businesses.toutify_updated_at
CALL ml_try('ALTER TABLE businesses ADD COLUMN toutify_tier VARCHAR(20) DEFAULT NULL');   -- businesses.toutify_tier
CALL ml_try('ALTER TABLE businesses ADD COLUMN toutify_removed TINYINT(1) NOT NULL DEFAULT 0');   -- businesses.toutify_removed
CALL ml_try('ALTER TABLE businesses ADD COLUMN featured_manual TINYINT(1) NOT NULL DEFAULT 0');   -- businesses.featured_manual
CALL ml_try('ALTER TABLE users ADD UNIQUE KEY uq_users_toutify_sub (toutify_sub)');   -- key uq_users_toutify_sub
CALL ml_try('ALTER TABLE businesses ADD KEY idx_biz_toutify_website (toutify_website_id)');   -- key idx_biz_toutify_website
CALL ml_try('ALTER TABLE businesses ADD KEY idx_biz_featured_manual (featured_manual, status)');   -- key idx_biz_featured_manual

-- == tables ===============================================================
-- CREATE TABLE supports IF NOT EXISTS, so these need no wrapper. Lifted
-- verbatim from upgrade-toutify.sql rather than retyped.
CREATE TABLE IF NOT EXISTS toutify_sessions (
  session_id VARCHAR(128) NOT NULL,
  sid        CHAR(32) DEFAULT NULL,          -- Toutify's session id, from the `sid` claim
  sub        CHAR(26) NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  revoked_at INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (session_id),
  KEY idx_tf_sid (sid),
  KEY idx_tf_sub (sub)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS toutify_events (
  event_id    VARCHAR(40) NOT NULL,
  type        VARCHAR(40) DEFAULT NULL,
  received_at INT UNSIGNED NOT NULL,
  PRIMARY KEY (event_id),
  KEY idx_tfe_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE ml_try;

-- ---------------------------------------------------------------------------
-- Check. Reads your own tables, so it runs on Hostinger. Every row should say
-- OK. COUNT(column) returns a row even on an empty table and still fails
-- loudly if the column is genuinely missing.
-- ---------------------------------------------------------------------------
SELECT 'users.toutify_sub'             AS piece, 'OK' AS state, COUNT(toutify_sub)        AS with_a_value FROM users
UNION ALL SELECT 'users.toutify_tools',        'OK', COUNT(toutify_tools)        FROM users
UNION ALL SELECT 'users.business_name',        'OK', COUNT(business_name)        FROM users
UNION ALL SELECT 'users.phone',                'OK', COUNT(phone)                FROM users
UNION ALL SELECT 'businesses.toutify_website_id','OK', COUNT(toutify_website_id) FROM businesses
UNION ALL SELECT 'businesses.toutify_tier',    'OK', COUNT(toutify_tier)         FROM businesses
UNION ALL SELECT 'businesses.featured_manual', 'OK', COUNT(featured_manual)      FROM businesses
UNION ALL SELECT 'toutify_sessions',           'OK', COUNT(*)                    FROM toutify_sessions
UNION ALL SELECT 'toutify_events',             'OK', COUNT(*)                    FROM toutify_events;

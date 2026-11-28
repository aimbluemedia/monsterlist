-- ---------------------------------------------------------------------------
-- MonsterList -> Toutify: the database part, on its own.
--
-- This is the Toutify section of database/upgrade-all.sql, lifted out so it can
-- be pasted straight into phpMyAdmin. Running either file gives the same
-- result; running both is harmless.
--
-- HOW TO RUN IT
--   hPanel -> phpMyAdmin -> click your database in the left-hand panel ->
--   SQL tab -> paste all of this -> Go.
--
--   Clicking the database first matters: the report at the end reads
--   information_schema, and without a database selected it has nothing to
--   report on.
--
-- WHAT IT DOES
--   Adds columns and two tables. Every column is NULL by default, so nothing
--   on the site changes until Toutify is switched on. It does not touch
--   users.password_hash, and it deletes nothing.
--
--   Safe to run twice: every step checks first and skips what is already there.
--
-- AFTERWARDS
--   Superadmin -> Diagnostics -> "Toutify sign-in" should show a tick against
--   "Database upgraded".
-- ---------------------------------------------------------------------------

-- ===========================================================================
-- Toutify (toutify.com) — the parent account system.
--
-- Every column here is NULL by default and nothing below touches
-- users.password_hash. A database that has run this is still a database that
-- works exactly as before: with TOUTIFY_MODE unset or 'test', none of it is
-- read on a normal page load.
--
-- The link is by Toutify's `sub`, a permanent 26-character id. Nothing of
-- MonsterList's own is replaced — the member keeps its id, its email and its
-- password, and toutify_sub is simply the other system's name for the same
-- person.
-- ===========================================================================

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'toutify_sub') > 0,
  'DO 0', 'ALTER TABLE users ADD COLUMN toutify_sub CHAR(26) DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- UNIQUE, not just indexed: two members pointing at one Toutify account would
-- mean a sign-in that could land on either, and which one it picked would
-- depend on row order.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND INDEX_NAME = 'uq_users_toutify_sub') > 0,
  'DO 0', 'ALTER TABLE users ADD UNIQUE KEY uq_users_toutify_sub (toutify_sub)');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'toutify_status') > 0,
  'DO 0', 'ALTER TABLE users ADD COLUMN toutify_status VARCHAR(20) DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'toutify_updated_at') > 0,
  'DO 0', 'ALTER TABLE users ADD COLUMN toutify_updated_at INT UNSIGNED DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- Toutify sends a member-level name, business name and phone. MonsterList had
-- nowhere to put the last two: `users` has `name` (which sign-up fills with the
-- domain, not a person) and nothing else. These are the two columns that were
-- missing, added NULL so nothing changes until something writes them.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'business_name') > 0,
  'DO 0', 'ALTER TABLE users ADD COLUMN business_name VARCHAR(180) DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'phone') > 0,
  'DO 0', 'ALTER TABLE users ADD COLUMN phone VARCHAR(40) DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;


-- Listings. Several MonsterList listings can share one Toutify website — the
-- same address listed twice — so this is a plain index, not a unique key.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND COLUMN_NAME = 'toutify_website_id') > 0,
  'DO 0', 'ALTER TABLE businesses ADD COLUMN toutify_website_id CHAR(26) DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND INDEX_NAME = 'idx_biz_toutify_website') > 0,
  'DO 0', 'ALTER TABLE businesses ADD KEY idx_biz_toutify_website (toutify_website_id)');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND COLUMN_NAME = 'toutify_updated_at') > 0,
  'DO 0', 'ALTER TABLE businesses ADD COLUMN toutify_updated_at INT UNSIGNED DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND COLUMN_NAME = 'toutify_sites') > 0,
  'DO 0', 'ALTER TABLE businesses ADD COLUMN toutify_sites TEXT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND COLUMN_NAME = 'toutify_tier') > 0,
  'DO 0', 'ALTER TABLE businesses ADD COLUMN toutify_tier VARCHAR(20) DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- Toutify's word for "this website is no longer theirs". The listing is hidden
-- from the public site, never deleted: a removal that turns out to be a mistake
-- has to be reversible, and a deleted listing takes its reviews with it.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND COLUMN_NAME = 'toutify_removed') > 0,
  'DO 0', 'ALTER TABLE businesses ADD COLUMN toutify_removed TINYINT(1) NOT NULL DEFAULT 0');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;


-- One row per signed-in browser, so that a back-channel logout from Toutify can
-- end a session this request is not holding.
--
-- PHP stores sessions as files keyed by id, and there is no way to open someone
-- else's. So the row is the authority instead: revoked_at is stamped, and the
-- next request that session makes sees it and signs itself out. The cost is one
-- indexed lookup per request for signed-in members only.
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

-- Webhook ids already seen. Toutify retries for 24 hours, so the same event
-- arrives more than once as a matter of course; the primary key is what makes
-- applying it twice impossible rather than merely unlikely. Rows older than 30
-- days are dropped each time this file runs.
CREATE TABLE IF NOT EXISTS toutify_events (
  event_id    VARCHAR(40) NOT NULL,
  type        VARCHAR(40) DEFAULT NULL,
  received_at INT UNSIGNED NOT NULL,
  PRIMARY KEY (event_id),
  KEY idx_tfe_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM toutify_events WHERE received_at < (UNIX_TIMESTAMP() - 30*86400);


-- ---------------------------------------------------------------------------
-- Did it work? Every line should say OK.
-- ---------------------------------------------------------------------------
SELECT 'users.toutify_sub' AS piece, IF(COUNT(*)>0,'OK','MISSING') AS state FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='toutify_sub'
UNION ALL SELECT 'users.toutify_status',          IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='toutify_status'
UNION ALL SELECT 'users.toutify_updated_at',      IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='toutify_updated_at'
UNION ALL SELECT 'users.business_name',           IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='business_name'
UNION ALL SELECT 'users.phone',                   IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='phone'
UNION ALL SELECT 'businesses.toutify_website_id', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='businesses' AND COLUMN_NAME='toutify_website_id'
UNION ALL SELECT 'businesses.toutify_sites', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='businesses' AND COLUMN_NAME='toutify_sites'
UNION ALL SELECT 'businesses.toutify_updated_at', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='businesses' AND COLUMN_NAME='toutify_updated_at'
UNION ALL SELECT 'businesses.toutify_tier',       IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='businesses' AND COLUMN_NAME='toutify_tier'
UNION ALL SELECT 'businesses.toutify_removed',    IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='businesses' AND COLUMN_NAME='toutify_removed'
UNION ALL SELECT 'toutify_sessions table',        IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='toutify_sessions'
UNION ALL SELECT 'toutify_events table',          IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='toutify_events';

-- ---------------------------------------------------------------------------
-- MonsterList — every outstanding schema change, for Hostinger (v166).
--
-- WHY THIS FILE
-- upgrade-toutify.sql, upgrade-all.sql and the rest guard themselves by
-- reading information_schema. Hostinger denies that to the database user:
--
--     #1044 - Access denied for user '..._monster'@'127.0.0.1'
--             to database 'information_schema'
--
-- so on that host the guard is what fails and nothing after it runs. This
-- file does the same work as plain statements. It replaces, in one go:
--
--     upgrade-toutify.sql          (the whole Toutify integration)
--     upgrade-featured-manual.sql  (the manual Featured toggle)
--     upgrade-toutify-tools.sql    (which tools a member has active)
--
-- HOW TO RUN IT
-- Paste the whole thing into phpMyAdmin -> SQL and press Go.
--
-- It is NOT self-checking. If part of it has already been applied you will
-- see one of these, and it is harmless:
--
--     #1060 - Duplicate column name '...'   -> that column is already there
--     #1061 - Duplicate key name '...'      -> that index is already there
--
-- phpMyAdmin stops at the first error. Delete the statements above it and
-- press Go again to run the rest. NOTHING HERE DELETES OR REWRITES DATA:
-- every statement only adds a column, an index or a table.
-- ---------------------------------------------------------------------------

-- == 1. Toutify: columns on users ==========================================
ALTER TABLE users ADD COLUMN toutify_sub CHAR(26) DEFAULT NULL;
ALTER TABLE users ADD COLUMN toutify_status VARCHAR(20) DEFAULT NULL;
ALTER TABLE users ADD COLUMN toutify_updated_at INT UNSIGNED DEFAULT NULL;
ALTER TABLE users ADD COLUMN business_name VARCHAR(180) DEFAULT NULL;
ALTER TABLE users ADD COLUMN phone VARCHAR(40) DEFAULT NULL;

-- == 2. Toutify: columns on businesses =====================================
ALTER TABLE businesses ADD COLUMN toutify_website_id CHAR(26) DEFAULT NULL;
ALTER TABLE businesses ADD COLUMN toutify_updated_at INT UNSIGNED DEFAULT NULL;
ALTER TABLE businesses ADD COLUMN toutify_tier VARCHAR(20) DEFAULT NULL;
ALTER TABLE businesses ADD COLUMN toutify_removed TINYINT(1) NOT NULL DEFAULT 0;

-- == 3. Toutify: keys ======================================================
-- uq_users_toutify_sub is UNIQUE and matters for correctness, not speed: it
-- is what stops two accounts here claiming the same Toutify identity.
ALTER TABLE users ADD UNIQUE KEY uq_users_toutify_sub (toutify_sub);
ALTER TABLE businesses ADD KEY idx_biz_toutify_website (toutify_website_id);

-- == 4. Toutify: tables ====================================================
-- These carry IF NOT EXISTS, which MySQL supports for CREATE TABLE, so
-- they are already safe to re-run on their own.
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

-- == 5. The manual Featured toggle =========================================
-- Its own column, never businesses.tier: the tier is a copy of users.plan and
-- the next plan change would wipe a mark written there.
ALTER TABLE businesses ADD COLUMN featured_manual TINYINT(1) NOT NULL DEFAULT 0;
CREATE INDEX idx_biz_featured_manual ON businesses (featured_manual, status);

-- == 6. Which Toutify tools a member has active ============================
ALTER TABLE users ADD COLUMN toutify_tools TEXT NULL;

-- ---------------------------------------------------------------------------
-- Check it worked. These read your own tables, not information_schema, so
-- they run on Hostinger. COUNT(column) returns a row even when the table is
-- empty, and still fails loudly if the column is missing.
-- ---------------------------------------------------------------------------
SELECT 'users.toutify_sub'          AS checked, COUNT(toutify_sub)        AS with_a_value FROM users;
SELECT 'users.toutify_tools'        AS checked, COUNT(toutify_tools)      AS with_a_value FROM users;
SELECT 'businesses.toutify_website_id' AS checked, COUNT(toutify_website_id) AS with_a_value FROM businesses;
SELECT 'businesses.featured_manual' AS checked, COUNT(featured_manual)    AS with_a_value FROM businesses;
SELECT 'toutify_sessions rows'      AS checked, COUNT(*)                  AS with_a_value FROM toutify_sessions;
SELECT 'toutify_events rows'        AS checked, COUNT(*)                  AS with_a_value FROM toutify_events;

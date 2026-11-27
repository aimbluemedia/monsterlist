-- ---------------------------------------------------------------------------
-- MonsterList — the two outstanding schema changes, for Hostinger (v161).
--
-- WHY THIS FILE EXISTS
-- The other upgrade files check whether a column is already there by reading
-- information_schema. Hostinger denies that to the database user:
--
--     #1044 - Access denied for user '..._monster'@'127.0.0.1'
--             to database 'information_schema'
--
-- so those guards cannot run at all there. This file does the same two
-- changes as plain statements instead. They are NOT self-checking.
--
-- HOW TO RUN IT
-- Paste the whole thing into phpMyAdmin → SQL and press Go. If a statement
-- has already been applied you will see one of these, and it is harmless:
--
--     #1060 - Duplicate column name 'featured_manual'   -> already added
--     #1061 - Duplicate key name 'idx_biz_featured_manual' -> already added
--     #1060 - Duplicate column name 'toutify_tools'     -> already added
--
-- phpMyAdmin stops at the first error, so if you hit one, delete the
-- statements above it and press Go again to run the rest. Nothing here
-- deletes or rewrites any data — both statements only add.
-- ---------------------------------------------------------------------------

-- 1. The manual Featured flag (v154). Its own column, never businesses.tier:
--    the tier is a copy of users.plan and the next plan change would wipe a
--    mark written there.
ALTER TABLE businesses ADD COLUMN featured_manual TINYINT(1) NOT NULL DEFAULT 0;

-- 2. Its index. Placement reads the flag on every city and category page,
--    alongside status, which those queries also filter on.
CREATE INDEX idx_biz_featured_manual ON businesses (featured_manual, status);

-- 3. Which Toutify tools a member has active (v160), as a JSON object.
ALTER TABLE users ADD COLUMN toutify_tools TEXT NULL;

-- ---------------------------------------------------------------------------
-- Check it worked. This reads your own tables, not information_schema, so it
-- runs on Hostinger. Both should return a row with the new column in it.
-- ---------------------------------------------------------------------------
-- COUNT(column) rather than SELECT ... LIMIT 1: it returns a row even when
-- the table is empty, and it still fails loudly if the column is missing.
SELECT 'businesses.featured_manual' AS column_checked, COUNT(featured_manual) AS rows_with_a_value FROM businesses;
SELECT 'users.toutify_tools'        AS column_checked, COUNT(toutify_tools)   AS rows_with_a_value FROM users;

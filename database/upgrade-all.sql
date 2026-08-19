-- ---------------------------------------------------------------------------
-- MonsterList — bring any database up to the current release, in one file.
--
-- WHAT THIS IS FOR
--   The numbered upgrade-v*.sql files each have to be run once, in order, and
--   a plain "ALTER TABLE ... ADD COLUMN" fails outright if the column is
--   already there — which stops the rest of the batch in phpMyAdmin. So a
--   half-applied database is easy to end up with and hard to get out of.
--
--   This file replaces all of them. Every step checks first and skips what is
--   already done, so it is safe to run on a database that is fully up to date,
--   partly up to date, or years behind. Run it as many times as you like.
--
-- HOW TO RUN IT
--   hPanel -> phpMyAdmin -> click your database on the left -> SQL tab ->
--   paste this whole file -> Go.
--
--   It ends with a report listing every table and column it cares about, so
--   you can see the result rather than trust it. Every line should say OK.
--
--   IF ONLY THE REPORT ERRORS, THE UPGRADE STILL WORKED. The report is the
--   last thing in the file and changes nothing; phpMyAdmin has a habit of
--   losing track of which database it is in once a query reads
--   information_schema, which is where any "#1109 - Unknown table ... in
--   information_schema" comes from. Everything above it has already run.
--   Superadmin -> Diagnostics reports the same state from inside the site,
--   and is the better place to check.
--
-- WHAT IT COVERS
--   v4  blocklist table
--   v5  users.website
--   v6  businesses.review_links
--   v7  tokens, the Profile section, the article queue
--   v8  per-plan token economics
--   plus the two Stripe pieces, for installs old enough to predate them.
--
-- It only ever adds. Nothing here drops a table, drops a column, or deletes a
-- row, so it cannot cost you data.
-- ---------------------------------------------------------------------------


-- ===========================================================================
-- v4 — blocklist: emails that may not sign up, domains that may not be listed
-- ===========================================================================
CREATE TABLE IF NOT EXISTS blocklist (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kind       ENUM('email','domain') NOT NULL,
  value      VARCHAR(190) NOT NULL,
  reason     VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_blocklist (kind, value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===========================================================================
-- v7 — the token ledger, the daily view record, the article queue
-- ===========================================================================

-- Every token movement, ever. Nothing is deleted, so a member can always be
-- shown why their balance is what it is.
--   reason    — 'monthly', 'promo:view', 'promo:submit', 'staff:adjust'
--   once_key  — set ONLY on credits that must happen at most once, e.g.
--               'monthly:2026-08'. The unique index then makes granting
--               idempotent however many times it is attempted. Repeatable
--               movements leave it NULL, and MySQL lets NULLs repeat in a
--               unique index — which is the whole reason it is its own column
--               rather than the unique key sitting on `reason`.
CREATE TABLE IF NOT EXISTS token_events (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  delta       INT NOT NULL,
  reason      VARCHAR(64) NOT NULL,
  once_key    VARCHAR(64) DEFAULT NULL,
  note        VARCHAR(255) DEFAULT NULL,
  ref_type    VARCHAR(32) DEFAULT NULL,
  ref_id      INT UNSIGNED DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY k_user (user_id, id),
  KEY k_daily (user_id, reason, created_at),
  UNIQUE KEY u_once (user_id, once_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One earning per member per promotion per day, enforced by the unique key
-- rather than by remembering to check.
CREATE TABLE IF NOT EXISTS promotion_views (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  promotion_id INT UNSIGNED NOT NULL,
  day          DATE NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY u_once_a_day (user_id, promotion_id, day),
  KEY k_daily (user_id, day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The Featured tier's monthly article: the member briefs it, staff write and
-- publish it, then post it out across the network's own channels.
CREATE TABLE IF NOT EXISTS articles (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  business_id  INT UNSIGNED DEFAULT NULL,
  month        CHAR(7) NOT NULL,               -- YYYY-MM, one per member per month
  topic        VARCHAR(200) NOT NULL,
  brief        TEXT DEFAULT NULL,
  status       ENUM('requested','writing','published') NOT NULL DEFAULT 'requested',
  url          VARCHAR(600) DEFAULT NULL,      -- where staff published it
  staff_note   VARCHAR(500) DEFAULT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY u_month (user_id, month),
  KEY k_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ===========================================================================
-- Stripe — present since the first release, guarded here for old installs
-- ===========================================================================
CREATE TABLE IF NOT EXISTS subscriptions (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id                INT UNSIGNED NOT NULL,
  plan                   ENUM('pro','featured') NOT NULL,
  status                 ENUM('active','past_due','canceled') NOT NULL DEFAULT 'active',
  stripe_subscription_id VARCHAR(64) DEFAULT NULL,
  current_period_end     DATETIME DEFAULT NULL,
  created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_subs_user (user_id),
  UNIQUE KEY uniq_stripe_sub (stripe_subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===========================================================================
-- Columns.
--
-- MySQL has no "ADD COLUMN IF NOT EXISTS" that works everywhere — MariaDB
-- accepts it, MySQL 8 does not — so each one asks the catalogue first and
-- builds either the ALTER or a do-nothing statement. That is what makes this
-- file safe to re-run, and it works on both.
-- ===========================================================================

-- v5: the domain given at sign-up. Checked against the blocklist before an
-- account exists, and used to pre-fill the listing and drive AI fill.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'website') > 0,
  'DO 0',
  'ALTER TABLE users ADD COLUMN website VARCHAR(190) DEFAULT NULL AFTER name');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- v6: review-site profiles from the setup wizard. Without it the "Our Reviews"
-- card can never appear, and saving a listing fails.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND COLUMN_NAME = 'review_links') > 0,
  'DO 0',
  'ALTER TABLE businesses ADD COLUMN review_links TEXT DEFAULT NULL AFTER social');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- v7: the long-form Profile section, shown on paid storefronts only.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND COLUMN_NAME = 'profile') > 0,
  'DO 0',
  'ALTER TABLE businesses ADD COLUMN profile LONGTEXT NULL AFTER description');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- v7: cached token balance. token_events above is the source of truth; this is
-- kept in step with it so every page showing a balance is one column read.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'token_balance') > 0,
  'DO 0',
  'ALTER TABLE users ADD COLUMN token_balance INT NOT NULL DEFAULT 0');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- Stripe's id for this member as a customer, so a second purchase reuses the
-- card on file instead of creating a duplicate customer.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'stripe_customer_id') > 0,
  'DO 0',
  'ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(64) DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;


-- ===========================================================================
-- v7 + v8 — token economics. All of it editable later in Superadmin -> Settings,
-- so these are starting values: "value = value" means a setting you have
-- already changed is left exactly as you set it.
-- ===========================================================================
INSERT INTO settings (name, value) VALUES
  -- What a promotion costs, the same for everyone. The plan decides how fast
  -- you earn and how many you may run — not what one costs.
  ('tokens_cost_promo',      '10'),

  -- Handed out on the member's first page load each month.
  ('tokens_grant_free',      '10'),
  ('tokens_grant_pro',       '150'),
  ('tokens_grant_featured',  '400'),

  -- Earned for opening someone else's promotion, by the viewer's plan.
  ('tokens_earn_free',       '2'),
  ('tokens_earn_pro',        '3'),
  ('tokens_earn_featured',   '4'),

  -- Most a member can earn in one day, by plan.
  ('tokens_daily_free',      '20'),
  ('tokens_daily_pro',       '40'),
  ('tokens_daily_featured',  '60'),

  -- Promotions a member may submit per calendar month. This is the ceiling
  -- effort cannot lift: tokens buy a promotion, the plan decides how many.
  ('promos_max_free',        '4'),
  ('promos_max_pro',         '20'),
  ('promos_max_featured',    '60'),

  -- Days of extra freshness a paid promotion carries in the feed, so it sits
  -- higher for longer without burying free members permanently.
  ('feed_boost_pro',         '7'),
  ('feed_boost_featured',    '14'),

  -- The two Stripe price IDs. Left empty on purpose — paste yours into
  -- Superadmin -> Settings, or here. They start "price_", not "prod_".
  ('stripe_price_pro',       ''),
  ('stripe_price_featured',  '')
ON DUPLICATE KEY UPDATE value = value;

-- v8 rebalanced two of the v7 grants. Only touches them if they are still
-- sitting at the exact v7 value, so a figure you have since chosen is safe.
UPDATE settings SET value = '10'  WHERE name = 'tokens_grant_free' AND value = '20';
UPDATE settings SET value = '150' WHERE name = 'tokens_grant_pro'  AND value = '120';


-- ===========================================================================
-- Report. Every line should read OK. Anything else is worth telling me about.
--
-- ORDER MATTERS HERE, for a reason that is nothing to do with SQL.
--
-- phpMyAdmin reads the statements it runs and, on seeing a query whose FROM is
-- `information_schema`, switches the database it thinks you are working in.
-- Anything after that looking for one of your own tables then fails with
--   #1109 - Unknown table 'settings' in information_schema
-- even though the table is there and every statement above it worked.
--
-- It only reacts to a top-level SELECT ... FROM information_schema, which is
-- why the column guards further up are unaffected: theirs is tucked inside
-- SET @ddl = IF((SELECT ...)), where the parser does not go looking.
--
-- So: the settings count runs first, and it is built as a prepared statement
-- naming its own database, which puts the table name inside a string where
-- nothing can second-guess it. The information_schema report goes last, where
-- there is nothing left after it to break.
-- ===========================================================================

SET @rep = CONCAT(
  'SELECT ''token settings'' AS piece, ',
  'IF(COUNT(*) = 15, ''OK'', CONCAT(''ONLY '', COUNT(*), '' OF 15'')) AS state ',
  'FROM `', DATABASE(), '`.settings WHERE name IN (',
  '''tokens_cost_promo'',''tokens_grant_free'',''tokens_grant_pro'',''tokens_grant_featured'',',
  '''tokens_earn_free'',''tokens_earn_pro'',''tokens_earn_featured'',',
  '''tokens_daily_free'',''tokens_daily_pro'',''tokens_daily_featured'',',
  '''promos_max_free'',''promos_max_pro'',''promos_max_featured'',',
  '''feed_boost_pro'',''feed_boost_featured'')');
PREPARE r FROM @rep; EXECUTE r; DEALLOCATE PREPARE r;

SELECT 'blocklist table'          AS piece, IF(COUNT(*) > 0, 'OK', 'MISSING') AS state FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blocklist'
UNION ALL SELECT 'token_events table',      IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'token_events'
UNION ALL SELECT 'promotion_views table',   IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'promotion_views'
UNION ALL SELECT 'articles table',          IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'articles'
UNION ALL SELECT 'subscriptions table',     IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscriptions'
UNION ALL SELECT 'users.website',           IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'website'
UNION ALL SELECT 'users.token_balance',     IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'token_balance'
UNION ALL SELECT 'users.stripe_customer_id',IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'stripe_customer_id'
UNION ALL SELECT 'businesses.review_links', IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses' AND COLUMN_NAME = 'review_links'
UNION ALL SELECT 'businesses.profile',      IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses' AND COLUMN_NAME = 'profile';

-- Nothing goes below this line. See the note above the report.

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


-- One row per domain waiting to become a listing. The intake queue is built
-- from this rather than from the member, because a member can own several
-- websites now and each is its own piece of work.
--   business_id — filled in when AI builds the listing. NULL means still to do.
--   note        — why the last build attempt failed, so a stuck row says so.
-- The unique key on domain is what stops the same website being queued twice
-- under two accounts.
CREATE TABLE IF NOT EXISTS intake_domains (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  domain      VARCHAR(255) NOT NULL,
  business_id INT UNSIGNED DEFAULT NULL,
  note        VARCHAR(255) DEFAULT NULL,
  added_by    INT UNSIGNED DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY u_domain (domain),
  KEY k_user (user_id, id),
  KEY k_open (business_id, id)
-- The collation is spelled out because this table's `domain` is compared to
-- users.website, which is utf8mb4_unicode_ci. Left to the server default the
-- two can differ, and MySQL refuses the comparison outright rather than
-- picking one: "Illegal mix of collations ... for operation '='".
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rows are moved into this table from users.website further down, under
-- "Backfills" — it has to wait until the columns it reads have been added.

-- One row per paid member per month: the work that plan owes them, and whether
-- it has been done. Opened on the member's own renewal date rather than on the
-- 1st, so somebody who joined on the 20th comes due on the 20th.
--   month     — YYYY-MM, and half of the unique key that makes opening the same
--               cycle twice harmless. There is no cron here; a staff page
--               opening the queue is what rolls everybody forward.
--   checklist — JSON list of the items ticked so far. Empty for Pro, where the
--               note is the checklist.
CREATE TABLE IF NOT EXISTS member_tasks (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  month      CHAR(7) NOT NULL,
  plan       VARCHAR(10) NOT NULL,
  due_on     DATE NOT NULL,
  checklist  TEXT DEFAULT NULL,
  note       VARCHAR(500) DEFAULT NULL,
  done_at    DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY u_month (user_id, month),
  KEY k_open (plan, done_at, due_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The date this member's plan renews — their own monthly anniversary.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'plan_renews_on') > 0,
  'DO 0',
  'ALTER TABLE users ADD COLUMN plan_renews_on DATE DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- Granted rather than bought: a test account or a gifted plan. Stripe has no
-- say over one, so a webhook about somebody else's cancelled subscription can
-- never downgrade it.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'plan_comped') > 0,
  'DO 0',
  'ALTER TABLE users ADD COLUMN plan_comped TINYINT(1) NOT NULL DEFAULT 0');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

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

-- The Schema.org business type — Plumber, Dentist, Attorney and so on. The
-- category is what the site browses by; this is what Google reads. NULL means
-- nobody has said, and the markup falls back to plain LocalBusiness.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND COLUMN_NAME = 'business_type') > 0,
  'DO 0',
  'ALTER TABLE businesses ADD COLUMN business_type VARCHAR(40) DEFAULT NULL AFTER category_id');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- Postcode, kept apart from the street address so it can go in its own
-- PostalAddress field rather than being buried in free text where nothing can
-- read it. A paid feature, like the phone and email beside it.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                  AND COLUMN_NAME = 'postcode') > 0,
  'DO 0',
  'ALTER TABLE businesses ADD COLUMN postcode VARCHAR(20) DEFAULT NULL AFTER address');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- v7: cached token balance. token_events above is the source of truth; this is
-- kept in step with it so every page showing a balance is one column read.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'token_balance') > 0,
  'DO 0',
  'ALTER TABLE users ADD COLUMN token_balance INT NOT NULL DEFAULT 0');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- A hostname is allowed to be 253 characters. users.website was cut to 190,
-- which silently shortened the few real domains longer than that into
-- something that matches nothing. Only rebuilt when it is still too narrow —
-- MODIFY rewrites the table, and there is no reason to do that on every run.
SET @ddl = IF((SELECT COALESCE(MAX(CHARACTER_MAXIMUM_LENGTH), 255) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'website') >= 255,
  'DO 0',
  'ALTER TABLE users MODIFY COLUMN website VARCHAR(255) DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- Member intake: when this account was created by staff or through the API
-- rather than by somebody filling in the sign-up form. NULL for a normal
-- signup, which is what keeps the intake queue to the accounts we made.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'intake_at') > 0,
  'DO 0',
  'ALTER TABLE users ADD COLUMN intake_at DATETIME DEFAULT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- The last thing that went wrong building this member's listing, so a row that
-- failed says why instead of just sitting in the queue looking untouched.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'intake_note') > 0,
  'DO 0',
  'ALTER TABLE users ADD COLUMN intake_note VARCHAR(255) DEFAULT NULL');
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
-- Backfills — moving existing data into the shapes added above.
--
-- These come after the columns section on purpose. They read columns that
-- section adds, and on a database that does not have them yet, a statement
-- naming one fails outright:
--   #1054 - Unknown column 'u.intake_note' in 'field list'
-- The guards above cannot help: they decide whether to run an ALTER, not
-- whether the rest of the file may mention the column.
-- ===========================================================================

-- Accounts taken on before intake_domains existed had exactly one domain — the
-- one on the account itself. Move it in, so nothing already in the queue
-- vanishes from it. Skips any that is already there, so re-running is safe.
--
-- The last clause is the one that needs explaining. Nothing ever stopped two
-- accounts being created against the same website, and on a live database some
-- were: the same domain typed in twice, months apart. u_domain allows a domain
-- once, so such a pair fails the whole statement with
--   #1062 - Duplicate entry 'example.com' for key 'u_domain'
-- and none of the backfill lands. The NOT EXISTS does not catch it, because it
-- only knows about rows already in the table, not about the second row this
-- same SELECT is about to produce.
--
-- So the domain goes to the account that registered it first, and the later
-- ones are left out. That is the same rule the site itself applies — adding a
-- domain already queued elsewhere is refused with "already queued for ..." —
-- and it has to be some rule, because one website cannot become two listings.
-- The report at the end of this file names every account left out, so you can
-- see them rather than having to trust that they were the right ones.
INSERT INTO intake_domains (user_id, domain, business_id, note, created_at)
SELECT u.id, u.website,
       (SELECT b.id FROM businesses b WHERE b.owner_id = u.id ORDER BY b.id LIMIT 1),
       u.intake_note, u.intake_at
  FROM users u
 WHERE u.intake_at IS NOT NULL
   AND u.website IS NOT NULL AND u.website <> ''
   AND NOT EXISTS (SELECT 1 FROM intake_domains d WHERE d.domain = u.website)
   AND u.id = (SELECT MIN(u2.id) FROM users u2
                WHERE u2.website = u.website
                  AND u2.intake_at IS NOT NULL);


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

-- Websites registered to more than one account. The queue gave each to the
-- account that registered it first; this says which, and who else had it.
-- An empty result — "MySQL returned an empty result set" — is the good answer
-- and means every intake account's website is its own.
SELECT d.domain                                             AS shared_domain,
       (SELECT u1.email FROM users u1 WHERE u1.id = d.user_id) AS queued_for,
       (SELECT GROUP_CONCAT(u2.email ORDER BY u2.id SEPARATOR ', ')
          FROM users u2 WHERE u2.website = d.domain AND u2.id <> d.user_id) AS not_queued_for
  FROM intake_domains d
 WHERE EXISTS (SELECT 1 FROM users u2 WHERE u2.website = d.domain AND u2.id <> d.user_id)
 ORDER BY d.domain;

SELECT 'blocklist table'          AS piece, IF(COUNT(*) > 0, 'OK', 'MISSING') AS state FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blocklist'
UNION ALL SELECT 'token_events table',      IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'token_events'
UNION ALL SELECT 'promotion_views table',   IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'promotion_views'
UNION ALL SELECT 'articles table',          IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'articles'
UNION ALL SELECT 'subscriptions table',     IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscriptions'
UNION ALL SELECT 'users.website',           IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'website'
UNION ALL SELECT 'users.token_balance',     IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'token_balance'
UNION ALL SELECT 'users.stripe_customer_id',IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'stripe_customer_id'
UNION ALL SELECT 'users.intake_at',          IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'intake_at'
UNION ALL SELECT 'users.intake_note',        IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'intake_note'
UNION ALL SELECT 'businesses.review_links', IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses' AND COLUMN_NAME = 'review_links'
UNION ALL SELECT 'businesses.profile',      IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses' AND COLUMN_NAME = 'profile'
UNION ALL SELECT 'businesses.business_type',IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses' AND COLUMN_NAME = 'business_type'
UNION ALL SELECT 'businesses.postcode',     IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses' AND COLUMN_NAME = 'postcode'
UNION ALL SELECT 'intake_domains table',    IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'intake_domains'
UNION ALL SELECT 'member_tasks table',      IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.TABLES  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'member_tasks'
UNION ALL SELECT 'users.plan_renews_on',    IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'plan_renews_on'
UNION ALL SELECT 'users.plan_comped',       IF(COUNT(*) > 0, 'OK', 'MISSING') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'      AND COLUMN_NAME = 'plan_comped';

-- Nothing goes below this line. See the note above the report.

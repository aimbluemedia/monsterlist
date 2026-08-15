-- ---------------------------------------------------------------------------
-- MonsterList upgrade v7 — tokens, member profiles and the article queue.
--
-- Run this once in phpMyAdmin (SQL tab) after uploading the v73 release.
-- Re-running it is harmless: it only reports that a table or column exists.
-- ---------------------------------------------------------------------------

-- The long-form Profile section, on the storefront for paid tiers only.
ALTER TABLE businesses ADD COLUMN profile LONGTEXT NULL AFTER description;

-- Cached token balance. The ledger below is the source of truth; this is kept
-- in step with it so every page that shows a balance is one column read.
ALTER TABLE users ADD COLUMN token_balance INT NOT NULL DEFAULT 0;

-- Every token movement, ever. Nothing is deleted, so a member can always be
-- shown why their balance is what it is.
--   reason    — what kind of movement: 'monthly', 'promo:view', 'promo:submit',
--               'staff:adjust'
--   once_key  — set ONLY on credits that must happen at most once, e.g.
--               'monthly:2026-08'. The unique index below then makes granting
--               idempotent however many times it is attempted. Repeatable
--               movements leave it NULL, and MySQL lets NULLs repeat in a
--               unique index — which is the whole reason it is a separate
--               column rather than the unique key sitting on `reason`.
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

-- Token economics. All editable later in Superadmin → Settings.
INSERT INTO settings (name, value) VALUES
  ('tokens_cost_promo',      '10'),
  ('tokens_earn_view',       '2'),
  ('tokens_daily_earn_cap',  '20'),
  ('tokens_grant_free',      '20'),
  ('tokens_grant_pro',       '120'),
  ('tokens_grant_featured',  '400')
ON DUPLICATE KEY UPDATE value = value;

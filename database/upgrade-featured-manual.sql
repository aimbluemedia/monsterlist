-- MonsterList — manual Featured flag, standalone (v154).
-- Safe to run more than once. Paste into phpMyAdmin and run.

-- ---------------------------------------------------------------------------
-- Manual Featured flag (v154)
--
-- A column of its own, NOT businesses.tier. The tier is a copy of users.plan
-- and three separate paths overwrite it — a Stripe payment, a cancellation and
-- a staff plan change all call sync_business_tiers(). A Featured mark written
-- into the tier would survive until the next of those and then vanish, with
-- nobody able to say why. This flag is never touched by the plan sync.
-- ---------------------------------------------------------------------------
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                 AND COLUMN_NAME = 'featured_manual') > 0,
              'DO 0',
              'ALTER TABLE businesses ADD COLUMN featured_manual TINYINT(1) NOT NULL DEFAULT 0');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- Placement reads this on every city and category page, so it is indexed with
-- status, which every one of those queries also filters on.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses'
                 AND INDEX_NAME = 'idx_biz_featured_manual') > 0,
              'DO 0',
              'CREATE INDEX idx_biz_featured_manual ON businesses (featured_manual, status)');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- Report last: a top-level SELECT on information_schema makes phpMyAdmin lose
-- the current database, so nothing may follow this.
SELECT 'businesses.featured_manual' AS piece,
       IF(COUNT(*) > 0, 'OK', 'MISSING') AS state
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses' AND COLUMN_NAME = 'featured_manual'
UNION ALL
SELECT 'idx_biz_featured_manual',
       IF(COUNT(*) > 0, 'OK', 'MISSING')
  FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'businesses' AND INDEX_NAME = 'idx_biz_featured_manual';

-- ---------------------------------------------------------------------------
-- MonsterList — which Toutify tools a member has active (v160).
-- Safe to run more than once.
--
-- A JSON object keyed by product: {"searchmonster":1,"promomonster":0,...}.
-- A column rather than a table because it is a handful of flags that are
-- always read together with the user row and never queried on their own.
-- ---------------------------------------------------------------------------
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                 AND COLUMN_NAME = 'toutify_tools') > 0,
              'DO 0',
              'ALTER TABLE users ADD COLUMN toutify_tools TEXT NULL');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- Report last: a top-level SELECT on information_schema makes phpMyAdmin lose
-- the current database, so nothing may follow this.
SELECT 'users.toutify_tools' AS piece,
       IF(COUNT(*) > 0, 'OK', 'MISSING') AS state
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'toutify_tools';

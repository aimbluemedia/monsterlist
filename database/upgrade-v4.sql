-- ---------------------------------------------------------------------------
-- MonsterList upgrade v4 — signup / submission blocklist.
--
-- Run this once on an existing install (phpMyAdmin → SQL tab). Fresh installs
-- get the same table from schema.sql and do not need this file.
--
-- Holds emails that may not create an account and domains that may not be
-- submitted as a listing website. Staff fill it from the Listings page
-- ("Reject + block") or by hand at /superadmin/blocked.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blocklist (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kind       ENUM('email','domain') NOT NULL,
  value      VARCHAR(190) NOT NULL,
  reason     VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_blocklist (kind, value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MonsterList v2 upgrade: logo uploads, listing claims, IndexNow key.
-- Fresh installs don't need this — schema.sql already includes everything.
ALTER TABLE businesses ADD COLUMN logo_url VARCHAR(255) DEFAULT NULL AFTER video_url;

CREATE TABLE IF NOT EXISTS claims (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  message     VARCHAR(1000) DEFAULT NULL,
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_claim (business_id, user_id),
  KEY idx_claim_status (status),
  CONSTRAINT fk_claim_biz  FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_claim_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

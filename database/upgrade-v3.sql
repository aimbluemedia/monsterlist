-- MonsterList v3 upgrade: the promotion engine.
-- Fresh installs don't need this — schema.sql already includes it.
--
-- A promotion is a link a member has already published somewhere (a blog post,
-- a product page, a video, a social post) that they want the member network to
-- see. Staff approve it, then it appears in the public feed at /promotions.

CREATE TABLE IF NOT EXISTS promotions (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  channel     ENUM('blog','product','service','review','youtube','facebook',
                   'instagram','tiktok','reddit','pinterest','other')
              NOT NULL DEFAULT 'other',
  url         VARCHAR(600) NOT NULL,
  title       VARCHAR(200) NOT NULL,
  blurb       VARCHAR(400) DEFAULT NULL,
  status      ENUM('pending','live','rejected') NOT NULL DEFAULT 'pending',
  clicks      INT UNSIGNED NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_promo_feed (status, created_at),
  KEY idx_promo_biz (business_id),
  KEY idx_promo_user (user_id),
  CONSTRAINT fk_promo_biz  FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_promo_user FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

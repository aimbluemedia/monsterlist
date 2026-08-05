-- ===========================================================================
-- MonsterList schema — MySQL 5.7+ / MariaDB 10.3+ (utf8mb4, InnoDB)
-- Import via phpMyAdmin or:  mysql -u USER -p DBNAME < schema.sql
-- ===========================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---- Geography ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS countries (
  code        CHAR(2) NOT NULL PRIMARY KEY,          -- ISO alpha-2
  name        VARCHAR(120) NOT NULL,
  flag        VARCHAR(16) DEFAULT NULL,
  is_popular  TINYINT(1) NOT NULL DEFAULT 0,          -- drives "Popular countries"
  popularity  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS regions (                  -- US states (US is 3-tier)
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  country_code CHAR(2) NOT NULL,
  code         VARCHAR(10) DEFAULT NULL,              -- e.g. AZ
  name         VARCHAR(120) NOT NULL,
  slug         VARCHAR(140) NOT NULL,
  is_popular   TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_region (country_code, slug),
  CONSTRAINT fk_region_country FOREIGN KEY (country_code) REFERENCES countries(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cities (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  country_code  CHAR(2) NOT NULL,
  region_id     INT UNSIGNED DEFAULT NULL,            -- NULL for 2-tier countries
  name          VARCHAR(140) NOT NULL,
  slug          VARCHAR(160) NOT NULL,
  is_popular    TINYINT(1) NOT NULL DEFAULT 0,
  listing_count INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_city (country_code, region_id, slug),
  KEY idx_city_slug (country_code, slug),
  CONSTRAINT fk_city_country FOREIGN KEY (country_code) REFERENCES countries(code),
  CONSTRAINT fk_city_region  FOREIGN KEY (region_id) REFERENCES regions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Categories -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id         VARCHAR(40) NOT NULL PRIMARY KEY,        -- e.g. 'home'
  label      VARCHAR(120) NOT NULL,
  icon       VARCHAR(16) DEFAULT NULL,
  popularity INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Users (members + admins) ----------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email              VARCHAR(190) NOT NULL UNIQUE,
  password_hash      VARCHAR(255) NOT NULL,
  name               VARCHAR(140) NOT NULL,
  role               ENUM('member','admin','superadmin') NOT NULL DEFAULT 'member',
  plan               ENUM('free','pro','featured') NOT NULL DEFAULT 'free',
  status             ENUM('active','suspended') NOT NULL DEFAULT 'active',
  stripe_customer_id VARCHAR(64) DEFAULT NULL,
  last_login_at      DATETIME DEFAULT NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(190) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  KEY idx_pr_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ip           VARCHAR(45) NOT NULL,
  email        VARCHAR(190) DEFAULT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_la_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Blocklist (emails barred from signup, domains barred from listings) -----
CREATE TABLE IF NOT EXISTS blocklist (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kind       ENUM('email','domain') NOT NULL,
  value      VARCHAR(190) NOT NULL,
  reason     VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_blocklist (kind, value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Subscriptions (Stripe monthly memberships) ------------------------------
CREATE TABLE IF NOT EXISTS subscriptions (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id                INT UNSIGNED NOT NULL,
  plan                   ENUM('pro','featured') NOT NULL,
  status                 ENUM('active','past_due','canceled') NOT NULL DEFAULT 'active',
  stripe_subscription_id VARCHAR(64) DEFAULT NULL UNIQUE,
  current_period_end     DATETIME DEFAULT NULL,
  created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_sub_user (user_id),
  CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Businesses ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS businesses (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  owner_id     INT UNSIGNED DEFAULT NULL,             -- NULL until claimed
  name         VARCHAR(180) NOT NULL,
  slug         VARCHAR(200) NOT NULL,
  category_id  VARCHAR(40) DEFAULT NULL,
  city_id      INT UNSIGNED DEFAULT NULL,
  tier         ENUM('free','pro','featured') NOT NULL DEFAULT 'free',
  status       ENUM('pending','live','rejected') NOT NULL DEFAULT 'pending',
  verified     TINYINT(1) NOT NULL DEFAULT 0,
  tagline      VARCHAR(255) DEFAULT NULL,
  description  TEXT,
  founded      SMALLINT UNSIGNED DEFAULT NULL,
  phone        VARCHAR(40) DEFAULT NULL,
  website      VARCHAR(255) DEFAULT NULL,
  email        VARCHAR(190) DEFAULT NULL,
  address      VARCHAR(255) DEFAULT NULL,
  lat          DOUBLE DEFAULT NULL,
  lng          DOUBLE DEFAULT NULL,
  hours        TEXT,                                   -- JSON [{d,h,open}]
  social       TEXT,                                   -- JSON {facebook,instagram,...}
  video_url    VARCHAR(255) DEFAULT NULL,              -- pro+: featured video embed
  logo_url     VARCHAR(255) DEFAULT NULL,              -- uploaded logo
  rating       DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  review_count INT NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_biz (city_id, slug),
  KEY idx_biz_city (city_id, status, tier),
  KEY idx_biz_cat (category_id, status),
  KEY idx_biz_owner (owner_id),
  FULLTEXT KEY ft_biz (name, tagline, description),
  CONSTRAINT fk_biz_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_biz_cat   FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_biz_city  FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Storefront content (pro+ profiles) ---------------------------------------
CREATE TABLE IF NOT EXISTS gallery (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  url         VARCHAR(255) NOT NULL,
  label       VARCHAR(140) DEFAULT NULL,
  sort        INT NOT NULL DEFAULT 0,
  KEY idx_gal_biz (business_id),
  CONSTRAINT fk_gal_biz FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  name        VARCHAR(180) NOT NULL,
  description VARCHAR(500) DEFAULT NULL,
  price       VARCHAR(60) DEFAULT NULL,
  KEY idx_srv_biz (business_id),
  CONSTRAINT fk_srv_biz FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  name        VARCHAR(180) NOT NULL,
  price       VARCHAR(60) DEFAULT NULL,
  note        VARCHAR(255) DEFAULT NULL,
  image_url   VARCHAR(255) DEFAULT NULL,
  KEY idx_prod_biz (business_id),
  CONSTRAINT fk_prod_biz FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED DEFAULT NULL,
  author_name VARCHAR(140) NOT NULL,
  rating      TINYINT UNSIGNED NOT NULL,               -- 1..5 (checked in app)
  body        TEXT,
  status      ENUM('live','hidden') NOT NULL DEFAULT 'live',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rev_biz (business_id, status),
  CONSTRAINT fk_rev_biz  FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Listing claims (unclaimed business → owner) --------------------------------
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

-- ---- Analytics (per-listing daily rollups) --------------------------------------
CREATE TABLE IF NOT EXISTS listing_events (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  event       ENUM('view','click','call') NOT NULL,
  day         DATE NOT NULL,
  count       INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_evt (business_id, event, day),
  CONSTRAINT fk_evt_biz FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Promotions (the promotion engine feed) --------------------------------------
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

-- ---- Site settings (editable by superadmin) --------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  name  VARCHAR(80) NOT NULL PRIMARY KEY,
  value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (name, value) VALUES
  ('site_name',            'MonsterList'),
  ('site_tagline',         'The worldwide small-business directory'),
  ('price_pro_monthly',    '19'),
  ('price_featured_monthly','49'),
  ('stripe_price_pro',     ''),
  ('stripe_price_featured','')
ON DUPLICATE KEY UPDATE name = name;

SET FOREIGN_KEY_CHECKS = 1;

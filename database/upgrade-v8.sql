-- ---------------------------------------------------------------------------
-- MonsterList upgrade v8 — make the paid plans worth paying for.
--
-- v7 gave every plan the same earn rate and no ceiling on how much a member
-- could publish, so a diligent Free member could out-promote a paying one just
-- by grinding views. This adds three things money buys and effort cannot:
--   * a higher earn rate per view
--   * a monthly ceiling on promotions, set by plan
--   * a freshness boost in the feed, so a paid promotion is seen by more people
--
-- Run once in phpMyAdmin (SQL tab) after uploading the v75 release.
-- ---------------------------------------------------------------------------

INSERT INTO settings (name, value) VALUES
  -- Tokens earned for opening someone else's promotion, by the viewer's plan.
  ('tokens_earn_free',      '2'),
  ('tokens_earn_pro',       '3'),
  ('tokens_earn_featured',  '4'),

  -- Most a member can earn in a day, by plan.
  ('tokens_daily_free',     '20'),
  ('tokens_daily_pro',      '40'),
  ('tokens_daily_featured', '60'),

  -- Promotions a member may submit per calendar month. This is the ceiling
  -- effort cannot lift: tokens buy a promotion, the plan decides how many.
  ('promos_max_free',       '4'),
  ('promos_max_pro',        '20'),
  ('promos_max_featured',   '60'),

  -- Days of extra "freshness" a paid promotion carries in the feed, so it sits
  -- higher for longer without burying free members permanently.
  ('feed_boost_pro',        '7'),
  ('feed_boost_featured',   '14')
ON DUPLICATE KEY UPDATE value = value;

-- The Free allowance now covers one promotion rather than two: the rest is
-- earned by viewing, which is the behaviour the tokens exist to buy.
UPDATE settings SET value = '10'  WHERE name = 'tokens_grant_free'     AND value = '20';
UPDATE settings SET value = '150' WHERE name = 'tokens_grant_pro'      AND value = '120';

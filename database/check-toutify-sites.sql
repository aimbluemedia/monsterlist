-- ---------------------------------------------------------------------------
-- MonsterList — why a storefront shows no active tools. READ ONLY.
--
-- Changes nothing. Uses SHOW and plain SELECTs, so it runs on Hostinger
-- without the information_schema rights it denies.
--
-- Run it in phpMyAdmin -> SQL. You get five result tables, in the order the
-- four things that have to be true are checked. The FIRST one that comes back
-- wrong is the answer — stop there.
--
-- IMPORTANT: query 5 reads the new column. If the whole thing stops with
--     #1054 Unknown column 'toutify_sites'
-- then query 1 already told you why: upgrade-hostinger.sql has not been run.
-- ---------------------------------------------------------------------------

-- Which listing. Edit this if the name is spelt differently in the database.
SET @like = '%Top Hat%';


-- 1. DOES THE COLUMN EXIST? -------------------------------------------------
-- One row = upgrade-hostinger.sql has been run. NO ROWS = it has not, and
-- that alone explains an all-off box: with no column, the storefront falls
-- back to the old staff-set member flags, which are off for most members.
SHOW COLUMNS FROM businesses LIKE 'toutify_sites';


-- 2. IS *ANY* LISTING LINKED TO A TOUTIFY WEBSITE? --------------------------
-- linked = 0 means no listing here carries a toutify_website_id, and the
-- backfill has nothing to match on — it will report
-- "of those, linked to a listing here: 0" and write nothing. Fix that by
-- running scripts/toutify-listings.php first; it is what sets the ids.
SELECT COUNT(*)                                                  AS listings_total,
       COUNT(toutify_website_id)                                 AS linked_to_toutify,
       SUM(toutify_removed = 1)                                  AS marked_removed
FROM businesses;


-- 3. THE LISTING ITSELF -----------------------------------------------------
-- owner_id NULL means the listing is unclaimed, and an unclaimed listing is
-- shown WITHOUT any status or switches at all, by design — not four off ones.
SELECT id, slug, status, owner_id,
       toutify_website_id,
       toutify_removed,
       toutify_updated_at
FROM businesses
WHERE name LIKE @like;


-- 4. THE OWNER'S OLD MEMBER-LEVEL FLAGS -------------------------------------
-- This is the fallback the box uses when the listing has no sites of its own.
-- NULL here plus nothing in query 5 = every card off, which is what you saw.
SELECT u.id                                   AS user_id,
       u.toutify_sub IS NOT NULL              AS linked_to_toutify,
       u.toutify_tools                        AS staff_set_flags
FROM users u
JOIN businesses b ON b.owner_id = u.id
WHERE b.name LIKE @like;


-- 5. THE PER-LISTING SITES (the column this all turns on) -------------------
-- NULL   = nothing saved yet; run scripts/toutify-sites-refresh.php
-- []     = Toutify says this website is on no sites
-- ["searchmonster", ...] = these cards should be Active
SELECT id, slug, toutify_website_id, toutify_sites
FROM businesses
WHERE name LIKE @like;

-- And the overall picture, to see whether the backfill did anything at all:
SELECT COUNT(toutify_sites)                                      AS listings_with_sites_saved,
       SUM(toutify_sites = '[]')                                 AS saved_but_on_no_sites,
       SUM(toutify_sites IS NOT NULL AND toutify_sites <> '[]')  AS saved_and_on_something
FROM businesses;

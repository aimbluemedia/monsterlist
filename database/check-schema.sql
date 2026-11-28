-- ---------------------------------------------------------------------------
-- MonsterList — what is already in your database. READ ONLY.
--
-- Changes nothing, deletes nothing, and never errors. Uses SHOW, which works
-- on your own tables without the information_schema rights Hostinger denies.
--
-- Run it in phpMyAdmin -> SQL. You get four result tables. Compare them with
-- the list in upgrade-hostinger.sql, or just send me the output.
-- ---------------------------------------------------------------------------

-- 1. Toutify columns on users (expect 6: toutify_sub, toutify_status,
--    toutify_updated_at, business_name, phone, toutify_tools)
SHOW COLUMNS FROM users LIKE '%toutify%';

-- 2. The rest of the users columns this needs
SHOW COLUMNS FROM users WHERE Field IN ('business_name', 'phone');

-- 3. Columns on businesses (expect toutify_website_id, toutify_updated_at,
--    toutify_tier, toutify_removed, featured_manual)
SHOW COLUMNS FROM businesses WHERE Field LIKE '%toutify%' OR Field = 'featured_manual';

-- 4. The tables (expect toutify_sessions and toutify_events)
SHOW TABLES LIKE 'toutify_%';

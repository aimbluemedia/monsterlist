-- ---------------------------------------------------------------------------
-- MonsterList — delete every review (v156).
--
-- THIS DELETES DATA AND CANNOT BE UNDONE. It removes every row in `reviews`,
-- seeded or genuine, and resets every listing's rating and review_count to
-- zero. Take a backup of the `reviews` table first if there is any chance a
-- real customer wrote one of them:
--
--     mysqldump -u USER -p DBNAME reviews > reviews-backup.sql
--
-- The reviews FEATURE is untouched. Storefronts keep the reviews section and
-- the write-a-review form, so new ones still work; this only clears what is
-- there now. MonsterScore drops for listings that had reviews, because 20 of
-- its 100 points come from them.
--
-- Safe to run twice: the second run deletes nothing and reports the same.
-- ---------------------------------------------------------------------------

-- What is about to go. Read this before running the rest.
SELECT COUNT(*) AS reviews_to_delete,
       COUNT(DISTINCT business_id) AS listings_affected
  FROM reviews;

DELETE FROM reviews;

-- businesses.rating and businesses.review_count are a cached count of the
-- rows just deleted, so they have to come down with them. Left alone, every
-- listing would keep showing stars for reviews that no longer exist.
UPDATE businesses SET rating = 0, review_count = 0
 WHERE rating <> 0 OR review_count <> 0;

-- Report last: a top-level SELECT on information_schema makes phpMyAdmin lose
-- the current database, so nothing may follow this one.
SELECT 'reviews remaining'            AS piece, COUNT(*) AS value FROM reviews
UNION ALL
SELECT 'listings still showing stars', COUNT(*) FROM businesses WHERE rating <> 0 OR review_count <> 0;

<?php
// Seed demo listings into popular cities so the directory doesn't launch empty.
// All demo businesses are UNCLAIMED (owner_id NULL) — real owners can claim them.
// Deterministic: same city always gets the same businesses. Safe to re-run.
// Usage: php scripts/seed_demo.php [--remove]

require __DIR__ . '/../app/bootstrap.php';

if (in_array('--remove', $argv, true)) {
    $n = q('DELETE FROM businesses WHERE owner_id IS NULL AND email LIKE "%@demo.invalid"')->rowCount();
    q('UPDATE cities SET listing_count = (SELECT COUNT(*) FROM businesses WHERE city_id = cities.id AND status = "live")');
    exit("Removed $n demo listings.\n");
}

$NAMES  = ['Summit','Ironwood','Blue Harbor','Copper','North Star','Evergreen','Redline','Meridian','Willow','Anchor','Bright','Vantage','Cobalt','Granite','Silverleaf','Harbor','Pinnacle','Cedar','Atlas','Kingfisher'];
$SUFFIX = ['Group','Co.','Services','& Sons','Studio','Works','Collective','Partners','Solutions','Labs'];
$REVIEWERS = ['Sam T.','Maria G.','David L.','Priya N.','James W.','Aisha B.','Tom H.','Elena R.','Chris P.','Nadia K.'];
$REVIEW_BODIES = [
    'Great service, fair prices and they showed up on time. Recommended.',
    'Very professional from first call to finished job. Will use again.',
    'Solid experience overall. Communication could be a touch faster, but the work itself was excellent.',
    'Friendly team and quality work. They went the extra mile for us.',
    'Exactly what we needed. Booking was easy and the results speak for themselves.',
];
$TAGLINES = [
    'Trusted %s serving %s and nearby areas.',
    'Local %s with a reputation for quality in %s.',
    'Family-run %s proudly serving the %s community.',
    '%s specialists — fast quotes for %s locals.',
];

$cats   = rows('SELECT id, label FROM categories ORDER BY id');
$cities = rows('SELECT ci.*, r.name AS region_name FROM cities ci LEFT JOIN regions r ON r.id = ci.region_id WHERE ci.is_popular = 1');

$made = 0;
foreach ($cities as $city) {
    $seed = crc32('ml-demo|' . $city['country_code'] . '|' . $city['id'] . '|' . $city['slug']);
    $rnd = function () use (&$seed) { $seed = ($seed * 1664525 + 1013904223) & 0xFFFFFFFF; return $seed / 4294967296; };

    $count = 5 + (int)floor($rnd() * 4);           // 5-8 per popular city
    for ($i = 0; $i < $count; $i++) {
        $cat  = $cats[(int)floor($rnd() * count($cats))];
        $name = $NAMES[(int)floor($rnd() * count($NAMES))] . ' ' . $SUFFIX[(int)floor($rnd() * count($SUFFIX))];
        $slug = slugify($name);
        if (row('SELECT id FROM businesses WHERE city_id = ? AND slug = ?', [$city['id'], $slug])) continue;

        $t    = $rnd();
        $tier = $t > 0.9 ? 'featured' : ($t > 0.72 ? 'pro' : 'free');
        $tag  = sprintf($TAGLINES[(int)floor($rnd() * count($TAGLINES))], strtolower($cat['label']), $city['name']);
        $desc = $name . ' is a local ' . strtolower($cat['label']) . ' business serving ' . $city['name']
              . ($city['region_name'] ? ', ' . $city['region_name'] : '') . ' and the surrounding area. '
              . 'Known for dependable work, clear communication and fair pricing, the team handles projects of all sizes for homeowners and businesses alike. '
              . 'This is an unclaimed listing — if this is your business, claim it to add photos, services and current details.';
        $phone = sprintf('(555) %03d-%04d', 100 + (int)floor($rnd() * 900), (int)floor($rnd() * 10000));
        $daysAgo = (int)floor($rnd() * 240);

        q('INSERT INTO businesses (owner_id, name, slug, category_id, city_id, tier, status, verified, tagline, description, phone, email, founded, created_at)
           VALUES (NULL, ?, ?, ?, ?, ?, "live", ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)',
          [$name, $slug, $cat['id'], $city['id'], $tier, $tier !== 'free' ? 1 : 0, $tag, $desc, $phone,
           $slug . '@demo.invalid', 1995 + (int)floor($rnd() * 28), $daysAgo]);
        $bizId = (int)db()->lastInsertId();

        // 2-5 reviews
        $nRev = 2 + (int)floor($rnd() * 4);
        for ($r = 0; $r < $nRev; $r++) {
            q('INSERT INTO reviews (business_id, user_id, author_name, rating, body, created_at)
               VALUES (?, NULL, ?, ?, ?, NOW() - INTERVAL ? DAY)',
              [$bizId,
               $REVIEWERS[(int)floor($rnd() * count($REVIEWERS))],
               $rnd() > 0.25 ? 5 : 4,
               $REVIEW_BODIES[(int)floor($rnd() * count($REVIEW_BODIES))],
               (int)floor($rnd() * $daysAgo ?: 1)]);
        }
        refresh_rating($bizId);
        $made++;
    }
    refresh_city_count((int)$city['id']);
}
echo "Seeded $made demo listings across " . count($cities) . " popular cities.\n";
echo "Remove them anytime with: php scripts/seed_demo.php --remove\n";

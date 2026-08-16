<?php
// ---------------------------------------------------------------------------
// Tokens: the currency that runs the promotion engine.
//
// Members spend tokens to put a link in front of the network, and earn them by
// opening other members' promotions. That loop is the whole point — attention
// paid is attention earned, so the feed is read by the people posting to it.
//
// token_events is the ledger and the only source of truth. users.token_balance
// is a cache kept in step with it, so showing a balance never costs a SUM.
// ---------------------------------------------------------------------------

/** The three plan keys, in ladder order. */
function token_plans(): array
{
    return ['free', 'pro', 'featured'];
}

/**
 * Is the token schema actually present?
 *
 * The allowance is granted on every member page load, so before this check a
 * missing upgrade-v7.sql did not disable tokens — it locked every member out of
 * their account entirely, with a database error where the dashboard should be.
 * A feature that has not been installed yet should switch itself off, not take
 * the site with it. Superadmin → Diagnostics is where the missing file is
 * reported; the member just sees no tokens until it is run.
 *
 * One information_schema query per request, memoised, and only reached on
 * member pages.
 */
function token_schema(): array
{
    static $have = null;
    if ($have === null) {
        $found = array_column(rows(
            "SELECT TABLE_NAME AS t FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME IN ('token_events','promotion_views','articles')"), 't');
        $have = [
            'tokens'   => in_array('token_events', $found, true)
                          && in_array('promotion_views', $found, true)
                          && column_exists('users', 'token_balance'),
            'articles' => in_array('articles', $found, true),
        ];
    }
    return $have;
}

function tokens_ready(): bool   { return token_schema()['tokens']; }
function articles_ready(): bool { return token_schema()['articles']; }

/**
 * Economics for one plan, all editable in Superadmin → Settings.
 *
 * Everything except the cost of a promotion varies by plan. That is the point:
 * viewing is rewarded for everyone, but a paid member earns faster, may earn
 * more each day, and may publish more each month — so effort alone cannot buy
 * what the plan sells.
 */
function token_rules(string $plan = 'free'): array
{
    if (!in_array($plan, token_plans(), true)) $plan = 'free';
    $defaults = [
        'earn'   => ['free' => 2,  'pro' => 3,   'featured' => 4],
        'daily'  => ['free' => 20, 'pro' => 40,  'featured' => 60],
        'grant'  => ['free' => 10, 'pro' => 150, 'featured' => 400],
        'promos' => ['free' => 4,  'pro' => 20,  'featured' => 60],
    ];
    return [
        'plan'           => $plan,
        'cost_promo'     => max(0, (int)setting('tokens_cost_promo', '10')),
        'earn_view'      => max(0, (int)setting("tokens_earn_$plan",  (string)$defaults['earn'][$plan])),
        'daily_earn_cap' => max(0, (int)setting("tokens_daily_$plan", (string)$defaults['daily'][$plan])),
        'grant'          => max(0, (int)setting("tokens_grant_$plan", (string)$defaults['grant'][$plan])),
        'promos_max'     => max(0, (int)setting("promos_max_$plan",   (string)$defaults['promos'][$plan])),
    ];
}

/**
 * How many promotions a member must open to afford one of their own.
 *
 * This is the number that has to make sense to a member, and it is derived
 * rather than configured — change the cost or the earn rate in Settings and
 * this follows, so the page can never quote a rate the ledger does not use.
 */
function token_views_per_promo(string $plan = 'free'): int
{
    $r = token_rules($plan);
    if ($r['earn_view'] <= 0 || $r['cost_promo'] <= 0) return 0;
    return (int)ceil($r['cost_promo'] / $r['earn_view']);
}

/** The monthly allowance for a plan. */
function token_monthly_grant(string $plan): int
{
    return token_rules($plan)['grant'];
}

/** How many promotions this plan may publish in a calendar month. */
function promos_monthly_max(string $plan): int
{
    return token_rules($plan)['promos_max'];
}

/** How many this member has already submitted this month. */
function promos_used_this_month(int $userId): int
{
    return (int)scalar(
        "SELECT COUNT(*) FROM promotions
         WHERE user_id = ? AND status != 'rejected'
           AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [$userId]);
}

function token_balance(int $userId): int
{
    if (!tokens_ready()) return 0;
    return (int)scalar('SELECT token_balance FROM users WHERE id = ?', [$userId]);
}

/**
 * Write a ledger entry and move the cached balance with it.
 *
 * $onceKey marks a credit that must happen at most once — 'monthly:2026-08'.
 * The unique index on (user_id, once_key) then does the checking, which is what
 * makes a monthly grant safe to attempt on every page load instead of needing a
 * cron job. Repeatable movements pass null and may repeat freely.
 *
 * Returns false when the entry already existed.
 */
function token_add(int $userId, int $delta, string $reason, ?string $note = null,
                   ?string $onceKey = null, ?string $refType = null, ?int $refId = null): bool
{
    if ($delta === 0 || !tokens_ready()) return false;
    try {
        q('INSERT INTO token_events (user_id, delta, reason, once_key, note, ref_type, ref_id)
           VALUES (?,?,?,?,?,?,?)', [$userId, $delta, $reason, $onceKey, $note, $refType, $refId]);
    } catch (PDOException $e) {
        // 23000 = duplicate key: this credit has already been given.
        if ($e->getCode() === '23000') return false;
        throw $e;
    }
    q('UPDATE users SET token_balance = token_balance + ? WHERE id = ?', [$delta, $userId]);
    return true;
}

/**
 * Give this month's allowance if it has not been given yet.
 *
 * Called on every member page load rather than from a scheduled job: shared
 * hosting cron is the least reliable part of this stack, and the unique key
 * makes repeating the attempt free.
 */
function token_grant_monthly(array $user): bool
{
    if (!tokens_ready()) return false;
    $amount = token_monthly_grant((string)$user['plan']);
    if ($amount <= 0) return false;
    $month = date('Y-m');
    return token_add((int)$user['id'], $amount, 'monthly',
        ucfirst((string)$user['plan']) . ' plan allowance for ' . date('F Y'),
        'monthly:' . $month);
}

/** Tokens earned today, against the daily cap. */
function token_earned_today(int $userId): int
{
    if (!tokens_ready()) return 0;
    return (int)scalar(
        "SELECT COALESCE(SUM(delta),0) FROM token_events
         WHERE user_id = ? AND delta > 0 AND reason = 'promo:view' AND DATE(created_at) = CURDATE()",
        [$userId]);
}

/**
 * Award tokens for opening another member's promotion.
 *
 * Returns the number of tokens earned, 0 if this view does not qualify. The
 * rules exist so the loop rewards actually looking at other people's work:
 * not your own promotion, once per promotion per day, and a daily ceiling.
 */
function token_earn_from_view(?array $user, array $promo): int
{
    if (!$user || !tokens_ready()) return 0;
    if ((int)$promo['user_id'] === (int)$user['id']) return 0;

    // The VIEWER's plan sets the rate — paying members earn faster.
    $rules = token_rules((string)($user['plan'] ?? 'free'));
    if ($rules['earn_view'] <= 0) return 0;

    // The last award of the day is trimmed to what is left of the ceiling
    // rather than paid in full. Checking "already at the cap?" and then paying
    // the whole rate overshoots by up to rate-1 — a Pro member on a stated cap
    // of 40 finished the day on 42, which makes the number on the page a lie.
    $amount = $rules['earn_view'];
    if ($rules['daily_earn_cap'] > 0) {
        $left = $rules['daily_earn_cap'] - token_earned_today((int)$user['id']);
        if ($left <= 0) return 0;
        $amount = min($amount, $left);
    }

    // The unique key is the check: if today's row already exists, this member
    // has already been paid for this promotion today.
    try {
        q('INSERT INTO promotion_views (user_id, promotion_id, day) VALUES (?,?,CURDATE())',
          [$user['id'], $promo['id']]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') return 0;
        throw $e;
    }

    // Repeatable, so no once_key — the promotion_views row above is what stops
    // this being earned twice for the same promotion on the same day.
    token_add((int)$user['id'], $amount, 'promo:view',
        'Viewed "' . mb_substr((string)$promo['title'], 0, 120) . '"',
        null, 'promotion', (int)$promo['id']);
    return $amount;
}

/** Spend tokens. Returns false — changing nothing — if the balance is short. */
function token_spend(int $userId, int $amount, string $note = '',
                     ?string $refType = null, ?int $refId = null): bool
{
    // With no ledger installed the charge cannot be recorded — so let the
    // action through rather than blocking it. A missing upgrade should cost the
    // accounting, not the feature.
    if ($amount <= 0 || !tokens_ready()) return true;
    // Conditional UPDATE: the balance check and the deduction are one statement,
    // so two submissions at once cannot both pass a "can they afford it?" test.
    $ok = q('UPDATE users SET token_balance = token_balance - ? WHERE id = ? AND token_balance >= ?',
            [$amount, $userId, $amount])->rowCount();
    if (!$ok) return false;
    q('INSERT INTO token_events (user_id, delta, reason, note, ref_type, ref_id)
       VALUES (?,?,?,?,?,?)', [$userId, -$amount, 'promo:submit', $note ?: null, $refType, $refId]);
    return true;
}

/** Staff adjustment, up or down, always with a reason attached. */
function token_adjust(int $userId, int $delta, string $note): bool
{
    if ($delta === 0 || !tokens_ready()) return false;
    q('INSERT INTO token_events (user_id, delta, reason, note) VALUES (?,?,?,?)',
      [$userId, $delta, 'staff:adjust', $note]);
    q('UPDATE users SET token_balance = GREATEST(0, token_balance + ?) WHERE id = ?', [$delta, $userId]);
    return true;
}

function token_history(int $userId, int $limit = 50): array
{
    if (!tokens_ready()) return [];
    $limit = max(1, min(200, $limit));
    return rows("SELECT * FROM token_events WHERE user_id = ? ORDER BY id DESC LIMIT $limit", [$userId]);
}

/** Human label for a ledger row. */
function token_reason_label(array $e): string
{
    if ($e['note']) return (string)$e['note'];
    return [
        'monthly'      => 'Monthly plan allowance',
        'staff:adjust' => 'Adjusted by our team',
        'promo:view'   => 'Viewed a member promotion',
        'promo:submit' => 'Promotion submitted',
    ][(string)$e['reason']] ?? (string)$e['reason'];
}

// ---------------------------------------------------------------------------
// The Featured tier's monthly article.
// ---------------------------------------------------------------------------

/** This member's article for a given month (default: now), or null. */
function article_for_month(int $userId, string $month = ''): ?array
{
    if (!articles_ready()) return null;
    return row('SELECT * FROM articles WHERE user_id = ? AND month = ?',
               [$userId, $month ?: date('Y-m')]);
}

/** Channels the network posts a Featured member's article out to. */
function article_channels(): array
{
    return ['Facebook', 'Instagram', 'TikTok', 'YouTube', 'Pinterest', 'Reddit'];
}

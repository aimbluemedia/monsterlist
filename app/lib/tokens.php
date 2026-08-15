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

/** Tunable economics, all editable in Superadmin → Settings. */
function token_rules(): array
{
    return [
        'cost_promo'     => max(0, (int)setting('tokens_cost_promo', '10')),
        'earn_view'      => max(0, (int)setting('tokens_earn_view', '2')),
        'daily_earn_cap' => max(0, (int)setting('tokens_daily_earn_cap', '20')),
        'grant' => [
            'free'     => max(0, (int)setting('tokens_grant_free', '20')),
            'pro'      => max(0, (int)setting('tokens_grant_pro', '120')),
            'featured' => max(0, (int)setting('tokens_grant_featured', '400')),
        ],
    ];
}

/** The monthly allowance for a plan. */
function token_monthly_grant(string $plan): int
{
    $g = token_rules()['grant'];
    return $g[$plan] ?? $g['free'];
}

function token_balance(int $userId): int
{
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
    if ($delta === 0) return false;
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
    if (!$user) return 0;
    if ((int)$promo['user_id'] === (int)$user['id']) return 0;

    $rules = token_rules();
    if ($rules['earn_view'] <= 0) return 0;
    if ($rules['daily_earn_cap'] > 0 && token_earned_today((int)$user['id']) >= $rules['daily_earn_cap']) return 0;

    // The unique key is the check: if today's row already exists, this member
    // has already been paid for this promotion today.
    try {
        q('INSERT INTO promotion_views (user_id, promotion_id, day) VALUES (?,?,CURDATE())',
          [$user['id'], $promo['id']]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') return 0;
        throw $e;
    }

    $amount = $rules['earn_view'];
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
    if ($amount <= 0) return true;
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
    if ($delta === 0) return false;
    q('INSERT INTO token_events (user_id, delta, reason, note) VALUES (?,?,?,?)',
      [$userId, $delta, 'staff:adjust', $note]);
    q('UPDATE users SET token_balance = GREATEST(0, token_balance + ?) WHERE id = ?', [$delta, $userId]);
    return true;
}

function token_history(int $userId, int $limit = 50): array
{
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
    return row('SELECT * FROM articles WHERE user_id = ? AND month = ?',
               [$userId, $month ?: date('Y-m')]);
}

/** Channels the network posts a Featured member's article out to. */
function article_channels(): array
{
    return ['Facebook', 'Instagram', 'TikTok', 'YouTube', 'Pinterest', 'Reddit'];
}

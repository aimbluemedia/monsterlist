<?php
// ---------------------------------------------------------------------------
// Monthly service cycles for paid members.
//
// A paid plan carries work we owe the member every month — a checklist for Pro,
// an article and six channel posts for Premium. This is the machinery that says
// who is owed that work right now, and lets staff tick it off.
//
// The cycle turns on the member's own renewal date rather than on the calendar
// month, so somebody who joined on the 20th comes due on the 20th, not on the
// 1st with everybody else.
//
// There is no cron. A renewal date that has passed is rolled forward the next
// time a staff page asks who is due, and the task row's unique (user_id, month)
// key makes opening the same cycle twice a no-op — the same approach the token
// allowance uses, and for the same reason: shared-hosting cron is the least
// dependable part of this stack.
// ---------------------------------------------------------------------------

/** Is the cycle schema present? Same graceful-degradation rule as tokens. */
function cycles_ready(): bool
{
    static $ok = null;
    if ($ok === null) {
        $ok = table_exists('member_tasks')
              && column_exists('users', 'plan_renews_on')
              && column_exists('users', 'plan_comped');
    }
    return $ok;
}

/**
 * The same day next month, clamped to a day that exists.
 *
 * Renewing on the 31st means February has to mean the 28th — or the 29th — and
 * PHP's own "+1 month" answers March 3rd, which is a date nobody agreed to.
 */
function cycle_add_month(string $date): string
{
    $d     = new DateTimeImmutable($date);
    $day   = (int)$d->format('j');
    $first = $d->modify('first day of next month');
    return $first->setDate((int)$first->format('Y'), (int)$first->format('n'),
                           min($day, (int)$first->format('t')))->format('Y-m-d');
}

/** What a plan owes each month. Pro is a free-text checklist; Premium is not. */
function cycle_items(string $plan): array
{
    if ($plan === 'featured') {
        // The article first, then each place it has to go — so a half-finished
        // month reads as "YouTube and Reddit still to do" rather than as a
        // single tick nobody can interrogate.
        return array_merge(['Article written & published'],
                           array_map(fn($c) => 'Posted to ' . $c, article_channels()));
    }
    return [];   // Pro: the note field is the checklist
}

/** The task row for one member and month, or null. */
function cycle_task(int $userId, string $month): ?array
{
    if (!cycles_ready()) return null;
    return row('SELECT * FROM member_tasks WHERE user_id = ? AND month = ?', [$userId, $month]);
}

/**
 * Bring one member up to date: open any cycle their renewal date has reached,
 * and move the date on.
 *
 * A member with no renewal date gets one now — that covers accounts that were
 * already paying before any of this existed.
 */
function cycle_roll(array $user): void
{
    if (!cycles_ready()) return;
    $plan = (string)($user['plan'] ?? 'free');
    if (!in_array($plan, ['pro', 'featured'], true)) return;

    $today  = date('Y-m-d');
    $renews = (string)($user['plan_renews_on'] ?? '');
    if ($renews === '') {
        // Their first cycle opens today, and the next falls a month from now.
        cycle_open((int)$user['id'], $plan, $today);
        q('UPDATE users SET plan_renews_on = ? WHERE id = ?', [cycle_add_month($today), (int)$user['id']]);
        return;
    }

    // A loop, not a single step: an account nobody has looked at for three
    // months owes three cycles, and skipping to the current one would quietly
    // write off the two in between.
    $guard = 0;
    while ($renews <= $today && $guard++ < 24) {
        cycle_open((int)$user['id'], $plan, $renews);
        $renews = cycle_add_month($renews);
    }
    if ($renews !== (string)$user['plan_renews_on']) {
        q('UPDATE users SET plan_renews_on = ? WHERE id = ?', [$renews, (int)$user['id']]);
    }
}

/** Open one cycle. Repeating this is free — the unique key absorbs it. */
function cycle_open(int $userId, string $plan, string $date): void
{
    try {
        q('INSERT INTO member_tasks (user_id, month, plan, due_on) VALUES (?,?,?,?)',
          [$userId, substr($date, 0, 7), $plan, $date]);
    } catch (PDOException $e) {
        if ((string)$e->getCode() !== '23000') throw $e;   // already open
    }
}

/** Roll every paid member forward. Called when a staff queue page loads. */
function cycle_roll_all(string $plan): void
{
    if (!cycles_ready()) return;
    foreach (rows('SELECT id, plan, plan_renews_on FROM users WHERE role = "member" AND plan = ?', [$plan]) as $m) {
        cycle_roll($m);
    }
}

/** Members owed work right now: cycle opened, not yet ticked off. */
function cycle_due(string $plan): array
{
    if (!cycles_ready()) return [];
    return rows(
        'SELECT t.*, u.email, u.name, u.plan_comped, u.plan_renews_on,
                b.id AS business_id, b.name AS business_name
           FROM member_tasks t
           JOIN users u ON u.id = t.user_id
           LEFT JOIN businesses b ON b.owner_id = u.id
          WHERE t.done_at IS NULL AND t.plan = ? AND u.plan = ?
          ORDER BY t.due_on ASC, t.id ASC', [$plan, $plan]);
}

/** Everyone on the plan, due or not — the roster, with their next date. */
function cycle_roster(string $plan): array
{
    if (!cycles_ready()) return [];
    return rows(
        'SELECT u.id, u.email, u.name, u.plan_comped, u.plan_renews_on,
                b.name AS business_name,
                (SELECT COUNT(*) FROM member_tasks t WHERE t.user_id = u.id AND t.done_at IS NOT NULL) AS months_done
           FROM users u
           LEFT JOIN businesses b ON b.owner_id = u.id
          WHERE u.role = "member" AND u.plan = ?
          ORDER BY u.plan_renews_on IS NULL, u.plan_renews_on ASC, u.id ASC', [$plan]);
}

/** Which checklist items are ticked on a task. */
function cycle_checked(?array $task): array
{
    $c = json_decode((string)($task['checklist'] ?? ''), true);
    return is_array($c) ? $c : [];
}

/**
 * Save a task's checklist and note.
 *
 * Completing itself is the one thing this does on its own: when every item a
 * plan owes has been ticked, the month is finished, and asking staff to tick
 * six boxes and then press Done as well is asking them to say it twice.
 */
function cycle_save(int $taskId, array $checked, string $note): void
{
    $task = row('SELECT * FROM member_tasks WHERE id = ?', [$taskId]);
    if (!$task) return;

    $items = cycle_items((string)$task['plan']);
    $keep  = array_values(array_intersect($items, $checked));
    $all   = $items && count($keep) === count($items);

    q('UPDATE member_tasks SET checklist = ?, note = ?, done_at = ? WHERE id = ?',
      [$keep ? json_encode($keep, JSON_UNESCAPED_SLASHES) : null,
       mb_substr($note, 0, 500) ?: null,
       $all ? date('Y-m-d H:i:s') : $task['done_at'],
       $taskId]);
}

/** Tick a month off, or put it back. */
function cycle_mark(int $taskId, bool $done): void
{
    q('UPDATE member_tasks SET done_at = ? WHERE id = ?', [$done ? date('Y-m-d H:i:s') : null, $taskId]);
}

/**
 * Put a member on a plan by hand, and give it a renewal date.
 *
 * Comped means exactly what it says: this plan was granted, not bought, and
 * Stripe has no say over it. Test accounts and gifted accounts are the same
 * thing to the site, and neither should be downgraded by a webhook about a
 * subscription that was never theirs.
 */
function cycle_set_plan(int $userId, string $plan, bool $comped = true): void
{
    if (!in_array($plan, ['free', 'pro', 'featured'], true)) return;

    q('UPDATE users SET plan = ? WHERE id = ?', [$plan, $userId]);
    sync_business_tiers($userId, $plan);
    if (!cycles_ready()) return;

    if ($plan === 'free') {
        // Nothing is owed on Free, so there is no date and no open cycle. Months
        // already completed stay in the record — they happened.
        q('UPDATE users SET plan_renews_on = NULL, plan_comped = 0 WHERE id = ?', [$userId]);
        q('DELETE FROM member_tasks WHERE user_id = ? AND done_at IS NULL', [$userId]);
        return;
    }

    q('UPDATE users SET plan_comped = ?, plan_renews_on = ? WHERE id = ?',
      [$comped ? 1 : 0, cycle_add_month(date('Y-m-d')), $userId]);

    // An unfinished cycle from the plan they were on is dropped. Both queues
    // match a task's plan against the member's current one, so a Pro task left
    // behind by a move to Premium appears on neither and is owed by nobody —
    // open forever, and only visible on this member's own page. Finished
    // months are untouched: those were served.
    q('DELETE FROM member_tasks WHERE user_id = ? AND done_at IS NULL AND plan <> ?', [$userId, $plan]);

    // The first cycle opens now rather than in a month's time: they are on the
    // plan from today, so today is when we start owing them something.
    //
    // A month already SERVED on the old plan is left alone and this does
    // nothing — the unique key absorbs it — so upgrading on the 5th after Pro
    // work was done on the 1st does not buy a second service in the same
    // month; the new plan's first cycle is the next one. An unfinished month,
    // deleted just above, reopens here on the new plan and moves to its queue.
    cycle_open($userId, $plan, date('Y-m-d'));
}

/** Is this member's plan a comp? Stripe must not overrule one. */
function cycle_is_comped(int $userId): bool
{
    if (!cycles_ready()) return false;
    return (int)scalar('SELECT plan_comped FROM users WHERE id = ?', [$userId]) === 1;
}

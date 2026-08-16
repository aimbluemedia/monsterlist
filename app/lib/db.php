<?php
// PDO singleton + tiny query helpers. Prepared statements everywhere.

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = $GLOBALS['config']['db'];
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $c['host'], $c['name'], $c['charset']);
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // A MySQL that is not answering must fail fast. Without this the
                // connect blocks until PHP's own limit, every worker piles up
                // behind it, and the visitor gets nginx's 504 — which names
                // nothing and points at nobody. Ten seconds turns that into the
                // message below, which says exactly what to check.
                PDO::ATTR_TIMEOUT            => 10,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            $detail = !empty($GLOBALS['config']['debug']) ? '<p><code>' . htmlspecialchars($e->getMessage()) . '</code></p>' : '';
            echo '<h1>Database connection failed</h1>'
               . '<p>The site could not connect to MySQL. Check the <code>db</code> settings in '
               . '<code>app/config.php</code> — host (usually <code>localhost</code>), database name, '
               . 'username and password must match what you created in cPanel → MySQL Databases, '
               . 'and the user must be <em>added to the database</em> with all privileges.</p>'
               . $detail
               . '<p>You can run a full server self-test at <code>/install-check.php</code>.</p>'
               . str_repeat(' ', 600);
            exit;
        }
    }
    return $pdo;
}

/**
 * A query failure with the statement attached, and the SQLSTATE kept.
 *
 * PDO carries its SQLSTATE ('23000' for a duplicate key, and so on) in the
 * exception code, and callers catch on it. Re-throwing a plain PDOException
 * silently replaced that code with 0 — so a caller relying on it, like the
 * token ledger treating a duplicate as "already granted", saw every duplicate
 * as a fatal error instead. The code is protected on Exception, which is why
 * this needs to be a subclass rather than a property assignment.
 */
class QueryException extends PDOException
{
    public function __construct(string $message, PDOException $previous)
    {
        parent::__construct($message, 0, $previous);
        $this->code = $previous->getCode();
        $this->errorInfo = $previous->errorInfo;
    }
}

/**
 * Run a prepared statement, return the PDOStatement.
 *
 * A failure is re-thrown with the statement attached. PDO's own message names
 * the fault but not the query, and "PDOException in db.php:38" is true of every
 * query in the application, which makes a live site impossible to diagnose.
 */
function q(string $sql, array $params = []): PDOStatement
{
    try {
        $st = db()->prepare($sql);
        $st->execute($params);
        return $st;
    } catch (PDOException $e) {
        $flat = trim(preg_replace('/\s+/', ' ', $sql));
        throw new QueryException($e->getMessage() . ' — SQL: ' . $flat, $e);
    }
}

/** First row or null. */
function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

/** All rows. */
function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** Single scalar value or null. */
function scalar(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/**
 * Every table in this database, as a lookup keyed by lowercase name.
 *
 * SHOW TABLES reads this database's own dictionary and nothing else. The
 * information_schema queries these helpers used to run are scoped with
 * TABLE_SCHEMA = DATABASE(), which reads like it is just as narrow, but on
 * shared hosting the server holds thousands of databases and the filter is
 * applied after the scan — so each call could take seconds, and the diagnostics
 * page fired a dozen of them. A probe whose job is to keep the site up must
 * never be the reason it goes down.
 *
 * One query per request, memoised: a schema does not change mid-request.
 */
function db_tables(): array
{
    static $list = null;
    if ($list === null) {
        $list = [];
        foreach (q('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) $list[strtolower((string)$t)] = true;
    }
    return $list;
}

/**
 * Does a table exist in the current database?
 *
 * Used by the diagnostics page to tell "this release's SQL has not been run"
 * apart from "the feature is broken" — the two look identical from a browser.
 */
function table_exists(string $table): bool
{
    return isset(db_tables()[strtolower($table)]);
}

/** Every column on one table, keyed by lowercase name. Empty if no such table. */
function db_columns(string $table): array
{
    static $seen = [];
    $key = strtolower($table);
    if (!isset($seen[$key])) {
        $seen[$key] = [];
        // The name goes into the SQL text — SHOW COLUMNS takes no placeholder
        // there — so anything that is not a plain identifier is refused rather
        // than quoted and hoped for.
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) && table_exists($table)) {
            foreach (q("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN) as $c) {
                $seen[$key][strtolower((string)$c)] = true;
            }
        }
    }
    return $seen[$key];
}

/** Does a column exist on a table? */
function column_exists(string $table, string $column): bool
{
    return isset(db_columns($table)[strtolower($column)]);
}

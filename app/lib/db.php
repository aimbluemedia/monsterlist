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
 * Does a table exist in the current database?
 *
 * Used by the diagnostics page to tell "this release's SQL has not been run"
 * apart from "the feature is broken" — the two look identical from a browser.
 * Results are memoised: a schema does not change inside one request.
 */
function table_exists(string $table): bool
{
    static $seen = [];
    if (isset($seen[$table])) return $seen[$table];
    $n = scalar('SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]);
    return $seen[$table] = ((int)$n > 0);
}

/** Does a column exist on a table? */
function column_exists(string $table, string $column): bool
{
    static $seen = [];
    $key = $table . '.' . $column;
    if (isset($seen[$key])) return $seen[$key];
    $n = scalar('SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]);
    return $seen[$key] = ((int)$n > 0);
}

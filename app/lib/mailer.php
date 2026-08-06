<?php
// Transactional mail via PHP mail() — works on typical cPanel hosts.
// Swap the body of send_mail() for SMTP/API delivery later if needed.

function send_mail(string $to, string $subject, string $body): bool
{
    // Mail must never break the action that triggered it. Some shared hosts
    // put mail() in disable_functions, which is a fatal Error rather than a
    // suppressible warning, and a missing welcome email is not a reason to
    // fail a signup whose account row has already been written.
    if (!function_exists('mail')) {
        error_log('MonsterList: mail() is disabled on this server; skipped "' . $subject . '" to ' . $to);
        return false;
    }
    $cfg  = $GLOBALS['config']['mail'] ?? [];
    $host = parse_url((string)($GLOBALS['config']['site_url'] ?? ''), PHP_URL_HOST) ?: 'localhost';
    $addr = $cfg['from'] ?? ('no-reply@' . $host);
    $name = $cfg['from_name'] ?? 'MonsterList';
    $headers = implode("\r\n", [
        'From: ' . sprintf('%s <%s>', $name, $addr),
        'Reply-To: ' . $addr,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ]);
    try {
        return @mail($to, $subject, $body, $headers);
    } catch (Throwable $e) {
        error_log('MonsterList: mail to ' . $to . ' failed — ' . $e->getMessage());
        return false;
    }
}

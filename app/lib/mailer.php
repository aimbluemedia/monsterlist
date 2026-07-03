<?php
// Transactional mail via PHP mail() — works on typical cPanel hosts.
// Swap the body of send_mail() for SMTP/API delivery later if needed.

function send_mail(string $to, string $subject, string $body): bool
{
    $cfg  = $GLOBALS['config']['mail'];
    $from = sprintf('%s <%s>', $cfg['from_name'], $cfg['from']);
    $headers = implode("\r\n", [
        'From: ' . $from,
        'Reply-To: ' . $cfg['from'],
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ]);
    return @mail($to, $subject, $body, $headers);
}

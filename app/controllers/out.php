<?php
// /out/{id} — tracked click-through to a business website (analytics "click" event).

$biz = row('SELECT id, website FROM businesses WHERE id = ? AND status = "live"', [(int)$segments[1]]);
if (!$biz || empty($biz['website'])) not_found();

track_event((int)$biz['id'], 'click');
header('X-Robots-Tag: noindex, nofollow');
redirect($biz['website']);

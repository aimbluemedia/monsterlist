<?php
// ---------------------------------------------------------------------------
// Shared inline SVG sprite. Included by the homepage promotion graphic and the
// promotion-engine section; the guard keeps it to one copy per page however
// many partials ask for it.
//
// Symbols are stroked by default (see .ml-sprite in style.css); the few that
// read better as solid shapes are listed in the fill rule there.
// ---------------------------------------------------------------------------
if (defined('ML_ICONS')) return;
define('ML_ICONS', 1);
?>
<svg class="ml-sprite" aria-hidden="true" focusable="false">
  <symbol id="ml-fb" viewBox="0 0 24 24"><path d="M13.5 21v-7.5h2.5l.5-3h-3V8.8c0-.9.3-1.3 1.3-1.3H16.6V4.8A17 17 0 0 0 14.5 4.7c-2.2 0-3.6 1.3-3.6 3.8v2H8.4v3h2.5V21z"/></symbol>
  <symbol id="ml-ig" viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.3"/></symbol>
  <symbol id="ml-tt" viewBox="0 0 24 24"><path d="M14 4v9.6a3 3 0 1 1-2.6-3v2.6a.6.6 0 1 0 .6.6V4z"/><path d="M14 4c.4 2.3 1.9 3.7 4.2 3.9v2.6C16.6 10.4 15.1 9.7 14 8.6z"/></symbol>
  <symbol id="ml-yt" viewBox="0 0 24 24"><rect x="2.5" y="5.5" width="19" height="13" rx="4.2"/><path d="m10.4 9.3 5 2.7-5 2.7z"/></symbol>
  <symbol id="ml-rd" viewBox="0 0 24 24"><circle cx="12" cy="13.6" r="7.2"/><circle cx="9.4" cy="13" r="1.05"/><circle cx="14.6" cy="13" r="1.05"/><path d="M9.2 16.4c1.8 1.3 3.8 1.3 5.6 0"/><path d="M16.3 5.6 14.4 12"/><circle cx="16.8" cy="5.1" r="1.5"/></symbol>
  <symbol id="ml-pt" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.4"/><path d="M10.2 20.4 12.6 11"/><path d="M9.4 13.4c-.9-2.7.5-5.3 3.1-5.6 2.3-.3 3.9 1.2 3.7 3.4-.2 2.3-1.8 3.7-3.4 3.3-.9-.2-1.3-1-1.1-1.9"/></symbol>
  <symbol id="ml-sr" viewBox="0 0 24 24"><path d="M20.5 12a8.5 8.5 0 1 1-2.5-6"/><path d="M20.5 12h-7.7"/></symbol>
  <symbol id="ml-sp" viewBox="0 0 24 24"><path d="m12 2.8 2.3 6.6 6.6 2.3-6.6 2.3-2.3 6.6-2.3-6.6L3.1 11.7l6.6-2.3z"/></symbol>
  <symbol id="ml-pp" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.6"/><path d="m16 16 4.6 4.6"/><path d="M11 7.6v6.8M7.6 11h6.8"/></symbol>
  <symbol id="ml-cl" viewBox="0 0 24 24"><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6 5.6 18.4"/></symbol>
  <symbol id="ml-blog" viewBox="0 0 24 24"><path d="M6 3h8.5L19 7.5V21H6z"/><path d="M14.5 3v4.5H19"/><path d="M9 12.5h7M9 16h5"/></symbol>
  <symbol id="ml-prod" viewBox="0 0 24 24"><path d="M12 3.2 4 7.6v8.8l8 4.4 8-4.4V7.6z"/><path d="M4 7.6 12 12l8-4.4"/><path d="M12 12v8.8"/></symbol>
  <symbol id="ml-serv" viewBox="0 0 24 24"><path d="M3.5 19.5h17"/><path d="M6.5 19.5v-5.2a5.5 5.5 0 0 1 11 0v5.2"/><path d="M12 6.4V3.6"/></symbol>
  <symbol id="ml-star" viewBox="0 0 24 24"><path d="m12 3.4 2.7 5.5 6 .9-4.35 4.2 1.03 6L12 17.2l-5.38 2.8 1.03-6L3.3 9.8l6-.9z"/></symbol>
  <symbol id="ml-link" viewBox="0 0 24 24"><path d="M10.5 13.5a4 4 0 0 0 5.7 0l2.6-2.6a4 4 0 1 0-5.7-5.7l-1.3 1.3"/><path d="M13.5 10.5a4 4 0 0 0-5.7 0l-2.6 2.6a4 4 0 1 0 5.7 5.7l1.3-1.3"/></symbol>
  <symbol id="ml-drop" viewBox="0 0 24 24"><path d="M12 3v10.5"/><path d="m8 10 4 4 4-4"/><path d="M4 16.5v2A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5v-2"/></symbol>
  <symbol id="ml-spark" viewBox="0 0 24 24"><path d="m9.5 3 1.6 4.4L15.5 9l-4.4 1.6L9.5 15l-1.6-4.4L3.5 9l4.4-1.6z"/><path d="m17.5 13 .9 2.5 2.6.9-2.6 1-.9 2.6-1-2.6-2.5-1 2.5-.9z"/></symbol>
  <symbol id="ml-people" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.4"/><path d="M2.8 20a6.2 6.2 0 0 1 12.4 0"/><path d="M16.4 5.2a3.4 3.4 0 0 1 0 6.5"/><path d="M17.8 14.4A6.2 6.2 0 0 1 21.2 20"/></symbol>
  <symbol id="ml-ok" viewBox="0 0 24 24"><path d="m5 12.5 4.6 4.6L19 7.6"/></symbol>
  <symbol id="ml-gm" viewBox="0 0 24 24"><path d="M12 2.6c.4 4.9 4.5 8.9 9.4 9.4-4.9.4-8.9 4.5-9.4 9.4-.4-4.9-4.5-8.9-9.4-9.4 4.9-.5 8.9-4.5 9.4-9.4z"/></symbol>
</svg>

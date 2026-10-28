<?php
const OPLEN_ADMIN_VERSION = '0.11.0';
const OPLEN_LAST_UPDATE = '01 Oct 2026 · 15:28 CDMX';
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function page_url(string $page): string { return 'index.php?page=' . rawurlencode($page); }



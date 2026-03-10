<?php
// services.php – Services listing (modern card-based layout)
require_once __DIR__ . '/app/init.php';
$auth->requireLogin();
$pageTitle = 'Services';
$db = Database::getInstance();

$search   = trim($_GET['q'] ?? '');
$cat      = $_GET['cat'] ?? '';
$platform = trim($_GET['platform'] ?? '');
$sort     = $_GET['sort'] ?? 'id';
$dir      = strtolower($_GET['dir'] ?? 'asc');
if (!in_array($dir, ['asc', 'desc'])) $dir = 'asc';

$cat = $cat !== '' ? trim($cat) : '';
$where  = "WHERE status='active'";
$params = [];
if ($search)   { $where .= " AND name LIKE ?"; $params[] = "%$search%"; }
if ($platform) { $where .= " AND category LIKE ?"; $params[] = "%$platform%"; }
if ($cat)      { $where .= " AND TRIM(COALESCE(category,'')) = ?"; $params[] = $cat; }

$orderBy = 'service_id ASC';
if ($sort === 'rate') $orderBy = 'rate ' . ($dir === 'desc' ? 'DESC' : 'ASC');
if ($sort === 'min')  $orderBy = 'min ' . ($dir === 'desc' ? 'DESC' : 'ASC');
if ($sort === 'max')  $orderBy = 'max ' . ($dir === 'desc' ? 'DESC' : 'ASC');
if ($sort === 'id')   $orderBy = 'service_id ' . ($dir === 'desc' ? 'DESC' : 'ASC');

$services   = $db->fetchAll("SELECT * FROM services $where ORDER BY $orderBy LIMIT 500", $params);
$categoriesRaw = $db->fetchAll("SELECT DISTINCT category FROM services WHERE status='active' ORDER BY category");
$seen = [];
$categories = [];
foreach ($categoriesRaw as $row) {
    $c = trim($row['category'] ?? '');
    if ($c !== '' && !isset($seen[$c])) {
        if ($platform === '' || stripos($c, $platform) !== false) {
            $seen[$c] = true;
            $categories[] = ['category' => $c];
        }
    }
}

$platformIcons = [
    'YouTube' => '▶', 'Instagram' => '📷', 'TikTok' => '🎵', 'Twitter' => '𝕏', 'Facebook' => 'f', 'LinkedIn' => 'in',
    'Telegram' => '✈', 'Spotify' => '♫', 'SoundCloud' => '🔊', 'Twitch' => '🎮', 'Discord' => '💬', 'Tumblr' => 't',
    'Reddit' => '🔴', 'Pinterest' => 'P', 'Vimeo' => 'V', 'VK' => 'VK', 'Dailymotion' => 'D', 'Apple Music' => '🎵',
    'Website Traffic' => '🌐', 'Mobile' => '📱', 'Kwai' => 'K', 'Deezer' => 'D', 'Clubhouse' => 'C', 'Shazam' => 'S',
    'Rumble' => 'R', 'Kick' => 'K', 'Medium' => 'M', 'BlueSky' => '🦋', 'Binance' => 'B', 'Default' => '+',
];
function platformIcon($category, $map) {
    foreach ($map as $key => $icon) {
        if (stripos($category, $key) !== false) return $icon;
    }
    return mb_substr($category, 0, 1);
}
function platformKeyFromCategory($category, $platformList) {
    foreach (array_keys($platformList) as $key) {
        if (stripos($category, $key) !== false) return $key;
    }
    return null;
}

$newCutoff = date('Y-m-d H:i:s', time() - 7*24*3600);
$sortLinks = [];
$q = array_filter(['q' => $search ?: null, 'cat' => $cat ?: null, 'platform' => $platform ?: null], function ($v) { return $v !== null && $v !== ''; });
foreach (['id' => 'ID', 'rate' => 'Price', 'min' => 'Min', 'max' => 'Max'] as $col => $label) {
    $q2 = $q;
    $q2['sort'] = $col;
    $q2['dir'] = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    $sortLinks[$col] = ['url' => path('services.php') . '?' . http_build_query($q2), 'label' => $label];
}

// Platform logos filter: key => [label, title, svg fragment]. Order shown in UI.
$platformList = [
    'Telegram' => ['label' => 'Telegram', 'title' => 'Telegram'],
    'Instagram' => ['label' => 'Instagram', 'title' => 'Instagram'],
    'YouTube' => ['label' => 'YouTube', 'title' => 'YouTube'],
    'TikTok' => ['label' => 'TikTok', 'title' => 'TikTok'],
    'Twitter' => ['label' => 'X', 'title' => 'Twitter / X'],
    'Facebook' => ['label' => 'Facebook', 'title' => 'Facebook'],
    'LinkedIn' => ['label' => 'LinkedIn', 'title' => 'LinkedIn'],
    'Discord' => ['label' => 'Discord', 'title' => 'Discord'],
    'Spotify' => ['label' => 'Spotify', 'title' => 'Spotify'],
    'Twitch' => ['label' => 'Twitch', 'title' => 'Twitch'],
    'Reddit' => ['label' => 'Reddit', 'title' => 'Reddit'],
    'Pinterest' => ['label' => 'Pinterest', 'title' => 'Pinterest'],
    'VK' => ['label' => 'VK', 'title' => 'VK'],
    'Tumblr' => ['label' => 'Tumblr', 'title' => 'Tumblr'],
    'SoundCloud' => ['label' => 'SoundCloud', 'title' => 'SoundCloud'],
    'Dailymotion' => ['label' => 'Dailymotion', 'title' => 'Dailymotion'],
    'Kwai' => ['label' => 'Kwai', 'title' => 'Kwai'],
    'Rumble' => ['label' => 'Rumble', 'title' => 'Rumble'],
    'BlueSky' => ['label' => 'BlueSky', 'title' => 'BlueSky'],
];

// Inline SVG icons for platform filter (24x24 viewBox). Keys must match $platformList.
$platformSvgs = [
    'Telegram' => '<path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>',
    'Instagram' => '<path fill="currentColor" d="M12 2.16c3.2 0 3.58 0 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s0 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.65.07-4.85.07s-3.58 0-4.85-.07c-3.25-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.65-.07-4.85s0-3.58.07-4.85C3.23 3.92 4.76 2.4 8.02 2.25 9.29 2.19 9.67 2.18 12.87 2.18zM12 0C8.74 0 8.33 0 7.05.07 2.7.27.27 2.7.07 7.05 0 8.33 0 8.74 0 12s0 3.67.07 4.95c.2 4.35 2.78 6.93 7.13 7.13C8.33 24 8.74 24 12 24s3.67 0 4.95-.07c4.35-.2 6.93-2.78 7.13-7.13C24 15.67 24 15.26 24 12s0-3.67-.07-4.95c-.2-4.35-2.78-6.93-7.13-7.13C15.67 0 15.26 0 12 0zm0 5.84a6.16 6.16 0 100 12.32 6.16 6.16 0 000-12.32zM12 16a4 4 0 110-8 4 4 0 010 8zm6.41-11.85a1.44 1.44 0 100 2.88 1.44 1.44 0 000-2.88z"/>',
    'YouTube' => '<path fill="currentColor" d="M23.5 6.5a2.9 2.9 0 00-2.05-2.05C19.5 4 12 4 12 4s-7.5 0-9.45.45A2.9 2.9 0 00.5 6.5 30 30 0 000 12a30 30 0 00.5 5.5 2.9 2.9 0 002.05 2.05C4.5 20 12 20 12 20s7.5 0 9.45-.45a2.9 2.9 0 002.05-2.05A30 30 0 0024 12a30 30 0 00-.5-5.5zM9.5 15.5v-7l6.5 3.5-6.5 3.5z"/>',
    'TikTok' => '<path fill="currentColor" d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/>',
    'Twitter' => '<path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
    'Facebook' => '<path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>',
    'LinkedIn' => '<path fill="currentColor" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>',
    'Discord' => '<path fill="currentColor" d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028 14.09 14.09 0 001.226-1.994.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>',
    'Spotify' => '<path fill="currentColor" d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.405.12-.78-.18-.9-.58-.12-.405.18-.78.58-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.48.66.24 1.021zm1.44-3.3c-.301.42-.84.54-1.26.24-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.02.6-1.14 4.44-1.341 9.84-.84 13.5 1.62.42.24.54.84.24 1.26zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.18-1.38-.72-.18-.6.18-1.2.72-1.38 4.26-1.26 11.28-1.02 15.72 1.62.54.3.72 1.02.42 1.56-.3.42-1.02.6-1.56.42z"/>',
    'Twitch' => '<path fill="currentColor" d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z"/>',
    'Reddit' => '<path fill="currentColor" d="M12 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 01-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.33-1.01 1.614l.002.003c-.076.35-.24.66-.51.897a3.23 3.23 0 01-2.556 1.194 3.24 3.24 0 01-2.555-1.194 2.02 2.02 0 01-.51-.897l.002-.003c-.574-.283-1.009-.898-1.009-1.614 0-.968.786-1.754 1.754-1.754.477 0 .899.182 1.206.49 1.195-.856 2.85-1.418 4.673-1.487l-.8-3.748-.002.041v-.021a1.25 1.25 0 011.248-1.25zM9.25 8.166c-.414 0-.75.336-.75.75 0 .414.336.75.75.75.414 0 .75-.336.75-.75a.75.75 0 00-.75-.75zm5.5 0c-.414 0-.75.336-.75.75 0 .414.336.75.75.75.414 0 .75-.336.75-.75a.75.75 0 00-.75-.75zM12 19.5a4.5 4.5 0 01-4.005-2.488 2.01 2.01 0 01.51-.897 2.02 2.02 0 01.51-.897 4.5 4.5 0 016 0c.17.237.334.547.51.897.17.237.334.547.51.897a4.5 4.5 0 01-4.005 2.488z"/>',
    'Pinterest' => '<path fill="currentColor" d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.18.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.001 11.985.001z"/>',
    'VK' => '<path fill="currentColor" d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-1-1.49-1.135-1.744-1.135-.356 0-.458.102-.458.593v1.575c0 .424-.135.678-1.253.678-1.846 0-3.896-1.118-5.335-3.202C4.624 10.857 4.03 8.57 4.03 8.096c0-.254.102-.491.593-.491h1.744c.44 0 .61.203.78.678.863 2.49 2.303 4.675 2.896 4.675.22 0 .322-.102.322-.66V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.203.17-.407.44-.407h2.744c.373 0 .508.203.508.643v3.473c0 .372.17.508.271.508.22 0 .407-.136.813-.542 1.254-1.406 2.151-3.574 2.151-3.574.119-.254.322-.491.763-.491h1.744c.525 0 .644.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.254.44 0 .78.186.254.796.779 1.203 1.253.745.847 1.32 1.558 1.473 2.05.17.49-.085.744-.576.744z"/>',
    'Tumblr' => '<path fill="currentColor" d="M14.563 24c-5.093 0-7.031-3.72-7.031-6.411V9.747H5.46V6.643c3.063-1.063 4.076-3.74 4.239-5.627h3.026v4.313h4.313v3.791h-4.313v7.41c0 1.628.76 2.063 2.313 2.063.61 0 1.39-.135 1.39-.135v3.36c-.423.063-1.017.135-1.627.135z"/>',
    'SoundCloud' => '<path fill="currentColor" d="M1.175 12.225c-.051 0-.094.046-.101.1l-.233 2.154.233 2.105c.007.058.05.098.101.098.05 0 .09-.04.099-.098l.255-2.105-.27-2.154c-.009-.06-.052-.1-.084-.1zm2.795.074c-.07 0-.12.063-.127.14l-.188 2.08.188 2.027c.007.073.057.127.127.127.063 0 .114-.054.121-.127l.204-2.027-.204-2.08c-.007-.077-.058-.14-.121-.14zm2.617-.037c-.084 0-.14.077-.14.16l-.14 2.12.14 2.06c0 .084.056.154.14.154.07 0 .127-.07.127-.154l.154-2.06-.154-2.12c0-.083-.057-.16-.127-.16zm2.568.037c-.098 0-.164.084-.164.177l-.117 2.083.117 2.04c0 .094.066.164.164.164.084 0 .15-.07.15-.164l.14-2.04-.14-2.083c0-.093-.066-.177-.15-.177zm2.27-.074c-.107 0-.184.098-.184.21l-.094 2.08.094 2.027c0 .113.077.21.184.21.098 0 .175-.097.175-.21l.107-2.027-.107-2.08c0-.112-.077-.21-.175-.21zm2.382.037c-.12 0-.21.12-.21.246l-.07 2.027.07 2.08c0 .127.09.246.21.246.12 0 .21-.12.21-.246l.084-2.08-.084-2.027c0-.126-.09-.246-.21-.246zm2.617-.037c-.14 0-.245.14-.245.28l-.047 2.08.047 2.027c0 .14.105.28.245.28.14 0 .245-.14.245-.28l.07-2.027-.07-2.08c0-.14-.105-.28-.245-.28zm2.382.037c-.164 0-.28.164-.28.35l-.023 2.027.023 2.08c0 .187.116.35.28.35.164 0 .28-.163.28-.35l.047-2.08-.047-2.027c0-.186-.116-.35-.28-.35zM12 5.18C12 3.006 10.467 0 7.35 0 4.767 0 2.7 2.337 2.7 5.18v9.64h14.4V5.18c0-2.833-1.867-5.18-4.45-5.18-.07 0-.14.007-.21.014C12.21.84 12 2.88 12 5.18z"/>',
    'Dailymotion' => '<path fill="currentColor" d="M12.072 11.906c.084.506.084.506.756 2.472l.756 2.471.487-1.622.486-1.622.486 1.622.487 1.622.756-2.471c.672-1.966.672-1.966.756-2.472.084-.505.084-.505-.323-.505h-.407l-.323.505-.323.505-.324-.505-.323-.505h-.814c-.407 0-.407 0-.323.505zm-2.35-4.448c-.323 0-.404 0-.485.084-.084.084-.084.165-.084.404v6.306c0 .404 0 .404.404.404h.404c.404 0 .404 0 .404-.404V8.047c0-.239 0-.32.084-.404.081-.084.162-.084.485-.084h.323V6.617h-.323zm4.448.323c-.162 0-.243 0-.324.081-.162.162-.162.405-.162 1.054v4.448c0 .647 0 .89.162 1.054.081.081.162.081.324.081.162 0 .243 0 .324-.081.162-.164.162-.407.162-1.054V7.816c0-.649 0-.892-.162-1.054-.081-.081-.162-.081-.324-.081zm2.35-.323c-.243 0-.404 0-.566.162-.162.162-.162.323-.162.566v4.851c0 .243 0 .404.162.566.162.162.323.162.566.162.243 0 .404 0 .566-.162.162-.162.162-.323.162-.566V7.574c0-.243 0-.404-.162-.566-.162-.162-.323-.162-.566-.162z"/>',
    'Kwai' => '<path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15v-5H8v-2h3V7h2v3h3v2h-3v5h-2z"/>',
    'Rumble' => '<path fill="currentColor" d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.5 12.5l-7 4.5V8l7 4.5z"/>',
    'BlueSky' => '<path fill="currentColor" d="M12 10.8c-2.04-2.04-5.36-2.04-7.4 0-2.04 2.04-2.04 5.36 0 7.4l3.7 3.7 3.7-3.7c2.04-2.04 2.04-5.36 0-7.4z"/>',
];

function platformSvg($key) {
    global $platformSvgs;
    if (isset($platformSvgs[$key])) {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="none" aria-hidden="true">' . $platformSvgs[$key] . '</svg>';
    }
    return '<span class="svc-platform-fallback">' . mb_substr($key, 0, 1) . '</span>';
}

require_once __DIR__ . '/layouts/header.php';
?>

<style>
/* ---- Services page: motion + layout ---- */
:root {
  --svc-ease: cubic-bezier(0.34, 1.56, 0.64, 1);
  --svc-duration: 0.35s;
}
@keyframes svcHeroPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.85; transform: scale(1.08); }
}
@keyframes svcCardReveal {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.svc-page-hero {
  background: linear-gradient(135deg, rgba(227,10,23,.08) 0%, rgba(227,10,23,.02) 50%, rgba(255,255,255,.6) 100%);
  border: 1px solid rgba(227,10,23,.12);
  border-radius: 20px;
  padding: 28px 32px;
  margin-bottom: 28px;
  position: relative;
  overflow: hidden;
  transition: box-shadow var(--svc-duration) ease, transform var(--svc-duration) var(--svc-ease);
}
.svc-page-hero:hover {
  box-shadow: 0 12px 40px rgba(227,10,23,.08);
  transform: translateY(-2px);
}
.svc-page-hero::before {
  content: '';
  position: absolute;
  top: -60px;
  right: -60px;
  width: 180px;
  height: 180px;
  background: radial-gradient(circle, rgba(227,10,23,.15) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
  animation: svcHeroPulse 8s ease-in-out infinite;
}
.svc-hero-title {
  font-family: 'Syne', sans-serif;
  font-size: clamp(1.5rem, 4vw, 1.85rem);
  font-weight: 800;
  color: var(--text);
  letter-spacing: -0.03em;
  margin-bottom: 6px;
}
.svc-hero-desc {
  font-size: 14px;
  color: var(--text-muted);
  max-width: 520px;
  line-height: 1.55;
}

/* Toolbar: search + sort + filters */
.svc-toolbar-wrap {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
}
.svc-search-form {
  display: flex;
  gap: 10px;
  align-items: center;
  flex: 1;
  min-width: 0;
  max-width: 420px;
}
.svc-search-form .form-control {
  flex: 1;
  min-width: 140px;
  background: #fff;
}
.svc-sort-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.svc-sort-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-muted);
}
.svc-sort-links {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}
.svc-sort-links a {
  display: inline-flex;
  align-items: center;
  padding: 8px 12px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  text-decoration: none;
  background: #fff;
  border: 1px solid var(--border);
  transition: all 0.2s ease;
}
.svc-sort-links a:hover {
  border-color: var(--primary);
  color: var(--primary);
  background: rgba(227,10,23,.06);
}
.svc-sort-links a.active {
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
}

/* Platform logos row */
.svc-platform-wrap {
  margin-bottom: 20px;
}
.svc-platform-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-muted);
  margin-bottom: 10px;
}
.svc-platform-scroll {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}
.svc-platform-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: #fff;
  border: 1.5px solid var(--border);
  color: var(--text-muted);
  text-decoration: none;
  transition: transform var(--svc-duration) var(--svc-ease), box-shadow var(--svc-duration) ease, border-color var(--svc-duration) ease, background var(--svc-duration) ease, color var(--svc-duration) ease;
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.svc-platform-btn svg {
  width: 24px;
  height: 24px;
  display: block;
}
.svc-platform-btn .svc-platform-fallback {
  font-size: 18px;
  font-weight: 700;
  line-height: 1;
}
.svc-platform-btn.svc-platform-all {
  font-size: 12px;
  font-weight: 700;
  width: auto;
  padding-left: 14px;
  padding-right: 14px;
}
.svc-platform-btn:hover {
  border-color: var(--primary);
  color: var(--primary);
  background: rgba(227,10,23,.06);
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 10px 28px rgba(227,10,23,.15);
}
.svc-platform-btn.active {
  background: linear-gradient(145deg, var(--primary), var(--primary-dark));
  border-color: var(--primary);
  color: #fff;
  box-shadow: 0 4px 16px rgba(227,10,23,.3);
}
.svc-platform-btn.active:hover {
  color: #fff;
  background: linear-gradient(145deg, var(--primary), var(--primary-dark));
}

/* Category pills (horizontal scroll) */
.svc-cats-wrap {
  margin-bottom: 24px;
}
.svc-cats-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-muted);
  margin-bottom: 10px;
}
.svc-cats-scroll {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding: 6px 0 12px;
  -webkit-overflow-scrolling: touch;
  scrollbar-height: 6px;
}
.svc-cats-scroll::-webkit-scrollbar { height: 6px; }
.svc-cat-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 14px;
  background: #fff;
  border: 1.5px solid var(--border);
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  text-decoration: none;
  color: var(--text-muted);
  transition: transform var(--svc-duration) var(--svc-ease), box-shadow var(--svc-duration) ease, border-color var(--svc-duration) ease, background var(--svc-duration) ease, color var(--svc-duration) ease;
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.svc-cat-pill:hover {
  border-color: var(--primary);
  color: var(--primary);
  background: rgba(227,10,23,.06);
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 10px 28px rgba(227,10,23,.15);
}
.svc-cat-pill.active {
  background: linear-gradient(145deg, var(--primary), var(--primary-dark));
  border-color: var(--primary);
  color: #fff;
  box-shadow: 0 4px 16px rgba(227,10,23,.3);
}
.svc-cat-pill .pill-icon {
  font-size: 16px;
  line-height: 1;
}

/* Results count */
.svc-results-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 18px;
}
.svc-results-count {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted);
}
.svc-results-count strong { color: var(--text); }

/* Service cards grid */
.svc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 18px;
}
@media (max-width: 380px) {
  .svc-grid { grid-template-columns: 1fr; }
}

.svc-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid var(--border);
  padding: 20px;
  transition: transform var(--svc-duration) var(--svc-ease), box-shadow var(--svc-duration) ease, border-color var(--svc-duration) ease;
  box-shadow: 0 2px 12px rgba(0,0,0,.04);
  display: flex;
  flex-direction: column;
  gap: 14px;
  position: relative;
  overflow: hidden;
  animation: svcCardReveal 0.5s var(--svc-ease) both;
}
.svc-card:nth-child(1){animation-delay:0.02s}.svc-card:nth-child(2){animation-delay:0.05s}.svc-card:nth-child(3){animation-delay:0.08s}.svc-card:nth-child(4){animation-delay:0.11s}.svc-card:nth-child(5){animation-delay:0.14s}.svc-card:nth-child(6){animation-delay:0.17s}.svc-card:nth-child(7){animation-delay:0.2s}.svc-card:nth-child(8){animation-delay:0.23s}.svc-card:nth-child(9){animation-delay:0.26s}.svc-card:nth-child(n+10){animation-delay:0.29s}
.svc-card::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--primary), var(--primary-light));
  opacity: 0;
  transition: opacity var(--svc-duration) ease;
}
.svc-card:hover {
  border-color: rgba(227,10,23,.3);
  box-shadow: 0 20px 48px rgba(227,10,23,.14), 0 0 0 1px rgba(227,10,23,.08);
  transform: translateY(-6px);
}
.svc-card:hover::after { opacity: 1; }
.svc-card-cta .btn {
  transition: transform var(--svc-duration) var(--svc-ease), box-shadow var(--svc-duration) ease;
}
.svc-card:hover .svc-card-cta .btn {
  transform: scale(1.02);
  box-shadow: 0 6px 20px rgba(227,10,23,.3);
}

.svc-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}
.svc-card-header-right {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  justify-content: flex-end;
  min-width: 0;
}
.svc-card-platform-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: var(--bg);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--text-muted);
}
.svc-card-platform-icon svg {
  width: 24px;
  height: 24px;
}
.svc-card-platform-icon .svc-platform-fallback {
  font-size: 18px;
  font-weight: 700;
  line-height: 1;
}
.svc-card-id-badge {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(145deg, rgba(227,10,23,.12), rgba(227,10,23,.06));
  border: 1px solid rgba(227,10,23,.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Syne', sans-serif;
  font-size: 13px;
  font-weight: 800;
  color: var(--primary);
  flex-shrink: 0;
}
.svc-card-cat {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 10px;
  background: var(--bg);
  border: 1px solid var(--border);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-muted);
  flex-shrink: 0;
}
.svc-card-cat .cat-icon { font-size: 13px; }
.svc-card-refill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 8px;
  background: rgba(34,197,94,.12);
  border: 1px solid rgba(34,197,94,.3);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: #16a34a;
  flex-shrink: 0;
}

.svc-card-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.9em;
}

.svc-card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}
.svc-card-rate {
  font-family: 'Syne', sans-serif;
  font-size: 18px;
  font-weight: 800;
  color: var(--primary);
}
.svc-card-rate span { font-size: 12px; font-weight: 600; opacity: .85; }
.svc-card-minmax {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.svc-card-minmax span {
  padding: 6px 12px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text);
}
.svc-card-minmax .min {
  background: rgba(227,10,23,.08);
  border: 1px solid rgba(227,10,23,.2);
}
.svc-card-minmax .max {
  background: rgba(99,102,241,.08);
  border: 1px solid rgba(99,102,241,.2);
}

.svc-card-cta {
  margin-top: auto;
  padding-top: 4px;
}
.svc-card-cta .btn {
  width: 100%;
  justify-content: center;
  padding: 12px 16px;
  font-size: 13px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.svc-card-new {
  position: absolute;
  top: 14px;
  right: 14px;
  padding: 4px 10px;
  border-radius: 8px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 10px rgba(227,10,23,.35);
}

/* List view (optional compact) */
.svc-list-view .svc-grid {
  grid-template-columns: 1fr;
}
.svc-list-view .svc-card {
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
}
.svc-list-view .svc-card-header { flex: 0 0 auto; }
.svc-list-view .svc-card-name {
  flex: 1 1 280px;
  min-height: 0;
  -webkit-line-clamp: 1;
}
.svc-list-view .svc-card-meta { flex: 0 0 auto; margin-left: auto; }
.svc-list-view .svc-card-cta { margin-top: 0; padding-top: 0; flex: 0 0 auto; }
.svc-list-view .svc-card-cta .btn { width: auto; }

/* View toggle */
.svc-view-toggle {
  display: flex;
  gap: 4px;
  border-radius: 12px;
  padding: 4px;
  background: var(--bg);
  border: 1px solid var(--border);
  width: fit-content;
}
.svc-view-toggle button {
  padding: 8px 14px;
  border: none;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  background: transparent;
  cursor: pointer;
  transition: all 0.2s;
}
.svc-view-toggle button:hover { color: var(--primary); }
.svc-view-toggle button.active {
  background: #fff;
  color: var(--primary);
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}

/* Empty state */
.svc-empty {
  text-align: center;
  padding: 56px 24px;
  background: #fff;
  border-radius: 20px;
  border: 1px solid var(--border);
  border-style: dashed;
}
.svc-empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.6;
}
.svc-empty-title {
  font-family: 'Syne', sans-serif;
  font-size: 18px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 8px;
}
.svc-empty-desc {
  font-size: 14px;
  color: var(--text-muted);
  margin-bottom: 20px;
  max-width: 320px;
  margin-left: auto;
  margin-right: auto;
}
.svc-empty .btn { min-width: 160px; }

@media (max-width: 768px) {
  .svc-page-hero { padding: 22px 20px; margin-bottom: 22px; }
  .svc-toolbar-wrap { flex-direction: column; align-items: stretch; }
  .svc-search-form { max-width: none; }
  .svc-sort-wrap { justify-content: flex-start; }
  .svc-sort-links { flex: 1; }
  .svc-sort-links a { padding: 10px 12px; min-height: 44px; }
  .svc-platform-scroll { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 8px; -webkit-overflow-scrolling: touch; }
  .svc-platform-btn { flex-shrink: 0; width: 44px; height: 44px; }
  .svc-cat-pill { padding: 10px 14px; font-size: 12px; }
  .svc-card { padding: 16px; }
  .svc-card-header-right { justify-content: flex-start; }
  .svc-card-name { font-size: 13px; }
  .svc-card-rate { font-size: 16px; }
  .svc-results-bar { flex-direction: column; align-items: flex-start; }
  .svc-view-toggle button { min-height: 44px; padding: 10px 16px; }
}

@media (prefers-reduced-motion: reduce) {
  .svc-card, .svc-cat-pill, .svc-sort-links a, .svc-page-hero { transition: none; animation: none; }
  .svc-card:hover, .svc-page-hero:hover { transform: none; }
  .svc-cat-pill:hover { transform: none; }
  .svc-page-hero::before { animation: none; }
}
</style>

<!-- Hero -->
<section class="svc-page-hero" data-reveal>
  <h1 class="svc-hero-title">Services</h1>
  <p class="svc-hero-desc">Browse all SMM services by network and category. Click a network icon to see only that network’s services, or use search and sort to find what you need.</p>
</section>

<!-- Search + Sort -->
<div class="svc-toolbar-wrap" data-reveal>
  <form method="GET" class="svc-search-form" role="search">
    <?php if ($cat): ?><input type="hidden" name="cat" value="<?= h($cat) ?>"><?php endif; ?>
    <?php if ($platform): ?><input type="hidden" name="platform" value="<?= h($platform) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= h($search) ?>" class="form-control" placeholder="Search services…" aria-label="Search services">
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
  <div class="svc-sort-wrap">
    <span class="svc-sort-label">Sort</span>
    <div class="svc-sort-links">
      <?php foreach ($sortLinks as $col => $info): ?>
      <a href="<?= h($info['url']) ?>" class="<?= $sort === $col ? 'active' : '' ?>"><?= h($info['label']) ?> <?= $sort === $col ? ($dir === 'asc' ? '↑' : '↓') : '' ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Platform logos: click to filter by network -->
<div class="svc-platform-wrap" data-reveal>
  <p class="svc-platform-label">Filter by network</p>
  <div class="svc-platform-scroll" role="tablist" aria-label="Filter by social network">
    <a class="svc-platform-btn svc-platform-all <?= !$platform ? 'active' : '' ?>" href="<?= h(path('services.php')) ?><?= $search ? '?q=' . urlencode($search) : '' ?>" title="All networks">All</a>
    <?php foreach ($platformList as $pKey => $pInfo):
      $isActive = $platform === $pKey;
      $platformUrl = path('services.php') . '?platform=' . urlencode($pKey);
      if ($search) $platformUrl .= '&q=' . urlencode($search);
    ?>
    <a class="svc-platform-btn <?= $isActive ? 'active' : '' ?>" href="<?= h($platformUrl) ?>" title="<?= h($pInfo['title']) ?>"><?= platformSvg($pKey) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Category pills -->
<div class="svc-cats-wrap" data-reveal>
  <p class="svc-cats-label">Category</p>
  <div class="svc-cats-scroll" role="tablist">
    <?php
      $allCatParams = array_filter(['platform' => $platform ?: null, 'q' => $search ?: null]);
      $allCatUrl = path('services.php') . ($allCatParams ? '?' . http_build_query($allCatParams) : '');
    ?>
    <a class="svc-cat-pill <?= !$cat ? 'active' : '' ?>" href="<?= h($allCatUrl) ?>">All</a>
    <?php foreach ($categories as $c):
      $icon = platformIcon($c['category'], $platformIcons);
      $catUrl = path('services.php') . '?cat=' . urlencode($c['category']);
      if ($platform) $catUrl .= '&platform=' . urlencode($platform);
      if ($search) $catUrl .= '&q=' . urlencode($search);
    ?>
    <a class="svc-cat-pill <?= $cat === $c['category'] ? 'active' : '' ?>" href="<?= h($catUrl) ?>"><span class="pill-icon"><?= h($icon) ?></span> <?= h($c['category']) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Results count + view toggle -->
<div class="svc-results-bar" data-reveal>
  <p class="svc-results-count"><strong><?= count($services) ?></strong> service<?= count($services) !== 1 ? 's' : '' ?></p>
  <?php if (!empty($services)): ?>
  <div class="svc-view-toggle" role="group" aria-label="View mode">
    <button type="button" class="svc-view-btn active" data-view="grid" aria-pressed="true">Grid</button>
    <button type="button" class="svc-view-btn" data-view="list" aria-pressed="false">List</button>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($services)): ?>
<div class="svc-list-container" id="svcListContainer">
<div class="svc-grid" id="svcGrid">
  <?php
  $displayedNew = false;
  foreach ($services as $s):
    $displayRate = $s['rate'] * (1 + $s['markup']/100);
    $isNew = isset($s['updated_at']) && $s['updated_at'] >= $newCutoff;
    $orderUrl = path('index.php') . '?cat=' . urlencode($s['category']) . '&service=' . $s['service_id'];
    $icon = platformIcon($s['category'], $platformIcons);
  ?>
  <?php $cardPlatformKey = platformKeyFromCategory($s['category'], $platformList); ?>
  <article class="svc-card" data-reveal>
    <?php if ($isNew): ?><span class="svc-card-new">New</span><?php endif; ?>
    <div class="svc-card-header">
      <span class="svc-card-platform-icon" aria-hidden="true"><?= $cardPlatformKey ? platformSvg($cardPlatformKey) : '<span class="svc-platform-fallback">' . h($icon) . '</span>' ?></span>
      <div class="svc-card-header-right">
        <span class="svc-card-id-badge">#<?= $s['service_id'] ?></span>
        <span class="svc-card-cat"><span class="cat-icon"><?= h($icon) ?></span> <?= h($s['category']) ?></span>
        <?php if (!empty($s['refill'])): ?><span class="svc-card-refill">Refill</span><?php endif; ?>
      </div>
    </div>
    <h2 class="svc-card-name"><?= h(mb_substr($s['name'], 0, 200)) ?></h2>
    <div class="svc-card-meta">
      <span class="svc-card-rate">$<?= number_format($displayRate, 2) ?> <span>/ 1K</span></span>
      <div class="svc-card-minmax">
        <span class="min">Min <?= number_format($s['min']) ?></span>
        <span class="max">Max <?= number_format($s['max']) ?></span>
      </div>
    </div>
    <div class="svc-card-cta">
      <a href="<?= h($orderUrl) ?>" class="btn btn-primary">Order now</a>
    </div>
  </article>
  <?php endforeach; ?>
</div>
</div>
<?php else: ?>
<div class="svc-empty" data-reveal>
  <div class="svc-empty-icon">🔍</div>
  <h2 class="svc-empty-title">No services found</h2>
  <p class="svc-empty-desc">Try changing filters or search term to see more services.</p>
  <a href="<?= h(path('services.php')) ?>" class="btn btn-primary">Show all services</a>
</div>
<?php endif; ?>

<script>
(function() {
  var container = document.getElementById('svcListContainer');
  if (!container) return;
  var btns = document.querySelectorAll('.svc-view-btn');
  btns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var view = this.getAttribute('data-view');
      btns.forEach(function(b) { b.classList.remove('active'); b.setAttribute('aria-pressed', 'false'); });
      this.classList.add('active'); this.setAttribute('aria-pressed', 'true');
      if (view === 'list') container.classList.add('svc-list-view');
      else container.classList.remove('svc-list-view');
    });
  });
})();
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>

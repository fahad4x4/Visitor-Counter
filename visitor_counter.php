<?php
/*
Plugin Name: Visitor Counter Pro
Description: Track and display visitor statistics with pure SVG charts and AJAX live refresh — no external dependencies.
Version: 2.0
Author: Fahad4x4
Author URI: https://github.com/fahad4x4
*/

if (!defined('IN_GS')) { die('you cannot load this page directly.'); }

// ────────────────────────────────────────────────────────────────────────────
// Constants
// ────────────────────────────────────────────────────────────────────────────
define('VCP_ID',      basename(__FILE__, '.php'));
define('VCP_VERSION', '2.0');
define('VCP_DIR',     GSDATAOTHERPATH . 'visitor_counter/');
define('VCP_TOTAL',   VCP_DIR . 'total.json');
define('VCP_META',    VCP_DIR . 'meta.json');

// ────────────────────────────────────────────────────────────────────────────
// Safe Output Helpers
// ────────────────────────────────────────────────────────────────────────────
function vcp_e($val) {
    echo htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}
function vcp_int($val) {
    return max(0, (int)$val);
}

// ────────────────────────────────────────────────────────────────────────────
// Nonce (CSRF Protection)
// ────────────────────────────────────────────────────────────────────────────
function vcp_create_nonce() {
    if (session_status() === PHP_SESSION_NONE) @session_start();
    if (empty($_SESSION['vcp_nonce'])) {
        $_SESSION['vcp_nonce'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['vcp_nonce'];
}
function vcp_verify_nonce($token) {
    if (session_status() === PHP_SESSION_NONE) @session_start();
    if (empty($_SESSION['vcp_nonce'])) return false;
    $valid = hash_equals($_SESSION['vcp_nonce'], (string)$token);
    if ($valid) $_SESSION['vcp_nonce'] = bin2hex(random_bytes(16));
    return $valid;
}

// ────────────────────────────────────────────────────────────────────────────
// Storage Helpers
// ────────────────────────────────────────────────────────────────────────────
function vcp_ensure_dir() {
    if (!file_exists(VCP_DIR)) mkdir(VCP_DIR, 0755, true);
}

// Atomic read-modify-write with exclusive lock to prevent race conditions
function vcp_increment_file($path, $key, $amount = 1) {
    vcp_ensure_dir();
    $fp = fopen($path, 'c+');
    if (!$fp) return false;
    if (flock($fp, LOCK_EX)) {
        $size = filesize($path);
        $raw  = $size > 0 ? fread($fp, $size) : '';
        $data = $raw ? json_decode($raw, true) : array();
        if (!is_array($data)) $data = array();
        $data[$key] = vcp_int($data[$key] ?? 0) + $amount;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return true;
}

function vcp_read_json($path, $default = array()) {
    if (!file_exists($path)) return $default;
    $raw  = file_get_contents($path);
    $data = $raw ? json_decode($raw, true) : null;
    return is_array($data) ? $data : $default;
}

function vcp_write_json($path, array $data) {
    vcp_ensure_dir();
    file_put_contents($path, json_encode($data), LOCK_EX);
}

function vcp_day_file($offset = 0) {
    return VCP_DIR . date('Y-m-d', strtotime("-{$offset} days")) . '.json';
}

// ────────────────────────────────────────────────────────────────────────────
// Tracking Logic
// ────────────────────────────────────────────────────────────────────────────
function vcp_track() {
    if (php_sapi_name() === 'cli') return;

    // Skip common bots and crawlers
    $ua   = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $bots = array('bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'facebookexternalhit');
    foreach ($bots as $bot) {
        if (strpos($ua, $bot) !== false) return;
    }

    // One unique visit per 24 hours per browser via HttpOnly cookie
    if (!isset($_COOKIE['vcp_visit'])) {
        setcookie('vcp_visit', '1', time() + 86400, '/', '', false, true);

        vcp_increment_file(vcp_day_file(0), 'today', 1);
        vcp_increment_file(VCP_TOTAL,       'total', 1);

        // Track hourly distribution
        $hour_file = VCP_DIR . date('Y-m-d') . '_hours.json';
        vcp_increment_file($hour_file, (string)(int)date('G'), 1);
    }
}

// ────────────────────────────────────────────────────────────────────────────
// Statistics
// ────────────────────────────────────────────────────────────────────────────
function vcp_get_stats() {
    $total_data = vcp_read_json(VCP_TOTAL, array('total' => 0));
    $today      = vcp_int(vcp_read_json(vcp_day_file(0), array('today' => 0))['today'] ?? 0);
    $yesterday  = vcp_int(vcp_read_json(vcp_day_file(1), array('today' => 0))['today'] ?? 0);

    $weekly  = 0;
    $monthly = 0;
    $daily_chart = array();

    for ($i = 29; $i >= 0; $i--) {
        $data  = vcp_read_json(vcp_day_file($i), array('today' => 0));
        $count = vcp_int($data['today'] ?? 0);
        $daily_chart[] = array(
            'label' => date('M j', strtotime("-{$i} days")),
            'count' => $count,
        );
        $monthly += $count;
        if ($i < 7) $weekly += $count;
    }

    // Hourly breakdown for today
    $hour_data = vcp_read_json(VCP_DIR . date('Y-m-d') . '_hours.json', array());
    $hourly = array();
    for ($h = 0; $h < 24; $h++) {
        $hourly[] = vcp_int($hour_data[(string)$h] ?? 0);
    }

    return array(
        'today'       => $today,
        'yesterday'   => $yesterday,
        'weekly'      => $weekly,
        'monthly'     => $monthly,
        'total'       => vcp_int($total_data['total'] ?? 0),
        'daily_chart' => $daily_chart,
        'hourly'      => $hourly,
    );
}

// ────────────────────────────────────────────────────────────────────────────
// Register Plugin
// ────────────────────────────────────────────────────────────────────────────
register_plugin(
    VCP_ID,
    'Visitor Counter Pro 📊',
    VCP_VERSION,
    'Fahad4x4',
    'https://github.com/fahad4x4',
    'Visitor statistics with SVG charts and live refresh — v' . VCP_VERSION,
    'settings',
    'vcp_admin_page'
);

add_action('theme-footer',     'vcp_frontend_output');
add_action('settings-sidebar', 'createSideMenu', array(VCP_ID, '📊 Visitor Counter'));

// ────────────────────────────────────────────────────────────────────────────
// AJAX Endpoint
// ────────────────────────────────────────────────────────────────────────────
if (isset($_GET['vcp_ajax']) && $_GET['vcp_ajax'] === '1') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    $s = vcp_get_stats();
    echo json_encode(array('today' => $s['today'], 'total' => $s['total']));
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// Frontend Widget
// ────────────────────────────────────────────────────────────────────────────
function vcp_frontend_output() {
    if (!function_exists('is_frontend') || !is_frontend()) return;
    vcp_track();

    $opts = vcp_read_json(VCP_META, array('show_widget' => '1'));
    if (($opts['show_widget'] ?? '1') === '0') return;

    $s = vcp_get_stats();
    ?>
<style>
#vcp-widget{display:inline-flex;align-items:center;gap:14px;font-family:'Segoe UI',sans-serif;font-size:13px;color:#ccc;padding:6px 14px;background:rgba(0,0,0,.35);border-radius:20px;}
#vcp-widget .vcp-item{display:flex;align-items:center;gap:5px;}
#vcp-widget .vcp-dot{width:7px;height:7px;border-radius:50%;background:#27ae60;animation:vcp-pulse 2s infinite;}
@keyframes vcp-pulse{0%,100%{opacity:1}50%{opacity:.3}}
#vcp-widget strong{color:#fff;}
</style>
<div id="vcp-widget" title="Visitor Statistics">
    <span class="vcp-item"><span class="vcp-dot"></span>Today: <strong id="vcp-today"><?php echo vcp_int($s['today']); ?></strong></span>
    <span class="vcp-item">Total: <strong id="vcp-total"><?php echo vcp_int($s['total']); ?></strong></span>
</div>
<script>
(function(){
    var url = <?php echo json_encode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?') . '?vcp_ajax=1'); ?>;
    setInterval(function(){
        fetch(url).then(function(r){return r.json();}).then(function(d){
            document.getElementById('vcp-today').textContent = d.today;
            document.getElementById('vcp-total').textContent = d.total;
        }).catch(function(){});
    }, 60000);
})();
</script>
    <?php
}

// ────────────────────────────────────────────────────────────────────────────
// SVG Chart Renderers — zero dependencies
// ────────────────────────────────────────────────────────────────────────────

/**
 * Render a pure-SVG bar chart.
 *
 * @param array  $data    Array of ['label'=>string, 'count'=>int]
 * @param string $color   Bar fill color
 * @param int    $w       SVG width
 * @param int    $h       SVG height
 */
function vcp_svg_bar(array $data, $color = '#2980b9', $w = 660, $h = 200) {
    $pad_left = 36; $pad_right = 8; $pad_top = 12; $pad_bottom = 40;
    $chart_w  = $w - $pad_left - $pad_right;
    $chart_h  = $h - $pad_top  - $pad_bottom;

    $counts = array_column($data, 'count');
    $max    = max(array_merge($counts, array(1))); // avoid division by zero
    $n      = count($data);
    $gap    = 3;
    $bar_w  = max(2, floor(($chart_w - ($n - 1) * $gap) / $n));

    // Y-axis grid lines (4 lines)
    $grid_steps = 4;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" style="width:100%;height:100%;">';
    $svg .= '<rect width="' . $w . '" height="' . $h . '" fill="transparent"/>';

    // Grid lines + Y labels
    for ($i = 0; $i <= $grid_steps; $i++) {
        $y   = $pad_top + $chart_h - ($i / $grid_steps) * $chart_h;
        $val = round(($i / $grid_steps) * $max);
        $svg .= '<line x1="' . $pad_left . '" y1="' . $y . '" x2="' . ($w - $pad_right) . '" y2="' . $y . '" stroke="#e8e8e8" stroke-width="1"/>';
        $svg .= '<text x="' . ($pad_left - 4) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="9" fill="#aaa">' . $val . '</text>';
    }

    // Bars + X labels
    for ($i = 0; $i < $n; $i++) {
        $count  = (int)$data[$i]['count'];
        $bar_h  = $count > 0 ? max(2, ($count / $max) * $chart_h) : 0;
        $x      = $pad_left + $i * ($bar_w + $gap);
        $y      = $pad_top  + $chart_h - $bar_h;

        // Bar with hover tooltip via <title>
        $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $bar_w . '" height="' . $bar_h . '"'
              . ' fill="' . htmlspecialchars($color, ENT_QUOTES) . '" rx="2"'
              . ' style="transition:opacity .15s" onmouseover="this.style.opacity=\'.7\'" onmouseout="this.style.opacity=\'1\'">'
              . '<title>' . htmlspecialchars($data[$i]['label'], ENT_QUOTES) . ': ' . $count . '</title>'
              . '</rect>';

        // X label — show every 5th to avoid clutter
        if ($n <= 10 || $i % 5 === 0 || $i === $n - 1) {
            $lx = $x + $bar_w / 2;
            $ly = $pad_top + $chart_h + 14;
            $svg .= '<text x="' . $lx . '" y="' . $ly . '" text-anchor="middle" font-size="9" fill="#aaa">'
                  . htmlspecialchars($data[$i]['label'], ENT_QUOTES) . '</text>';
        }
    }

    $svg .= '</svg>';
    return $svg;
}

/**
 * Render a pure-SVG line chart for hourly data.
 *
 * @param array  $data   24-element array of integers
 * @param string $color  Line/fill color
 * @param int    $w      SVG width
 * @param int    $h      SVG height
 */
function vcp_svg_line(array $data, $color = '#27ae60', $w = 660, $h = 200) {
    $pad_left = 36; $pad_right = 8; $pad_top = 12; $pad_bottom = 32;
    $chart_w  = $w - $pad_left - $pad_right;
    $chart_h  = $h - $pad_top  - $pad_bottom;
    $n        = count($data);
    $max      = max(array_merge($data, array(1)));

    // Build coordinate points
    $points = array();
    for ($i = 0; $i < $n; $i++) {
        $x = $pad_left + ($i / ($n - 1)) * $chart_w;
        $y = $pad_top  + $chart_h - ($data[$i] / $max) * $chart_h;
        $points[] = array('x' => $x, 'y' => $y, 'v' => $data[$i]);
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" style="width:100%;height:100%;">';
    $svg .= '<rect width="' . $w . '" height="' . $h . '" fill="transparent"/>';

    // Grid lines
    for ($i = 0; $i <= 4; $i++) {
        $y   = $pad_top + $chart_h - ($i / 4) * $chart_h;
        $val = round(($i / 4) * $max);
        $svg .= '<line x1="' . $pad_left . '" y1="' . $y . '" x2="' . ($w - $pad_right) . '" y2="' . $y . '" stroke="#e8e8e8" stroke-width="1"/>';
        $svg .= '<text x="' . ($pad_left - 4) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="9" fill="#aaa">' . $val . '</text>';
    }

    // Filled area path (closed polygon)
    $poly = $points[0]['x'] . ',' . ($pad_top + $chart_h);
    foreach ($points as $p) $poly .= ' ' . $p['x'] . ',' . $p['y'];
    $poly .= ' ' . $points[$n-1]['x'] . ',' . ($pad_top + $chart_h);
    $fill_color = $color; // re-used below
    $svg .= '<polygon points="' . $poly . '" fill="' . htmlspecialchars($fill_color, ENT_QUOTES) . '" fill-opacity=".15"/>';

    // Line path using smooth bezier (catmull-rom approximation)
    $path = 'M ' . $points[0]['x'] . ' ' . $points[0]['y'];
    for ($i = 1; $i < $n; $i++) {
        $cp1x = $points[$i-1]['x'] + ($points[$i]['x'] - $points[$i-1]['x']) * 0.4;
        $cp1y = $points[$i-1]['y'];
        $cp2x = $points[$i]['x']   - ($points[$i]['x'] - $points[$i-1]['x']) * 0.4;
        $cp2y = $points[$i]['y'];
        $path .= ' C ' . $cp1x . ' ' . $cp1y . ', ' . $cp2x . ' ' . $cp2y . ', ' . $points[$i]['x'] . ' ' . $points[$i]['y'];
    }
    $svg .= '<path d="' . $path . '" fill="none" stroke="' . htmlspecialchars($color, ENT_QUOTES) . '" stroke-width="2"/>';

    // Data points with tooltip
    foreach ($points as $i => $p) {
        $label = $i === 0 ? '12am' : ($i < 12 ? $i . 'am' : ($i === 12 ? '12pm' : ($i - 12) . 'pm'));
        $svg  .= '<circle cx="' . $p['x'] . '" cy="' . $p['y'] . '" r="3" fill="' . htmlspecialchars($color, ENT_QUOTES) . '">'
               . '<title>' . $label . ': ' . $p['v'] . '</title>'
               . '</circle>';
        // X label every 3 hours
        if ($i % 3 === 0) {
            $svg .= '<text x="' . $p['x'] . '" y="' . ($pad_top + $chart_h + 14) . '" text-anchor="middle" font-size="9" fill="#aaa">' . $label . '</text>';
        }
    }

    $svg .= '</svg>';
    return $svg;
}

// ────────────────────────────────────────────────────────────────────────────
// Admin Page
// ────────────────────────────────────────────────────────────────────────────
function vcp_admin_page() {
    $notice = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST['vcp_nonce']) || !vcp_verify_nonce($_POST['vcp_nonce'])) {
            $notice = '<div class="vcp-error">❌ Security check failed. Please try again.</div>';
        } elseif (isset($_POST['vcp_reset'])) {
            $files = glob(VCP_DIR . '*.json');
            if ($files) foreach ($files as $f) unlink($f);
            vcp_write_json(VCP_TOTAL, array('total' => 0));
            $notice = '<div class="vcp-success">✅ All statistics have been reset.</div>';
        } elseif (isset($_POST['vcp_save_settings'])) {
            $show = isset($_POST['show_widget']) ? '1' : '0';
            vcp_write_json(VCP_META, array('show_widget' => $show));
            $notice = '<div class="vcp-success">✅ Settings saved.</div>';
        }
    }

    $s    = vcp_get_stats();
    $opts = vcp_read_json(VCP_META, array('show_widget' => '1'));

    // Trend vs yesterday
    $trend = ''; $trend_color = '#888';
    if ($s['yesterday'] > 0) {
        $pct = round((($s['today'] - $s['yesterday']) / $s['yesterday']) * 100);
        if      ($pct > 0)  { $trend = "▲ {$pct}%"; $trend_color = '#27ae60'; }
        elseif  ($pct < 0)  { $trend = '▼ ' . abs($pct) . '%'; $trend_color = '#e74c3c'; }
        else                { $trend = '→ 0%'; }
    }

    // Pre-render SVG charts on the server — zero JS needed for display
    $bar_svg  = vcp_svg_bar($s['daily_chart'], '#2980b9', 660, 200);
    $line_svg = vcp_svg_line($s['hourly'],     '#27ae60', 660, 200);
    ?>
<style>
.vcp-wrap       { max-width:920px; font-family:'Segoe UI',sans-serif; color:#222; }
.vcp-card       { background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:22px; margin-bottom:18px; box-shadow:0 2px 6px rgba(0,0,0,.06); }
.vcp-card h3    { margin:0 0 16px; font-size:15px; color:#2c3e50; border-bottom:2px solid #3498db; padding-bottom:8px; }
.vcp-grid4      { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:18px; }
.vcp-grid2      { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
/* Stat cards */
.vcp-stat       { border-radius:10px; padding:20px; color:#fff; text-align:center; }
.vcp-stat .num  { font-size:38px; font-weight:800; line-height:1; margin-bottom:6px; }
.vcp-stat .lbl  { font-size:11px; text-transform:uppercase; letter-spacing:.6px; opacity:.75; }
.vcp-stat .trnd { font-size:12px; margin-top:6px; font-weight:600; }
.vcp-blue       { background:linear-gradient(135deg,#2980b9,#1a5276); }
.vcp-green      { background:linear-gradient(135deg,#27ae60,#1e8449); }
.vcp-purple     { background:linear-gradient(135deg,#8e44ad,#6c3483); }
.vcp-orange     { background:linear-gradient(135deg,#e67e22,#ca6f1e); }
/* Chart wrapper */
.vcp-chart      { width:100%; height:200px; overflow:hidden; }
/* Buttons */
.vcp-btn        { display:inline-block; padding:10px 20px; background:linear-gradient(135deg,#2980b9,#1a5276); color:#fff; border:none; border-radius:6px; font-size:14px; font-weight:700; cursor:pointer; transition:opacity .2s; line-height:1.4; }
.vcp-btn:hover  { opacity:.85; }
.vcp-btn-danger { background:linear-gradient(135deg,#e74c3c,#c0392b); }
.vcp-btn-green  { background:linear-gradient(135deg,#27ae60,#1e8449); }
/* Notices */
.vcp-success    { background:#d4edda; color:#155724; padding:14px 18px; border-radius:8px; border:1px solid #c3e6cb; margin-bottom:16px; font-weight:600; }
.vcp-error      { background:#f8d7da; color:#721c24; padding:14px 18px; border-radius:8px; border:1px solid #f5c6cb; margin-bottom:16px; font-weight:600; }
/* Toggle */
.vcp-toggle     { display:flex; align-items:center; gap:10px; }
.vcp-toggle input { display:none; }
.vcp-toggle .vcp-sl { width:44px; height:24px; background:#ccc; border-radius:12px; cursor:pointer; position:relative; transition:background .3s; flex-shrink:0; }
.vcp-toggle .vcp-sl::after { content:''; position:absolute; width:18px; height:18px; background:#fff; border-radius:50%; top:3px; left:3px; transition:left .3s; box-shadow:0 1px 3px rgba(0,0,0,.3); }
.vcp-toggle input:checked + .vcp-sl { background:#27ae60; }
.vcp-toggle input:checked + .vcp-sl::after { left:23px; }
</style>

<div class="vcp-wrap">
    <h2 style="margin-top:0;font-size:20px;">
        📊 Visitor Counter Pro
        <small style="font-weight:400;color:#888;">v<?php echo VCP_VERSION; ?></small>
        <span style="float:right;font-size:12px;color:#aaa;font-weight:400;">Updated: <?php echo date('Y-m-d H:i'); ?></span>
    </h2>

    <?php echo $notice; ?>

    <!-- Stat Cards -->
    <div class="vcp-grid4">
        <div class="vcp-stat vcp-blue">
            <div class="num" id="adm-today"><?php echo vcp_int($s['today']); ?></div>
            <div class="lbl">Today</div>
            <?php if ($trend): ?>
            <div class="trnd" style="color:<?php vcp_e($trend_color); ?>;"><?php vcp_e($trend); ?> vs yesterday</div>
            <?php endif; ?>
        </div>
        <div class="vcp-stat vcp-green">
            <div class="num"><?php echo vcp_int($s['weekly']); ?></div>
            <div class="lbl">This Week</div>
        </div>
        <div class="vcp-stat vcp-purple">
            <div class="num"><?php echo vcp_int($s['monthly']); ?></div>
            <div class="lbl">This Month</div>
        </div>
        <div class="vcp-stat vcp-orange">
            <div class="num" id="adm-total"><?php echo vcp_int($s['total']); ?></div>
            <div class="lbl">All Time</div>
        </div>
    </div>

    <!-- SVG Charts (server-rendered, no JS required) -->
    <div class="vcp-grid2">
        <div class="vcp-card">
            <h3>📈 Daily Visitors — Last 30 Days</h3>
            <div class="vcp-chart"><?php echo $bar_svg; ?></div>
        </div>
        <div class="vcp-card">
            <h3>🕐 Hourly Distribution — Today</h3>
            <div class="vcp-chart"><?php echo $line_svg; ?></div>
        </div>
    </div>

    <!-- Settings -->
    <div class="vcp-card">
        <h3>⚙️ Settings</h3>
        <form method="post" action="load.php?id=<?php echo VCP_ID; ?>">
            <input type="hidden" name="vcp_nonce" value="<?php echo vcp_create_nonce(); ?>">
            <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:8px;">
                        Show Widget on Frontend
                    </label>
                    <label class="vcp-toggle">
                        <input type="checkbox" name="show_widget" id="vcp-sw" value="1"
                            <?php echo (($opts['show_widget'] ?? '1') !== '0') ? 'checked' : ''; ?>>
                        <span class="vcp-sl"></span>
                        <span id="vcp-sw-lbl" style="font-size:13px;">
                            <?php echo (($opts['show_widget'] ?? '1') !== '0') ? 'Visible' : 'Hidden'; ?>
                        </span>
                    </label>
                </div>
                <button type="submit" name="vcp_save_settings" class="vcp-btn vcp-btn-green" style="margin-top:20px;">
                    💾 Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Danger Zone -->
    <div class="vcp-card" style="border-color:#e74c3c;">
        <h3 style="border-color:#e74c3c;color:#c0392b;">⚠️ Danger Zone</h3>
        <p style="color:#666;font-size:14px;margin-top:0;">
            Permanently deletes all visitor records. This action cannot be undone.
        </p>
        <form method="post" action="load.php?id=<?php echo VCP_ID; ?>"
              onsubmit="return confirm('Are you sure? All visitor data will be permanently deleted.')">
            <input type="hidden" name="vcp_nonce" value="<?php echo vcp_create_nonce(); ?>">
            <button type="submit" name="vcp_reset" class="vcp-btn vcp-btn-danger">🗑️ Reset All Statistics</button>
        </form>
    </div>
</div>

<script>
// Toggle widget label
document.getElementById('vcp-sw').addEventListener('change', function() {
    document.getElementById('vcp-sw-lbl').textContent = this.checked ? 'Visible' : 'Hidden';
});

// Live refresh admin stat cards every 60s (today + total only)
(function(){
    var url = window.location.href.split('?')[0] + '?vcp_ajax=1';
    setInterval(function(){
        fetch(url).then(function(r){return r.json();}).then(function(d){
            document.getElementById('adm-today').textContent = d.today;
            document.getElementById('adm-total').textContent = d.total;
        }).catch(function(){});
    }, 60000);
})();
</script>
    <?php
}

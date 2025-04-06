<?php
ob_start();

/*
Plugin Name: Visitor Counter Pro
Description: A plugin to track and display visitor statistics.
Version: 1.2
Author: FAHAD ABUFAISAL
Author URI: https://github.com/fahad4x4
*/

$thisfile = basename(__FILE__, ".php");

// ✅ تسجيل الإضافة في تبويب "Theme" بلوحة التحكم
register_plugin(
    $thisfile,
    'Visitor Counter Pro',
    '1.2',
    'FAHAD ABUFAISAL',
    'https://github.com/fahad4x4',
    'Displays visitor statistics with AJAX support',
    'theme',  // ⬅️ يجعل الإضافة تظهر في تبويب "Theme"
    'visitor_counter_settings_page'
);

// ✅ تحميل ملفات اللغة
i18n_merge($thisfile) || i18n_merge($thisfile, 'en_US');

// ✅ تحميل ملفات CSS و JS
define('VISITOR_PLUGIN_PATH', $SITEURL . 'plugins/visitor_counter/');
queue_script('visitor_counter_ajax', VISITOR_PLUGIN_PATH . 'js/ajax.js', '1.0', true);
queue_style('visitor_counter_style', VISITOR_PLUGIN_PATH . 'css/style.css', '1.0');

// ✅ ربط الوظائف بالأحداث
add_action('theme-footer', 'visitor_counter_display');  // تأكد من أن هذه الدالة تم استدعاؤها في المكان المناسب
add_action('theme-sidebar', 'createSideMenu', array($thisfile, 'Visitor Counter Settings'));

// دالة تتبع الزوار
function visitor_counter_track() {
    $folder = GSDATAOTHERPATH . '/visitor_counter/';
    $todayFile = $folder . date('Y-m-d') . '.json';  // ملف الزوار اليومي
    $allTimeFile = $folder . 'total.json';            // ملف الزوار الإجمالي

    // التحقق من وجود المجلد وإنشاؤه إذا لزم الأمر
    if (!file_exists($folder)) {
        mkdir($folder, 0755, true);
        error_log("Folder created at: " . $folder);
    } else {
        error_log("Folder already exists: " . $folder);
    }

    // التحقق من وجود الملفات وتهيئة البيانات
    $todayData = file_exists($todayFile) ? json_decode(file_get_contents($todayFile), true) : ['today' => 0];
    $allData = file_exists($allTimeFile) ? json_decode(file_get_contents($allTimeFile), true) : ['total' => 0];

    // تحقق من الكوكيز
    if (!isset($_COOKIE['visitor_counter'])) {
        setcookie('visitor_counter', 'visited', time() + 86400, "/"); // صالح ليوم واحد
        error_log("Cookie set: visitor_counter = visited");

        // تحديث البيانات
        $todayData['today'] += 1;
        $allData['total'] += 1;

        // كتابة البيانات في الملفات
        $writeSuccessToday = file_put_contents($todayFile, json_encode($todayData));
        $writeSuccessAll = file_put_contents($allTimeFile, json_encode($allData));

        // تحقق من ما إذا كان تم الكتابة بنجاح
        if ($writeSuccessToday && $writeSuccessAll) {
            error_log("Files updated successfully.");
        } else {
            error_log("Failed to update files.");
        }
    } else {
        error_log("Cookie already set: visitor_counter = " . $_COOKIE['visitor_counter']);
    }  // إغلاق if الخاص بالكود الذي يتحقق من الكوكيز
}


// 🔹 جلب الإحصائيات
function visitor_counter_get_stats() {
    $folder = GSDATAOTHERPATH . '/visitor_counter/';
    $allTimeFile = $folder . 'total.json';
    $allData = file_exists($allTimeFile) ? json_decode(file_get_contents($allTimeFile), true) : ['total' => 0];

    $weeklyTotal = 0;
    $monthlyTotal = 0;

    // حساب إجمالي الزوار للأسبوع
    for ($i = 0; $i < 7; $i++) {
        $file = $folder . date('Y-m-d', strtotime("-$i days")) . '.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $weeklyTotal += $data['today'];
        }
    }

    // حساب إجمالي الزوار للشهر
    for ($i = 0; $i < 30; $i++) {
        $file = $folder . date('Y-m-d', strtotime("-$i days")) . '.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $monthlyTotal += $data['today'];
        }
    }

    return [
        'today' => file_exists($folder . date('Y-m-d') . '.json') ? json_decode(file_get_contents($folder . date('Y-m-d') . '.json'), true)['today'] : 0,
        'weekly' => $weeklyTotal,
        'monthly' => $monthlyTotal,
        'total' => $allData['total']
    ];
}

// 🔹 عرض العداد في الفوتر ✅
function visitor_counter_display() {
    visitor_counter_track();
    $stats = visitor_counter_get_stats();
    echo '<div id="visitor-counter">Today: ' . $stats['today'] . ' | Total: ' . $stats['total'] . '</div>';
}

// 🔹 صفحة الإعدادات ✅
function visitor_counter_settings_page() {
    $stats = visitor_counter_get_stats();
    echo '<h3>Visitor Counter Settings</h3>';
    echo '<p>Unique visitor tracking and statistics.</p>';
    echo '<p>Today: <strong id="today-count">' . $stats['today'] . '</strong></p>';
    echo '<p>This Week: <strong id="week-count">' . $stats['weekly'] . '</strong></p>';
    echo '<p>This Month: <strong id="month-count">' . $stats['monthly'] . '</strong></p>';
    echo '<p>Total Visitors: <strong id="total-count">' . $stats['total'] . '</strong></p>';
}

?>

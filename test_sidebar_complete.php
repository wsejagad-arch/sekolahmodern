<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Test Sidebar Complete</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/mycss.css" rel="stylesheet">
    <style>
        body { margin: 0; padding: 0; }
        #wrapper { display: flex; }
        .content-area { flex: 1; padding: 20px; }
        .test-info { background: #f8f9fc; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .status { padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .status.ok { background: #d4edda; color: #155724; }
        .status.fail { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">

<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['hak_akses'] = 1; // Admin
$_SESSION['nama'] = 'Test User';
$conn = null;
include 'sidebar.php';
?>

<div class="content-area">
    <h2>🧪 Test Sidebar Functionality</h2>
    
    <div class="test-info">
        <h4>Status Checks:</h4>
        <div id="status-jquery" class="status">⏳ Checking jQuery...</div>
        <div id="status-bootstrap" class="status">⏳ Checking Bootstrap...</div>
        <div id="status-sidebar" class="status">⏳ Checking Sidebar...</div>
        <div id="status-links" class="status">⏳ Checking Links...</div>
    </div>
    
    <div class="test-info">
        <h4>Actions to Test:</h4>
        <ol>
            <li><strong>Klik "Data Guru"</strong> - Menu harus expand/collapse</li>
            <li><strong>Klik "Lihat Data Guru"</strong> - URL harus berubah ke home.php?page=data-guru</li>
            <li><strong>Klik "Dashboard"</strong> - URL harus berubah ke home.php</li>
            <li><strong>Hover over links</strong> - Cursor harus jadi pointer</li>
        </ol>
    </div>
    
    <div class="test-info">
        <h4>📊 Event Log:</h4>
        <div id="event-log" style="font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; background: white; padding: 10px; border: 1px solid #ddd;"></div>
    </div>
    
    <div class="test-info">
        <h4>🔍 Quick Fixes:</h4>
        <button onclick="forceReloadSidebar()" class="btn btn-primary">Force Reload Sidebar JS</button>
        <button onclick="testAllLinks()" class="btn btn-success">Test All Links</button>
        <button onclick="clearLog()" class="btn btn-secondary">Clear Log</button>
    </div>
</div>

</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<script>
const log = (msg, type = 'info') => {
    const now = new Date();
    const time = now.getHours().toString().padStart(2, '0') + ':' + 
                 now.getMinutes().toString().padStart(2, '0') + ':' + 
                 now.getSeconds().toString().padStart(2, '0');
    const icon = type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️';
    $('#event-log').prepend(`[${time}] ${icon} ${msg}<br>`);
    console.log(msg);
};

const setStatus = (id, text, isOk) => {
    $(`#${id}`).text(text).removeClass('ok fail').addClass(isOk ? 'ok' : 'fail');
};

$(document).ready(function() {
    log('Document ready');
    
    // Check jQuery
    if (typeof $ !== 'undefined') {
        setStatus('status-jquery', `✅ jQuery ${$.fn.jquery} loaded`, true);
        log(`jQuery version: ${$.fn.jquery}`, 'success');
    } else {
        setStatus('status-jquery', '❌ jQuery NOT loaded', false);
        log('jQuery NOT loaded', 'error');
    }
    
    // Check Bootstrap
    if (typeof $.fn.collapse !== 'undefined') {
        setStatus('status-bootstrap', '✅ Bootstrap JS loaded', true);
        log('Bootstrap JS loaded', 'success');
    } else {
        setStatus('status-bootstrap', '❌ Bootstrap JS NOT loaded', false);
        log('Bootstrap JS NOT loaded', 'error');
    }
    
    // Check Sidebar
    const sidebar = $('#accordionSidebar');
    if (sidebar.length > 0) {
        setStatus('status-sidebar', `✅ Sidebar found (${sidebar.find('a').length} links)`, true);
        log(`Sidebar found with ${sidebar.find('a').length} links`, 'success');
    } else {
        setStatus('status-sidebar', '❌ Sidebar NOT found', false);
        log('Sidebar NOT found', 'error');
    }
    
    // Check Links
    const clickableLinks = sidebar.find('a[href]:not([href="#"])').length;
    const collapseLinks = sidebar.find('a[data-toggle="collapse"]').length;
    setStatus('status-links', `✅ ${clickableLinks} clickable, ${collapseLinks} collapse`, true);
    log(`Found ${clickableLinks} clickable links and ${collapseLinks} collapse triggers`);
    
    // Attach event listeners
    sidebar.find('a[data-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('data-target');
        log(`Collapse toggle clicked: ${target}`);
        $(target).collapse('toggle');
    });
    
    sidebar.find('a[href]:not([href="#"])').not('[data-toggle="collapse"]').on('click', function(e) {
        const href = $(this).attr('href');
        log(`Link clicked: ${href}`, 'success');
    });
    
    // Listen to collapse events
    sidebar.find('.collapse').on('show.bs.collapse', function() {
        log(`Opening collapse: ${this.id}`, 'success');
    });
    
    sidebar.find('.collapse').on('hide.bs.collapse', function() {
        log(`Closing collapse: ${this.id}`);
    });
    
    log('✅ All event listeners attached', 'success');
});

function forceReloadSidebar() {
    log('🔄 Force reloading sidebar JavaScript...');
    
    $('#accordionSidebar a[data-toggle="collapse"]').off('click').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('data-target');
        log(`🔄 Manually toggling: ${target}`);
        $(target).collapse('toggle');
    });
    
    log('✅ Sidebar JS reloaded', 'success');
}

function testAllLinks() {
    log('🧪 Testing all links...');
    let passed = 0;
    let failed = 0;
    
    $('#accordionSidebar a').each(function() {
        const href = $(this).attr('href');
        const text = $(this).text().trim();
        
        if (!href) {
            log(`⚠️ Link without href: "${text}"`, 'error');
            failed++;
        } else if (href === '#') {
            // Collapse links are OK with #
            if ($(this).attr('data-toggle') === 'collapse') {
                log(`✅ Collapse link OK: "${text}"`);
                passed++;
            }
        } else {
            log(`✅ Link OK: "${text}" → ${href}`);
            passed++;
        }
    });
    
    log(`📊 Test complete: ${passed} passed, ${failed} failed`, passed > failed ? 'success' : 'error');
}

function clearLog() {
    $('#event-log').empty();
    log('Log cleared');
}
</script>

</body>
</html>

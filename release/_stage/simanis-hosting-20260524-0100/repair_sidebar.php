<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>🔧 Sidebar Repair Tool</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/mycss.css" rel="stylesheet">
    <style>
        body { background: #f8f9fc; }
        #wrapper { display: flex; min-height: 100vh; }
        .repair-panel { flex: 1; padding: 20px; }
        .status-box { background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status-box h4 { margin: 0 0 10px 0; color: #333; }
        .status-item { padding: 8px; margin: 5px 0; border-radius: 4px; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-fail { background: #f8d7da; color: #721c24; }
        .log-box { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto; }
        .btn-repair { background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn-repair:hover { background: #c0392b; }
        .btn-test { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn-test:hover { background: #2980b9; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    
    <?php
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $_SESSION['hak_akses'] = 1;
    $_SESSION['nama'] = 'Admin Test';
    $conn = null;
    include 'sidebar.php';
    ?>
    
    <div class="repair-panel">
        <h1>🔧 Sidebar Repair & Diagnostic Tool</h1>
        
        <div class="status-box">
            <h4>📊 System Status</h4>
            <div id="status-jquery" class="status-item">⏳ Checking jQuery...</div>
            <div id="status-bootstrap" class="status-item">⏳ Checking Bootstrap...</div>
            <div id="status-sidebar" class="status-item">⏳ Checking Sidebar...</div>
            <div id="status-links" class="status-item">⏳ Checking Links...</div>
        </div>
        
        <div class="status-box">
            <h4>🛠️ Repair Actions</h4>
            <button class="btn-repair" onclick="forceRepairSidebar()">🔧 Force Repair Sidebar</button>
            <button class="btn-test" onclick="testAllLinks()">✅ Test All Links</button>
            <button class="btn-test" onclick="testCollapses()">📂 Test All Collapses</button>
            <button class="btn-test" onclick="clearConsole()">🗑️ Clear Console</button>
        </div>
        
        <div class="status-box">
            <h4>📝 Live Console Log</h4>
            <div class="log-box" id="console-log">
                <div>Waiting for actions...</div>
            </div>
        </div>
        
        <div class="status-box">
            <h4>📋 Manual Test Checklist</h4>
            <ol>
                <li><strong>Klik "Data Guru"</strong> → Menu harus expand</li>
                <li><strong>Klik "Lihat Data Guru"</strong> → URL harus ke home.php?page=data-guru</li>
                <li><strong>Klik "Data Siswa"</strong> → Menu harus expand</li>
                <li><strong>Klik "Dashboard"</strong> → URL harus ke home.php</li>
                <li><strong>Klik "Monitoring"</strong> → Menu harus expand</li>
            </ol>
        </div>
    </div>
    
</div>

<!-- Load jQuery FIRST -->
<script src="vendor/jquery/jquery.min.js"></script>

<!-- Then Bootstrap -->
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Then other libraries -->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<script>
// Logging function
const logToConsole = (msg, type = 'info') => {
    const timestamp = new Date().toLocaleTimeString();
    const icon = type === 'error' ? '❌' : type === 'success' ? '✅' : type === 'warn' ? '⚠️' : 'ℹ️';
    const color = type === 'error' ? '#e74c3c' : type === 'success' ? '#27ae60' : type === 'warn' ? '#f39c12' : '#3498db';
    
    $('#console-log').prepend(`<div style="color:${color}">[${timestamp}] ${icon} ${msg}</div>`);
    console.log(`[${timestamp}] ${msg}`);
};

// Status update function
const updateStatus = (id, msg, isOk) => {
    $(`#${id}`).text(msg).removeClass('status-ok status-fail').addClass(isOk ? 'status-ok' : 'status-fail');
};

// Main diagnostic
$(document).ready(function() {
    logToConsole('System initializing...', 'info');
    
    // Check jQuery
    if (typeof $ !== 'undefined') {
        updateStatus('status-jquery', `✅ jQuery ${$.fn.jquery} loaded`, true);
        logToConsole(`jQuery ${$.fn.jquery} loaded`, 'success');
    } else {
        updateStatus('status-jquery', '❌ jQuery NOT loaded', false);
        logToConsole('jQuery NOT loaded!', 'error');
        return;
    }
    
    // Check Bootstrap
    if (typeof $.fn.collapse !== 'undefined') {
        updateStatus('status-bootstrap', '✅ Bootstrap JS loaded', true);
        logToConsole('Bootstrap JS loaded', 'success');
    } else {
        updateStatus('status-bootstrap', '❌ Bootstrap JS NOT loaded', false);
        logToConsole('Bootstrap JS NOT loaded!', 'error');
    }
    
    // Check Sidebar
    const $sidebar = $('#accordionSidebar');
    if ($sidebar.length > 0) {
        const linkCount = $sidebar.find('a').length;
        updateStatus('status-sidebar', `✅ Sidebar found (${linkCount} links)`, true);
        logToConsole(`Sidebar found with ${linkCount} links`, 'success');
    } else {
        updateStatus('status-sidebar', '❌ Sidebar NOT found', false);
        logToConsole('Sidebar element NOT found!', 'error');
        return;
    }
    
    // Check Links
    const regularLinks = $sidebar.find('a[href]:not([href="#"])').not('[data-toggle="collapse"]').length;
    const collapseLinks = $sidebar.find('a[data-toggle="collapse"]').length;
    updateStatus('status-links', `✅ ${regularLinks} regular + ${collapseLinks} collapse links`, true);
    logToConsole(`Found ${regularLinks} regular links and ${collapseLinks} collapse triggers`, 'success');
    
    // Auto-apply repair
    setTimeout(forceRepairSidebar, 500);
});

// Force repair function
function forceRepairSidebar() {
    logToConsole('🔧 Starting forced repair...', 'warn');
    
    const $sidebar = $('#accordionSidebar');
    
    // Remove ALL existing event handlers
    $sidebar.find('a').off('click');
    logToConsole('Removed all existing event handlers', 'info');
    
    // Fix collapse links
    const $collapseLinks = $sidebar.find('a[data-toggle="collapse"]');
    $collapseLinks.each(function(index) {
        const $link = $(this);
        const target = $link.attr('data-target');
        
        $link.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            logToConsole(`Collapse clicked: ${target}`, 'info');
            
            // Toggle the target
            $(target).collapse('toggle');
            
            // Update icon
            $link.toggleClass('collapsed');
            
            return false;
        });
    });
    logToConsole(`Fixed ${$collapseLinks.length} collapse links`, 'success');
    
    // Fix regular links
    const $regularLinks = $sidebar.find('a[href]:not([href="#"])').not('[data-toggle="collapse"]');
    $regularLinks.each(function() {
        const $link = $(this);
        const href = $link.attr('href');
        
        $link.on('click', function(e) {
            logToConsole(`Link clicked: ${href}`, 'success');
            // Let default navigation happen
        });
        
        // Ensure proper CSS
        $link.css({
            'pointer-events': 'auto',
            'cursor': 'pointer'
        });
    });
    logToConsole(`Fixed ${$regularLinks.length} regular links`, 'success');
    
    // Listen to collapse events
    $sidebar.find('.collapse').on('show.bs.collapse', function() {
        logToConsole(`📂 Opening: ${this.id}`, 'success');
    });
    
    $sidebar.find('.collapse').on('hide.bs.collapse', function() {
        logToConsole(`📁 Closing: ${this.id}`, 'info');
    });
    
    logToConsole('✅ REPAIR COMPLETE! Try clicking sidebar items now.', 'success');
}

// Test all links
function testAllLinks() {
    logToConsole('🧪 Testing all links...', 'info');
    
    let passed = 0, failed = 0;
    
    $('#accordionSidebar a').each(function() {
        const $link = $(this);
        const href = $link.attr('href');
        const text = $link.text().trim().substring(0, 30);
        const hasToggle = $link.attr('data-toggle') === 'collapse';
        
        if (!href) {
            logToConsole(`❌ No href: "${text}"`, 'error');
            failed++;
        } else if (href === '#' && hasToggle) {
            logToConsole(`✅ Collapse link OK: "${text}"`, 'success');
            passed++;
        } else if (href !== '#') {
            logToConsole(`✅ Regular link OK: "${text}" → ${href}`, 'success');
            passed++;
        } else {
            logToConsole(`⚠️ Link with #: "${text}"`, 'warn');
        }
    });
    
    logToConsole(`📊 Test complete: ${passed} passed, ${failed} failed`, passed > 0 ? 'success' : 'error');
}

// Test collapses
function testCollapses() {
    logToConsole('🧪 Testing collapse functionality...', 'info');
    
    const collapses = ['#collapseTwo', '#collapseSiswa', '#collapseMonitoring', '#collapsePages'];
    let index = 0;
    
    const testNext = () => {
        if (index >= collapses.length) {
            logToConsole('✅ All collapse tests complete!', 'success');
            return;
        }
        
        const target = collapses[index];
        logToConsole(`Testing ${target}...`, 'info');
        
        $(target).collapse('show');
        
        setTimeout(() => {
            if ($(target).hasClass('show')) {
                logToConsole(`✅ ${target} opened successfully`, 'success');
            } else {
                logToConsole(`❌ ${target} failed to open`, 'error');
            }
            
            $(target).collapse('hide');
            
            index++;
            setTimeout(testNext, 800);
        }, 600);
    };
    
    testNext();
}

// Clear console
function clearConsole() {
    $('#console-log').html('<div>Console cleared.</div>');
    logToConsole('Console cleared', 'info');
}
</script>

</body>
</html>

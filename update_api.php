<?php
$file = 'c:\xampp\htdocs\jurnal\api\jurnal_7kih_save.php';
$content = file_get_contents($file);

// Replace kih_score logic
$old_kih_score = "function kih_score(string \$date, string \$nowTime, string \$start, string \$end): array
{
    \$now = strtotime(\$date . ' ' . \$nowTime);
    \$s = strtotime(\$date . ' ' . \$start);
    \$e = strtotime(\$date . ' ' . \$end);
    if (\$now === false || \$s === false || \$e === false) {
        return ['di_luar_waktu', 55];
    }
    if (\$now >= \$s && \$now <= \$e) {
        \$span = max(1, \$e - \$s);
        \$progress = (\$now - \$s) / \$span;
        return \$progress <= 0.35 ? ['sangat_tepat', 100] : ['tepat', 90];
    }
    if (\$now > \$e && \$now <= strtotime('+2 hours', \$e)) {
        return ['terlambat', 70];
    }
    return ['di_luar_waktu', 45];
}";

// We want to reject if completely out of time
$new_kih_score = "function kih_score(string \$date, string \$nowTime, string \$start, string \$end): array
{
    \$now = strtotime(\$date . ' ' . \$nowTime);
    \$s = strtotime(\$date . ' ' . \$start);
    \$e = strtotime(\$date . ' ' . \$end);
    
    // Handle cross midnight (e.g. Isya 19:00 - 03:59)
    if (\$e < \$s) {
        // If current time is early morning (e.g. 02:00), we consider the start time was yesterday
        if (date('H', \$now) < 12) {
            \$s = strtotime('-1 day', \$s);
        } else {
            // Current time is evening, end time is tomorrow
            \$e = strtotime('+1 day', \$e);
        }
    }
    
    if (\$now === false || \$s === false || \$e === false) {
        return ['ditolak', 0];
    }
    
    if (\$now >= \$s && \$now <= \$e) {
        \$span = max(1, \$e - \$s);
        \$progress = (\$now - \$s) / \$span;
        return \$progress <= 0.35 ? ['sangat_tepat', 100] : ['tepat', 90];
    }
    
    // If they submit within 15 minutes late, maybe tolerate a bit, but user asked to be strict.
    // The user said: "Jika anak ini nanti absennya di luar rentang waktu yang agama tentukan atau melebihi batas waktu salat tertentu, berarti tidak sah atau ditolak."
    // So if not in range, reject it completely.
    return ['ditolak', 0];
}";

$content = str_replace($old_kih_score, $new_kih_score, $content);

// In the save logic:
$old_score_call = "[\$timeliness, \$score] = kih_score(\$today, \$nowTime, \$window['start'], \$window['end']);";
$new_score_call = "[\$timeliness, \$score] = kih_score(\$today, \$nowTime, \$window['start'], \$window['end']);
if (\$timeliness === 'ditolak' && \$habit === 'beribadah') {
    if (\$photo['absolute']) @unlink(\$photo['absolute']);
    kih_json([
        'success' => false, 
        'message' => \"Waktu absen ditolak! Pengisian di luar rentang waktu sah ({\$window['start']} - {\$window['end']}).\"
    ]);
}";

$content = str_replace($old_score_call, $new_score_call, $content);

file_put_contents($file, $content);
echo "Updated jurnal_7kih_save.php successfully.";
?>

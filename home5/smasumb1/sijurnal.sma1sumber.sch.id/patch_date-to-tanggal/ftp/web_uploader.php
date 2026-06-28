<?php
// web_uploader.php
// Upload ZIP to server and optionally trigger extraction via extract_and_deploy.php
// Usage: https://YOUR_DOMAIN/patch_date-to-tanggal/ftp/web_uploader.php?token=TOKEN

$DEPLOY_TOKEN = 'r3nTv9KJx4'; // change if desired
$ZIP_NAME = 'patch_date-to-tanggal.zip';
$EXTRACT_SCRIPT = 'extract_and_deploy.php';

header('Content-Type: text/html; charset=utf-8');

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;
if ($token !== $DEPLOY_TOKEN){
    http_response_code(403);
    echo "<h2>Forbidden</h2>";
    echo "<p>Missing or invalid token.</p>";
    echo "<p>Provide ?token={$DEPLOY_TOKEN}</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!isset($_FILES['file'])){
        echo "<p>No file uploaded.</p>";
    } else {
        $u = $_FILES['file'];
        if ($u['error'] !== UPLOAD_ERR_OK){
            echo "<p>Upload error: " . h($u['error']) . "</p>";
        } else {
            $tmp = $u['tmp_name'];
            $dst = __DIR__ . DIRECTORY_SEPARATOR . $ZIP_NAME;
            if (!move_uploaded_file($tmp, $dst)){
                echo "<p>Failed to move uploaded file.</p>";
            } else {
                echo "<p>Uploaded to: " . h($dst) . "</p>";
                if (isset($_POST['extract']) && $_POST['extract']=='1'){
                    // trigger extraction script (local request)
                    $extractPath = __DIR__ . DIRECTORY_SEPARATOR . $EXTRACT_SCRIPT;
                    if (file_exists($extractPath)){
                        echo "<pre>Extraction output:\n";
                        // use include to run script and capture output
                        ob_start();
                        // simulate GET token for included script
                        $_GET['token'] = $DEPLOY_TOKEN;
                        include $extractPath;
                        $out = ob_get_clean();
                        echo h($out);
                        echo "</pre>";
                    } else {
                        echo "<p>Extraction script not found: " . h($extractPath) . "</p>";
                    }
                }
            }
        }
    }
}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Uploader Patch</title>
</head>
<body>
<h2>Uploader Patch: upload `patch_date-to-tanggal.zip`</h2>
<form method="post" enctype="multipart/form-data">
  <p><input type="file" name="file" accept=".zip" required></p>
  <p><label><input type="checkbox" name="extract" value="1"> Ekstrak setelah upload</label></p>
  <input type="hidden" name="token" value="<?php echo h($DEPLOY_TOKEN); ?>">
  <p><button type="submit">Upload</button></p>
</form>
<p>Setelah selesai, hapus file ini dan `extract_and_deploy.php` dari server.</p>
</body>
</html>
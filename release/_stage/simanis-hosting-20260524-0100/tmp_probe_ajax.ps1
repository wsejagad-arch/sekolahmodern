$ProgressPreference = 'SilentlyContinue'
$base = 'http://103.131.217.1:8239'
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$page = Invoke-WebRequest -Uri ($base + '/login') -WebSession $session -UseBasicParsing -TimeoutSec 30
$csrf = [regex]::Match($page.Content, 'name=[''\"]csrf_test_name[''\"][^>]*value=[''\"]([^''\"]+)[''\"]').Groups[1].Value

$passPlain = 'Sman1$umber'
$bytes = [System.Text.Encoding]::UTF8.GetBytes($passPlain)
$sha = [System.Security.Cryptography.SHA512]::Create()
$passHex = ([System.BitConverter]::ToString($sha.ComputeHash($bytes))).Replace('-', '').ToLowerInvariant()

$form = @{
  username = 'administrator'
  pass = $passPlain
  password = $passHex
  semester = '20252'
  sekolahid = 'be22cb1d-1b20-45dd-92a1-061a675e13ef'
  nm_sek = 'SMA NEGERI 1 SUMBER'
  csrf_test_name = $csrf
}

$null = Invoke-WebRequest -Uri ($base + '/login/cekuser') -Method POST -WebSession $session -Body $form -UseBasicParsing -TimeoutSec 30

$pages = @('/data_ekskul', '/data_siswa', '/nilai_rapor')
$regex = [regex]'(url\s*[:=]\s*[''\"]([^''\"]+)[''\"]|tampil_siswa\s*\([^\)]*\)|/([a-z0-9_\-/]+))'

foreach ($p in $pages) {
  $r = Invoke-WebRequest -Uri ($base + $p) -WebSession $session -UseBasicParsing -TimeoutSec 30
  Write-Output ('===PAGE=' + $p + ' STATUS=' + [int]$r.StatusCode)
  $html = $r.Content
  $m = $regex.Matches($html)
  $hits = @()
  foreach ($x in $m) {
    $v = ($x.Value -replace '\s+', ' ').Trim()
    if ($v -match 'ekskul|ekstra|siswa|rapor|nilai|rombel|tampil') {
      $hits += $v
    }
  }
  $hits = $hits | Select-Object -Unique | Select-Object -First 120
  foreach ($h in $hits) { Write-Output $h }
}

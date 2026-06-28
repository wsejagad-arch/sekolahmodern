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
  username='administrator'
  pass=$passPlain
  password=$passHex
  semester='20252'
  sekolahid='be22cb1d-1b20-45dd-92a1-061a675e13ef'
  nm_sek='SMA NEGERI 1 SUMBER'
  csrf_test_name=$csrf
}

$null = Invoke-WebRequest -Uri ($base + '/login/cekuser') -Method POST -WebSession $session -Body $form -UseBasicParsing -TimeoutSec 30
$r = Invoke-WebRequest -Uri ($base + '/data_siswa') -WebSession $session -UseBasicParsing -TimeoutSec 30
$html = $r.Content

$srcs = [regex]::Matches($html, '<script[^>]+src=[''\"]([^''\"]+)[''\"]') | ForEach-Object { $_.Groups[1].Value }
$srcs = $srcs | Select-Object -Unique
Write-Output ('SCRIPT_COUNT=' + $srcs.Count)

foreach($s in $srcs){
  $u = if($s.StartsWith('http')){ $s } elseif($s.StartsWith('/')){ $base + $s } else { $base + '/' + $s }
  try{
    $js = Invoke-WebRequest -Uri $u -WebSession $session -UseBasicParsing -TimeoutSec 20
    $txt = $js.Content
    if($txt -match 'dataTable|DataTable|ajax|serverSide|tampil_siswa|ekskul|siswa|nilai_rapor'){
      Write-Output ('===JS ' + $u)
      $lines = $txt -split "`n"
      $hits = $lines | Where-Object { $_ -match 'ajax|serverSide|tampil_siswa|ekskul|siswa|nilai_rapor' } | Select-Object -First 30
      foreach($ln in $hits){
        $clean = ($ln -replace '\s+',' ').Trim()
        if($clean -ne ''){ Write-Output $clean }
      }
    }
  } catch {
    continue
  }
}

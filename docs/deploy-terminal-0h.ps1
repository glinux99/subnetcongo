$ErrorActionPreference = 'Stop'
$repo = Split-Path -Parent $PSScriptRoot
$sshKey = Join-Path $env:USERPROFILE '.ssh\id_ed25519'
$remote = 'subnetcongo_dan_subnet@subnetcongo.net'
$log = Join-Path $PSScriptRoot 'deploy-terminal-0h.log'
Start-Transcript -Path $log -Append

$ts = Get-Date -Format 'yyyyMMdd-HHmmss'
$bk = "${ts}-terminal-fix"
Write-Output "== $(Get-Date) BACKUP $bk (php + build mirrors) =="
& ssh -i $sshKey -o BatchMode=yes -o StrictHostKeyChecking=accept-new -p 22 $remote "mkdir -p web/elearning/backups/$bk && cp web/elearning/app/Http/Controllers/System/TerminalController.php web/elearning/backups/$bk/TerminalController.php.bak && tar -czf web/elearning/backups/$bk/build-backup.tgz -C web build elearning/public/build && echo BACKUP_OK"

Write-Output "== UPLOAD TerminalController.php =="
& scp -i $sshKey -P 22 (Join-Path $repo 'app\Http\Controllers\System\TerminalController.php') "${remote}:web/elearning/app/Http/Controllers/System/TerminalController.php"

$localHash = (Get-FileHash (Join-Path $repo 'app\Http\Controllers\System\TerminalController.php') -Algorithm MD5).Hash.ToLower()
$remoteLine = & ssh -i $sshKey -o BatchMode=yes -p 22 $remote 'md5sum web/elearning/app/Http/Controllers/System/TerminalController.php'
$remoteHash = ($remoteLine -split '\s+')[0].Trim()
Write-Output "md5 local=$localHash remote=$remoteHash"
if ($localHash -ne $remoteHash) { throw 'md5 mismatch TerminalController.php' }

Write-Output "== CREATE build tar =="
$tmp = Join-Path $env:TEMP 'deploy-build-midnight.tgz'
Remove-Item $tmp -Force -ErrorAction SilentlyContinue
tar -czf $tmp -C (Join-Path $repo 'public') build
Write-Output "== UPLOAD build =="
& scp -i $sshKey -P 22 $tmp "${remote}:~/deploy-build.tgz"

Write-Output "== EXTRACT mirrors =="
& ssh -i $sshKey -o BatchMode=yes -p 22 $remote 'tar -xzf ~/deploy-build.tgz -C web/ && echo BUILD_WEB_OK; tar -xzf ~/deploy-build.tgz -C web/elearning/public/ && echo BUILD_ELEARNING_OK; md5sum web/build/manifest.json web/elearning/public/build/manifest.json; rm ~/deploy-build.tgz'

$localManifest = (Get-FileHash (Join-Path $repo 'public\build\manifest.json') -Algorithm MD5).Hash.ToLower()
Write-Output "local manifest md5=$localManifest"

Write-Output "== HTTP CHECKS =="
foreach ($p in @('/', '/login')) {
  & ssh -i $sshKey -o BatchMode=yes -p 22 $remote "curl -s -o /dev/null -w '%{http_code} $p' --max-time 20 https://subnetcongo.net$p"
  Write-Output ''
}
Write-Output "== DONE $(Get-Date) =="
Stop-Transcript

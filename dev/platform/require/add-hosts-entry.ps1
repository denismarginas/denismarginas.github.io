param(
    [Parameter(Mandatory = $true)][string]$HostName,
    [string]$Ip = '127.0.0.1'
)

$hosts = Join-Path $env:SystemRoot 'System32\drivers\etc\hosts'
$pattern = '^\s*' + [regex]::Escape($Ip) + '\s+(.*\s)?' + [regex]::Escape($HostName) + '(\s|$)'
if (Select-String -LiteralPath $hosts -Pattern $pattern -Quiet) { exit 0 }

$admin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $admin) {
    try {
        $argList = @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', "`"$PSCommandPath`"", '-HostName', $HostName, '-Ip', $Ip)
        $p = Start-Process powershell -Verb RunAs -Wait -PassThru -WindowStyle Hidden -ArgumentList $argList
        exit $p.ExitCode
    } catch {
        exit 1
    }
}

try {
    $text = [IO.File]::ReadAllText($hosts)
    $line = "$Ip`t$HostName"
    if ($text.Length -gt 0 -and -not $text.EndsWith("`n")) { $line = "`r`n" + $line }
    [IO.File]::AppendAllText($hosts, $line + "`r`n", [Text.Encoding]::ASCII)
    ipconfig /flushdns | Out-Null
    exit 0
} catch {
    exit 1
}

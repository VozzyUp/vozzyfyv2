$ErrorActionPreference = "Stop"

Set-Location (Split-Path $PSScriptRoot -Parent)

New-Item -ItemType Directory -Force -Path ".docker" | Out-Null

$envFile = ".docker\\stack.env"

if (!(Test-Path $envFile)) {
    $userSuffix = -join (((48..57) + (97..122) | Get-Random -Count 8) | ForEach-Object { [char]$_ })
    $dbUser = "getfy_$userSuffix"
    $dbPass = -join (((48..57) + (65..90) + (97..122) | Get-Random -Count 32) | ForEach-Object { [char]$_ })
    $rootPass = -join (((48..57) + (65..90) + (97..122) | Get-Random -Count 32) | ForEach-Object { [char]$_ })

    @"
DB_DATABASE=getfy
DB_USERNAME=$dbUser
DB_PASSWORD=$dbPass
APP_URL=http://localhost
GETFY_HTTP_PORT=80
MYSQL_DATABASE=getfy
MYSQL_USER=$dbUser
MYSQL_PASSWORD=$dbPass
MYSQL_ROOT_PASSWORD=$rootPass
"@ | Set-Content -NoNewline -Path $envFile
}

docker compose --env-file $envFile up --build -d

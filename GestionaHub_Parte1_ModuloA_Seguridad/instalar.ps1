# Instala la Parte 1 (Modulo A: POS y Facturacion + Seguridad) en tu proyecto GestionaHub.
# Uso (PowerShell, desde esta carpeta):
#   powershell -ExecutionPolicy Bypass -File .\instalar.ps1 -Proyecto "C:\ruta\a\gestionahub"
param([Parameter(Mandatory = $true)][string]$Proyecto)

$origen = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $Proyecto 'artisan'))) { Write-Error "No encuentro 'artisan' en $Proyecto"; exit 1 }

$utf8SinBom = New-Object System.Text.UTF8Encoding $False
$respaldo = Join-Path $Proyecto ("respaldo_parte1_" + (Get-Date -Format 'yyyyMMdd_HHmmss'))
$carpetas = '01_Migraciones', '02_Modelos', '03_Vistas', '04_Controladores', '05_Rutas', '06_Servicios', '07_Pruebas'

foreach ($c in $carpetas) {
    $base = Join-Path $origen $c
    Get-ChildItem -Path $base -Recurse -File | ForEach-Object {
        $relativo = $_.FullName.Substring($base.Length + 1)
        $destino = Join-Path $Proyecto $relativo
        if (Test-Path $destino) {
            $copia = Join-Path $respaldo $relativo
            New-Item -ItemType Directory -Force -Path (Split-Path $copia) | Out-Null
            Copy-Item $destino $copia -Force
        }
        New-Item -ItemType Directory -Force -Path (Split-Path $destino) | Out-Null
        # Se reescribe en UTF-8 sin BOM
        [System.IO.File]::WriteAllText($destino, [System.IO.File]::ReadAllText($_.FullName), $utf8SinBom)
        Write-Host "  copiado  $relativo"
    }
}

# Carga las rutas nuevas desde routes/web.php (una sola vez)
$web = Join-Path $Proyecto 'routes\web.php'
$texto = [System.IO.File]::ReadAllText($web)
if ($texto -notmatch 'modulo_a\.php') {
    $texto = $texto.TrimEnd() + "`r`n`r`n// Modulo A: POS, facturacion y pantalla de bloqueo`r`nrequire __DIR__ . '/modulo_a.php';`r`n"
    [System.IO.File]::WriteAllText($web, $texto, $utf8SinBom)
    Write-Host "  routes/web.php: agregado require de modulo_a.php"
}

Write-Host ""
Write-Host "Listo. Respaldo de los archivos reemplazados en: $respaldo"
Write-Host "Ahora ejecuta:"
Write-Host "  docker compose exec app php artisan migrate"
Write-Host "  docker compose exec app php artisan optimize:clear"

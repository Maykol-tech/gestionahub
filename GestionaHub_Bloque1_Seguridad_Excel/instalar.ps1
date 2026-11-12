# Instala el Bloque 1 (Seguridad + Excel + limpieza visual) en tu proyecto GestionaHub.
# Requisito: tener instalada la Parte 1 (POS y Facturacion + Seguridad).
# Uso (PowerShell, desde esta carpeta):
#   powershell -ExecutionPolicy Bypass -File .\instalar.ps1 -Proyecto "C:\ruta\a\gestionahub"
param([Parameter(Mandatory = $true)][string]$Proyecto)

$origen = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $Proyecto 'artisan'))) { Write-Error "No encuentro 'artisan' en $Proyecto"; exit 1 }
if (-not (Test-Path (Join-Path $Proyecto 'routes\modulo_a.php'))) { Write-Warning "No encuentro routes\modulo_a.php: instala primero la Parte 1." }

$utf8SinBom = New-Object System.Text.UTF8Encoding $False
$respaldo = Join-Path $Proyecto ("respaldo_bloque1_" + (Get-Date -Format 'yyyyMMdd_HHmmss'))
$carpetas = '2_Controladores', '3_Vistas', '4_Rutas', '5_Servicios', '6_Pruebas'

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
        # Se lee y escribe explicitamente en UTF-8 sin BOM (asi no se rompen tildes ni simbolos)
        [System.IO.File]::WriteAllText($destino, [System.IO.File]::ReadAllText($_.FullName, $utf8SinBom), $utf8SinBom)
        Write-Host "  copiado  $relativo"
    }
}

# La campana flotante antigua (el icono roto de la esquina inferior izquierda) ya no se usa
$campana = Join-Path $Proyecto 'resources\views\admin\partials\campana.blade.php'
if (Test-Path $campana) {
    $copia = Join-Path $respaldo 'resources\views\admin\partials\campana.blade.php'
    New-Item -ItemType Directory -Force -Path (Split-Path $copia) | Out-Null
    Move-Item $campana $copia -Force
    Write-Host "  eliminado resources\views\admin\partials\campana.blade.php (queda en el respaldo)"
}

# Carga las rutas del bloque desde routes/web.php (una sola vez)
$web = Join-Path $Proyecto 'routes\web.php'
$texto = [System.IO.File]::ReadAllText($web, $utf8SinBom)
if ($texto -notmatch 'bloque1\.php') {
    $texto = $texto.TrimEnd() + "`r`n`r`n// Bloque 1: exportaciones a Excel`r`nrequire __DIR__ . '/bloque1.php';`r`n"
    [System.IO.File]::WriteAllText($web, $texto, $utf8SinBom)
    Write-Host "  routes/web.php: agregado require de bloque1.php"
}

Write-Host ""
Write-Host "Listo. Respaldo de lo reemplazado en: $respaldo"
Write-Host "Ahora ejecuta:"
Write-Host "  docker compose exec app composer require maatwebsite/excel:^3.1"
Write-Host "  docker compose exec app php artisan optimize:clear"

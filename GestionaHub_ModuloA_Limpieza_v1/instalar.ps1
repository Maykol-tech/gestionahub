# Instala la limpieza de textos y el Modulo A (Reportes GEST-40/41 y delivery) en GestionaHub.
# Uso (PowerShell, desde esta carpeta):
#   powershell -ExecutionPolicy Bypass -File .\instalar.ps1 -Proyecto "C:\ruta\a\gestionahub"
param([Parameter(Mandatory = $true)][string]$Proyecto)

$origen = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $Proyecto 'artisan'))) { Write-Error "No encuentro 'artisan' en $Proyecto"; exit 1 }

# 1) Reportes GEST-40/41 (mismo instalador que ya conoces).
& (Join-Path $origen 'instalar_reportes.ps1') -Proyecto $Proyecto

# 2) Limpieza: quita el parcial de la campana con caracteres basura (no lo usa ninguna pantalla).
$parcial = Join-Path $Proyecto 'resources\views\admin\partials\campana.blade.php'
if (Test-Path $parcial) {
    Remove-Item $parcial -Force
    Write-Host "  borrado  resources\views\admin\partials\campana.blade.php (caracteres basura)"
} else {
    Write-Host "  limpieza: no hay parcial de campana que borrar"
}

Write-Host ""
Write-Host "Listo. Ahora ejecuta, una linea a la vez:"
Write-Host "  docker compose exec app php artisan optimize:clear"
Write-Host "  docker compose exec app php artisan test --filter=ReportesGest40Gest41Test"

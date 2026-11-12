# Instala Reportes GEST-40 (ventas por periodo y modulo) y GEST-41 (repartidores) en GestionaHub.
# Requisito: tener instalado el Bloque 1 (Excel con Laravel-Excel).
# Uso (PowerShell, desde esta carpeta):
#   powershell -ExecutionPolicy Bypass -File .\instalar.ps1 -Proyecto "C:\ruta\a\gestionahub"
param([Parameter(Mandatory = $true)][string]$Proyecto)

$origen = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $Proyecto 'artisan'))) { Write-Error "No encuentro 'artisan' en $Proyecto"; exit 1 }
if (-not (Test-Path (Join-Path $Proyecto 'routes\bloque1.php'))) { Write-Warning "No encuentro routes\bloque1.php: instala primero el Bloque 1." }

$utf8SinBom = New-Object System.Text.UTF8Encoding $False
$respaldo = Join-Path $Proyecto ("respaldo_reportes_" + (Get-Date -Format 'yyyyMMdd_HHmmss'))
$carpetas = '1_Migraciones', '2_Controladores', '3_Vistas', '4_Rutas', '5_Servicios', '6_Pruebas'

function Respaldar([string]$relativo) {
    $actual = Join-Path $Proyecto $relativo
    if (Test-Path $actual) {
        $copia = Join-Path $respaldo $relativo
        New-Item -ItemType Directory -Force -Path (Split-Path $copia) | Out-Null
        Copy-Item $actual $copia -Force
    }
}

foreach ($c in $carpetas) {
    $base = Join-Path $origen $c
    Get-ChildItem -Path $base -Recurse -File | ForEach-Object {
        $relativo = $_.FullName.Substring($base.Length + 1)
        $destino = Join-Path $Proyecto $relativo
        Respaldar $relativo
        New-Item -ItemType Directory -Force -Path (Split-Path $destino) | Out-Null
        # Lectura y escritura explicita en UTF-8 sin BOM (no se rompen tildes ni simbolos)
        [System.IO.File]::WriteAllText($destino, [System.IO.File]::ReadAllText($_.FullName, $utf8SinBom), $utf8SinBom)
        Write-Host "  copiado  $relativo"
    }
}

# ModulosController: la pantalla /admin/reportes ahora usa ReportesController::pantalla()
$relModulos = 'app\Http\Controllers\ModulosController.php'
$modulos = Join-Path $Proyecto $relModulos
$texto = [System.IO.File]::ReadAllText($modulos, $utf8SinBom)
if ($texto.Contains('ReportesController::class)->pantalla(')) {
    Write-Host "  ModulosController ya estaba actualizado"
} else {
    $ini = $texto.IndexOf('    protected function datosReportes(')
    $fin = if ($ini -ge 0) { $texto.IndexOf('    protected function datosCobros(', $ini) } else { -1 }
    if ($ini -lt 0 -or $fin -lt 0) {
        Write-Warning "No pude ubicar datosReportes() en ModulosController. Aplica el cambio a mano (ver INSTRUCCIONES.txt, paso 3)."
    } else {
        Respaldar $relModulos
        $nuevo = @'
    /** Reportes GEST-40 (ventas por periodo y modulo) y GEST-41 (repartidores): ver ReportesController. */
    protected function datosReportes(Request $request): array
    {
        return app(ReportesController::class)->pantalla($request, $this->tenantId());
    }


'@
        $texto = $texto.Substring(0, $ini) + $nuevo.Replace("`r`n", "`n").Replace("`n", "`r`n").TrimEnd() + "`r`n`r`n" + $texto.Substring($fin)
        [System.IO.File]::WriteAllText($modulos, $texto, $utf8SinBom)
        Write-Host "  ModulosController: datosReportes() ahora delega en ReportesController"
    }
}

# Carga las rutas de reportes desde routes/web.php (una sola vez)
$web = Join-Path $Proyecto 'routes\web.php'
$texto = [System.IO.File]::ReadAllText($web, $utf8SinBom)
if ($texto -notmatch 'reportes\.php') {
    Respaldar 'routes\web.php'
    $texto = $texto.TrimEnd() + "`r`n`r`n// Reportes GEST-40 y GEST-41: ventas por canal y repartidores`r`nrequire __DIR__ . '/reportes.php';`r`n"
    [System.IO.File]::WriteAllText($web, $texto, $utf8SinBom)
    Write-Host "  routes/web.php: agregado require de reportes.php"
}

Write-Host ""
Write-Host "Listo. Respaldo de lo reemplazado en: $respaldo"
Write-Host "Ahora ejecuta:"
Write-Host "  docker compose exec app php artisan migrate"
Write-Host "  docker compose exec app php artisan optimize:clear"
Write-Host "  docker compose exec app php artisan test --filter=ReportesGest40Gest41Test"

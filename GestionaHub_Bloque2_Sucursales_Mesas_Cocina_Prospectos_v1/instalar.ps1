# Instala Bloque 2: prospeccion IA (GEST-42/43), QR de mesas (GEST-46), cocina en vivo (GEST-47) y sucursales (GEST-48).
# Requisito: Bloque 1 instalado (CRUDs de repartidores y proveedores).
# Uso (PowerShell, desde esta carpeta):
#   powershell -ExecutionPolicy Bypass -File .\instalar.ps1 -Proyecto "C:\ruta\a\gestionahub"
param([Parameter(Mandatory = $true)][string]$Proyecto)

$origen = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $Proyecto 'artisan'))) { Write-Error "No encuentro 'artisan' en $Proyecto"; exit 1 }
if (-not (Test-Path (Join-Path $Proyecto 'routes\bloque1.php'))) { Write-Warning "No encuentro routes\bloque1.php: instala primero el Bloque 1." }

$utf8SinBom = New-Object System.Text.UTF8Encoding $False
$respaldo = Join-Path $Proyecto ("respaldo_bloque2_" + (Get-Date -Format 'yyyyMMdd_HHmmss'))
$carpetas = '2_Controladores', '3_Vistas', '4_Rutas', '5_Servicios', '6_Pruebas'

function Respaldar([string]$relativo) {
    $actual = Join-Path $Proyecto $relativo
    if (Test-Path $actual) {
        $copia = Join-Path $respaldo $relativo
        New-Item -ItemType Directory -Force -Path (Split-Path $copia) | Out-Null
        Copy-Item $actual $copia -Force
    }
}

function Guardar([string]$ruta, [string]$texto) {
    [System.IO.File]::WriteAllText($ruta, $texto, $utf8SinBom)
}

# 1) Copia de archivos completos (con respaldo de lo reemplazado).
foreach ($c in $carpetas) {
    $base = Join-Path $origen $c
    if (-not (Test-Path $base)) { continue }
    Get-ChildItem -Path $base -Recurse -File | ForEach-Object {
        $relativo = $_.FullName.Substring($base.Length + 1)
        $destino = Join-Path $Proyecto $relativo
        Respaldar $relativo
        New-Item -ItemType Directory -Force -Path (Split-Path $destino) | Out-Null
        Guardar $destino ([System.IO.File]::ReadAllText($_.FullName, $utf8SinBom))
        Write-Host "  copiado  $relativo"
    }
}

# 2) config\services.php: bloque de prospeccion IA (solo si no existe).
$relServ = 'config\services.php'
$serv = Join-Path $Proyecto $relServ
$texto = [System.IO.File]::ReadAllText($serv, $utf8SinBom)
if ($texto.Contains("'prospeccion' => [")) {
    Write-Host "  config\services.php: el bloque prospeccion ya existe"
} else {
    $ancla = "    'slack' => ["
    $pos = $texto.IndexOf($ancla)
    if ($pos -lt 0) {
        Write-Warning "No encuentro 'slack' en $relServ. Agrega a mano el bloque 'prospeccion' (ver INSTRUCCIONES.txt)."
    } else {
        Respaldar $relServ
        $bloque = @'
    'prospeccion' => [
        // Microservicio Python (FastAPI + Playwright). Contrato: POST {url}/prospectar
        'url' => env('PROSPECCION_IA_URL'),
        'token' => env('PROSPECCION_IA_TOKEN'),
        'timeout' => (int) env('PROSPECCION_IA_TIMEOUT', 60),
    ],

'@
        $texto = $texto.Substring(0, $pos) + $bloque.Replace("`r`n", "`n").Replace("`n", "`r`n") + $texto.Substring($pos)
        Guardar $serv $texto
        Write-Host "  config\services.php: agregado bloque prospeccion"
    }
}

# 3) ModulosController::datosSucursales: pasa la lista de sucursales a la vista.
$relMod = 'app\Http\Controllers\ModulosController.php'
$mod = Join-Path $Proyecto $relMod
$texto = [System.IO.File]::ReadAllText($mod, $utf8SinBom)
if ($texto.Contains("'usadas' => 1 + ")) {
    Write-Host "  ModulosController: sucursales ya estaba actualizado"
} else {
    $ancla = "'maxSucursales' => `$max,"
    $pos = $texto.IndexOf($ancla)
    if ($pos -lt 0) {
        Write-Warning "No encuentro 'maxSucursales' en $relMod. Agrega a mano en datosSucursales() las lineas 'sucursales' y 'usadas' (ver INSTRUCCIONES.txt)."
    } else {
        Respaldar $relMod
        $fin = $pos + $ancla.Length
        $nuevas = "`r`n            'sucursales' => \App\Models\Sucursal::query()->orderBy('nombre')->get(),`r`n            'usadas' => 1 + \App\Models\Sucursal::query()->count(),"
        $texto = $texto.Substring(0, $fin) + $nuevas + $texto.Substring($fin)
        Guardar $mod $texto
        Write-Host "  ModulosController: datosSucursales() ahora envia sucursales"
    }
}

# 4) routes\web.php: carga routes\bloque2.php una sola vez.
$web = Join-Path $Proyecto 'routes\web.php'
$texto = [System.IO.File]::ReadAllText($web, $utf8SinBom)
if ($texto -notmatch 'bloque2\.php') {
    Respaldar 'routes\web.php'
    $texto = $texto.TrimEnd() + "`r`n`r`n// Bloque 2: prospeccion IA, QR de mesas, cocina en vivo y sucursales`r`nrequire __DIR__ . '/bloque2.php';`r`n"
    Guardar $web $texto
    Write-Host "  routes\web.php: agregado require de bloque2.php"
} else {
    Write-Host "  routes\web.php: bloque2.php ya estaba cargado"
}

Write-Host ""
Write-Host "Listo. Respaldo de lo reemplazado en: $respaldo"
Write-Host "Ahora ejecuta, una linea a la vez:"
Write-Host "  docker compose exec app composer require simplesoftwareio/simple-qrcode:^4.2"
Write-Host "  docker compose exec app php artisan optimize:clear"
Write-Host "  docker compose exec app php artisan test --filter=Bloque2SucursalesMesasCocinaTest"
Write-Host "  docker compose exec app php artisan test --filter=ProspeccionIaTest"

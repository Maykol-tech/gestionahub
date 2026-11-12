# Instala Bloque 1: CRUD de Repartidores y Proveedores, datos de demo y limpieza de textos.
# Uso (PowerShell, desde esta carpeta):
#   powershell -ExecutionPolicy Bypass -File .\instalar.ps1 -Proyecto "C:\ruta\a\gestionahub"
param([Parameter(Mandatory = $true)][string]$Proyecto)

$origen = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $Proyecto 'artisan'))) { Write-Error "No encuentro 'artisan' en $Proyecto"; exit 1 }
if (-not (Test-Path (Join-Path $Proyecto 'routes\bloque1.php'))) { Write-Warning "No encuentro routes\bloque1.php: instala primero el Bloque 1 de seguridad y Excel." }

$utf8SinBom = New-Object System.Text.UTF8Encoding $False
$respaldo = Join-Path $Proyecto ("respaldo_bloque1_crud_" + (Get-Date -Format 'yyyyMMdd_HHmmss'))
$carpetas = '1_Migraciones', '2_Controladores', '3_Vistas', '4_Rutas', '5_Servicios', '6_Pruebas'

function Respaldar([string]$relativo) {
    $actual = Join-Path $Proyecto $relativo
    if (Test-Path $actual) {
        $copia = Join-Path $respaldo $relativo
        New-Item -ItemType Directory -Force -Path (Split-Path $copia) | Out-Null
        Copy-Item $actual $copia -Force
    }
}

# 1) Copia de archivos. Los binarios (PNG) se copian tal cual; el resto en UTF-8 sin BOM.
foreach ($c in $carpetas) {
    $base = Join-Path $origen $c
    Get-ChildItem -Path $base -Recurse -File | ForEach-Object {
        $relativo = $_.FullName.Substring($base.Length + 1)
        $destino = Join-Path $Proyecto $relativo
        Respaldar $relativo
        New-Item -ItemType Directory -Force -Path (Split-Path $destino) | Out-Null
        if ($_.Extension -eq '.png') {
            Copy-Item $_.FullName $destino -Force
        } else {
            [System.IO.File]::WriteAllText($destino, [System.IO.File]::ReadAllText($_.FullName, $utf8SinBom), $utf8SinBom)
        }
        Write-Host "  copiado  $relativo"
    }
}

# 2) Menu lateral: agrega Repartidores justo despues de Repartos (si no existe ya).
$relLayout = 'resources\views\layouts\app.blade.php'
$layout = Join-Path $Proyecto $relLayout
$texto = [System.IO.File]::ReadAllText($layout, $utf8SinBom)
if ($texto.Contains('admin.repartidores.crud.index')) {
    Write-Host "  Menu: Repartidores ya estaba en layouts\app.blade.php"
} else {
    $ancla = "['Repartos', 'truck', 'repartos'],"
    $pos = $texto.IndexOf($ancla)
    if ($pos -lt 0) {
        Write-Warning "No encuentro la linea de Repartos en $relLayout. Agrega a mano: ['Repartidores', 'users', 'admin.repartidores.crud.index'],"
    } else {
        Respaldar $relLayout
        $fin = $pos + $ancla.Length
        $nuevaLinea = "`r`n            ['Repartidores', 'users', 'admin.repartidores.crud.index'],"
        $texto = $texto.Substring(0, $fin) + $nuevaLinea + $texto.Substring($fin)
        [System.IO.File]::WriteAllText($layout, $texto, $utf8SinBom)
        Write-Host "  Menu: agregado Repartidores"
    }
}

# 3) Rutas: carga los dos archivos nuevos desde routes\web.php (una sola vez).
$web = Join-Path $Proyecto 'routes\web.php'
$texto = [System.IO.File]::ReadAllText($web, $utf8SinBom)
$agregar = ''
if ($texto -notmatch 'repartidores_crud\.php') { $agregar += "require __DIR__ . '/repartidores_crud.php';`r`n" }
if ($texto -notmatch 'proveedores_crud\.php') { $agregar += "require __DIR__ . '/proveedores_crud.php';`r`n" }
if ($agregar -ne '') {
    Respaldar 'routes\web.php'
    $texto = $texto.TrimEnd() + "`r`n`r`n// Bloque 1: CRUD de repartidores y proveedores`r`n" + $agregar
    [System.IO.File]::WriteAllText($web, $texto, $utf8SinBom)
    Write-Host "  routes\web.php: agregadas las rutas de repartidores y proveedores"
} else {
    Write-Host "  routes\web.php: las rutas ya estaban cargadas"
}

Write-Host ""
Write-Host "Listo. Respaldo de lo reemplazado en: $respaldo"
Write-Host "Ahora ejecuta, una linea a la vez:"
Write-Host "  php artisan migrate"
Write-Host "  php artisan storage:link"
Write-Host "  php artisan optimize:clear"
Write-Host "  php artisan db:seed --class=DemoDataSeeder     (solo en tu entorno local, no en produccion)"
Write-Host "  php artisan test --filter=Bloque1CrudDemoTest"

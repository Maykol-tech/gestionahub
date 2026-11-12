# Instala Bloque 3 (landing): SEO con IA (GEST-50), libro de reclamaciones, popup de oferta (GEST-52),
# chatbot de captura de leads (GEST-53) y precios con toggle (GEST-54). Requisito: Bloque 2 instalado.
# Uso (PowerShell, desde esta carpeta):
#   powershell -ExecutionPolicy Bypass -File .\instalar.ps1 -Proyecto "C:\ruta\a\gestionahub"
param([Parameter(Mandatory = $true)][string]$Proyecto)

$origen = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $Proyecto 'artisan'))) { Write-Error "No encuentro 'artisan' en $Proyecto"; exit 1 }
if (-not (Test-Path (Join-Path $Proyecto 'routes\bloque2.php'))) { Write-Warning "No encuentro routes\bloque2.php: instala primero el Bloque 2." }

$utf8SinBom = New-Object System.Text.UTF8Encoding $False
$respaldo = Join-Path $Proyecto ("respaldo_bloque3_landing_" + (Get-Date -Format 'yyyyMMdd_HHmmss'))
$carpetas = '1_Migraciones', '2_Controladores', '3_Vistas', '5_Servicios', '6_Pruebas'

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

# 2) config\services.php: bloque openai (solo si no existe).
$relServ = 'config\services.php'
$serv = Join-Path $Proyecto $relServ
$texto = [System.IO.File]::ReadAllText($serv, $utf8SinBom)
if ($texto.Contains("'openai' => [")) {
    Write-Host "  config\services.php: el bloque openai ya existe"
} else {
    $ancla = "    'slack' => ["
    $pos = $texto.IndexOf($ancla)
    if ($pos -lt 0) {
        Write-Warning "No encuentro 'slack' en $relServ. Agrega a mano el bloque 'openai' (ver INSTRUCCIONES.txt)."
    } else {
        Respaldar $relServ
        $bloque = @'
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

'@
        $texto = $texto.Substring(0, $pos) + $bloque.Replace("`r`n", "`n").Replace("`n", "`r`n") + $texto.Substring($pos)
        Guardar $serv $texto
        Write-Host "  config\services.php: agregado bloque openai"
    }
}

# 3) routes\bloque2.php o web.php: ruta del SEO del superadmin (una sola vez).
$web = Join-Path $Proyecto 'routes\web.php'
$texto = [System.IO.File]::ReadAllText($web, $utf8SinBom)
if ($texto -match "landing\.seo") {
    Write-Host "  rutas: la ruta de SEO ya existe"
} else {
    $ancla = "->name('landing.lead');"
    $pos = $texto.IndexOf($ancla)
    if ($pos -lt 0) {
        Write-Warning "No encuentro la ruta landing.lead en routes\web.php. Agrega a mano: Route::post('/landing/seo', [LandingEditorController::class, 'seo'])->name('landing.seo'); dentro del grupo superadmin."
    } else {
        Respaldar 'routes\web.php'
        $fin = $pos + $ancla.Length
        $linea = "`r`n    Route::post('/landing/seo', [LandingEditorController::class, 'seo'])->name('landing.seo');"
        $texto = $texto.Substring(0, $fin) + $linea + $texto.Substring($fin)
        Guardar $web $texto
        Write-Host "  routes\web.php: agregada ruta de SEO"
    }
}

Write-Host ""
Write-Host "Listo. Respaldo de lo reemplazado en: $respaldo"
Write-Host "Ahora ejecuta, una linea a la vez:"
Write-Host "  docker compose exec app php artisan migrate"
Write-Host "  docker compose exec app php artisan optimize:clear"
Write-Host "  docker compose exec app php artisan test --filter=Bloque3LandingTest"

<?php

/*
 * Limpieza de textos con caracteres basura (doble codificacion UTF-8) en el proyecto.
 *
 * Uso dentro del contenedor (desde la carpeta del proyecto):
 *   docker compose exec app php scripts/limpiar_textos.php            -> solo muestra los archivos que cambiarian
 *   docker compose exec app php scripts/limpiar_textos.php --aplicar  -> los corrige (antes guarda una copia en storage/app/limpieza_textos_FECHA)
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Support\LimpiezaTextos;

$aplicar = in_array('--aplicar', $argv ?? [], true);
$raiz = dirname(__DIR__);
$carpetas = ['app', 'resources', 'routes', 'config', 'database', 'lang'];
$extensiones = ['php', 'js', 'css', 'md', 'txt', 'json', 'html', 'svg'];
$respaldo = $raiz . '/storage/app/limpieza_textos_' . date('Ymd_His');

$revisados = 0;
$cambiados = [];

foreach ($carpetas as $carpeta) {
    if (! is_dir("$raiz/$carpeta")) {
        continue;
    }

    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$raiz/$carpeta", FilesystemIterator::SKIP_DOTS));
    foreach ($iterador as $archivo) {
        if (! $archivo->isFile() || ! in_array(strtolower($archivo->getExtension()), $extensiones, true)) {
            continue;
        }

        $ruta = $archivo->getPathname();
        $original = file_get_contents($ruta);
        $revisados++;

        if (! mb_check_encoding($original, 'UTF-8')) {
            echo "  OMITIDO (no es UTF-8): " . substr($ruta, strlen($raiz) + 1) . PHP_EOL;
            continue;
        }

        $nuevo = LimpiezaTextos::reparar($original);
        if ($nuevo === $original) {
            continue;
        }

        $relativa = substr($ruta, strlen($raiz) + 1);
        $cambiados[] = $relativa;

        if ($aplicar) {
            $copia = $respaldo . '/' . $relativa;
            if (! is_dir(dirname($copia))) {
                mkdir(dirname($copia), 0775, true);
            }
            copy($ruta, $copia);
            // UTF-8 sin BOM, igual que el resto del proyecto
            file_put_contents($ruta, $nuevo);
        }
        echo ($aplicar ? '  corregido: ' : '  cambiaria: ') . $relativa . PHP_EOL;
    }
}

echo PHP_EOL . "Archivos revisados: {$revisados}. Con caracteres basura: " . count($cambiados) . PHP_EOL;
if (! $aplicar && $cambiados) {
    echo "Para corregirlos ejecuta el mismo comando con --aplicar." . PHP_EOL;
}
if ($aplicar && $cambiados) {
    echo "Copia de respaldo: storage/app/" . basename($respaldo) . PHP_EOL;
}

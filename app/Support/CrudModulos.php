<?php

namespace App\Support;

class CrudModulos
{
    public static function cliente($modulo)
    {
        // Retornamos una estructura básica para que la vista no falle
        return (object) [
            'titulo' => ucfirst($modulo),
            'modulo' => $modulo,
            'columnas' => [],
            'campos' => []
        ];
    }
}
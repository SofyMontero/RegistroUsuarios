<?php

if (!defined('FOTO_USUARIO_DEFAULT')) {
    define('FOTO_USUARIO_DEFAULT', 'usuario.png');
}

if (!function_exists('normalizar_genero_usuario')) {
    function normalizar_genero_usuario($genero)
    {
        $genero = strtolower(trim((string) $genero));
        if (in_array($genero, array('masculino', 'm', 'hombre', 'male'), true)) {
            return 'Masculino';
        }
        if (in_array($genero, array('femenino', 'f', 'mujer', 'female'), true)) {
            return 'Femenino';
        }

        return '';
    }
}

if (!function_exists('foto_por_genero')) {
    function foto_por_genero($genero)
    {
        $genero = normalizar_genero_usuario($genero);
        if ($genero === 'Masculino') {
            return 'hombre.jpg';
        }
        if ($genero === 'Femenino') {
            return 'mujer.png';
        }

        return FOTO_USUARIO_DEFAULT;
    }
}

if (!function_exists('foto_usuario_o_default')) {
    function foto_usuario_o_default($ext)
    {
        $ext = trim((string) $ext);

        return $ext !== '' ? $ext : FOTO_USUARIO_DEFAULT;
    }
}

if (!function_exists('directorio_imagenes_usuario')) {
    function directorio_imagenes_usuario()
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'imagenes' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('contenido_foto_usuario')) {
    function contenido_foto_usuario($archivo)
    {
        $archivo = basename(trim((string) $archivo));
        if ($archivo === '') {
            $archivo = FOTO_USUARIO_DEFAULT;
        }

        $ruta = directorio_imagenes_usuario() . $archivo;
        if (is_file($ruta) && is_readable($ruta)) {
            $contenido = file_get_contents($ruta);
            if ($contenido !== false) {
                return $contenido;
            }
        }

        return '';
    }
}

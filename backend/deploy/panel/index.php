<?php

/**
 * Front controller para servir el panel en https://cometax.click/panel
 * dentro de un hosting compartido, donde el document root es fijo
 * (public_html) y no se puede apuntar a la carpeta public/ de Laravel.
 *
 * Copiar este archivo a:  ~/domains/cometax.click/public_html/panel/index.php
 * El código de Laravel vive en: ~/domains/cometax.click/laravel/  (fuera de public_html)
 *
 * Es idéntico al public/index.php de Laravel salvo por las rutas, que suben
 * dos niveles hasta salir de public_html en vez de uno.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$base = __DIR__.'/../../laravel';

if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $base.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';

$app->handleRequest(Request::capture());

<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../src/app.php';

require_once __DIR__.'/inventario/index.php';
require_once __DIR__.'/catalogo/index.php';
require_once __DIR__.'/adicionables/index.php';
require_once __DIR__.'/comandas/index.php';
require_once __DIR__.'/compras/index.php';
require_once __DIR__.'/cortez/index.php';
require_once __DIR__.'/cuenta_descuento/index.php';
require_once __DIR__.'/cuentas/index.php';
require_once __DIR__.'/domicilio/index.php';
require_once __DIR__.'/historial/index.php';
require_once __DIR__.'/pedidos/index.php';
require_once __DIR__.'/pedidos_adicionales/index.php';
require_once __DIR__.'/productos/index.php';
require_once __DIR__.'/productos_grupos/index.php';
require_once __DIR__.'/sonido/index.php';
require_once __DIR__.'/usuarios/index.php';



$app->match('/', function () use ($app) {

    return $app['twig']->render('ag_dashboard.html.twig', array());
        
})
->bind('dashboard');


$app->run();
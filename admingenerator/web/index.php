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
if (1) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

session_start();

if (isset($_POST['usuario']) && isset($_POST['clave'])) {
    require_once('../../configuracion.php');
    require_once('../../SERV/PHP/db.php');

    $usuario = db_codex($_POST['usuario']);
    $clave = db_codex($_POST['clave']);
    $c = "SELECT usuario, nivel FROM usuarios WHERE usuario = '{$usuario}' AND clave=SHA1('{$clave}') AND nivel IN ('gerente') AND disponible=1 LIMIT 1";
    $r = db_consultar($c);

    if ((int) db_num_resultados($r) > 0) {
        $usuario = db_fetch($r);

        $_SESSION['iniciado'] = true;
        $_SESSION['usuario'] = $usuario;
    } else {
        $mensaje_login = "usuario o clave incorrectos";
    }
}


if (!isset($_SESSION['iniciado'])) {
    require_once 'login.php';
    return;
}

require_once __DIR__ . '/controllers/base.php';

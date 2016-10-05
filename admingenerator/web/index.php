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

if (!isset($_SESSION['iniciado'])) {
    require_once 'login.php';
    return;
}
require_once __DIR__.'/controllers/base.php';

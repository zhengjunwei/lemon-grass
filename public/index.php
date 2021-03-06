<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/12
 * Time: 下午5:20
 */

// Autoload
require '../vendor/autoload.php';
require '../config/config.php';

use NoahBuscher\Macaw\Macaw;

session_start();
header('Access-Control-Allow-Origin: ' . BaseController::get_allow_origin());

// routes
require '../router/routes.php';
require '../router/user.php';
require '../router/ad.php';
require '../router/stat.php';
require '../router/notice.php';
Macaw::dispatch();
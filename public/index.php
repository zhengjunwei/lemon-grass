<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/12
 * Time: 下午5:20
 */

// Autoload
require '../vendor/autoload.php';

use NoahBuscher\Macaw\Macaw;

session_start();
header('Content-type: application/JSON; charset=UTF-8');

$HTTP_CODE = array(
  400 => 'Bad Request',
  401 => 'Unauthorized',
  422 => 'Unprocessable Entity',
);
function exit_with_error($code, $msg, $http_code) {
  global $HTTP_CODE;
  header("HTTP/1.1 $http_code " . $HTTP_CODE[$http_code]);
  exit(json_encode(array(
    'code' => $code,
    'msg' => $msg,
  )));
}

// routes
require '../router/routes.php';
require '../router/user.php';
require '../router/ad.php';
Macaw::dispatch();
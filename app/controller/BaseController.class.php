<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/13
 * Time: 下午5:58
 */

class BaseController {
  static $HTTP_CODE = array(
    400 => 'Bad Request',
    401 => 'Unauthorized',
    422 => 'Unprocessable Entity',
  );

  public function __construct() {

  }

  protected function get_pdo_read() {
    return require dirname(__FILE__) . '/../../config/pdo_admin.php';
  }
  protected function get_pdo_write() {
    return require '../../config/pdo_admin.php';
  }

  protected function exit_with_error($code, $msg, $http_code) {
    header('Content-type: application/JSON; charset=UTF-8');
    header("HTTP/1.1 $http_code " . self::$HTTP_CODE[$http_code]);
    exit(json_encode(array(
      'code' => $code,
      'msg' => $msg,
    )));
  }
  protected function output($result) {
    header('Content-type: application/JSON; charset=UTF-8');
    exit(json_encode($result));
  }
} 
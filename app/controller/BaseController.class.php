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
    403 => 'Forbidden',
    422 => 'Unprocessable Entity',
  );

  protected $need_auth = true;

  public function __construct() {
    // 在这里校验用户身份
    if ($this->need_auth && $_SERVER['REQUEST_METHOD'] != 'OPTIONS') {
      if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
        $this->exit_with_error(1, '登录失效', 401);
      }
    }
  }

  public static function get_allow_origin() {
    $origins = explode(',', ALLOW_ORIGIN);
    $from = $_SERVER['HTTP_ORIGIN'];
    if (in_array($from, $origins)) {
      return $from;
    }
    return 'null';
  }

  public function on_options() {
    header('Access-Control-Allow-Headers: accept, content-type');
    header('Access-Control-Allow-Methods: GET,PUT,POST,PATCH,DELETE');
    header('Content-type: application/JSON; charset=UTF-8');

    exit(json_encode(array(
      'code' => 0,
      'method' => 'options',
      'msg' => 'ready',
    )));
  }

  protected function get_pdo_read() {
    return require dirname(__FILE__) . '/../../inc/pdo_slave.php';
  }
  protected function get_pdo_write() {
    return require dirname(__FILE__) . '/../../inc/pdo.php';
  }
  protected function get_cm() {
    require dirname(__FILE__) . '/../../inc/cm.class.php';
    return new CM();
  }
  protected function get_post_data() {
    $request = file_get_contents('php://input');
    return json_decode($request, true);
  }

  protected function exit_with_error($code, $msg, $http_code, $debug = '') {
    header("HTTP/1.1 $http_code " . self::$HTTP_CODE[$http_code]);
    header('Content-type: application/JSON; charset=UTF-8');
    $result = array(
      'code' => $code,
      'msg' => $msg,
      'debug' => $debug,
    );
    if ($http_code === 401) { // 登录失效或未登录
      $result['me'] = array();
    }
    exit(json_encode($result));
  }
  protected function output($result) {
    header('Content-type: application/JSON; charset=UTF-8');
    exit(json_encode($result));
  }
} 
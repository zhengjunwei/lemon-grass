<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/17
 * Time: 下午1:35
 */

use diy\service\Auth;

class UserController extends BaseController {
  protected $need_auth = false;

  public function get_info() {
    if ($_SESSION['id']) {
      $result = array(
        'code' => 0,
        'msg' => 'is login',
        'me' => $this->get_user_info(),
      );
      $this->output($result);
    }
    $this->exit_with_error(1, 'not login', 401);
  }

  public function login() {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $verify_code = trim($_POST['verifycode']);

    if ($verify_code != $_SESSION['Checknum']) {
      $this->exit_with_error(1, '验证码错误', 400);
    }

    if ($username == '' || $password == '') {
      $this->exit_with_error(2, '用户名或密码不能为空', 422);
    }

    $service = new Auth();
    $pass = $service->validate($username, $password);
    if (!$pass) {
      $this->exit_with_error(3, '用户名或密码错误', 400);
    }
    // 只向技术和商务开放
    if (!$service->has_permission()) {
      $this->exit_with_error(4, '暂时只向商务开放', 400);
    }

    $result = array(
      'code' => 0,
      'msg' => '登录成功',
      'me' => $this->get_user_info(),
    );
    $this->output($result);
  }

  public function logout() {
    $_SESSION['id'] = $_SESSION['user'] = $_SESSION['role'] = null;
    $this->output(array(
      'code' => 0,
      'msg' => 'logout',
    ));
  }

  /**
   * 取存在sesssion里的用户数据
   *
   * @return array
   */
  private function get_user_info() {
    return $_SESSION['role'] == Auth::$CP_PERMISSION ? array(
      'id' => $_SESSION['id'],
      'email' => $_SESSION['email'],
      'role' => $_SESSION['role'],
      'fullname' => $_SESSION['fullname'],
      'balance' => $_SESSION['balance'],
      'last_login' => $_SESSION['last_login'],
      'sidebar' => 'cp',
    ) : array(
      'id'       => $_SESSION['id'],
      'user'     => $_SESSION['user'],
      'fullname' => $_SESSION['fullname'],
      'role'     => $_SESSION['role'],
    );
  }
} 
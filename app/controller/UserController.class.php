<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/17
 * Time: 下午1:35
 */

class UserController extends BaseController {
  protected $need_auth = false;

  public function get_info() {
    if ($_SESSION['id']) {
      $result = array(
        'code' => 0,
        'msg' => 'is login',
        'me' => array(
          'id' => $_SESSION['id'],
          'user' => $_SESSION['user'],
          'fullname' => $_SESSION['fullname'],
          'role' => $_SESSION['role'],
        ),
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

    $password = md5($password .$username);
    $pdo = $this->get_pdo_read();
    $sql = "SELECT `id`,`QQ`,`permission`,`NAME`,`associate`
            FROM `t_admin`
            WHERE `username`=:username AND `password`=:password AND `status`=1";
    $stat = $pdo->prepare($sql);
    $stat->execute(array(
      ':username' => $username,
      ':password' => $password,
    ));
    $admin = $stat->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
      $this->exit_with_error(3, '用户名或密码错误', 400);
    }
    // 只向技术和商务开放
    if (!in_array((int)$admin['permission'], array(0, 1, 5, 6))) {
      $this->exit_with_error(4, '暂时只向商务开放', 400);
    }
    $_SESSION['user'] = $username;
    $_SESSION['id'] = $admin['id'];
    $_SESSION['role'] = $admin['permission'];
    $_SESSION['fullname'] = $admin['NAME'];
    $result = array(
      'code' => 0,
      'msg' => '登录成功',
      'me' => array(
        'id' => $_SESSION['id'],
        'user' => $_SESSION['user'],
        'fullname' => $_SESSION['fullname'],
        'role' => $_SESSION['role'],
      ),
    );
    $this->output($result);
  }

  public function logout() {
    $_SESSION['id'] = $_SESSION['fullname'] = $_SESSION['role'] = '';
    $this->output(array(
      'code' => 0,
      'msg' => 'logout',
    ));
  }
} 
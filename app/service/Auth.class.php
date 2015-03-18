<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 15/2/2
 * Time: 下午5:06
 */

namespace diy\service;

use PDO;

class Auth extends Base {
  static $CP_PERMISSION = 100;

  public $user;

  public function validate($username, $password) {
    if (preg_match('/[\w\d\.\-]+@[\w\d\-]+\.\w{2,4}/i', $username)) {
      $this->validate_cp($username, $password);
    }
    $password = $this->encrypt( $username, $password );
    $pdo = $this->get_read_pdo();
    $sql = "SELECT `id`,`QQ`,`permission`,`NAME`,`associate`
            FROM `t_admin`
            WHERE `username`=:username AND `password`=:password AND `status`=1";
    $state = $pdo->prepare($sql);
    $state->execute(array(
      ':username' => $username,
      ':password' => $password,
    ));
    $this->user = $user = $state->fetch(PDO::FETCH_ASSOC);

    // 记录用户信息
    session_start();
    $_SESSION['user'] = $username;
    $_SESSION['id'] = $user['id'];
    $_SESSION['role'] = $user['permission'];
    $_SESSION['fullname'] = $user['NAME'];

    return !!$this->user;
  }

  public function has_permission() {
    return !in_array((int)$this->user['permission'], array(0, 1, 5, 6));
  }

  private function validate_cp( $email, $password ) {
    $password = $this->encrypt($email, $password);
    $pdo = $this->get_read_pdo();
    $sql = "SELECT `id`, `balance`, `username`, `corp`, `owner`,
              `last_login_time`, `last_login_ip`
            FROM `t_diy_user`
            WHERE `email`=:email AND `password`=:password AND `status`=0";
    $state = $pdo->prepare($sql);
    $state->execute(array(
      ':email' => $email,
      ':password' => $password,
    ));
    $this->user = $user = $state->fetch(PDO::FETCH_ASSOC);

    // 记录到session
    session_start();
    $_SESSION['email'] = $email;
    $_SESSION['id'] = $user['id'];
    $_SESSION['role'] = self::$CP_PERMISSION;
    $_SESSION['fullname'] = $user['username'];
    $_SESSION['balance'] = $user['balance'];
    $_SESSION['last_login'] = array(
      'time' => $user['last_login_time'],
      'ip' => $user['last_login_ip'],
    );
  }

  /**
   * @param $username
   * @param $password
   *
   * @return string
   */
  private function encrypt( $username, $password ) {
    return md5( $password . $username . SALT );
  }
}
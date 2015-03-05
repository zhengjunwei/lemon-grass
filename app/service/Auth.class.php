<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 15/2/2
 * Time: 下午5:06
 */

namespace diy\service;

class Auth extends Base {
  public function validate($username, $password) {
    $password = md5($password .$username);
    $pdo = $this->get_read_pdo();
    $sql = "SELECT `id`,`QQ`,`permission`,`NAME`,`associate`
            FROM `t_admin`
            WHERE `username`=:username AND `password`=:password AND `status`=1";
    $stat = $pdo->prepare($sql);
    $stat->execute(array(
      ':username' => $username,
      ':password' => $password,
    ));
    return $stat->fetch(PDO::FETCH_ASSOC);
  }
}
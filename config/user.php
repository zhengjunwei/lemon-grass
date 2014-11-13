<?php
/**
 * 处理用户相关的请求
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/13
 * Time: 下午3:04
 */
use NoahBuscher\Macaw\Macaw;

Macaw::get('user/', function () {
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
    exit(json_encode($result));
  }
  exit_with_error(1, 'not login', 401);
});

Macaw::post('user', function () {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $verify_code = $_POST['verifycode'];

  if ($verify_code != $_SESSION['Checknum']) {
    exit_with_error(1, '验证码错误', 400);
  }

  if ($username == '' || $password == '') {
    exit_with_error(2, '用户名或密码不能为空', 400);
  }

  $password = md5($password .$username);
  $pdo = require '../inc/pdo_read.php';
  $sql = "SELECT id,QQ,permission,associate
          FROM t_admin
          WHERE username=:username and password=:password and `status`=1";
  $stat = $pdo->prepare($sql);
  $admin = $stat->execute(array(
    ':username' => $username,
    ':password' => $password,
  ));
  if (!$admin) {
    exit_with_error(3, '用户名或密码错误', 400);
  }
  // 只向技术和商务开放
  if (in_array($admin['permission'], array(0, 5, 6))) {
    exit_with_error(4, '暂时只向商务开放', 400);
  }
  $_SESSION['user'] = $username;
  $_SESSION['id'] = $admin['id'];
  $_SESSION['role'] = $admin['permission'];
  $_SESSION['fullname'] = $admin['fullname'];
  $result = array(
    'code' => 0,
    'msg' => 'login',
    'me' => array(
      'id' => $_SESSION['id'],
      'user' => $_SESSION['user'],
      'fullname' => $_SESSION['fullname'],
      'role' => $_SESSION['role'],
    ),
  );
  echo json_encode($result);
});

Macaw::delete('user', function () {

});

Macaw::dispatch();
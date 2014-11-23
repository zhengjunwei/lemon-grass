<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/12
 * Time: 下午5:24
 */
use NoahBuscher\Macaw\Macaw;

Macaw::get(BASE . '', 'HomeController@home');

Macaw::get(BASE . 'dashboard/', function () {
  require dirname(__FILE__) . '/../dev_inc/admin_ad_info.class.php';
  $DB = require dirname(__FILE__) . '/../config/pdo_test.php';

  $week_ago = date('Y-m-d H:i:s', time() - 691200);
  $month_ago = date('Y-m-d H:i:s', time() - 2678400);
  $yesterday = date('Y-m-d H:i:s', time() - 86400);
  $me = $_SESSION['id'];


  // 取在线广告数
  $ad = admin_ad_info::get_ad_number_by_owner($DB, $me);

  // 取一周内激活数
  $transfer = 0;

  // 取一周内下载数
  $download = 0;

  // 取最近发生变化的5个广告
  $latest = admin_ad_info::get_ad_info_by_owner($DB, $me, '', '', '', 0, 5, 'status_time');

  // 取一个月内的流量统计

  $result = array(
    'code' => 0,
    'msg' => 'ok',
    'data' => array(
      'ad' => $ad,
      'transfer' => $transfer,
      'download' => $download,
      'money' => 0,
      'cash' => 0,
      'saved' => 0,
      'percent' => 0,
      'record' => $latest,
      'day' => array(),
    ),
  );
  exit(json_encode($result));
});

Macaw::post(BASE . 'file/', 'BaseController@upload');

Macaw::options(BASE . '(:all)', function () {
  header('Access-Control-Allow-Headers: accept, content-type');
  header('Access-Control-Allow-Methods: GET,PUT,POST,PATCH,DELETE');
});

Macaw::error(function() {
  echo '404 :: Not Found';
});
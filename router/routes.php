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
  $DB = require dirname(__FILE__) . '/../inc/pdo_slave.php';

  $week_ago = date('Y-m-d H:i:s', time() - 691200);
  $month_ago = date('Y-m-d H:i:s', time() - 2678400);
  $yesterday = date('Y-m-d H:i:s', time() - 86400);
  $me = $_SESSION['id'];

  // 取在线广告数
  $ad = admin_ad_info::get_ad_online_number_by_owner($DB, $me);
  $adids = admin_ad_info::get_adids_by_owner($DB, $me, $month_ago);
  $adids = implode("','", $adids);

  // 取一周内激活数
  require dirname(__FILE__) . '/../dev_inc/transfer_stat.class.php';
  $t = new transfer_stat();
  $transfer = $t->get_ad_transfer_by_ads($DB, $week_ago, $yesterday, $adids);
  $transfer_total = 0;
  foreach ( $transfer as $day ) {
    $transfer_total += $day['transfer'];
  }

  // 取一周内下载数
  $download = 0;

  // 取最近发生变化的5个广告
  $latest = admin_ad_info::get_ad_info_by_owner($DB, $me, '', '', '', 0, 5, 'status_time');

  // 取一个月内的流量统计
  $transfer = $t->get_ad_transfer_by_ads($DB, $month_ago, $yesterday, $adids);
  foreach ( $transfer as $key => $value ) {
    $transfer[$key]['date'] = $key;
  }


  $result = array(
    'code' => 0,
    'msg' => 'ok',
    'data' => array(
      'ad' => $ad,
      'total_transfer' => $transfer_total,
      'total_download' => $download,
      'money' => 0,
      'cash' => 0,
      'saved' => 0,
      'percent' => 0,
      'record' => $latest,
      'transfer' => $transfer,
    ),
  );
  exit(json_encode($result));
});

Macaw::post(BASE . 'file/', 'BaseController@upload');

Macaw::error(function() {
  echo '404 :: Not Found';
});
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/15
 * Time: 下午6:02
 */

use NoahBuscher\Macaw\Macaw;

function compare_by_step_rmb($a, $b) {
  return $b['step_rmb'] - $a['step_rmb'];
}

Macaw::get('ad/', function () {
  $DB = require '../config/pdo_admin.php';
  require_once dirname(__FILE__) . "/../dev_inc/admin_ad_info.class.php";
  require_once dirname(__FILE__)."/../dev_inc/admin_task.class.php";

  $query = isset($_REQUEST['query']) ? $_REQUEST['query'] : FALSE;
  $pagesize = isset($_REQUEST['pagesize']) ? (int)$_REQUEST['pagesize'] : 10;
  $page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 0;
  $start = $page * $pagesize;

  $ad_info = new admin_ad_info();
  $res = $ad_info->select_ad_join_source_all($DB, $query, 0);
  require_once(dirname(__FILE__) . '/../dev_inc/transfer_stat.class.php');
  $t=new transfer_stat(true);
  $transfer = transfer_stat::get_ads_last_7_days_transfer($DB);
  require_once(dirname(__FILE__) . '/../dev_inc/ad_quote.class.php');
  $adquote = ad_quote::get_ads_last_7_days_quote($DB);
  $stat = array();
  foreach ($transfer as $value) {
    $stat[$value['ad_id']][floor((time() - strtotime($value['transfer_date'])) / 86400) - 1]['transfer'] = $value['transfer_total'];
  }
  foreach ($adquote as $value) {
    $stat[$value['ad_id']][floor((time() - strtotime($value['quote_date'])) / 86400) - 1]['rmb'] = $value['rmb'];
  }
  $real = array();
  foreach ($stat as $key => $value) {
    for($i = 0; $i < 7; $i++) {
      if ($value[$i]['transfer'] > 0 && $value[$i]['rmb'] > 0) {
        $real[$key]['real'] = round($value[$i]['rmb'] / $value[$i]['transfer'] / 100, 2);
        break;
      }
    }
    if ($i > 3) {
      $real[$key]['style'] = true;
    }
  }
  $three_days_ago = date("Y-m-d", time() - 86400 * 3);
  $yesterday = date("Y-m-d");
  $last_three_days_transfer = $t->get_ad_transfer_by_ads($DB, $three_days_ago, $yesterday, '');
  require_once(dirname(__FILE__).'/../dev_inc/admin_task.class.php');
  $last_three_days_task = admin_task_stat::get_ad_task_outcome($DB, $three_days_ago, $yesterday);
  $ad_jobs = admin_ad_info::get_all_ad_job($DB);
  uasort($res, 'compare_by_step_rmb');
  $task = admin_task::get_task_count_group_by_ad($DB);
  $channels = array();
  $ads = array();
  $offline = array();
  $result = array();
  foreach ($res as $value) {
    $ad_name = $value['ad_name'];

    if (!$query && $value['status'] == 1) {
      continue;
    }

    $channel = $value['channel'];
    if (in_array($channel, $channels)) {
      $cid = array_search($channel, $channels);
    } else {
      $cid = count($channels);
      $channels[] = $channel;
    }
    if (in_array($ad_name, $ads)) {
      $aid = array_search($ad_name, $ads);
    } else {
      $aid = count($ads);
      $ads[] = $ad_name;
    }
    $id = $value['id'];
    $result[] = array_merge($value, array(
      'channel_id' => $cid,
      'aid' => $aid,
      'has_channel' => (boolean)$channel,
      'packname' => str_replace('.', '-', $value['pack_name']),
      'is_online' => $value['status'] == 0,
      'create_time' => substr($value['create_time'], 0, 10),
      'status_time' => substr($value['status_time'], 0, 10),
      'class' => $value['ad_app_type'] == 1 ? 'Android' : 'iPhone',
      'sdk_type' => $value['ad_sdk_type'] == 1 ? 'ad_list' : ($value['ad_sdk_type'] == 2 ? 'push' : 'wap'),
      'task' => isset($task[$value['id']]) && $task[$value['id']] > 0,
      'others' => $value['others'] != '' ? $value['others'] : '编辑注释',
      'weight' => $value['weight'] / 100,
      'real' => (float)$real[$id]['real'],
      'real_style' => $real[$id]['style'],
      'num' => (int)($value['rmb'] / $value['step_rmb']),
      'job_num' => (int)$ad_jobs[$id]['jobnum'],
      'job_time' => date("H:i", strtotime($ad_jobs[$id]['jobtime'])),
      'task_cost' => $last_three_days_transfer[$id]['transfer'] > 0 ? round($last_three_days_task[$id] / $last_three_days_transfer[$id]['transfer'] / 100, 2) : 0,
    ));
  }

  echo json_encode(array(
    'code' => 0,
    'msg' => 'get',
    'total' => count($result),
    'list' => array_slice($result, $start, $pagesize),
  ));
});
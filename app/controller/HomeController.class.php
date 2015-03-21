<?php
use diy\service\AD;
use diy\service\Auth;
use diy\service\Transfer;

/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/13
 * Time: 下午5:59
 */

class HomeController extends BaseController {
  public function home() {
    echo '<h1>controller ready</h1>';
  }

  public function dashboard() {
    $week_ago = date('Y-m-d', time() - 691200);
    $month_ago = date('Y-m-d', time() - 2678400);
    $yesterday = date('Y-m-d', time() - 86400);
    $me = $_SESSION['id'];
    $im_cp = $_SESSION['role'] == Auth::$CP_PERMISSION;
    $service = new AD();
    $transfer = new Transfer();

    // 取在线广告数
    $filter = array(
      ($im_cp ? 'create_user' : 'salesman') => $me,
      'status' => 0,
    );
    $ad = $service->get_ad_number($filter);
    $filter['start'] = $month_ago;
    $adids = $service->get_ad_ids($filter);

    // 取一周内激活数
    $transfers = $transfer->get_ad_transfer(array(
      'start' => $week_ago,
      'end' => $yesterday,
      'ad_id' => $adids,
    ));
    $transfer_total = $rmb_total = 0;
    foreach ( $transfers as $day ) {
      $transfer_total += $day['transfer'];
      $rmb_total += $day['rmb'];
    }

    // 取一周内下载数
    $download = 0;

    // 取最近发生变化的5个广告
    $latest = $service->get_ad_info(array(
      ($im_cp ? 'create_user' : 'salesman') => $me,
    ), 0, 5, 'status_time');
    foreach ( $latest as $id => $item ) {
      $item['id'] = $id;
      $latest[$id] = $item;
    }
    $latest = array_values($latest);

    // 取一个月内的流量统计
    $chart_transfer = $transfer->get_ad_transfer(array(
      'start' => $month_ago,
      'end' => $yesterday,
      'ad_id' => $adids,
    ), 'transfer_date');

    $result = array(
      'code' => 0,
      'msg' => 'ok',
      'data' => array(
        'ad' => $ad,
        'total_transfer' => $transfer_total,
        'total_download' => $download,
        'money' => $rmb_total / 100,
        'cash' => 0,
        'saved' => 0,
        'percent' => 0,
        'record' => $latest,
        'transfer' => $chart_transfer,
      ),
    );
    $this->output($result);
  }
} 
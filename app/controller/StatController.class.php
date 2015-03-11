<?php
use diy\service\AD;
use diy\service\Transfer;

/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/17
 * Time: 下午12:40
 */

class StatController extends BaseController {
  public function get_ad_stat() {
    $me = $_SESSION['id'];

    $today = date('Y-m-d');
    $week = date('Y-m-d', time() - 604800);
    $start = empty($_REQUEST['start']) ? $week : $_REQUEST['start'];
    $end = empty($_REQUEST['end']) ? $today : $_REQUEST['end'];
    $pagesize = empty($_REQUEST['pagesize']) ? 10 : (int)$_REQUEST['pagesize'];
    $page = (int)$_REQUEST['page'];
    $page_start = $page * $pagesize;
    $keyword = $_REQUEST['keyword'];
    $channel = $_REQUEST['channel'];
    $ad_name = $_REQUEST['ad_name'];

    $filter = array(
      'salesman' => $me,
      'start' => $start,
      'end' => $end,
      'keyword' => $keyword,
    );
    if ($channel) {
      $filter['channel'] = $channel;
    }
    if ($ad_name) {
      $filter['ad_name'] = $ad_name;
    }
    $ad_service = new AD();
    $ad_info = $ad_service->get_ad_info($filter, $page_start, $pagesize);
    $total = $ad_service->get_ad_number($filter);

    $service = new Transfer();
    $service->get_ad_transfer(array(
      'start' => $start,
      'end' => $end,
      'ad_id' => array_unique(array_keys($ad_info)),
    ), 'ad_id');

    $ad_stat = array();
    foreach ($ad_info as $id => $value) {
      if ($value['oversea'] == 1) {
        //本页面不显示海外广告的统计数据
        continue;
      }
      $ad = array_merge($value, array(
        'id' => $id,
        'device1' => isset($transfer_res[$id]) ? (int)$transfer_res[$id]['transfer'] : 0,
      ));
      $ad_stat[] = $ad;
    }

    $this->output(array(
      'code' => 0,
      'msg' => 'fetched',
      'total' => $total,
      'start' => $page_start,
      'list' => $ad_stat,
    ));
  }
} 
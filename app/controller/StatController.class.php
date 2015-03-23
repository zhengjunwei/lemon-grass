<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/17
 * Time: 下午12:40
 */

use diy\service\AD;
use diy\service\Auth;
use diy\service\IOS_Click;
use diy\service\Transfer;

class StatController extends BaseController {
  public function get_ad_stat() {
    list( $start, $end, $pagesize, $page_start, $filter ) = $this->getFilter();
    $ad_service = new AD();
    $ad_info = $ad_service->get_ad_info($filter, $page_start, $pagesize);
    $total = $ad_service->get_ad_number($filter);
    $ad_ids = array_unique(array_keys($ad_info));

    $service = new Transfer();
    $transfer_res = $service->get_ad_transfer(array(
      'start' => $start,
      'end' => $end,
      'ad_id' => $ad_ids,
    ), 'ad_id');
    $ios = new IOS_Click();
    $click_res = $ios->get_ad_click(array(
      'start' => $start,
      'end' => $end,
      'ad_id' => $ad_ids,
    ), 'ad_id');

    $ad_stat = array();
    foreach ($ad_info as $id => $value) {
      if ($value['oversea'] == 1) {
        //本页面不显示海外广告的统计数据
        continue;
      }
      $transfer = (int)$transfer_res[$id];
      $ad = array_merge($value, array(
        'id' => $id,
        'transfer' => $transfer,
        'click' => (int)$click_res[$id],
        'cost' => $ad_info['quote_rmb'] * $transfer,
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

  public function get_the_ad_stat($id) {
    $ad = new AD();
    if (!$ad->check_ad_owner($id)) {
      $this->exit_with_error(20, '您无法查询此广告', 401);
    }
    list($start, $end) = $this->getFilter($id);

    $info = $ad->get_ad_info(array('id' => $id), 0, 1);
    $service = new Transfer();
    $transfer_res = $service->get_ad_transfer(array(
      'start' => $start,
      'end' => $end,
      'ad_id' => $id,
    ), 'transfer_date');
    $ios = new IOS_Click();
    $click_res = $ios->get_ad_click(array(
      'start' => $start,
      'end' => $end,
      'ad_id' => $id,
    ), 'stat_date');

    $start = strtotime($start);
    $end = strtotime($end);
    $result = array();
    for ($i = $start; $i <= $end; $i += 86400) {
      $date = date('Y-m-d', $i);
      $transfer = (int)$transfer_res[$date];
      $result[] = array(
        'ad_id' => $id,
        'date' => $date,
        'transfer' => $transfer,
        'click' => (int)$click_res[$date],
        'cost' => $transfer * $info['quote_rmb'],
      );
    }

    $this->output(array(
      'code' => 0,
      'msg' => 'fetched',
      'total' => count($result),
      'start' => 0,
      'list' => $result,
    ));
  }

  public function get_ad_daily_stat($id, $date) {
    $ad = new AD();
    if (!$ad->check_ad_owner($id)) {
      $this->exit_with_error(20, '您无法查询此广告', 401);
    }
    list($start, $end) = $this->getFilter($id);

    $service = new Transfer();
    $result = $service->get_ad_transfer(array(
      'start' => $start,
      'end' => $end,
      'ad_id' => $id,
      'transfer_date' => $date,
    ), 'transfer_date');

    $this->output(array(
      'code' => 0,
      'msg' => 'fetched',
      'total' => count($result),
      'start' => 0,
      'list' => $result,
    ));
  }

  /**
   * @param bool $ad_id
   *
   * @return array
   */
  private function getFilter($ad_id = false) {

    $today      = date( 'Y-m-d' );
    $week       = date( 'Y-m-d', time() - 604800 );
    $start      = empty( $_REQUEST['start'] ) ? $week : $_REQUEST['start'];
    $end        = empty( $_REQUEST['end'] ) ? $today : $_REQUEST['end'];
    $pagesize   = empty( $_REQUEST['pagesize'] ) ? 10 : (int) $_REQUEST['pagesize'];
    $page       = (int) $_REQUEST['page'];
    $page_start = $page * $pagesize;
    $keyword    = $_REQUEST['keyword'];
    $channel    = $_REQUEST['channel'];
    $ad_name    = $_REQUEST['ad_name'];

    $filter = array(
      'start'                                 => $start,
      'end'                                   => $end,
      'keyword'                               => $keyword,
    );
    if ($ad_id) {
      $filter['id'] = $ad_id;
    } else {
      $me    = $_SESSION['id'];
      $im_cp = $_SESSION['role'] == Auth::$CP_PERMISSION;
      $filter[$im_cp ? 'create_user' : 'salesman'] = $me;
    }
    if ( $channel ) {
      $filter['channel'] = $channel;
    }
    if ( $ad_name ) {
      $filter['ad_name'] = $ad_name;
    }

    return array( $start, $end, $pagesize, $page_start, $filter );
  }
} 
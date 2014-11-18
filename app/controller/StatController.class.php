<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/17
 * Time: 下午12:40
 */

class StatController extends BaseController {
  public function get_ad_stat() {
    $DB = $this->get_pdo_read();
    $me = defined('DEBUG') ? 4 : $_SESSION['id'];

    $today = date('Y-m-d');
    $week = date('Y-m-d', time() - 604800);
    $start = empty($_REQUEST['start']) ? $week : $_REQUEST['start'];
    $end = empty($_REQUEST['end']) ? $today : $_REQUEST['end'];
    $pagesize = empty($_REQUEST['pagesize']) ? 10 : (int)$_REQUEST['pagesize'];
    $page = (int)$_REQUEST['page'];
    $page_start = $page * $pagesize;
    $keyword = $_REQUEST['keyword'];

    require_once(dirname(__FILE__) . '/../../dev_inc/admin_ad_info.class.php');
    $adinfo = admin_ad_info::get_ad_info_by_owner($DB, $me, $start, $end, $keyword, $page_start, $pagesize);
    $adids = implode("','", array_unique(array_keys($adinfo)));
    $total = admin_ad_info::get_ad_number_by_owner($DB, $me, $start, $end, $keyword);

    require_once(dirname(__FILE__) . '/../../dev_inc/transfer_stat.class.php');
    $t = new transfer_stat(true);
    $transfer_res = $t->get_ad_transfer_by_ads($DB, $start, $end, $adids);

    $DB = null;
    $channels = array();
    $ads = array();
    $ad_stat = array();
    foreach ($adinfo as $key => $value) {
      if ($value['oversea'] == 1) {
        //本页面不显示海外广告的统计数据
        continue;
      }
      $channel = $value['channel'];
      if (in_array($channel, $channels)) {
        $cid = array_search($channel, $channels);
      } else {
        $cid = count($channels);
        $channels[] = $channel;
      }
      $ad_name = $value['ad_name'];
      if (in_array($ad_name, $ads)) {
        $aid = array_search($ad_name, $ads);
      } else {
        $aid = count($ads);
        $ads[] = $ad_name;
      }
      $ad = array(
        'id' => $key,
        'channel_id' => $value['cid'],
        'channel' => $channel,
        'cid' => $cid,
        'ad_name' => $ad_name,
        'aid' => $aid,
        'ctime' => date('m-d', strtotime($value['create_time'])),
        'status' => $value['status'],
        'device1' => isset($transfer_res[$key]) ? (int)$transfer_res[$key]['transfer'] : 0,
      );
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
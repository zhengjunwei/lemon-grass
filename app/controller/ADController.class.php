<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/17
 * Time: 下午2:01
 */

class ADController extends BaseController {
  public function get_list() {
    $DB = $this->get_pdo_read();
    require_once dirname(__FILE__) . "/../../dev_inc/admin_ad_info.class.php";
    require_once dirname(__FILE__) . "/../../dev_inc/admin_task.class.php";

    $me = defined('DEBUG') ? DEBUG : $_SESSION['id'];

    $today = date('Y-m-d');
    $week = date('Y-m-d', time() - 604800);
    $start = isset($_REQUEST['start']) ? $_REQUEST['start'] : $week;
    $end = isset($_REQUEST['end']) ? $_REQUEST['end'] : $today;
    $keyword = isset($_REQUEST['$keyword']) ? $_REQUEST['$keyword'] : FALSE;
    $pagesize = isset($_REQUEST['pagesize']) ? (int)$_REQUEST['pagesize'] : 10;
    $page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 0;
    $page_start = $page * $pagesize;

    $ad_info = new admin_ad_info();
    $res = $ad_info->get_ad_infos_by_owner($DB, $me, $start, $end, $keyword, $page_start, $pagesize);
    $total = $ad_info->get_ad_number_by_owner($DB, $me, $start, $end, $keyword);

    require_once(dirname(__FILE__) . '/../../dev_inc/transfer_stat.class.php');
    $transfer = transfer_stat::get_ads_last_7_days_transfer($DB);

    require_once(dirname(__FILE__) . '/../../dev_inc/ad_quote.class.php');
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

    $ad_jobs = admin_ad_info::get_all_ad_job($DB);

    $channels = array();
    $ads = array();
    $result = array();
    foreach ($res as $value) {
      $ad_name = $value['ad_name'];
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
        'class' => $value['ad_app_type'] == 1 ? 'Android' : 'iPhone',
        'sdk_type' => $value['ad_sdk_type'] == 1 ? 'ad_list' : ($value['ad_sdk_type'] == 2 ? 'push' : 'wap'),
        'others' => $value['others'] != '' ? $value['others'] : '编辑注释',
        'weight' => $value['weight'] / 100,
        'real' => (float)$real[$id]['real'],
        'real_style' => $real[$id]['style'],
        'num' => $value['step_rmb'] != 0 ? (int)($value['rmb'] / $value['step_rmb']) : 0,
        'job_num' => (int)$ad_jobs[$id]['jobnum'],
        'job_time' => date("H:i", strtotime($ad_jobs[$id]['jobtime'])),
      ));
    }

    $this->output(array(
      'code' => 0,
      'msg' => 'get',
      'total' => $total,
      'list' => $result,
    ));
  }

  public function init($id) {
    $DB = $this->get_pdo_read();
    require_once dirname(__FILE__) . "/../../dev_inc/admin_ad_info.class.php";

    $ad_info = new admin_ad_info();
    $labels = $ad_info->select_ad_labels($DB);
    $init = array(
      'ad_type' => 0,
      'cate' => 1,
      'cpc_cpa' => 'cpa',
      'days' => 1,
      's_rmb' => 10,
      'ratio' => 1,
      'endtime' => date("Y-m-d H:i:s", time()+7776000),
      'put_level' => 3,
      'imsi' => 0,
      'put_net' => 0,
      'net_type' => $CM->all_net_types,
      'put_jb' => 0,
      'put_ipad' => 0,
      'owner' => -1,
      'feedback' => 0,
      'cycle' => 0,
      'salt' => substr(md5(time()), 0, 8),
      'platforms' => array(),
      'cates' => array(),
      'ad_types' => $labels,
      'is_android' => true,
      'url_type'=>'',
      'province_type' => 0,
      'provinces' => array(),
      'share_text'=>'',
      'oversea' => 0,
      'hours' => array(),
      'mins' => array(),
      'job_h' => 0,
      'job_i' => 0,
      'off_ymd' => date('Y-m-d'),
      'job_off' => 0,
      'off_h' => 0,
      'off_i' => 0,
      'job_edit' => true,
      'down_type' => 0,
      'advertisers' => array(),
      'advertiser_tag' => '0000',
      'has_permission' => 0,
      'has_os_black' => 0,
      'less_or_more' => 0,
    );
    if ($id === 'init') {
      return $this->output($init);
    }
  }
} 
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/17
 * Time: 下午2:01
 */

class ADController extends BaseController {
  static $T_CALLBACK = 't_adinfo_callback';
  static $T_IOS_INFO = 't_adinfo_ios';
  static $T_SOURCE = 't_ad_source';
  static $T_INFO = 't_adinfo';
  static $FIELDS_CALLBACK = array('put_jb', 'put_ipad', 'salt', 'click_url', 'ip', 'url_type', 'corp', 'http_param', 'process_name', 'down_type');
  static $FIELDS_CHANNEL = array('channel', 'channel_id', 'owner', 'channel_url', 'channel_user', 'channel_pwd', 'feedback', 'cycle');

  private function get_ad_info() {
    require_once dirname(__FILE__) . "/../../dev_inc/admin_ad_info.class.php";
    return new admin_ad_info();
  }

  /**
   * 取广告列表
   * @author Meathill
   * @since 0.1.0
   */
  public function get_list() {
    $DB = $this->get_pdo_read();
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
    $res = $ad_info->get_ad_info_by_owner($DB, $me, $start, $end, $keyword, $page_start, $pagesize);
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

  /**
   * 取新建广告的表单项，修改广告时当前内容
   * @author Meathill
   * @since 0.1.0
   * @param $id
   */
  public function init($id) {
    $DB = $this->get_pdo_read();
    $CM = $this->get_cm();
    $ad_info = $this->get_ad_info();

    $labels = $ad_info->select_ad_labels($DB);
    $init = array(
      'ad_app_type' => 1,
      'ad_type' => 0,
      'cate' => 1,
      'cpc_cpa' => 'cpa',
      's_rmb' => 10,
      'ratio' => 1,
      'put_level' => 3,
      'imsi' => 0,
      'put_net' => 0,
      'net_type' => 0,
      'put_jb' => 0,
      'put_ipad' => 0,
      'owner' => -1,
      'feedback' => 0,
      'cycle' => 0,
      'salt' => substr(md5(time()), 0, 8),
      'url_type'=>'',
      'province_type' => 0,
      'share_text'=>'',
      'down_type' => 0,
    );
    $options = array(
      'cates' => array(),
      'net_types' => $CM->all_net_types,
      'ad_types' => $labels,
      'provinces' => array(),
    );
    foreach ($CM->ad_cate as $key => $value) {
      $options['cates'][] = array(
        'key' => $key,
        'value' => $value,
      );
    }
    foreach ($CM->provinces as $key => $value) {
      $options['provinces'][] = array(
        'key' => $key,
        'value' => $value,
      );
    }

    if ($id === 'init') {
      $this->output(array(
        'code' => 0,
        'msg' => 'init',
        'ad' => $init,
        'options' => $options,
      ));
    }
    // 广告内容
    $res = $ad_info->get_ad_by_id($DB, $id);
    $ad_shoot = preg_replace('/^,|,$/', '', $res['ad_shoot']);

    // 上传文件记录
    $upload_log = $ad_info->select_upload_log($id, $DB);

    // 取广告主后台信息
    $channel = $ad_info->select_ad_source($id, $DB);
    $channel['owner'] = $channel['owner'] ? $channel['owner'] : 1;

    $options = array_merge($options, array(
      'apk_history' => $upload_log,
      'ad_url_full' => (substr($res['ad_url'], 0, 7) == 'upload/' ? 'http://www.dianjoy.com/dev/' : '') . $res['ad_url'],
      'shoots' => array_filter(preg_split('/,{2,}/', $ad_shoot)),
    ));
    $result = array_merge(init, $res, (array)$channel, array(

    ));

    $this->output(array(
      'code' => 0,
      'msg' => 'fetched',
      'ad' => $result,
      'options' => $options,
    ));
  }

  /**
   * 创建新广告
   * @author Meathill
   * @since 0.1.0
   */
  public function create() {
    $DB = $this->get_pdo_write();
    $CM = $this->get_cm();
    require dirname(__FILE__) . '/../../dev_inc/admin_location.class.php';
    require dirname(__FILE__) . '/../../app/utils/array.php';

    $id = $CM->id1();
    $attr = $this->get_post_data();

    if (strlen($attr['ad_text']) > 45) {
      $this->exit_with_error(1, '广告语不能超过45个字符', 400);
    }
    if (!$attr['pack_name'] || !$attr['ad_size'] || !$attr['ad_lib']) {
      $this->exit_with_error(2, '广告包相关信息没有填全', 400);
    }
    if ($attr['ad_app_type'] == 2) {
      if (strpos($attr['ad_url'], 'upload') !== 0 && $attr['ip'] != '' && $attr['click_url'] != '') {
        if ($attr['put_jb'] != 0) {
          $this->exit_with_error(10, '非越狱包请选择投放全部设备', 400);
        }
      } else if ($attr['put_jb'] != 1) {
        $this->exit_with_error(11, '越狱包请选择投放越狱设备', 400);
      }
      if ($attr['feedback'] >= 4 && $attr['feedback'] - $attr['put_jb'] != 4) {
        $this->exit_with_error(12, ' 数据反馈形式与投放目标不符,请重新审查', 400);
      }
    }

    // 取出分表数据
    $attr = array_omit($attr, self::$FIELDS_CALLBACK, self::$FIELDS_CHANNEL);
    $callback = array_pick($attr, self::$FIELDS_CALLBACK);
    $channel = array_pick($attr, self::$FIELDS_CHANNEL);

    // 插入广告信息
    $check = SQLHelper::insert($DB, self::$T_INFO, $attr);
    if (!$check) {
      var_dump(SQLHelper::$info);
      $this->exit_with_error(20, '插入广告失败', 400);
    }
    //广告投放地理位置信息
    if (count($attr['provinces'])) {
      $values = array();
      $params = array();
      $count = 0;
      foreach ($attr['provinces'] as $province_id) {
        $values[] = "('$id',':province$count')";
        $params[":province$count"] = $province_id;
      }
      $values = implode(',', $values);
      $check = admin_location::insert_ad_province($DB, $values, $params);
      if (!$check) {
        $this->exit_with_error(21, '插入投放地理位置失败', 400);
      }
    }
    // 记录平台专属数据
    $callback['id'] = $id;
    if ($attr['ad_app_type'] == 2) {
      $check = SQLHelper::insert($DB, self::$T_IOS_INFO, $callback);
      if (!$check) {
        $this->exit_with_error(22, '插入iOS专属数据失败', 400);
      }
    } else {
      $callback = array_pick($callback, 'salt', 'click_url', 'ip');
      $check = SQLHelper::insert($DB, self::$T_CALLBACK, $callback);
      if (!$check) {
        $this->exit_with_error(23, '插入Android回调信息失败', 400);
      }
    }
    // 添加广告主后台信息.
    $channel['id'] = $id;
    $check = SQLHelper::insert($DB, self::$T_SOURCE, $channel);
    if (!$check) {
      $this->exit_with_error(24, '插入广告主后台信息失败', 400);
    }

    $this->output(array(
      'code' => 0,
      'msg' => 'created',
      'ad' => array(
        'id' => $id
      ),
    ));
  }

  /**
   * 修改广告
   * 部分属性的修改不会直接体现在表中，而是以请求的方式存在
   * @author Meathill
   * @since 0.1.0
   * @param $id
   */
  public function update($id) {

  }
} 
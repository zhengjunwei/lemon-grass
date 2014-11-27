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
  public static $T_APPLY = 't_diy_apply';
  static $FIELDS_CALLBACK = array('put_jb', 'put_ipad', 'salt', 'click_url', 'ip', 'url_type', 'corp', 'http_param', 'process_name', 'down_type', 'open_url_type');
  static $FIELDS_CHANNEL = array('channel', 'cid', 'url', 'user', 'pwd', 'feedback', 'cycle');
  static $FIELDS_APPLY = array('status', 'today_left', 'job_num');

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
    $ad_info =  $this->get_ad_info();
    $me = defined('DEBUG') ? DEBUG : $_SESSION['id'];

    $keyword = isset($_REQUEST['$keyword']) ? $_REQUEST['$keyword'] : FALSE;
    $pagesize = isset($_REQUEST['pagesize']) ? (int)$_REQUEST['pagesize'] : 10;
    $page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 0;
    $page_start = $page * $pagesize;

    $res = $ad_info->get_ad_info_by_owner($DB, $me, '', '', $keyword, $page_start, $pagesize);
    $total = $ad_info->get_ad_number_by_owner($DB, $me, '', '', $keyword);
    $adids = array_keys(array_filter($res));

    // 取总投放量
    $rmb_out = $ad_info->get_rmb_out_by_ad($DB, $adids);

    // 取当前申请
    $apply = new \diy\service\Apply();
    $applies = $apply->get_list_by_id($adids);
    $applies_by_ad = array();
    foreach ( $applies as $id => $apply ) {
      $adid = $apply['adid'];
      if (!is_array($applies_by_ad[$adid])) {
        $applies_by_ad[$adid] = array();
      }
      unset($apply['adid']);
      $apply = array_filter($apply);
      $key = array_keys($apply)[0]; // 因为过滤掉了没有内容的键，又删掉了adid，只剩下要操作的key了
      $apply[$key . '_id'] = $id;
      $applies_by_ad[$adid][] = array_filter($apply);
    }

    $ad_jobs = admin_ad_info::get_all_ad_job($DB);

    $channels = array();
    $ads = array();
    $result = array();
    foreach ($res as $id => $value) {
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

      $apply = array();
      if (is_array($applies_by_ad[$id])) {
        foreach ( $applies_by_ad[$id] as $item ) {
          if (array_key_exists('set_rmb', $item)) {
            $item = array(
              'set_today_left_id' => $item['set_rmb_id'],
              'set_today_left' => $value['step_rmb'] != 0 ? $item['set_rmb'] / $value['step_rmb'] : 0,
            );
          }
          $apply = array_merge($apply, $item);
        }
      }

      $result[] = array_merge($value, $apply, array(
        'id' => $id,
        'channel_id' => $cid,
        'aid' => $aid,
        'packname' => str_replace('.', '-', $value['pack_name']),
        'class' => $value['ad_app_type'] == 1 ? 'Android' : 'iPhone',
        'others' => $value['others'] != '' ? $value['others'] : '编辑注释',
        'today_left' => $value['step_rmb'] != 0 ? (int)($value['rmb'] / $value['step_rmb']) : 0,
        'job_num' => (int)$ad_jobs[$id]['jobnum'],
        'job_time' => date("H:i", strtotime($ad_jobs[$id]['jobtime'])),
        'has_transfer' => $rmb_out[$id] > 0,
        'is_ready' => $value['status'] == 1 || $value['status'] == 0,
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
    $me = $_SESSION['id'];
    $attr = $this->get_post_data();

    $attr = $this->validate( $attr );

    // 拆分不同表的数据
    $callback = array_pick($attr, self::$FIELDS_CALLBACK);
    $channel = array_pick($attr, self::$FIELDS_CHANNEL);
    $attr = array_omit($attr, self::$FIELDS_CALLBACK, self::$FIELDS_CHANNEL, 'total_num');
    $attr['id'] = $callback['id'] = $channel['id'] = $id;
    $attr['status'] = 2; // 新建，待审核
    $attr['create_user'] = $channel['owner'] = $me;

    // 插入广告信息
    $check = SQLHelper::insert($DB, self::$T_INFO, $attr);
    if (!$check) {
      $this->exit_with_error(20, '插入广告失败', 400, SQLHelper::$info);
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
    if ($attr['ad_app_type'] == 2) {
      $check = SQLHelper::insert($DB, self::$T_IOS_INFO, $callback);
      if (!$check) {
        $this->exit_with_error(22, '插入iOS专属数据失败', 400);
      }
    } else if ($callback['click_url']) { // 有回调再插入
      $callback = array_pick($callback, 'id', 'salt', 'click_url', 'ip');
      $check = SQLHelper::insert($DB, self::$T_CALLBACK, $callback);
      if (!$check) {
        $this->exit_with_error(23, '插入Android回调信息失败', 400);
      }
    }
    // 添加广告主后台信息.
    $check = SQLHelper::insert($DB, self::$T_SOURCE, $channel);
    if (!$check) {
      $this->exit_with_error(24, '插入广告主后台信息失败', 400, SQLHelper::$info);
    }

    // 给运营发通知
    $notice = new Notification();
    $notice_status = $notice->send(array(
      'ad_id' => $id,
      'alarm_type' => Notification::$NEW_AD,
      'create_time' => date('Y-m-d H:i:s'),
    ));

    $this->output(array(
      'code' => 0,
      'msg' => 'created',
      'notice' => $notice_status ? '通知已发' : '通知失败',
      'ad' => array(
        'id' => $id
      ),
    ));
  }

  /**
   * 修改广告
   * 部分属性的修改不会直接体现在表中，而是以请求的方式存在
   * 针对状态`status`、每日投放量`job_num`、今日余量`today_left`的修改会产生申请
   * 其它修改会直接入库
   * @author Meathill
   * @since 0.1.0
   * @param $id
   * @param array [optional] $attr
   */
  public function update($id, $attr = null) {
    $DB = $this->get_pdo_write();
    require dirname(__FILE__) . '/../../dev_inc/admin_location.class.php';
    require dirname(__FILE__) . '/../../app/utils/array.php';

    $attr = $attr ? $attr : $this->get_post_data();

    // 发申请
    if (array_key_exists('status', $attr) || array_key_exists('job_num', $attr)
      || array_key_exists('today_left', $attr)) {
      return $this::send_apply($DB, $id, array_pick($attr, self::$FIELDS_APPLY));
    }

    $attr = $this->validate($attr, $id);
    // 拆分不同表的数据
    $callback = array_pick($attr, self::$FIELDS_CALLBACK);
    $channel = array_pick($attr, self::$FIELDS_CHANNEL);
    $attr = array_omit($attr, self::$FIELDS_CALLBACK, self::$FIELDS_CHANNEL, 'total_num');

    // 插入广告信息
    $check = SQLHelper::update($DB, self::$T_INFO, $attr, $id);
    if (!$check) {
      $this->exit_with_error(30, '修改广告失败', 400);
      var_dump(SQLHelper::$info);
    }
    //广告投放地理位置信息
    if (count($attr['provinces'])) {
      admin_location::del_by_ad($DB, $id);
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
        $this->exit_with_error(31, '修改投放地理位置失败', 400);
      }
    }
    // 记录平台专属数据
    if ($attr['ad_app_type'] == 2) {
      $check = SQLHelper::update($DB, self::$T_IOS_INFO, $callback, $id);
      if (!$check) {
        $this->exit_with_error(32, '修改iOS专属数据失败', 400);
      }
    } else if ($callback['click_url']) { // 有回调再插入
      $callback = array_pick($callback, 'id', 'salt', 'click_url', 'ip');
      $check = SQLHelper::update($DB, self::$T_CALLBACK, $callback, $id);
      if (!$check) {
        $this->exit_with_error(33, '修改Android回调信息失败', 400);
      }
    }
    // 添加广告主后台信息
    if ($channel) {
      $check = SQLHelper::update($DB, self::$T_SOURCE, $channel, $id);
      if (!$check) {
        $this->exit_with_error(34, '修改广告主后台信息失败', 400);
      }
    }

    $this->output(array(
      'code' => 0,
      'msg' => '修改完成',
      'data' => $attr,
    ));
  }

  /**
   * 删除广告
   * @param $id
   */
  public function delete($id) {
    $ad_info = $this->get_ad_info();
    $DB = $this->get_pdo_read();

    // 拒绝操作跑出量的广告
    $rmb_out = $ad_info->get_rmb_out_by_ad($DB, $id);
    if ($rmb_out[$id] > 0) {
      $this->exit_with_error(50, '此广告已经推广，不能删除。您可以将其下线。', 400);
    }

    // 拒绝操作别人的广告
    $me = $_SESSION['id'];
    $check = $ad_info->check_ad_owner($DB, $id, $me);
    if (!$check) {
      $this->exit_with_error(51, '您无权操作此广告', 403);
    }

    $attr = array(
      'status' => -1,
    );
    return $this->update($id, $attr);
  }

  /**
   * 发送申请
   * @param PDO $DB
   * @param $id
   * @param array $changed
   */
  private function send_apply(PDO $DB, $id, array $changed ) {
    $now = date('Y-m-d H:i:s');
    $attr = array(
      'userid' => $_SESSION['id'],
      'adid' => $id,
      'create_time' => $now,
    );

    // 对同一属性的修改不能同时有多个
    $service = new \diy\service\Apply();
    foreach ( $changed as $key => $value) {
      if ($key == 'today_left') { // 今日余量需转换成rmb
        $key = 'rmb';
        $DB = $this->get_pdo_read();
        $step_rmb = SQLHelper::get_attr($DB, self::$T_INFO, $id, 'step_rmb');
        $value = (int)$value * (int)$step_rmb;
      }
      $key = 'set_' . $key;
      if ($service->is_available_same_attr($id, $key)) {
        $this->exit_with_error(41, '该属性上次修改申请还未审批，不能再次修改', 400);
      }
      $attr[$key] = $value;
    }
    $check = SQLHelper::insert($DB, self::$T_APPLY, $attr);
    if (!$check) {
      $this->exit_with_error(40, '创建申请失败', 403);
    }

    // 给运营发通知
    $notice = new Notification();
    $notice_status = $notice->send(array(
      'ad_id' => $id,
      'alarm_type' => Notification::$EDIT_AD,
      'create_time' => $now,
    ));

    $this->output(array(
      'code' => 0,
      'msg' => 'apply received',
      'notice' => $notice_status ? '通知已发' : '通知失败',
      'data' => $attr,
    ));
  }


  /**
   * 校验用户修改的内容
   * @param array $attr
   * @param string [optional] $id
   * @return array
   */
  private function validate(array $attr, $id = '' ) {
    // 防XSS
    foreach ( $attr as $key => $value ) {
      $attr[$key] = htmlspecialchars(trim(strip_tags($value, ENT_QUOTES | ENT_HTML5)));
    }

    if ( array_key_exists('ad_text', $attr) && strlen( $attr['ad_text'] ) > 45 ) {
      $this->exit_with_error( 1, '广告语不能超过45个字符', 400 );
    }
    if (!$id && (! $attr['pack_name'] || ! $attr['ad_size'] || ! $attr['ad_lib'] )) {
      $this->exit_with_error( 2, '广告包相关信息没有填全', 400 );
    }
    if ( array_key_exists('ad_app_type', $attr) && $attr['ad_app_type'] == 2 ) {
      if ( strpos( $attr['ad_url'], 'upload' ) !== 0 && $attr['ip'] != '' && $attr['click_url'] != '' ) {
        if ( $attr['put_jb'] != 0 ) {
          $this->exit_with_error( 10, '非越狱包请选择投放全部设备', 400 );
        }
      } else if ( $attr['put_jb'] != 1 ) {
        $this->exit_with_error( 11, '越狱包请选择投放越狱设备', 400 );
      }
      if ( $attr['feedback'] >= 4 && $attr['feedback'] - $attr['put_jb'] != 4 ) {
        $this->exit_with_error( 12, ' 数据反馈形式与投放目标不符,请重新审查', 400 );
      }
    }

    // 对数据进行预处理
    if ($attr['net_type']) {
      if (in_array(0, $attr['net_type'])) {
        $attr['net_type'] = 0;
      } else {
        $attr['net_type'] = implode(',', $attr['net_type']);
      }
    }
    if ($attr['seq_rmb'] || $attr['step_rmb']) {
      $attr['seq_rmb'] = $attr['seq_rmb'] == '' ? (int)$attr['step_rmb'] : (int)$attr['seq_rmb'];
    }
    if (!$id) {
      $attr['create_time'] = date('Y-m-d H:i:s');
    }
    if ($attr['feedback']) {
      $attr['open_url_type'] = $attr['feedback'] == 4 ? 0 : 1;
    }

    return $attr;
  }
} 
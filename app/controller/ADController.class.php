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
  static $T_RMB = 't_adinfo_rmb';
  public static $T_APPLY = 't_diy_apply';
  static $FIELDS_CALLBACK = array('put_jb', 'put_ipad', 'salt', 'click_url', 'ip', 'url_type', 'corp', 'http_param', 'process_name', 'down_type', 'open_url_type');
  static $FIELDS_CHANNEL = array('channel', 'owner', 'cid', 'url', 'user', 'pwd', 'feedback', 'cycle');
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
    $me = $_SESSION['id'];

    $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : FALSE;
    $pagesize = isset($_REQUEST['pagesize']) ? (int)$_REQUEST['pagesize'] : 10;
    $page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 0;
    $page_start = $page * $pagesize;

    $res = $ad_info->get_ad_info_by_owner($DB, $me, '', '', $keyword, $page_start, $pagesize);
    $total = $ad_info->get_ad_number_by_owner($DB, $me, '', '', $keyword);
    $adids = array_keys(array_filter($res));
    $users = array();
    foreach ( $res as $value ) {
      $users[] = $value['execute_owner'];
    }

    // 取总投放量
    $rmb_out = $ad_info->get_rmb_out_by_ad($DB, $adids);

    // 取商务名单
    require dirname(__FILE__) . '/../../dev_inc/admin.class.php';
    $users = admin::get_user_info_by_id($DB, implode(',', array_filter(array_unique($users))));

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
      $apply = array_filter($apply, function ($value) {
        return isset($value);
      });
      // 同时有每日限量和今日余量说明是要修改每日限量
      if (array_key_exists('set_job_num', $apply) && array_key_exists('set_rmb', $apply)) {
        unset($apply['set_rmb']);
      }
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
              'set_today_left' => $item['set_rmb'],
            );
          }
          $apply = array_merge($apply, $item);
        }
      }

      $result[] = array_merge($value, $apply, array(
        'id' => $id,
        'channel_id' => $cid,
        'aid' => $aid,
        'execute_owner' => $users[$value['execute_owner']],
        'packname' => str_replace('.', '-', $value['pack_name']),
        'class' => $value['ad_app_type'] == 1 ? 'Android' : 'iPhone',
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
    require dirname(__FILE__) . '/../../dev_inc/admin.class.php';
    $DB = $this->get_pdo_read();
    $CM = $this->get_cm();
    $ad_info = $this->get_ad_info();

    $labels = $ad_info->select_ad_labels($DB);
    $sales = admin::get_all_sales($DB);
    $me = $_SESSION['id'];
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
      'feedback' => 0,
      'cycle' => 0,
      'salt' => substr(md5(time()), 0, 8),
      'url_type'=>'',
      'province_type' => 0,
      'share_text'=>'',
      'down_type' => 0,
      'owner' => $me,
    );
    $options = array(
      'cates' => array(),
      'net_types' => $CM->all_net_types,
      'ad_types' => $labels,
      'provinces' => array(),
      'sales' => array(),
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
    foreach ( $sales as $key => $value ) {
      $options['sales'][] = array(
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

    require dirname(__FILE__) . '/../../dev_inc/admin_location.class.php';
    // 广告内容
    $res = $ad_info->get_ad_info_by_id($DB, $id);
    $ad_shoot = preg_replace('/^,|,$/', '', $res['ad_shoot']);
    $ad_shoots = preg_split('/,+/', $ad_shoot);
    if (is_array($ad_shoots)) {
      foreach ( $ad_shoots as $key => $ad_shoot ) {
        $ad_shoots[$key] = $this->createCompletePath($ad_shoot);
      }
      $res['shoots'] = $ad_shoots;
    }
    $res['ad_url'] = $this->createCompletePath($res['ad_url']);
    $res['pic_path'] = $this->createCompletePath($res['pic_path']);

    // 上传文件记录
    $upload_log = $ad_info->select_upload_log($DB, $id);

    // 省份
    if ($res['province_type'] == 1) {
      $provinces = admin_location::get_provinces_by_ad($DB, $id);
      foreach ( $options['provinces'] as $key => $province ) {
        $options['provinces'][$key]['checked'] = in_array($province['key'], $provinces) ? 'checked' : '';
      }
    }

    $options = array_merge($options, array(
      'apk_history' => $upload_log,
    ));
    $result = array_merge($init, $res);

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
   * @param string $key
   */
  public function create($key) {
    if ($key != 'init') {
      $this->exit_with_error(10, '请求错误', 400);
    }
    $DB = $this->get_pdo_write();
    $CM = $this->get_cm();
    require dirname(__FILE__) . '/../../dev_inc/admin_location.class.php';
    require dirname(__FILE__) . '/../../app/utils/array.php';

    $id = $CM->id1();
    $me = $_SESSION['id'];
    $now = date('Y-m-d H:i:s');
    $attr = $this->get_post_data();

    $attr = $this->validate( $attr );

    // 拆分不同表的数据
    $callback = array_pick($attr, self::$FIELDS_CALLBACK);
    $channel = array_pick($attr, self::$FIELDS_CHANNEL);
    $attr = array_omit($attr, self::$FIELDS_CALLBACK, self::$FIELDS_CHANNEL, 'total_num');
    $attr['id'] = $callback['ad_id'] = $channel['id'] = $id;
    $attr['status'] = 2; // 新建，待审核
    $attr['create_user'] = $channel['execute_owner'] = $me;
    $attr['create_time'] = $now;

    //广告投放地理位置信息
    if ($attr['province_type'] == 1 && isset($attr['provinces'])) {
      if (!is_array($attr['provinces'])) {
        $attr['provinces'] = array((int)$attr['provinces']);
      }
      if (count($attr['provinces'])) {
        $check = admin_location::insert_ad_province($DB, $id, $attr['provinces']);
        if (!$check) {
          $this->exit_with_error(21, '插入投放地理位置失败', 400);
        }
      }
    }
    unset($attr['provinces']);

    // 插入广告信息
    $check = SQLHelper::insert($DB, self::$T_INFO, $attr);
    if (!$check) {
      $this->exit_with_error(20, '插入广告失败', 400, SQLHelper::$info);
    }
    // 插入消费记录
    $rmb = array(
      'id' => $id,
      'rmb' => 0,
      'rmb_in' => 0,
      'rmb_out' => 0,
    );
    $check = SQLHelper::insert($DB, self::$T_RMB, $rmb);
    if (!$check) {
      $this->exit_with_error(25, '插入消费记录失败', 400, SQLHelper::$info);
    }
    // 创建默认的深度任务
    require dirname(__FILE__) . '/../../dev_inc/admin_task.class.php';
    require dirname(__FILE__) . '/../../dev_inc/ADOperationLogger.class.php';
    $tasks = admin_task::get_task_default($DB);
    foreach ($tasks as $task) {
      $task_id = $CM->id1();
      $task_step_rmb = $task['step_rmb'];
      $type = $task['type'];
      $delta = $task['delta'];
      $name = $task['name'];
      $desc = $task['desc'];
      $param = $task['param'];
      $probability = $task['probability'];
      if ($task_id = admin_task::add_task($DB, $task_id, $id, $task_step_rmb, $type, $delta, $name, $desc, $now, $param, $probability)) {
        // log it
        $log = new ADOperationLogger($DB);
        $log->log($id, 'task', 'add', "$task_step_rmb, $type, $delta, $name, $desc, $param, $probability => $task_id");
      }
    }
    // 记录平台专属数据
    if ($attr['ad_app_type'] == 2) {
      $check = SQLHelper::insert($DB, self::$T_IOS_INFO, $callback);
      if (!$check) {
        $this->exit_with_error(22, '插入iOS专属数据失败', 400, SQLHelper::$info);
      }
    } else if ($callback['click_url']) { // 有回调再插入
      $callback = array_pick($callback, 'salt', 'click_url', 'ip');
      $callback['id'] = $id;
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
      'create_time' => $now,
    ));

    // 给运营发邮件
    $mail = new \diy\service\Mailer();
    $subject = '商务[' . $_SESSION['fullname'] . ']创建新广告：' . $attr['channel'] . ' ' . $attr['ad_name'];
    $mail->send(OP_MAIL, $subject, $mail->create('ad-new', $attr));

    $this->output(array(
      'code' => 0,
      'msg' => '创建广告成功。相关通知' . ($notice_status ? '已发' : '失败'),
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
   *
   * @param $id
   * @param array [optional] $attr
   *
   * @return null
   */
  public function update($id, $attr = null) {
    $DB = $this->get_pdo_write();
    require dirname(__FILE__) . '/../../dev_inc/admin_location.class.php';
    require dirname(__FILE__) . '/../../app/utils/array.php';

    $attr = $attr ? $attr : $this->get_post_data();

    // 需要发申请的修改
    if (array_key_exists('status', $attr) || array_key_exists('job_num', $attr)
      || array_key_exists('today_left', $attr)) {
      return $this::send_apply($DB, $id, $attr);
    }

    $attr = $this->validate($attr, $id);
    // 拆分不同表的数据
    $callback = array_pick($attr, self::$FIELDS_CALLBACK);
    $channel = array_pick($attr, self::$FIELDS_CHANNEL);
    $attr = array_omit($attr, self::$FIELDS_CALLBACK, self::$FIELDS_CHANNEL, 'total_num');

    // 更新广告信息
    $check = SQLHelper::update($DB, self::$T_INFO, $attr, $id);
    if (!$check) {
      $this->exit_with_error(30, '修改广告失败', 400);
    }

    $notice_status = false;
    if ($attr['others']) { // 发送一枚通知
      $notice = new Notification();
      $notice_status = $notice->send(array(
        'ad_id' => $id,
        'alarm_type' => Notification::$EDIT_AD_COMMENT,
        'create_time' => date('Y-m-d H:i:s'),
      ));

      $mail = new \diy\service\Mailer();
      $mail->send(OP_MAIL, '广告备注修改', $mail->create('ad-modified', array(
        'id' => $id,
      )));
    }

    //广告投放地理位置信息
    if (isset($attr['province_type'])) {
      admin_location::del_by_ad($DB, $id);
    }
    if ($attr['province_type'] == 1 && isset($attr['provinces'])) {
      if (!is_array($attr['provinces'])) {
        $attr['provinces'] = array($attr['provinces']);
      }
      if (count($attr['provinces'])) {
        $check = admin_location::insert_ad_province($DB, $id, $attr['provinces']);
        if (!$check) {
          $this->exit_with_error(31, '修改投放地理位置失败', 400);
        }
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
      'notice' => $notice_status ? 'ok' : 'fail',
      'ad' => $attr,
    ));
    return null;
  }

  /**
   * 删除广告
   *
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
    $this->update($id, $attr);
  }

  /**
   * 发送申请
   * @param PDO $DB
   * @param $id
   * @param array $changed
   *
   * @return null
   */
  private function send_apply(PDO $DB, $id, array $changed ) {
    $now = date('Y-m-d H:i:s');
    $attr = array(
      'userid' => $_SESSION['id'],
      'adid' => $id,
      'create_time' => $now,
      'send_msg' => trim($changed['message']),
    );
    unset($changed['message']);

    // 取欲修改的属性和值
    $key = '';
    $value = 0;
    $label = '';
    if (isset($changed['today_left'])) { // 今日余量需转换成rmb
      $key = 'rmb';
      $value = (int)$changed['today_left'];
      $label = '今日余量';
    }
    if (isset($changed['job_num'])) { // 每日投放需要看是否同时修改今日余量
      if (isset($changed['rmb'])) {
        $attr['set_rmb'] = $changed['job_num'];
      }
      $key = 'job_num';
      $value = $changed['job_num'];
      $label = '每日限量';
    }
    if (isset($changed['status'])) {
      $key = 'status';
      $value = $changed['status'];
      $label = '上/下线';
    }

    // 对同一属性的修改不能同时有多个
    $service = new \diy\service\Apply();
    if ($service->is_available_same_attr($id, 'set_' . $key)) {
      $this->exit_with_error(41, '该属性上次修改申请还未审批，不能再次修改', 400);
    }

    $attr['set_' . $key] = $value;
    $check = SQLHelper::insert($DB, self::$T_APPLY, $attr);
    if (!$check) {
      $this->exit_with_error(40, '创建申请失败', 403, SQLHelper::$info);
    }
    $attr['id'] = SQLHelper::$lastInsertId;

    // 给运营发通知
    $notice = new Notification();
    $notice_status = $notice->send(array(
      'ad_id' => $id,
      'uid' => $attr['id'],
      'alarm_type' => Notification::$EDIT_AD,
      'create_time' => $now,
    ));

    // 给运营发邮件
    $info = $this->get_ad_info()->get_ad_info_by_id($DB, $id);
    $mail = new \diy\service\Mailer();
    $mail->send(OP_MAIL, '广告属性修改', $mail->create('apply-new', array_merge($info, array(
      'label' => $label,
      'is_status' => $key == 'status',
      'value' => $value,
      'comment' => $attr['send_msg'],
      'owner' => $_SESSION['fullname'],
    ))));

    header('HTTP/1.1 201 Created');
    $this->output(array(
      'code' => 0,
      'msg' => 'apply received',
      'notice' => $notice_status ? '通知已发' : '通知失败',
      'data' => $attr,
    ));
    return;
  }


  /**
   * 校验用户修改的内容
   * @param array $attr
   * @param string [optional] $id
   * @return array
   */
  private function validate(array $attr, $id = '' ) {
    // 防XSS
    $attr = array_strip_tags($attr);

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

    // 去掉上传中的绝对路径
    $uploads = array('ad_url', 'ad_shoot', 'pic_path');
    foreach ( $uploads as $key ) {
      if ($attr[$key]) {
        $attr[$key] = str_replace(UPLOAD_URL, '', $attr[$key]);
      }
    }

    // 对数据进行预处理
    if (isset($attr['net_type'])) {
      if (is_array($attr['net_type'])) {
        if (in_array(0, $attr['net_type'])) {
          $attr['net_type'] = 0;
        } else {
          $attr['net_type'] = implode(',', $attr['net_type']);
        }
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
    // TODO 将来考虑建一个专门的通知表，存储不需要运营操作，但他们应该知悉的内容
    if (isset($attr['message'])) {
      $attr['others'] = $attr['message'];
      unset($attr['message']);
    }

    return $attr;
  }

  /**
   * 返回完整路径
   * @param string $url
   *
   * @return string
   */
  private function createCompletePath( $url ) {
    return ( preg_match( '/^upload\//', $url ) ? UPLOAD_URL : '' ) . $url;
  }
} 
<?php
use diy\service\Apply;

/**
 * Created by PhpStorm.
 * Date: 2014/11/23
 * Time: 23:09
 * @overview 
 * @author Meatill <lujia.zhai@dianjoy.com>
 * @since 
 */

class ApplyController extends BaseController {
  /**
   * @return Apply
   */
  private function get_service() {
    return new Apply();
  }

  /**
   * 取个人的所有申请
   */
  public function get_list() {
    $me = $_SESSION['id'];
    $keyword = isset($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
    $page = (int)$_REQUEST['page'];
    $pagesize = isset($_REQUEST['pagesize']) ? (int)$_REQUEST['pagesize'] : 10;
    $start = $page * $pagesize;

    $service = $this->get_service();
    $applies = $service->get_list($me, $keyword, $start, $pagesize);
    $keys = array('status', 'job_num', 'rmb', 'ad_url');
    $labels = array(
      'status' => '上下线',
      'job_num' => '每日限量',
      'rmb' => '今日余量',
      'ad_url' => '替换包',
    );
    $today = mktime(0, 0, 0);
    $expires = array();
    $handler = array();

    foreach ( $applies as $index => $apply ) {
      foreach ( $keys as $key ) {
        $s_key = 'set_' . $key;
        if (isset($apply[$s_key])) {
          $apply['attr'] = $labels[$key];
          $apply['after'] = $apply[$s_key];
          if (!$apply['handler']) { // 尚未处理，取之前的值
            $apply['before'] = $service->get_ad_attr($apply['adid'], $key);
          }
          if ($key == 'rmb') {
            // 如果是今日之前的申请，自动作废
            if (strtotime($apply['create_time']) < $today) {
              $expires[] = $apply['id'];
              unset($applies[$index]);
              break;
            }
            $step_rmb = $service->get_ad_attr($apply['adid'], 'step_rmb');
            $apply['after'] = $apply['after'] / $step_rmb;
            $apply['before'] = $apply['before'] / $step_rmb;
          }
          $apply['is_url'] = $key == 'ad_url';
          $apply['is_status'] = $key == 'status';
          $applies[$index] = $apply;
          break;
        }
      }
      $handler[] = $apply['handler'];
    }

    // 作废申请
    $service->update(array(
      'status' => Apply::EXPIRED
    ), $expires);

    // 取用户姓名
    $handlers = implode(',', array_filter(array_unique($handler)));
    if ($handlers) {
      require dirname(__FILE__) . '/../../dev_inc/admin.class.php';
      $users = admin::get_user_info_by_id($this->get_pdo_read(), $handlers);
      foreach ( $applies as $index => $apply ) {
        $applies[$index]['handler'] = isset($users[$apply['handler']]) ? $users[$apply['handler']] : $apply['handler'];
      }
    }

    $total = $service->get_total_number($me, $keyword);
    $this->output(array(
      'code' => 0,
      'msg' => 'fetched',
      'total' => $total,
      'list' => array_values($applies),
    ));
  }

  /**
   * 撤回某个申请
   * @param $id
   */
  public function delete($id) {
    $me = $_SESSION['id'];
    $service = $this->get_service();

    // 禁止操作别人的申请
    if (!$service->is_owner($id, $me)) {
      $this->exit_with_error(10, '您无权操作此申请', 403);
    }

    // 禁止操作已操作的申请
    if (!$service->is_available($id)) {
      $this->exit_with_error(11, '此申请已处理，您不能再次操作', 403);
    }

    $attr = array(
      'handler' => $me,
      'status' => Apply::WITHDRAWN,
    );
    $check = $service->update($attr, $id);
    if (!$check) {
      $this->exit_with_error(20, '操作失败', 400);
    }
    $this->output(array(
      'code' => 0,
      'msg' => 'deleted',
    ));
  }
} 
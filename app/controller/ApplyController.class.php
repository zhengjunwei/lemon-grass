<?php
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
   * @return \diy\service\Apply
   */
  private function get_service() {
    return new \diy\service\Apply();
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
    $total = $service->get_total_number($me, $keyword);
    $keys = array('status', 'job_num', 'rmb');
    $labels = array(
      'status' => '上下线',
      'job_num' => '每日限量',
      'rmb' => '今日余量',
    );

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
            $step_rmb = $service->get_ad_attr($apply['adid'], 'step_rmb');
            $apply['after'] = $apply['after'] / $step_rmb;
            $apply['before'] = $apply['before'] / $step_rmb;
          }
          $applies[$index] = $apply;
          break;
        }
      }
    }


    $this->output(array(
      'code' => 0,
      'msg' => 'fetched',
      'total' => $total,
      'list' => $applies,
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
      'status' => \diy\service\Apply::WITHDRAWN,
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
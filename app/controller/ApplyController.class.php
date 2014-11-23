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

  public function get_list() {
    $me = $_SESSION['id'];
    $keyword = isset($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
    $page = (int)$_REQUEST['page'];
    $pagesize = isset($_REQUEST['pagesize']) ? (int)$_REQUEST['pagesize'] : 10;
    $start = $page * $pagesize;

    $service = $this->get_service();
    $applies = $service->get_list($me, $keyword, $start, $pagesize);
    $total = $service->get_total_number($me, $keyword);

    $this->output(array(
      'code' => 0,
      'msg' => 'fetched',
      'total' => $total,
      'list' => $applies,
    ));
  }

  public function delete($id) {
    $me = $_SESSION['id'];
    $service = $this->get_service();

    // 禁止操作别人的申请
    if (!$service->is_owner($id, $me)) {
      $this->exit_with_error(10, '您无权操作此申请', 403);
    }

    $attr = array(
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
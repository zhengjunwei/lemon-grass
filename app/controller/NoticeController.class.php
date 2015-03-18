<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/12/23
 * Time: 下午3:40
 */

use diy\service\Notification;

class NoticeController extends BaseController {
  private function get_service() {
    return new Notification();
  }
  public function get_list() {
    $service = $this->get_service();
    $me = (int)$_SESSION['id'];
    $role = (int)$_SESSION['role'];
    $latest = (int)$_GET['latest'];

    $alarms = $service->get_notice($me, $role, $latest);
    $this->output(array(
      'code' => 0,
      'msg' => 'fetched',
      'list' => $alarms,
    ));
  }

  public function delete($id) {
    $service = $this->get_service();
    $me = (int)$_SESSION['id'];

    $check = $service->set_status($id, Notification::$HANDLED, $me);
    if (!$check) {
      $this->exit_with_error(1, '操作失败', 400);
    }

    $this->output(array(
      'code' => 0,
      'msg' => 'checked',
    ));
  }
}
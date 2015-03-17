<?php
/**
 * 通知类
 * User: meathill
 * Date: 13-12-19
 * Time: 上午10:59
 */

namespace diy\service;

use Mustache_Engine;
use PDO;
use SQLHelper;

class Notification extends Base {
  const LOG = 't_admin_alarm_log';

  static $NEW_AD = 20;
  static $EDIT_AD = 21;
  public static $REPLACE_AD = 28;
  static $EDIT_AD_COMMENT = 26;

  static $NORMAL = 0;
  static $HANDLED = 1;

  public function send($attr) {
    $DB = $this->get_write_pdo();
    $sql = SQLHelper::create_insert_sql(self::LOG, $attr);
    $params = SQLHelper::get_parameters($attr);
    $state = $DB->prepare($sql);
    return $state->execute($params);
  }

  public function get_notice($admin_id, $role, $latest) {
    $DB = $this->get_read_pdo();
    $m = new Mustache_Engine();
    $ad_service = new AD();

    $sql = "SELECT `type`
            FROM `t_alarm_group`
            WHERE `group`=$role";
    $types = $DB->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    $types = implode(',', $types);
    $type_sql = $types ? " OR `alarm_type` IN ($types)" : '';

    // 只取最近一周，再早的估计也没啥处理的必要了
    $date = date('Y-m-d', time() - 86400 * 6);
    $sql = "SELECT a.`id`, `uid`, `user_id`, `app_id`, `ad_id`, `status`,
              `create_time`, `op_time`, `description`, `handler`
            FROM `t_admin_alarm_log` a LEFT JOIN `t_alarm_type` t ON a.alarm_type=t.id
            WHERE (`admin_id`='$admin_id' $type_sql)
              AND `create_time`>'$date' AND `status`=0 AND a.`id`>$latest
            ORDER BY `id` DESC";
    $alarms = $DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($alarms as &$alarm) {
      $alarm['id'] = (int)$alarm['id'];
      if ($alarm['ad_id']) {
        if (strlen($alarm['ad_id']) == 32) {
          $ad = $ad_service->get_ad_info(array('id' => $alarm['ad_id']), 0, 1);
          $alarm['name'] = $ad['ad_name'];
        }
      }
      $alarm['status'] = (int)$alarm['status'];
      $alarm['handler'] = $m->render($alarm['handler'], $alarm);
    }

    return $alarms;
  }

  public function get_notice_by_uid($uid) {
    $DB = $this->get_read_pdo();
    $sql = "SELECT `id`
            FROM `t_admin_alarm_log`
            WHERE `uid`=:uid";
    $state = $DB->prepare($sql);
    $state->execute(array(':uid' => $uid));
    return $state->fetchColumn();
  }

  public function set_status( $id, $HANDLED ) {
    $DB = $this->get_write_pdo();
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE `t_admin_alarm_log`
            SET `status`=$HANDLED, `op_time`='$now'
            WHERE `id`=:id";
    $state = $DB->prepare($sql);
    return $state->execute(array(':id' => $id));
  }
}
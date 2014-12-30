<?php
/**
 * 通知类
 * User: meathill
 * Date: 13-12-19
 * Time: 上午10:59
 */

class Notification extends \diy\service\Base {
  const LOG = 't_admin_alarm_log';

  static $NEW_AD = 20;
  static $EDIT_AD = 21;
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
    require dirname(__FILE__) . '/../../dev_inc/admin_ad_info.class.php';
    $ad_info = new admin_ad_info();
    $DB = $this->get_read_pdo();
    $m = new Mustache_Engine();

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
          $ad = $ad_info->get_ad_info_by_id($DB, $alarm['ad_id']);
          $alarm['name'] = $ad['ad_name'];
        }
      }
      $alarm['status'] = (int)$alarm['status'];
      $alarm['handler'] = $m->render($alarm['handler'], $alarm);
    }

    return $alarms;
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
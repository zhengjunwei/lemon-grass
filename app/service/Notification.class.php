<?php
/**
 * 通知类
 * User: meathill
 * Date: 13-12-19
 * Time: 上午10:59
 */

class Notification extends \diy\service\Base {
  const LOG = 't_admin_alarm_log';
  const TYPE = 't_alarm_type';

  static $NEW_AD = 20;
  static $EDIT_AD = 21;

  public function send($attr) {
    $DB = $this->get_write_pdo();
    $sql = SQLHelper::create_insert_sql(self::LOG, $attr);
    $params = SQLHelper::get_parameters($attr);
    $state = $DB->prepare($sql);
    return $state->execute($params);
  }
}
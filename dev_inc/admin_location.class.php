<?php
/**
 * 处理关于省份的操作
 * User: woddy
 * Date: 14-9-2
 * Time: 下午3:28
 */
include_once(dirname(__FILE__) . '/../inc/location.class.php');
class admin_location extends location {
  static function insert_ad_province(PDO $DB, $values, $params) {
    $sql = "INSERT INTO `t_ad_province`
            (`ad_id`, `province_id`)
            VALUES $values";
    $state = $DB->prepare($sql);
    return $state->exec($params);
  }
}

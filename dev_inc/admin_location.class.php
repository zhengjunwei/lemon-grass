<?php
/**
 * 处理关于省份的操作
 * User: woddy
 * Date: 14-9-2
 * Time: 下午3:28
 */
include_once(dirname(__FILE__) . '/../inc/location.class.php');
class admin_location extends location {
  static function get_provinces_by_ad(PDO $DB, $adid) {
    $sql = "SELECT `province_id`
            FROM `t_ad_province`
            WHERE `ad_id`=:id";
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $adid));
    return $state->fetchAll(PDO::FETCH_ASSOC);
  }
  static function insert_ad_province(PDO $DB, $id, $provinces) {
    $values = array();
    $params = array();
    $count = 0;
    foreach ($provinces as $province) {
      $values[] = "('$id', :province$count)";
      $params[":province$count"] = $province;
      $count++;
    }
    $values = implode(',', $values);
    $sql = "INSERT INTO `t_ad_province`
            (`ad_id`, `province_id`)
            VALUES $values";
    $state = $DB->prepare($sql);
    return $state->execute($params);
  }

  static function del_by_ad(PDO $DB, $ad_id) {
    $sql = "DELETE FROM `t_ad_province`
            WHERE `ad_id`=:ad_id";
    $state = $DB->prepare($sql);
    return $state->execute(array(
      ':ad_id' => $ad_id,
    ));
  }
}

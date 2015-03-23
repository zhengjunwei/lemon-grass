<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 15/3/23
 * Time: 下午1:29
 */

namespace diy\service;


use diy\utils\Utils;
use PDO;

class IOS_Click extends Base {
  public function get_ad_click( $filters, $group ) {
    $DB = $this->get_stat_pdo();
    $filter = $this->parse_filter($filters);
    $group_field = $group ? "`$group`," : "";
    $sql = "SELECT $group_field SUM(`num`) AS `num`
            FROM `s_ios_click`
            WHERE $filter";
    if ($group) {
      $sql .= "\nGROUP BY `$group`";
    }
    return $DB->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
  }

  protected function parse_filter($filters, $is_append = false) {
    $spec = array('start', 'end');
    $pick = Utils::array_pick($filters, $spec);
    $filters = Utils::array_omit($filters, $spec);
    $result = parent::parse_filter($filters, $is_append);
    foreach ($pick as $key => $value) {
      switch ($key) {
        case 'start':
          $result .= " AND `stat_date`>='$value'";
          break;

        case 'end':
          $result .= " AND `stat_date`<='$value'";
          break;
      }
    }
    return $result;
  }
}
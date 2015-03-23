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

class IOS_Stat extends Base {
  const HOUR = 'HOUR';
  const DATE = 'DATE';

  public function get_ad_click( $filters, $group, $type = 'DATE' ) {
    $DB = $this->get_stat_pdo();
    $filter = $this->parse_filter($filters);
    $group_field = $group ? "$type(`$group`)," : '';
    $sql = "SELECT $group_field SUM(`num`) AS `num`
            FROM `s_ios_click`
            WHERE $filter";
    if ($group) {
      $sql .= "\nGROUP BY $type(`$group`)";
    }
    return $DB->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
  }

  public function get_ad_transfer( $filters, $group, $type = 'DATE') {
    $DB = $this->get_stat_pdo();
    $filter = $this->parse_filter($filters);
    $group_field = $group ? "$type(`$group`)," : '';
    $sql = "SELECT $group_field, `num`
            FROM `s_ios_transfer`
            WHERE $filter";
    if ($group) {
      $sql .= "\nGROUP BY $type(`$group`)";
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

        case 'date':
          $result .= " AND DATE(`stat_date`)='$value'";
          break;
      }
    }
    return $result;
  }
}
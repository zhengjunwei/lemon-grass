<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 15/2/26
 * Time: 下午5:30
 */

namespace diy\service;

use diy\utils\Utils;
use PDO;

class Transfer extends Base {
  /**
   * 取广告统计
   * @param array $filters
   * @param string $group
   *
   * @return array
   */
  public function get_ad_transfer( array $filters, $group = '' ) {
    $DB = $this->get_read_pdo();
    $filter_sql = $this->parse_filter($filters);
    $group_sql = $group ? "`$group`," : '';
    $sql = "SELECT $group_sql SUM(`transfer_total`) AS `transfer`
            FROM `s_transfer_stat_ad`
            WHERE $filter_sql";
    if ($group) {
      $sql .= " \nGROUP BY `$group`";
    }
    return $DB->query($sql)->fetchAll($group ? PDO::FETCH_KEY_PAIR : PDO::FETCH_COLUMN);
  }

  protected function parse_filter($filters, $is_append = false) {
    $spec = array('start', 'end');
    $pick = Utils::array_pick($filters, $spec);
    $filters = Utils::array_omit($filters, $spec);
    $result = parent::parse_filter($filters, $is_append);
    foreach ($pick as $key => $value) {
      switch ($key) {
        case 'start':
          $result .= " AND `transfer_date`>='$value'";
          break;

        case 'end':
          $result .= " AND `transfer_date`<='$value'";
          break;
      }
    }
    return $result;
  }
}
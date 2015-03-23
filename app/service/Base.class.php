<?php
/**
 * Created by PhpStorm.
 * Date: 2014/11/23
 * Time: 23:19
 * @overview 
 * @author Meatill <lujia.zhai@dianjoy.com>
 * @since 
 */

namespace diy\service;


use PDO;

class Base {

  protected $DB;
  protected $DB_write;
  protected $DB_stat;

  /**
   * @return PDO
   */
  protected function get_read_pdo() {
    $this->DB = $this->DB ? $this->DB : require dirname(__FILE__) . '/../connector/pdo_slave.php';
    return $this->DB;
  }

  /**
   * @return PDO
   */
  protected function get_write_pdo() {
    $this->DB_write = $this->DB_write ? $this->DB_write : require dirname(__FILE__) . '/../connector/pdo.php';
    return $this->DB_write;
  }

  protected function get_stat_pdo() {
    $this->DB_stat = $this->DB_stat ? $this->DB_stat : require dirname(__FILE__) . '/../connector/pdo_stat_read_remote.php';
    return $this->DB_stat;
  }

  public function __construct() {

  }

  /**
   * 根据传入的过滤数组取出过滤sql
   *
   * @param array $filters
   * @param bool $is_append 是否为追加条件
   *
   * @return string
   */
  protected function parse_filter($filters, $is_append = false) {
    $sql = '';
    if (is_array($filters)) {
      foreach ($filters as $key => $filter) {
        if (isset($filter)) {
          $point = strpos($key, '.');
          $key = $point !== false ? substr($key, 0, $point + 1) . '`' . substr($key, $point + 1) . '`' : "`$key`";
          if (is_array($filter)) {
            $filter = implode("','", $filter);
            $sql .= " AND $key IN ('$filter')";
          } else {
            $sql .= " AND $key='$filter'";
          }
        }
      }
    }
    return $is_append ? $sql : substr($sql, 5);
  }
} 
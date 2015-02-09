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

  /**
   * @return PDO
   */
  protected function get_read_pdo() {
    $this->DB = $this->DB ? $this->DB : require dirname(__FILE__) . '/../../inc/pdo_slave.php';
    return $this->DB;
  }

  /**
   * @return PDO
   */
  protected function get_write_pdo() {
    $this->DB_write = $this->DB_write ? $this->DB_write : require dirname(__FILE__) . '/../../inc/pdo.php';
    return $this->DB_writeDB;
  }

  public function __construct() {

  }

  /**
   * 根据传入的过滤数组取出过滤sql
   *
   * @param array $filters
   * @return string
   */
  protected function parse_filter($filters) {
    $sql = '';
    if (is_array($filters)) {
      foreach ($filters as $key => $filter) {
        if (isset($filter)) {
          if (is_array($filter)) {
            $filter = implode("','", $filter);
            $sql .= " AND `$key` IN ('$filter')";
          } else {
            $sql .= " AND `$key`='$filter'";
          }
        }
      }
    }
    return $sql;
  }
} 
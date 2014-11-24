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
    $DB = $this->DB ? $this->DB : require dirname(__FILE__) . '/../../config/pdo_test.php';
    return $DB;
  }

  /**
   * @return PDO
   */
  protected function get_write_pdo() {
    $DB = $this->DB_write ? $this->DB_write : require dirname(__FILE__) . '/../../config/pdo_test.php';
    return $DB;
  }

  public function __construct() {

  }
} 
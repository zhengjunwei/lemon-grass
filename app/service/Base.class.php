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

  /**
   * @return PDO
   */
  protected function get_read_pdo() {
    return require dirname(__FILE__) . '/../../config/pdo_test.php';
  }

  /**
   * @return PDO
   */
  protected function get_write_pdo() {
    return require dirname(__FILE__) . '/../../config/pdo_test.php';
  }

  public function __construct() {

  }
} 
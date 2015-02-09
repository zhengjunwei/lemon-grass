<?php
/**
 * Created by PhpStorm.
 * User: 路佳
 * Date: 2015/2/6
 * Time: 18:24
 */

namespace diy\service;


use PDO;

class User extends Base {
  public function get_user_info($filters) {
    $DB = $this->get_read_pdo();
    $filter_sql = $this->parse_filter($filters);
    $sql = "SELECT `id`, `NAME`
            FROM `t_admin`
            WHERE `status`=0 $filter_sql";
    return $DB->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
  }
}
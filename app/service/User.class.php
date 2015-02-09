<?php
/**
 * Created by PhpStorm.
 * User: 路佳
 * Date: 2015/2/6
 * Time: 18:24
 */

namespace diy\service;


class User extends Base {
  public function get_user_info($filters) {
    $DB = $this->get_read_pdo();
    $sql = "SELECT `id`, `NAME`
            FROM `t_admin`
            WHERE `id` IN ($owner)";
    return $DB->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
  }
}
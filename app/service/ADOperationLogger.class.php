<?php
/**
 * Date: 13-10-23
 * Time: 下午2:17
 * @overview log ad operations
 * @author Meathill <lujia.zhai@dianjoy.com>
 */
namespace diy\service;

use PDO;

class ADOperationLogger extends Base {
  const TABLE = 't_ad_operation_log';
  private $logs;

  public function add($adid, $type, $action, $comment) {
    $this->logs[] = "(" . $_SESSION['id'] . ", '$adid', '$type', '$action', '$comment',";
  }

  public function log($adid, $type, $action, $comment = '', $is_ok = 0) {
    $DB = $this->get_write_pdo();
    $now = date('Y-m-d H:i:s');
    $sql = $this->getTemplate() . "(" . $_SESSION['id'] . ", '$adid', '$type', '$action', '$comment', $is_ok, '$now')";
    return $DB->exec($sql);
  }

  public function logAll($is_ok = 0) {
    $DB = $this->get_write_pdo();
    $now = date('Y-m-d H:i:s');
    $addon = " $is_ok, '$now'),";
    $sql = $this->getTemplate() . implode($addon, $this->logs) . $addon;
    $this->logs = array();
    return $DB->exec(substr($sql, 0, -1));
  }

  public function get_list($id, $start, $end) {
    $DB = $this->get_read_pdo();
    $sql = "SELECT a.*,b.`NAME`
            FROM `t_ad_operation_log` AS a JOIN `t_admin` AS b ON a.`user`=b.`id`
            WHERE `adid`=:id AND !(`type`='quote' && `action`='insert')
              AND `is_ok`=0 AND `datetime`>=:start AND date(`datetime`)<=:end
            ORDER BY `datetime` DESC,`id` DESC";
    $state = $DB->prepare($sql);
    $state->execute(array(
      ':id' => $id,
      ':start' => $start,
      ':end' => $end,
    ));
    return $state->fetchAll(PDO::FETCH_ASSOC);
  }

  private function getTemplate() {
    $sql = 'INSERT INTO `t_ad_operation_log`
            (`user`, `adid`, `type`, `action`, `comment`, `is_ok`, `datetime`)
            VALUES ';
    return $sql;
  }

  public function get_log( $filters ) {
    $DB = $this->get_read_pdo();
    $filter = $this->parse_filter($filters);
    $sql = "SELECT l.*, a.`NAME`
            FROM `t_ad_operation_log` AS l JOIN `t_admin` AS a ON l.`user`=a.`id`
            WHERE $filter
            ORDER BY l.`id` DESC
            LIMIT 1";
    return $DB->query($sql)->fetch(PDO::FETCH_ASSOC);
  }
}
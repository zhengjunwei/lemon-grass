<?php
/**
 * Created by PhpStorm.
 * Date: 2014/11/23
 * Time: 23:13
 * @overview 
 * @author Meatill <lujia.zhai@dianjoy.com>
 * @since 
 */

namespace diy\service;

use PDO;

class Apply extends Base {
  static $TABLE = 't_diy_apply';
  const NORMAL = 0;
  const ACCEPTED = 1;
  const DECLINED = 2;
  const WITHDRAWN = 3;

  public function get_ad_attr( $adid, $key ) {
    $DB = $this->get_read_pdo();
    $sql = "SELECT `$key`
            FROM `t_adinfo`
            WHERE `id`=:id";
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $adid));
    return $state->fetchColumn();
  }

  public function get_list($userid, $keyword = '', $start = 0, $pagesize = 10) {
    $DB = $this->get_read_pdo();
    $keyword_sql = $keyword ? " AND (`ad_name` LIKE :keyword OR `channel` LIKE :keyword)" : '';
    $sql = "SELECT a.`id`, `adid`, `set_status`, `set_job_num`, `set_rmb`, a.`create_time`, `handle_time`, a.`status`,
              `ad_name`, `channel`, `cid`
            FROM `" . self::$TABLE . "` a LEFT JOIN `t_adinfo` i ON a.`adid`=i.`id`
              LEFT JOIN `t_ad_source` c ON a.`adid`=c.`id`
            WHERE `userid`='$userid' AND a.`status`!=" . self::WITHDRAWN . " $keyword_sql
            ORDER BY `create_time` DESC
            LIMIT $start, $pagesize";
    if ($keyword) {
      $state = $DB->prepare($sql);
      $state->execute(array(':keyword' => $keyword));
      return $state->fetchAll(PDO::FETCH_ASSOC);
    }
    return $DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  }

  public function get_list_by_id( $adids ) {
    $adids = is_array($adids) ? implode("', '", $adids) : $adids;
    $DB = $this->get_read_pdo();
    $sql = "SELECT `id`, `adid`, `set_status`, `set_job_num`, `set_rmb`
            FROM `" . self::$TABLE . "`
            WHERE `status`=0 AND `adid` IN ('$adids')";
    return $DB->query($sql)->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
  }

  public function get_total_number($userid, $keyword) {
    $DB = $this->get_read_pdo();
    $keyword_sql = $keyword ? " AND (`ad_name` LIKE :keyword OR `channel` LIKE :keyword)" : '';
    $sql = "SELECT COUNT('x')
            FROM " . self::$TABLE . " a LEFT JOIN `t_adinfo` i ON a.`adid`=i.`id`
              LEFT JOIN `t_ad_source` c ON a.`adid`=c.`id`
            WHERE `userid`='$userid' AND a.`status`!=" . self::WITHDRAWN . " $keyword_sql";
    if ($keyword) {
      $state = $DB->prepare($sql);
      $state->execute(array(':keyword' => $keyword));
      return $state->fetchColumn();
    }
    return $DB->query($sql)->fetchColumn();
  }

  public function update($attr, $id) {
    $DB = $this->get_write_pdo();
    $attr['handle_time'] = date('Y-m-d H:i:s');
    return \SQLHelper::update($DB, self::$TABLE, $attr, $id);
  }

  public function is_owner($id, $me) {
    $DB = $this->get_read_pdo();
    $sql = "SELECT 'X'
            FROM `" . self::$TABLE . "`
            WHERE `id`=:id AND `userid`=:me";
    $state = $DB->prepare($sql);
    $state->execute(array(
      ':id' => $id,
      ':me' => $me,
    ));
    return $state->fetchColumn();
  }

  public function is_available( $id ) {
    $DB = $this->get_read_pdo();
    $sql = "SELECT 'X'
            FROM `" . self::$TABLE . "`
            WHERE `id`=:id AND `status`=0";
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $id));
    return $state->fetchColumn();
  }

  public function is_available_same_attr($adid, $attr) {
    $DB = $this->get_read_pdo();
    $sql = "SELECT 'X'
            FROM `" . self::$TABLE . "`
            WHERE `adid`=:adid AND `$attr` IS NOT NULL";
    $state = $DB->prepare($sql);
    $state->execute(array(':adid' => $adid));
    return $state->fetchColumn();
  }
} 
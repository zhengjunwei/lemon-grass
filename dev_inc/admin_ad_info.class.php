<?php
/**
 * 跟广告相关的读取，部分择自admins
 * User: Meathill
 * Date: 14-9-1
 * Time: 下午6:33
 */
include_once(dirname(__FILE__) . '/../inc/ad_info.class.php');
require dirname(__FILE__) . '/../app/SQLHelper.class.php';

class admin_ad_info extends ad_info {
  /**
   * 取广告信息
   * @param PDO $DB
   * @param $salesman
   * @param string $start
   * @param string $end
   * @param string $keyword
   * @param int $page_start
   * @param int $pagesize
   * @param string $order
   * @return array
   */
  public static function get_ad_info_by_owner(PDO $DB, $salesman, $start = '', $end = '',
    $keyword = '', $page_start = 0, $pagesize = 10, $order = 'create_time') {
    if ($keyword) {
      $keyword = " AND (`ad_name` LIKE '%$keyword%' OR `channel` LIKE '%$keyword%') ";
    }
    if ($start) {
      $start = " AND `create_time`>='$start'";
    }
    if ($end) {
      $end = " AND `create_time`<='$end'";
    }
    $sql = "SELECT a.`id`, `ad_name`, `create_time`, `status_time`, `quote_rmb`,
              `step_rmb`, `status`, `owner`, `execute_owner`, `channel`, `cid`,
              `others`, `rmb`
            FROM `t_adinfo` a LEFT JOIN `t_ad_source` b ON a.`id`=b.`id`
              LEFT JOIN `t_adinfo_rmb` r ON a.`id`=r.`id`
            WHERE (`owner`='$salesman' OR `execute_owner`='$salesman')
              AND status>=0 $start $end $keyword
            ORDER BY `$order` DESC
            LIMIT $page_start, $pagesize";
    return $DB->query($sql)->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_UNIQUE);
  }

  /**
   * 取商务的广告数量
   * @author Meathill
   *
   * @param PDO $DB
   * @param $salesman
   * @param $start
   * @param $end
   * @param string $keyword
   *
   * @return int $int
   */
  public static function get_ad_number_by_owner(PDO $DB, $salesman, $start = '', $end = '', $keyword = '') {
    if ($keyword) {
      $keyword = " AND (`ad_name` LIKE '%$keyword%' OR `channel` LIKE '%$keyword%') ";
    }
    if ($start) {
      $start = " AND `create_time`>='$start'";
    }
    if ($end) {
      $end = " AND `create_time`<='$end'";
    }
    $sql = "SELECT COUNT('X')
            FROM `t_adinfo` a LEFT JOIN `t_ad_source` s ON a.`id`=s.`id`
            WHERE (`owner`='$salesman' OR `execute_owner`='$salesman')
              AND `status`>=0 $start $end $keyword";
    return (int)$DB->query($sql)->fetchColumn();
  }

  public static function get_all_ad_job(PDO $DB) {
    $the_day_after_tomorrow = date("Y-m-d", time() + 86400 * 2);
    $sql = "SELECT `ad_id`,`jobtime`,`jobnum`
            FROM `t_ad_job`
            WHERE `jobtype` IN (2,3) AND `at_every`='every' AND is_run=0
              AND `jobnum`>0 AND `jobtime`<'$the_day_after_tomorrow'
            GROUP BY ad_id";
    return $DB->query($sql)->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_UNIQUE|PDO::FETCH_GROUP);
  }

  public static function get_adids_by_owner(PDO $DB, $me, $start = '' ) {
    IF ($start) {
      $start = " AND `create_time`>='$start'";
    }
    $sql = "SELECT a.`id`
            FROM `t_adinfo` a LEFT JOIN `t_ad_source` s ON a.`id`=s.`id`
            WHERE `status`>=0 AND `owner`=$me $start";
    return $DB->query($sql)->fetchAll(PDO::FETCH_COLUMN);
  }

  public static function get_ad_online_number_by_owner( PDO $DB, $me ) {
    $sql = "SELECT COUNT('X')
            FROM `t_adinfo` a LEFT JOIN `t_ad_source` s ON a.`id`=s.`id`
            WHERE `status`=0 AND `owner`=$me";
    return (int)$DB->query($sql)->fetchColumn();
  }

  public function get_rmb_out_by_ad(PDO $DB, $adids ) {
    $adids = is_array($adids) ? implode("','", $adids) : $adids;
    $sql = "SELECT `id`,`rmb_out`
            FROM `t_adinfo_rmb`
            WHERE `id` IN ('$adids')";
    return $DB->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  }

  public function check_ad_owner(PDO $DB, $id, $me) {
    $sql = "SELECT 'x'
            FROM `t_adinfo` i LEFT JOIN `t_ad_source` s ON i.id=s.id
            WHERE `id`=:id AND owner=:me";
    $state = $DB->prepare($sql);
    $state->execute(array(
      ':id' => $id,
      ':me' => $me,
    ));
    return $state->fetchColumn();
  }

  public function get_ad_info_by_id(PDO $DB, $id ) {
    $sql = "SELECT a.*, `owner`, `channel`, `cid`
            FROM `t_adinfo` a LEFT JOIN `t_ad_source` b ON a.id=b.id
            WHERE a.`id`=:id";
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $id));
    return $state->fetch(PDO::FETCH_ASSOC);
  }

  public function get_ad_info_by_pack_name(PDO $DB, $pack_name) {
    $sql = "SELECT `ad_name`, `ad_app_type`, `pic_path`, `ad_desc`,
              `cpc_cpa`, `ad_shoot`, `cate`, `ad_type`
            FROM `t_adinfo`
            WHERE `pack_name`=:pack_name
            ORDER BY `create_time` DESC
            LIMIT 1";
    $state = $DB->prepare($sql);
    $state->execute(array(':pack_name' => $pack_name));
    return $state->fetch(PDO::FETCH_ASSOC);
  }

  public function select_upload_log(PDO $DB, $id) {
    $sql = "SELECT *
            FROM `t_upload_log`
            WHERE `id`=:id AND `type`='ad_url'
            ORDER BY upload_time DESC";
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $id));
    return $state->fetchAll(PDO::FETCH_ASSOC);
  }

}

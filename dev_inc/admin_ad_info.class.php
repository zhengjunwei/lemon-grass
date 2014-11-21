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
  public static function get_ad_info_by_owner(PDO $DB, $salesman, $start = '', $end = '',
    $keyword = '', $page_start = 0, $pagesize = 10) {
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
              `step_rmb`, `status`, `owner`, `channel`, `cid`
            FROM `t_adinfo` a LEFT JOIN `t_ad_source` b ON a.id=b.id
            WHERE owner='$salesman' AND status>=0 $start $end $keyword
            ORDER BY `create_time` DESC
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
   * @return string $int
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
            WHERE `owner`=$salesman AND `status`>=0 $start $end $keyword";
    return $DB->query($sql)->fetchColumn();
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

  public function get_rmb_out_by_ad(PDO $DB, $adids ) {
    $adids = is_array($adids) ? implode("','", $adids) : $adids;
    $sql = "SELECT `id`,`rmb_out`
            FROM `t_adinfo_rmb`
            WHERE `id` IN ('$adids')";
    return $DB->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  }
}

<?php
/**
 * Created by PhpStorm.
 * User: 路佳
 * Date: 2015/2/6
 * Time: 16:23
 */

namespace diy\service;

use diy\utils\Utils;
use PDO;

require dirname(__FILE__) . '/../utils/array.php';

class AD extends Base {
  /**
   * 取广告信息
   * @param array $filters
   * @param int $page_start
   * @param int $pagesize
   * @param string $order
   * @return array
   */
  public function get_ad_info($filters, $page_start = 0, $pagesize = 10, $order = 'create_time') {
    $DB = $this->get_read_pdo();
    $filter = $this->parse_filter($filters);
    $sql = "SELECT a.`id`, `ad_name`, `create_time`, `status_time`, `quote_rmb`,
              `step_rmb`, `status`, `owner`, `execute_owner`, `channel`, `cid`,
              `others`, `rmb`, `pack_name`
            FROM `t_adinfo` a LEFT JOIN `t_ad_source` b ON a.`id`=b.`id`
              LEFT JOIN `t_adinfo_rmb` r ON a.`id`=r.`id`
            WHERE `status`>=0 $filter
            ORDER BY `$order` DESC
            LIMIT $page_start, $pagesize";
    $state = $DB->query($sql);
    if ($state) {
      return $state->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
    } else {
      header('sql: ' . $sql);
      header('error: ' . json_encode($DB->errorInfo()));
    }
  }

  /**
   * 取商务的广告数量
   * @author Meathill
   *
   * @param array $filters
   *
   * @return int $int
   */
  public function get_ad_number($filters) {
    $DB = $this->get_read_pdo();
    $filter_sql = $this->parse_filter($filters);
    $sql = "SELECT COUNT('X')
            FROM `t_adinfo` a LEFT JOIN `t_ad_source` s ON a.`id`=s.`id`
            WHERE `status`>=0 $filter_sql";
    return (int)$DB->query($sql)->fetchColumn();
  }

  public function get_rmb_out_by_ad($ad_ids) {
    $DB = $this->get_read_pdo();
    $ad_ids = is_array($ad_ids) ? implode("','", $ad_ids) : $ad_ids;
    $sql = "SELECT `id`,`rmb_out`
            FROM `t_adinfo_rmb`
            WHERE `id` IN ('$ad_ids')";
    return $DB->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  }

  /**
   * 从最近一个月的数据中查询广告是否跑出量
   * TODO 过年后让汪慧增加索引，改成不限时间的
   *
   * @param $ad_ids
   * @param $start
   * @param $end
   *
   * @return array
   */
  public function get_transfer_by_ad($ad_ids, $start, $end) {
    $DB = $this->get_read_pdo();
    $ad_ids = is_array($ad_ids) ? implode("','", $ad_ids) : $ad_ids;
    $result = array();
    $sql = "SELECT `ad_id`, `transfer_total`
            FROM `s_transfer_stat_ad`
            WHERE `ad_id` IN ('$ad_ids')
              AND `transfer_date`>'$start' AND `transfer_date`<'$end'";
    $transfers = $DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ( $transfers as $transfer ) {
      $ad_id = $transfer['ad_id'];
      if (array_key_exists($ad_id, $result)) {
        $result[$ad_id] += $transfer['transfer_total'];
      } else {
        $result[$ad_id] = $transfer['transfer_total'];
      }
    }
    return $result;
  }

  public function get_all_ad_job() {
    $DB = $this->get_read_pdo();
    $the_day_after_tomorrow = date("Y-m-d", time() + 86400 * 2);
    $sql = "SELECT `ad_id`,`jobtime`,`jobnum`
            FROM `t_ad_job`
            WHERE `jobtype` IN (2,3) AND `at_every`='every' AND is_run=0
              AND `jobnum`>0 AND `jobtime`<'$the_day_after_tomorrow'
            GROUP BY ad_id";
    return $DB->query($sql)->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_UNIQUE|PDO::FETCH_GROUP);
  }

  protected function parse_filter($filters) {
    $spec = array('keyword', 'start', 'end', 'salesman');
    $pick = Utils::array_pick($filters, $spec);
    $filters = Utils::array_omit($filters, $spec);
    $result = parent::parse_filter($filters);
    foreach ($pick as $key => $value) {
      switch ($key) {
        case 'start':
          $result .= " AND `create_time`>'$value'";
          break;

        case 'end':
          $result .= " AND `create_time`<'$value'";
          break;

        case 'keyword':
          $result .= $value ? " AND (`ad_name` LIKE '%$value%' OR `channel` LIKE '%$value%')" : '';
          break;

        case 'salesman':
          $result .= " AND (`owner`=$value OR `execute_owner`=$value)";
          break;
      }
    }
    return $result;
  }
}
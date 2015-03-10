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
            WHERE $filter
            ORDER BY `$order` DESC
            LIMIT $page_start, $pagesize";
    $state = $DB->query($sql);
    if ($state) {
      return $pagesize == 1 ? $state->fetch(PDO::FETCH_ASSOC) : $state->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
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
            WHERE $filter_sql";
    return (int)$DB->query($sql)->fetchColumn();
  }

  public function get_ad_ids( array $filters ) {
    $DB = $this->get_read_pdo();
    $filter_sql = $this->parse_filter($filters);
    $sql = "SELECT a.`id`
            FROM `t_adinfo` a LEFT JOIN `t_ad_source` b ON a.`id`=b.`id`
            WHERE $filter_sql";
    return $DB->query($sql)->fetchAll(PDO::FETCH_COLUMN);
  }

  public function get_ad_info_by_pack_name($pack_name) {
    $DB = $this->get_read_pdo();
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

  public function get_rmb_out_by_ad($ad_ids) {
    $DB = $this->get_read_pdo();
    $ad_ids = is_array($ad_ids) ? implode("','", $ad_ids) : $ad_ids;
    $sql = "SELECT `id`,`rmb_out`
            FROM `t_adinfo_rmb`
            WHERE `id` IN ('$ad_ids')";
    return $DB->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
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

  public function check_ad_owner( $id, $me ) {
    $DB = $this->get_read_pdo();
    $sql = "SELECT 'x'
            FROM `t_adinfo` i LEFT JOIN `t_ad_source` s ON i.`id`=s.`id`
            WHERE i.`id`=:id AND (`owner`=:me OR `execute_owner`=:me)";
    $state = $DB->prepare($sql);
    $state->execute(array(
      ':id' => $id,
      ':me' => $me,
    ));
    return $state->fetchColumn();
  }

  protected function parse_filter($filters, $is_append = false) {
    $spec = array('keyword', 'start', 'end', 'salesman');
    $pick = Utils::array_pick($filters, $spec);
    $filters = Utils::array_omit($filters, $spec);
    if (!array_key_exists('status', $filters)) {
      $filters['status'] = array(0, 1, 2); // 上线，下线，申请
    }
    $result = parent::parse_filter($filters, $is_append);
    foreach ($pick as $key => $value) {
      switch ($key) {
        case 'start':
          $result .= " AND `create_time`>='$value'";
          break;

        case 'end':
          $result .= " AND `create_time`<='$value'";
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
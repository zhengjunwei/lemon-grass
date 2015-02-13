<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 13-12-26
 * Time: 下午4:24
 */

namespace diy\service;

use PDO;

class Quote extends Base {
  /**
   * 取一段时间的广告收入，并按时间和广告id合并数据
   * @see HistoryInfo
   *
   * @param array $ad_ids
   * @param String $start
   * @param String $end
   *
   * @return array
   */
  public function get_quote($ad_ids, $start, $end) {
    $DB = $this->get_read_pdo();
    $ad_ids = is_array($ad_ids) ? implode("','", $ad_ids) : $ad_ids;
    $sql = "SELECT `ad_id`, `quote_rmb`, `nums`, `quote_date`
            FROM `t_adquote`
            WHERE `quote_date`>='$start' AND `quote_date`<='$end' AND `ad_id` IN ('$ad_ids')";
    $quote = $DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $result = array();
    foreach ($quote as $value) {
      $month = substr($value['quote_date'], 0, 7);
      $ad_id = $value['ad_id'];
      $income = $value['quote_rmb'] * $value['nums'];
      if (isset($result[$ad_id])) {
        $result[$ad_id][$month] += $income;
      } else {
        $result[$ad_id] = array(
          $month => $income,
        );
      }
    }
    return $result;
  }
} 
<?php
/**
 * Created by PhpStorm.
 * Date: 13-11-23
 * Time: 下午9:10
 * @overview 用来进行回款相关的数据库操作
 * @author Meatill <lujia.zhai@dianjoy.com>
 * @since 3.1 (2013-11-23)
 */

namespace diy\service;

use PDO;

class Payment extends Base {

  /**
   * 取某段时期内所有回款记录
   * @param array $ad_ids 广告id
   * @param string $start 开始日期
   * @param string $end 结束日期
   * @return array
   */
  public function get_payment($ad_ids, $start, $end) {
    $DB = $this->get_read_pdo();
    $start = substr($start, 0, 10);
    $end = substr($end, 0, 10);
    $ad_ids = is_array($ad_ids) ? implode("','", $ad_ids) : $ad_ids;
    $sql = "SELECT `id`,`month`,`payment`,`invoice`, `rmb`, `paid_time`,
              `invoice_time`, `invoice_rmb`, `payment_person`, `real_rmb`, `comment`
            FROM `t_ad_payment`
            WHERE `month`>='$start' AND `month`<='$end' AND `id` IN ('$ad_ids')";
    return $DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  }
} 
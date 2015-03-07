<?php
use diy\service\AD;
use diy\service\Payment;
use diy\service\Quote;
use diy\service\Transfer;
use diy\utils\Utils;

/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 15/2/11
 * Time: 下午6:57
 */

class HistoryInfo extends BaseController {
  public function get_list() {
    $query = $_REQUEST['keyword'];
    if (!$query) {
      $this->output(array(
        'code' => 0,
        'msg' => '没有关键词',
      ));
    }

    // 取3个月内创建的广告，100个基本等于不限
    $season = date('Y-m-d', time() - 86400 * 90);
    $today = date('Y-m-d');
    $service = new AD();
    $transfer = new Transfer();
    $ads = $service->get_ad_info(array(
      'keyword' => $query,
      'start' => $season,
    ), 0, 100);
    $ad_ids = array_keys($ads);

    // 取广告运行状态
    $rmb_out = $transfer->get_ad_transfer(array(
      'ad_id' => $ad_ids,
      'start' => $season,
      'end' => $today
    ), 'ad_id');
    foreach ( $rmb_out as $rmb ) {
      $rmb_out[$rmb['ad_id']] = $rmb['transfer'];
    }

    // 取广告结算状态
    $payment_service = new Payment();
    $quote_service = new Quote();
    $payments = $payment_service->get_payment($ad_ids, $season, $today);
    $quotes = $quote_service->get_quote($ad_ids, $season, $today);
    foreach ( $payments as $payment ) {
      $ad_id = $payment['id'];
      $month = substr($payment['month'], 0, 7);
      $ads[$ad_id]['payment'] += (int)$payment['rmb'];
      $ads[$ad_id]['quote'] += (int)$quotes[$ad_id][$month];
    }

    $result = array();
    foreach ( $ads as $key => $ad ) {
      $item = Utils::array_pick($ad, 'ad_name', 'others', 'create_time', 'quote_rmb', 'payment', 'quote');
      $item['transfer'] = $rmb_out[$key];
      $item['payment_percent'] = $item['quote'] != 0 ? round($item['payment'] / $item['quote'] * 100, 2) : 0;
      $item['id'] = $key;
      $result[] = $item;
    }

    // 按照回款率第一，有无备注，有无推广的优先级进行排序
    usort($result, function ($a, $b) {
      if ($a['payment_percent'] != $b['payment_percent']) {
        return $a['payment_percent'] < $b['payment_percent'] ? 1 : -1;
      }
      if (!$a['others'] || !$b['others']) {
        return $a['others'] < $b['others'] ? 1 : -1;
      }
      if (!$a['cost'] || !$b['cost']) {
        return $a['cost'] < $b['cost'] ? 1 : -1;
      }
      return 0;
    });

    $this->output(array(
      'code' => 0,
      'msg' => 'fetch',
      'list' => array_slice($result, 0, 10),
    ));
  }
}
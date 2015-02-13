<?php
use diy\service\AD;
use diy\service\Payment;
use diy\service\Quote;
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
    $service = new AD();
    $ads = $service->get_ad_info(array(
      'keyword' => $query,
    ));
    $ad_ids = array_keys($ads);

    // 取出最早最晚的创建时间
    // 这里假定广告创建后很快就会上线，结束时间以最迟的广告顺延一个月
    $start = date('Y-m-d H:i:s');
    $end = '';
    foreach ( $ads as &$ad ) {
      $start = $start < $ad['create_time'] ? $start : $ad['create_time'];
      $end= $end > $ad['create_time'] ? $end : $ad['create_time'];
      $ad['payment'] = $ad['quote'] = 0;
    }
    $end = date('Y-m-d H:i:s', strtotime($end) + 2592000);


    // 取广告运行状态
    $rmb_out = $service->get_transfer_by_ad($ad_ids, $start, $end);

    // 取广告结算状态
    $payment_service = new Payment();
    $quote_service = new Quote();
    $payments = $payment_service->get_payment($ad_ids, $start, $end);
    $quotes = $quote_service->get_quote($ad_ids, $start, $end);
    foreach ( $payments as $payment ) {
      $ad_id = $payment['id'];
      $month = substr($payment['month'], 0, 7);
      $ads[$ad_id]['payment'] += (int)$payment['rmb'];
      $ads[$ad_id]['quote'] += (int)$quotes[$ad_id][$month];
    }


    $result = array();
    foreach ( $ads as $key => $ad ) {
      $item = Utils::array_pick($ad, 'ad_name', 'others', 'create_time', 'quote_rmb', 'payment', 'quote');
      $item['status'] = $rmb_out[$key] > 0;
      $item['payment_percent'] = round($item['payment'] / $item['quote'] * 100, 2);
      $item['id'] = $key;
      $result[] = $item;
    }

    $this->output(array(
      'code' => 0,
      'msg' => 'fetch',
      'list' => $result,
    ));
  }
}
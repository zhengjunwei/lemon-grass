<?php
use diy\service\AD;
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
    $result = array();
    foreach ( $ads as $ad ) {
      $item = Utils::array_pick($ad, 'ad_name', 'others', 'create_time', 'quote_rmb');
      $result[] = $item;
    }

    $this->output(array(
      'code' => 0,
      'msg' => 'fetch',
      'list' => $result,
    ));
  }
}
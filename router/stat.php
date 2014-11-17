<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/16
 * Time: 下午3:29
 */
use NoahBuscher\Macaw\Macaw;

function compare_by_rmb($ad1, $ad2) {
  return $ad2['rmb1'] - $ad1['rmb1'];
}

Macaw::get('stat/', 'StatController@get_ad_stat');
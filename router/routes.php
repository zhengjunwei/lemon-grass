<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/12
 * Time: 下午5:24
 */
use NoahBuscher\Macaw\Macaw;

Macaw::get(BASE . '', 'HomeController@home');

Macaw::get(BASE . 'dashboard/', function () {
  $result = array(
    'code' => 0,
    'msg' => 'ok',
    'data' => array(
      'ad' => 50,
      'activated' => 1324,
      'download' => 3111,
      'money' => 1983,
      'cash' => 1864,
      'saved' => 2906,
      'percent' => round(1864 / 2906 * 100, 2),
      'record' => array(),
      'day' => array(),
    ),
  );
  exit(json_encode($result));
});

Macaw::post(BASE . 'file/', 'BaseController@upload');

Macaw::error(function() {
  echo '404 :: Not Found';
});
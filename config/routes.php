<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/12
 * Time: 下午5:24
 */
use NoahBuscher\Macaw\Macaw;

Macaw::get('', 'HomeController@home');

Macaw::get('(:all)', function ($fn) {
  echo 'sth:' . $fn;
});

Macaw::error(function() {
  echo '404 :: Not Found';
});
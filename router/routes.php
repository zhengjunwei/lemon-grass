<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/12
 * Time: 下午5:24
 */
use NoahBuscher\Macaw\Macaw;

Macaw::get(BASE . '', 'HomeController@home');

Macaw::get(BASE . 'dashboard/', 'HomeController@dashboard');

Macaw::options(BASE . 'file/', 'BaseController@on_options');

Macaw::post(BASE . 'file/', 'FileController@upload');

Macaw::options(BASE . 'fetch', 'BaseController@on_options');
Macaw::post(BASE . 'fetch/', 'FileController@fetch');

Macaw::error(function() {
  echo '404 :: Not Found';
});
<?php
/**
 * 处理用户相关的请求
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/13
 * Time: 下午3:04
 */
use NoahBuscher\Macaw\Macaw;

Macaw::get(BASE . 'user/', 'UserController@get_info');

Macaw::post(BASE . 'user/', 'UserController@login');

Macaw::options(BASE . 'user/', 'BaseController@on_options');

Macaw::delete(BASE . 'user/', 'UserController@logout');
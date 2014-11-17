<?php
/**
 * 处理用户相关的请求
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/13
 * Time: 下午3:04
 */
use NoahBuscher\Macaw\Macaw;

Macaw::get('user/', 'UserController@get_info');

Macaw::post('user/', 'UserController@login');

Macaw::delete('user', 'UserController@logout');
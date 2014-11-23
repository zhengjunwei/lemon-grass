<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/15
 * Time: 下午6:02
 */

use NoahBuscher\Macaw\Macaw;

Macaw::get(BASE . 'ad/', 'ADController@get_list');

Macaw::get(BASE . 'ad/(:any)', 'ADController@init');

Macaw::post(BASE . 'ad/', 'ADController@create');

Macaw::patch(BASE . 'ad/(:any)', 'ADController@update');
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/15
 * Time: 下午6:02
 */

use NoahBuscher\Macaw\Macaw;

Macaw::get('ad/', 'ADController@get_list');

Macaw::get('ad/(:id)', 'ADController@init');
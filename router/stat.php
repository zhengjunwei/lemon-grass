<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/16
 * Time: 下午3:29
 */
use NoahBuscher\Macaw\Macaw;

Macaw::get(BASE . 'stat/', 'StatController@get_ad_stat');

Macaw::get(BASE . 'stat/(:any)', 'StatController@get_the_ad_stat');

Macaw::get(BASE . 'stat/(:any)/(:any)', 'StatController@get_ad_daily_stat');
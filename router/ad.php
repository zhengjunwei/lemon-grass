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

Macaw::options(BASE . 'ad/(:any)', 'BaseController@on_options');

Macaw::post(BASE . 'ad/(:any)', 'ADController@create');

Macaw::patch(BASE . 'ad/(:any)', 'ADController@update');

Macaw::delete(BASE . 'ad/(:any)', 'ADController@delete');

Macaw::get(BASE . 'apply/', 'ApplyController@get_list');

Macaw::options(BASE . 'apply/(:any)', 'BaseController@on_options');

Macaw::delete(BASE . 'apply/(:any)', 'ApplyController@delete');

Macaw::get(BASE . 'info/', 'HistoryInfo@get_list');
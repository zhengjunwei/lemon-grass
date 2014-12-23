<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/12/23
 * Time: 下午3:38
 */

use NoahBuscher\Macaw\Macaw;

Macaw::get('notice/', 'NoticeController@get_list');

Macaw::options('notice/(:any)', 'BaseController@on_options');

Macaw::delete('notice/(:any)', 'NoticeController@delete');
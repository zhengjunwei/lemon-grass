<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/18
 * Time: 下午5:42
 */

/**
 * 从一个数组中择出来需要的
 *
 * @param $array
 *
 * @return array
 */
function array_pick($array) {
  $keys = array_slice(func_get_args(), 1);
  $keys = array_flatten($keys);
  $pick = array();
  foreach ( $keys as $key ) {
    if (!array_key_exists($key, $array)) {
      continue;
    }
    $pick[$key] = $array[$key];
  }
  return $pick;
}

function array_omit($array) {
  $keys = array_slice(func_get_args(), 1);
  $keys = array_flatten($keys);
  $pick = array();
  foreach ( $array as $key => $value ) {
    if (in_array($key, $keys)) {
      continue;
    }
    $pick[$key] = $value;
  }
  return $pick;
}
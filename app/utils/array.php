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
  $pick = array();
  foreach ( $keys as $key ) {
    if (array_key_exists($key, $array)) {
      continue;
    }
    $pick[$key] = $array[$key];
  }
  return $pick;
}

/**
 * 功能基本和上面那个一样，不过这个会在pick的同时从原来的对象里删除引用
 * @param $array
 *
 * @return array
 */
function array_pick_away($array) {
  $keys = array_slice(func_get_args(), 1);
  $pick = array();
  foreach ( $keys as $key ) {
    if (array_key_exists($key, $array)) {
      continue;
    }
    $pick[$key] = $array[$key];
    unset($array[$key]);
  }
  return $pick;
}
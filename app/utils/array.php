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

/**
 * 以递归的形式遍历一个数组，审查每一个对象
 * @param $array
 * @return array
 */
function array_strip_tags($array) {
  $result = array();

  foreach ( $array as $key => $value ) {
    $key = strip_tags($key);

    if (is_array($value)) {
      $result[$key] = array_strip_tags($value);
    } else {
      $result[$key] = htmlspecialchars(trim(strip_tags($value, ENT_QUOTES | ENT_HTML5)));
    }
  }

  return $result;
}
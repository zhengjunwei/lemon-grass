<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/18
 * Time: 下午6:48
 */

class SQLHelper {
  public static function create_insert_sql($table, $array, $use_prepare = true) {
    $keys = array_keys($array);
    $values = array_values($array);
    $key = implode('`, `', $keys);
    if ($use_prepare) {
      $value = implode(', :', $keys);
      $sql = "INSERT INTO `$table`
              (`$key`)
              VALUES (:$value)";
    } else {
      $value = implode('\', \'', $values);
      $sql = "INSERT INTO $table
            (`$key`)
            VALUES ('$value')";
    }
    return $sql;
  }

  public static function get_input_parameters($array) {
    $params = array();
    foreach ( $array as $key => $value ) {
      $params[":$key"] = $value;
    }
    return $params;
  }
}

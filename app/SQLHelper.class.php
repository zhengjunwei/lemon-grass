<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/18
 * Time: 下午6:48
 */

class SQLHelper {
  static $info = null;

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
      $sql = "INSERT INTO `$table`
            (`$key`)
            VALUES ('$value')";
    }
    return $sql;
  }

  private static function create_update_sql( $table, $attr, $id, $use_prepare = true ) {
    $fields = array();
    if ($use_prepare) {
      foreach ( $attr as $key ) {
        $fields[] = "`$key`=:$key";
      }
    } else {
      foreach ( $attr as $key => $value ) {
        $fields[] = "`$key`='$value'";
      }
    }
    $fields = implode(', ', $fields);
    $sql = "UPDATE `$table`
            SET $fields
            WHERE `id`=$id";
    return $sql;
  }

  public static function get_parameters($array) {
    $params = array();
    foreach ( $array as $key => $value ) {
      $params[":$key"] = $value;
    }
    return $params;
  }

  public static function insert(PDO $DB, $table, $attr) {
    $sql = self::create_insert_sql($table, $attr);
    $params = self::get_parameters($attr);
    $state = $DB->prepare($sql);
    $result = $state->execute($params);
    self::$info = $state->errorInfo();
    return $result;
  }

  public static function update( PDO $DB, $table, $attr, $id ) {
    $sql = self::create_update_sql($table, $attr, $id);
    $params = self::get_parameters($attr);
    $state = $DB->prepare($sql);
    $result = $state->execute($params);
    self::$info = $state->errorInfo();
    return $result;
  }
}

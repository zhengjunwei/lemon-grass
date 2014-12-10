<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/18
 * Time: 下午6:48
 */

class SQLHelper {
  static $info = '';

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
      foreach ( $attr as $key => $value ) {
        $fields[] = "`$key`=:$key";
      }
      if (is_array($id)) {
        $count = 0;
        $ids = array();
        foreach ( $id as $value ) {
          $ids[] = ':id' . $count;
          $count++;
        }
        $id_sql = implode(',', $ids);
        $id_sql = "IN ($id_sql)";
      } else {
        $id_sql = '=:id';
      }
    } else {
      foreach ( $attr as $key => $value ) {
        $fields[] = "`$key`='$value'";
      }
      if (is_array($id)) {
        $id_sql = implode("','", $id);
        $id_sql = "IN ('$id_sql')";
      } else {
        $id_sql = "='$id'";
      }
    }
    $fields = implode(', ', $fields);
    $sql = "UPDATE `$table`
            SET $fields
            WHERE `id`$id_sql";
    return $sql;
  }

  public static function get_parameters($array, $id = null) {
    $params = array();
    foreach ( $array as $key => $value ) {
      $params[":$key"] = $value;
    }
    if ($id) {
      if (is_array($id)) {
        $count = 0;
        foreach ( $id as $value ) {
          $params[":id$count"] = $value;
        }
      } else {
        $params[':id'] = $id;
      }
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
    $params = self::get_parameters($attr, $id);
    $state = $DB->prepare($sql);
    $result = $state->execute($params);
    self::$info = $state->errorInfo();
    return $result;
  }

  public static function get_attr( PDO $DB, $table, $id ) {
    $keys = array_slice(func_get_args(), 3);
    $is_single = count($keys) === 1;
    $keys = implode('`, `', $keys);
    $sql = "SELECT `$keys`
            FROM `$table`
            WHERE `id`=:id";
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $id));
    return $state->fetch($is_single ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC);
  }
}

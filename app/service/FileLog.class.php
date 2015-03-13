<?php

namespace diy\service;

class FileLog extends Base {
  public function insert($id, $type, $path, $file_name) {
    $DB = $this->get_write_pdo();
    $now = date('Y-m-d H:i:s');
    $upload_user = (int)$_SESSION['id'];
    $sql = "INSERT INTO `t_upload_log`
            (`id`, `TYPE`, `url`, `upload_user`, `upload_time`, `file_name`)
            VALUE (:id, :type, :path, '$upload_user', '$now', :file_name)";
    $state = $DB->prepare($sql);
    return $state->execute(array(
      ':id' => $id,
      ':type' => $type,
      ':path' => $path,
      ':file_name' => $file_name,
    ));
  }

  public function get_file_name($id) {
    $DB = $this->get_read_pdo();
    $sql = "SELECT `file_name`
            FROM `t_upload_log`
            WHERE `id`=:id AND `TYPE`='ad_url'
            ORDER BY `upload_time` DESC
            LIMIT 1";
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $id));
    return $state->fetchColumn();
  }

  public function insert_fetch_log( $id, $type, $path, $file ) {
    $DB = $this->get_write_pdo();
    $now = date('Y-m-d H:i:s');
    $fetch_user = (int)$_SESSION['id'];
    $sql = "INSERT INTO `t_fetch_log`
            (`id`, `type`, `url`, `fetch_user`, `fetch_time`, `from`)
            VALUE (:id, :type, :url, $fetch_user, '$now', :from)";
    $state = $DB->prepare($sql);
    return $state->execute(array(
      ':id' => $id,
      ':type' => $type,
      ':url' => $path,
      ':from' => $file,
    ));
  }
}

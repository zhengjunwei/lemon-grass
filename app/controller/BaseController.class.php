<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/13
 * Time: 下午5:58
 */

class BaseController {
  static $HTTP_CODE = array(
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    422 => 'Unprocessable Entity',
  );

  public function __construct() {

  }

  protected function get_pdo_read() {
    return require dirname(__FILE__) . '/../../inc/pdo_slave.php';
  }
  protected function get_pdo_write() {
    return require dirname(__FILE__) . '/../../inc/pdo.php';
  }
  protected function get_cm() {
    require dirname(__FILE__) . '/../../inc/cm.class.php';
    return new CM();
  }
  protected function get_post_data() {
    $request = file_get_contents('php://input');
    return json_decode($request, true);
  }

  protected function exit_with_error($code, $msg, $http_code, $debug = '') {
    header('Content-type: application/JSON; charset=UTF-8');
    header("HTTP/1.1 $http_code " . self::$HTTP_CODE[$http_code]);
    exit(json_encode(array(
      'code' => $code,
      'msg' => $msg,
      'debug' => $debug,
    )));
  }
  protected function output($result) {
    header('Content-type: application/JSON; charset=UTF-8');
    exit(json_encode($result));
  }

  public function upload() {
    require(dirname(__FILE__) . "/../../inc/cm.class.php");
    $CM = new CM;
    $DB = require(dirname(__FILE__) . "/../../inc/pdo.php");

    $file = $_FILES['file'];
    if (!$file) {
      $this->exit_with_error(1, '无法获取文件，请检查服务器设置。', 400);
    }
    $id = isset($_REQUEST['id']) && $_REQUEST['id'] != '' && $_REQUEST['id'] != 'undefined' ? $_REQUEST['id'] : $CM->id1();
    $type = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'ad_url';
    $upload_user = $_SESSION['id'];

    $uppath = isset($CM->uppath[$type]) ? $CM->uppath[$type] : 'upload/';
    $dir = UPLOAD_BASE . $uppath . date("Ym") . '/';
    if (!is_dir($dir)) {
      mkdir($dir, 0777, TRUE);
    }
    $ext = substr($file['name'], strrpos($file['name'], '.'));
    if (strpos($ext, 'php') !== false) {
      $ext = '.ban';
    }

    $index = 0;
    $new_path = $dir . $index . '_' . $id . $ext;
    while (file_exists($new_path)) {
      $index++;
      $new_path = $dir . $index . '_' . $id . $ext;
    }

    //对管理员和广告主后台上传的图片文件自动压缩
    if ($type == 'pic_path') {
      $image = $ext == '.png' ? imagecreatefrompng($file['tmp_name']) : imagecreatefromjpeg($file['tmp_name']);
      $path_128_128 = $dir . $index . '_'  . $id . '_128_128' . $ext;
      list($width_origin, $height_origin) = getimagesize($file['tmp_name']);
      if ($width_origin != 128 || $height_origin != 128) {
        $image_p = imagecreatetruecolor(128, 128);
        imagealphablending($image_p,false);
        imagesavealpha($image_p,true);
        if ($width_origin > $height_origin) {
          imagecopyresampled($image_p, $image, 0, 0, (int)($width_origin - $height_origin) / 2, 0, 128, 128, $height_origin, $height_origin);
        } else {
          imagecopyresampled($image_p, $image, 0, 0, 0, (int)($height_origin - $width_origin) / 2, 128, 128, $width_origin, $width_origin);
        }
        if ($ext == '.jpg') {
          imagejpeg($image_p, $path_128_128);
        } else {
          imagepng($image_p, $path_128_128);
        }
        imagedestroy($image_p);
      } else {
        imagealphablending($image,false);
        imagesavealpha($image,true);
        if ($ext == '.jpg') {
          imagejpeg($image, $path_128_128);
        } else {
          imagepng($image, $path_128_128);
        }
      }
    }
    if ($type == 'ad_shoot') {
      $image = $ext == '.png' ? imagecreatefrompng($file['tmp_name']) : imagecreatefromjpeg($file['tmp_name']);
      $path_400 = $dir . $index . '_' . $id . '_h_400' . $ext;
      list($width_origin, $height_origin) = getimagesize($file['tmp_name']);
      if ($height_origin != 400) {
        $new_width = (int)($width_origin / $height_origin * 400);
        $image_p = imagecreatetruecolor($new_width, 400);
        imagealphablending($image_p, false);
        imagesavealpha($image_p,true);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, 400, $width_origin, $height_origin);
        if ($ext == '.jpg') {
          imagejpeg($image_p, $path_400);
        } else {
          imagepng($image_p, $path_400);
        }
        imagedestroy($image_p);
      } else {
        imagealphablending($image,false);
        imagesavealpha($image,true);
        if ($ext == '.jpg') {
          imagejpeg($image, $path_400);
        } else {
          imagepng($image, $path_400);
        }
      }
    }

    move_uploaded_file($file['tmp_name'], $new_path);

    // 记录到log里
    require_once(dirname(__FILE__) . '/../../dev_inc/upload.class.php');
    upload::insert($DB, $id, $type, $new_path, $upload_user, $file['name']);

    // 生成反馈
    $url = UPLOAD_BASE === '' ? UPLOAD_URL . $new_path : str_replace(UPLOAD_BASE, UPLOAD_URL, $new_path);
    $result = array(
      'code' => 0,
      'msg' => 'uploaded',
      'id' => $id,
      'url' => $url,
    );

    if ($ext == '.apk') { // 仅解释apk文件，其他直接返回空
      try {
        require_once(dirname(__FILE__) . '/../../dev_inc/apk_parser.class.php');
        require dirname(__FILE__) . '/../../app/utils/functions.php';
        $p = new ApkParser();
        $p->open($new_path);
        $permission = $p->getPermission();
        foreach ($permission as $key => $value) {
          $permission[$key] = str_replace('.', '-', $value);
        }
        $package = array(
          'pack_name' => $p->getPackage(),
          'ad_lib' => $p->getVersionName(),
          'ad_size' => format_file_size(filesize($new_path)),
        );

        if ($type == 'ad_url') {
          // 从数据库读相同包名的广告有哪些可以直接用的数据
          require dirname(__FILE__) . '/../../dev_inc/admin_ad_info.class.php';
          $ad_info = new admin_ad_info();
          $info = $ad_info->get_ad_info_by_pack_name($DB, $package['pack_name']);
          $info['ad_shoot'] = $info['ad_shoot'] ? UPLOAD_URL . $info['ad_shoot'] : '';
          $info['pic_path'] = $info['pic_path'] ? UPLOAD_URL . $info['pic_path'] : '';
        }

        $result = array_merge($result, array(
          'md5' => md5_file($new_path),
          'permission' => $permission,
          'form' => array_merge($package, (array)$info),
        ));
      } catch (Exception $e) {
        $package = $e->getMessage();
      }
    }

    $this->output($result);
  }

  public function on_options() {
    header('Access-Control-Allow-Headers: accept, content-type');
    header('Access-Control-Allow-Methods: GET,PUT,POST,PATCH,DELETE');

    exit(json_encode(array(
      'code' => 0,
      'method' => 'options',
      'msg' => 'ready',
    )));
  }
} 
<?php
use diy\service\AD;
use diy\service\FileLog;
use diy\utils\Utils;

/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 15/1/22
 * Time: 下午4:17
 */

class FileController extends BaseController {
  private $radar_map = array(
    'pack_name' => 'packagename',
    'ad_name' => 'app_name',
    'label' => 'app_category',
    'pic_path' => 'icon_path',
    'ad_size' => 'file_size',
    'ad_lib' => 'app_versioncode',
    'ad_shoot' => 'screenshots',
    'ad_desc' => 'memo',
  );

  public function upload() {
    $file = $_FILES['file'];
    if (!$file) {
      $this->exit_with_error(1, '无法获取文件，请检查服务器设置。', 400);
    }

    $id = isset($_REQUEST['id']) && $_REQUEST['id'] != '' && $_REQUEST['id'] != 'undefined' ? $_REQUEST['id'] : $this->create_id();
    $type = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'ad_url';
    $file_name = $file['name'];
    $md5 = $_REQUEST['md5'];

    if ($md5) {
      $file_md5 = md5_file($file['tmp_name']);
      if ($md5 != $file_md5) {
        $this->exit_with_error(2, '文件MD5不一致，上传失败', 408);
      }
    }

    $new_path = $this->get_file_path( $type, $file_name, $id );

    //对管理员和广告主后台上传的图片文件自动压缩
    if ($type == 'pic_path') {
      $this->resize_image( $new_path, $file, 128, 128 );
    }
    if ($type == 'ad_shoot') {
      $this->resize_image( $new_path, $file, 0, 400);
    }

    move_uploaded_file($file['tmp_name'], $new_path);

    // 记录到log里
    $service = new FileLog();
    $service->insert($id, $type, $new_path, $file_name);

    // 生成反馈
    $url = UPLOAD_BASE === '' ? UPLOAD_URL . $new_path : str_replace(UPLOAD_BASE, UPLOAD_URL, $new_path);
    $result = array(
      'code' => 0,
      'msg' => 'uploaded',
      'id' => $id,
      'url' => $url,
      'form' => array(),
    );

    if (preg_match('/\.apk$/', $new_path)) { // 仅解释apk文件，其他直接返回空
      $package = $this->parse_apk( $new_path, $type);
      $result = array_merge($result, $package);
    }
    $result['form']['id'] = $id;

    $this->output($result);
  }

  public function fetch() {
    $file = trim($_POST['file']);
    $type = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'ad_url';
    $id = isset($_REQUEST['id']) && $_REQUEST['id'] != '' && $_REQUEST['id'] != 'undefined' ? $_REQUEST['id'] : $this->create_id();

    // 过滤不抓取的情况
    if (preg_match('/itunes\.apple\.com/', $file)) {
      $this->output(array(
        'code' => 1,
        'msg' => 'itunes不抓取',
      ));
    }

    $result = array(
      'code' => 0,
      'form' => array(),
      'id' => $id,
    );
    // 已经在我们的机器上了，直接分析
    $path = '';
    if (preg_match(LOCAL_FILE, $file)) {
      $result['msg'] = 'exist';
      $path = preg_replace(LOCAL_FILE, UPLOAD_BASE, $file);
    } else {
      try {
        $content = file_get_contents($file);
        $filename = $this->parse_filename($file, $http_response_header);
        $path = $this->get_file_path($type, $filename, $id);
        file_put_contents($path, $content);
        // 生成反馈
        $result['msg'] = 'fetched';
      } catch (Exception $e) {
        $this->exit_with_error(2, '找不到目标文件，无法完成抓取。', 404);
      }
    }

    // 记录到log里
    $service = new FileLog();
    $service->insert_fetch_log($id, $type, $path, $file, $filename);

    if (preg_match('/\.apk$/', $path)) {
      $package = $this->parse_apk($path, $type);
      $result = array_merge($result, $package);
    }
    $result['form']['ad_url'] = UPLOAD_BASE === '' ? UPLOAD_URL . $path : str_replace(UPLOAD_BASE, UPLOAD_URL, $path);
    $result['form']['id'] = $id;

    $this->output($result);
  }

  /**
   * @param $type
   * @param $file_name
   * @param $id
   *
   * @return string
   */
  private function get_file_path( $type, $file_name, $id ) {
    require(dirname(__FILE__) . "/../../inc/cm.class.php");
    $CM = new CM;
    $path = isset( $CM->uppath[ $type ] ) ? $CM->uppath[ $type ] : 'upload/';
    $dir = UPLOAD_BASE . $path . date( "Ym" ) . '/';
    if ( ! is_dir( $dir ) ) {
      mkdir( $dir, 0777, true );
    }
    $ext = substr( $file_name, strrpos( $file_name, '.' ) );
    if ( strpos( $ext, 'php' ) !== false ) {
      $ext = '.ban';
    }

    $index = 0;
    $new_path = $dir . $index . '_' . $id . $ext;
    while ( file_exists( $new_path ) ) {
      $index ++;
      $new_path = $dir . $index . '_' . $id . $ext;
    }

    return $new_path;
  }

  /**
   * @param string $path
   * @param string $file
   * @param int $width
   * @param int $height
   *
   * @return boolean
   */
  private function resize_image( $path, $file, $width = 0, $height = 0 ) {
    $is_png = preg_match('/\.png$/', $path);
    $image = $is_png ? imagecreatefrompng( $file['tmp_name'] ) : imagecreatefromjpeg( $file['tmp_name'] );
    list( $width_origin, $height_origin ) = getimagesize( $file['tmp_name'] );
    if ($width == 0 || $height == 0) {
      $suffix = '_' . ($width ? 'w' : 'h') . '_' . ($width ? $width : $height);
    } else {
      $suffix = "_{$width}_{$height}";
    }
    $path = $this->get_resize_path($path, $suffix);
    $width = $width ? $width : $width_origin;
    $height = $height ? $height : $height_origin;

    if ( $width_origin != $width || $height_origin != $height ) {
      $canvas = imagecreatetruecolor( $width, $height );
      imagealphablending( $canvas, false );
      imagesavealpha( $canvas, true );
      if ( $width_origin > $height_origin ) {
        imagecopyresampled( $canvas, $image, 0, 0, (int) ( $width_origin - $height_origin ) / 2, 0, $width, $height, $height_origin, $height_origin );
      } else {
        imagecopyresampled( $canvas, $image, 0, 0, 0, (int) ( $height_origin - $width_origin ) / 2, $width, $height, $width_origin, $width_origin );
      }
      if ( $is_png ) {
        imagepng( $canvas, $path );
      } else {
        imagejpeg( $canvas, $path );
      }
      imagedestroy( $canvas );
    } else {
      imagealphablending( $image, false );
      imagesavealpha( $image, true );
      if ( $is_png ) {
        imagepng( $image, $path );
      } else {
        imagejpeg( $image, $path );
      }
    }

    return true;
  }

  /**
   * @param $new_path
   * @param $type
   *
   * @return array
   */
  private function parse_apk( $new_path, $type ) {
    try {
      $apk = new ApkParser\Parser($new_path);
      $manifest = $apk->getManifest();
      $permission = $manifest->getPermissions();
      $package = array(
        'pack_name' => $manifest->getPackageName(),
        'ad_lib'    => $manifest->getVersionName(),
        'ad_size'   => Utils::format_file_size( filesize( $new_path ) ),
      );

      $info = array();
      if ( $type == 'ad_url' ) {
        // 从数据库读相同包名的广告有哪些可以直接用的数据
        $ad_service = new AD();
        $info = $ad_service->get_ad_info_by_pack_name($package['pack_name']);
        if (!$info && !defined('DEBUG')) { // 没有同包名的广告，再试试应用雷达
          try {
            $info = json_decode(file_get_contents('http://192.168.0.165/apk_info.php?pack_name=' . $package['pack_name']));
          } catch (Exception $e) {

          }
          if ($info) {
            foreach ( $this->radar_map as $key => $value ) {
              $info[$key] = $info[$value];
            }
            $info['shoots'] = explode(',', $info['ad_shoot']);
          }
        }
        $info['ad_shoot'] = $info['ad_shoot'] ? UPLOAD_URL . $info['ad_shoot'] : '';
        $info['pic_path'] = $info['pic_path'] ? UPLOAD_URL . $info['pic_path'] : '';
      }

      $result = array(
        'permission' => $permission,
        'form'       => array_merge( $package, (array) $info ),
      );
    } catch ( Exception $e ) {
      $result = array(
        'error' => $e->getMessage(),
      );
    }

    return $result;
  }

  /**
   * @param $path
   * @param $suffix
   *
   * @return string
   */
  private function get_resize_path( $path, $suffix ) {
    $offset = strrpos($path, '.');
    return substr($path, 0, $offset) . $suffix . substr($path, $offset);
  }

  /**
   * @return string
   */
  private function create_id() {
    return md5(uniqid());
  }

  /**
   * 从一串HTTP响应头里分析文件名称
   *
   * @param $url
   * @param $http_response_header
   */
  private function parse_filename( $url, $http_response_header ) {
    $http_reg = '/^HTTP\/\d\.\d (\d)\d{2}/';
    $location_reg = '/^Location: (\S+)/';
    $content_reg = '/^Content-Disposition: \w+; filename="(\S)+"/';
    $is_rewrite = $is_final = false;
    foreach ( $http_response_header as $response ) {
      $matches = array();

      // 这行是状态码？
      $is_status = preg_match($http_reg, $response, $matches);
      if ($is_status) {
        if ($matches[1] == '3') { // 跳转
          $is_rewrite = true;
        } elseif ($matches[1] == '2') { // 最终
          $is_final = true;
        }
        continue;
      }

      // 还是跳转后的url？
      $is_location = preg_match($location_reg, $response, $matches);
      if ($is_location) {
        $url = $matches[1];
        continue;
      }

      // 或者是包含文件名的什么东西？
      $is_disposition = preg_match($content_reg, $response, $matches);
      if ($is_disposition) {
        $url = $matches[1];
      }
    }
    return $url;
  }
}
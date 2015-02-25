<?php
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
    $DB = $this->get_pdo_write();

    $file = $_FILES['file'];
    if (!$file) {
      $this->exit_with_error(1, '无法获取文件，请检查服务器设置。', 400);
    }

    $id = isset($_REQUEST['id']) && $_REQUEST['id'] != '' && $_REQUEST['id'] != 'undefined' ? $_REQUEST['id'] : $this->create_id();
    $type = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'ad_url';
    $file_name = $file['name'];
    $upload_user = $_SESSION['id'];
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
    require_once(dirname(__FILE__) . '/../../dev_inc/upload.class.php');
    upload::insert($DB, $id, $type, $new_path, $upload_user, $file_name);

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
      $package = $this->parse_apk( $new_path, $type, $DB);
      $result = array_merge($result, $package);
    }
    $result['form']['id'] = $id;

    $this->output($result);
  }

  public function fetch() {
    $file = $_POST['file'];
    $type = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'ad_url';
    $id = isset($_REQUEST['id']) && $_REQUEST['id'] != '' && $_REQUEST['id'] != 'undefined' ? $_REQUEST['id'] : '';

    // 过滤不抓取的情况
    if (preg_match('/itunes\.apple\.com/', $file)) {
      $this->output(array(
        'code' => 1,
        'msg' => 'itunes不抓取',
      ));
    }

    // 已经在我们的机器上了，直接分析
    $result = array(
      'code' => 0,
      'form' => array(),
      'id' => $id,
    );
    if (preg_match(LOCAL_FILE, $file)) {
      $result['msg'] = 'exist';
      $path = preg_replace(LOCAL_FILE, UPLOAD_BASE, $file);
    } else {
      $path = $this->get_file_path($type, $file, $id);
      file_put_contents($path, file_get_contents($file));

      // 生成反馈
      $result['msg'] = 'fetched';
    }

    if (preg_match('/\.apk$/', $path)) {
      $package = $this->parse_apk($path, $type, $this->get_pdo_read(), $result);
      array_merge($result, $package);
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
    $id = $id ? $id : $CM->id1();
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
   * @param $DB
   *
   * @return array
   */
  private function parse_apk( $new_path, $type, $DB ) {
    try {
      require_once( dirname( __FILE__ ) . '/../../dev_inc/apk_parser.class.php' );
      require dirname( __FILE__ ) . '/../../app/utils/functions.php';
      $p = new ApkParser();
      $p->open( $new_path );
      $permission = $p->getPermission();
      foreach ( $permission as $key => $value ) {
        $permission[ $key ] = str_replace( '.', '-', $value );
      }
      $package = array(
        'pack_name' => $p->getPackage(),
        'ad_lib'    => $p->getVersionName(),
        'ad_size'   => format_file_size( filesize( $new_path ) ),
      );

      $info = array();
      if ( $type == 'ad_url' ) {
        // 从数据库读相同包名的广告有哪些可以直接用的数据
        require dirname( __FILE__ ) . '/../../dev_inc/admin_ad_info.class.php';
        $ad_info = new admin_ad_info();
        $info    = $ad_info->get_ad_info_by_pack_name( $DB, $package['pack_name'] );
        if (!$info) { // 没有同包名的广告，再试试应用雷达
          $info = json_decode(file_get_contents('http://192.168.0.165/apk_info.php?pack_name=' . $package['pack_name']));
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
}
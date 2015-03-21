<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 15/3/21
 * Time: 下午2:54
 */
require dirname(__FILE__) . '/../vendor/autoload.php';

use diy\service\AD;
use diy\service\Mailer;

class MailerTest extends PHPUnit_Framework_TestCase {
  public function testCreate() {
    $mail = new Mailer();
    $service = new AD();

    $template = 'apply-new';
    $ad_id = '7277f7f90b2b7071160ab53125714fcd';
    $extra = array(
      'id' => $ad_id,
      'label' => '每日限量',
      'value' => 100,
      'comment' => 'phpunit 测试',
      'owner' => '肉山',
    );

    $info = $service->get_ad_info(array('id' => $ad_id), 0, 1);

    $html = $mail->create($template, array_merge($info, $extra));

    file_put_contents('tests/mail.html', $html);

    $this->assertNotEmpty($html);
  }
}

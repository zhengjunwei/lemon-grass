<?php
/**
 * Created by PhpStorm.
 * Date: 2014/12/31
 * Time: 22:06
 * @overview 
 * @author Meatill <lujia.zhai@dianjoy.com>
 * @since 
 */

namespace diy\service;
use PHPMailer;
use Mustache_Engine;

class Mailer {
  private $mail;

  public function errorInfo() {
    return $this->mail->ErrorInfo;
  }

  public function __construct() {
    $this->mail = new PHPMailer();

    $this->mail->isSMTP();
    $this->mail->Host = 'smtp.exmail.qq.com';
    $this->mail->SMTPAuth = true;
    $this->mail->Username = 'support@dianjoy.com';
    $this->mail->Password = MAIL_PASSWORD;
    $this->mail->SMTPSecure = 'ssl';
    $this->mail->Port = 465;

    $this->mail->From = 'support@dianjoy.com';
    $this->mail->FromName = '点乐自助平台';
  }

  public function create( $template, $data = null ) {
    $content = file_get_contents(dirname(__FILE__) . '/../../template/mail/' . $template . '.html');
    if (is_array($data)) {
      $m = new Mustache_Engine(array('cache' => '/var/tmp'));
      $content = $m->render($content, $data);
    }
    return $content;
  }

  public function send($to, $subject, $content) {
    $this->mail->addAddress($to);
    $this->mail->Subject = $subject;
    $this->mail->Body = $content;

    return $this->mail->send();
  }
}
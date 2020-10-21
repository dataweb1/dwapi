<?php
use Symfony\Component\Yaml\Yaml;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ApiMail{
  public $key;

  public function __construct($template_key)
  {
    $this->key = $template_key;
  }

  private function getTemplateBody() {
    $mail_templates = Yaml::parse(file_get_contents(__DIR__ . '/../../config/mail_templates.yml'));

    if (array_key_exists($this->key, $mail_templates["mail_templates"])) {
      $data = $mail_templates["mail_templates"][$this->key];
      return $data;
    } else {
      throw new Exception('Template "' . $this->key . '" not found', 404);
    }
  }

  public function send() {
    $mail = new PHPMailer(true); //Argument true in constructor enables exceptions

    //From email address and name
    $mail->From = "from@yourdomain.com";
    $mail->FromName = "Full Name";

    //To address and name
    $mail->addAddress("bert@data-web.be", "Recepient Name");
    //$mail->addAddress("recepient1@example.com"); //Recipient name is optional

    //Address to which recipient will reply
    $mail->addReplyTo("bert@data-web.be", "Reply");

    //CC and BCC
    //$mail->addCC("cc@example.com");
    //$mail->addBCC("bcc@example.com");

    //Send HTML or Plain Text email
    $mail->isHTML(true);

    $mail->Subject = "Subject Text";
    $mail->Body = $this->getTemplateBody();
    $mail->AltBody = "This is the plain text version of the email content";

    try {
      $mail->send();
      //echo "Message has been sent successfully";
    } catch (Exception $e) {
      throw new Exception('Mailer error: "' . $mail->ErrorInfo, 400);
    }
  }
}
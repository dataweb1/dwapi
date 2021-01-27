<?php
namespace dwApi\api;
use Symfony\Component\Yaml\Yaml;
use PHPMailer\PHPMailer\PHPMailer;


/**
 * Class Mail
 * @package dwApi\api
 */
class Mail{
  private $mail_parameters;
  private $request;
  private $response;

  public function __construct()
  {
    $this->request = Request::getInstance();
    $this->response = Response::getInstance();
    $this->mail_parameters = $this->request->getParameters("get", "mail");
    Project::getInstance();
  }

  /**
   * getElement.
   * @param $element
   * @return bool|mixed
   * @throws DwapiException
   */
  private function getElement($element) {
    if ($this->mail_parameters[$element] != "") {
      return Template::renderTwigString($this->mail_parameters[$element], $this->response->getTwigVariables());
    }
    else {
      $template = Template::pickTemplate(
        $element,
        $this->request->project,
        $this->request->action,
        $this->request->getParameters("get", "entity"));
      if ($template != "") {
        return Template::renderTwigFile($template, $this->response->getTwigVariables());
      }
    }

    return false;
  }

  /**
   * send.
   * @throws DwapiException
   * @throws \PHPMailer\PHPMailer\Exception
   */
  public function send() {
    $mail = new PHPMailer(true); //Argument true in constructor enables exceptions

    //From email address and name
    $mail->From = $this->getElement("from_email");
    $mail->FromName = $this->getElement("from_name");

    $mail->addAddress( $this->getElement("to_email"), $this->getElement("to_name"));

    $reply_email = $this->getElement("reply_email");
    if ($reply_email) {
      $mail->addReplyTo($reply_email, $this->getElement("reply_name"));
    }

    $bcc_email = $this->getElement("bcc_email");
    if ($bcc_email) {
      $mail->addBcc($bcc_email);
    }


    //Send HTML or Plain Text email
    $mail->isHTML(true);

    $mail->Subject = $this->getElement("subject");
    $mail->Body = $this->getElement("body");
    //$mail->AltBody = "This is the plain text version of the email content";

    try {
      $mail->send();
      //echo "Message has been sent successfully";
    } catch (DwapiException $e) {
      throw new DwapiException($mail->ErrorInfo, DwapiException::DW_MAIL_ERROR);
    }
  }
}